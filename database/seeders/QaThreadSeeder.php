<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\CertificationStatus;
use App\Enums\QaThreadStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Certification;
use App\Models\QaReply;
use App\Models\QaThread;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * 開発用 質問掲示板(qa-board) シーダー。
 *
 * **設計思想(要件シート「初期データ」に対応)**:
 *
 * 1. **公開済の資格ごとに、未解決・解決済を混在させてスレッドを散布**: 資格チップでの絞り込み・
 *    解決状態タブの動作を確認できるようにする。
 *
 * 2. **回答数(0件/数件)と作成日時をばらつかせる**: 一覧の未回答バッジ表示、新着順ソート、
 *    ページネーションの動作を確認できるようにする。
 *
 * 3. **固定 student(student@certify-lms.test)を投稿者にしたスレッドを用意**: 「自分の質問」の
 *    編集・削除・解決マークの動線を、固定アカウントでいつでも確認できるようにする。
 *
 * 依存順序: `UserSeeder` → `CertificationSeeder` → 本Seeder(受講登録の有無は問わない)。
 */
final class QaThreadSeeder extends Seeder
{
    public function run(): void
    {
        $certifications = Certification::query()
            ->where('status', CertificationStatus::Published->value)
            ->get();

        if ($certifications->isEmpty()) {
            $this->command?->warn('QaThreadSeeder: 公開済の資格が存在しません。先にCertificationSeederを実行してください。');

            return;
        }

        $students = User::query()
            ->where('role', UserRole::Student->value)
            ->whereIn('status', [UserStatus::InProgress->value, UserStatus::Graduated->value])
            ->get();

        if ($students->isEmpty()) {
            $this->command?->warn('QaThreadSeeder: 受講生が存在しません。先にUserSeederを実行してください。');

            return;
        }

        foreach ($certifications as $certification) {
            $this->seedThreadsForCertification($certification, $students);
        }

        $this->seedFixedStudentThreads($certifications, $students);
    }

    /**
     * @param Collection<int, User> $students
     */
    private function seedThreadsForCertification(Certification $certification, Collection $students): void
    {
        // 資格ごとに、未解決2件・解決済1件を基本パターンとして散布する
        $patterns = [
            ['status' => QaThreadStatus::Open, 'replyCount' => 0, 'hoursAgo' => 5],
            ['status' => QaThreadStatus::Open, 'replyCount' => 2, 'hoursAgo' => 30],
            ['status' => QaThreadStatus::Resolved, 'replyCount' => 3, 'hoursAgo' => 72],
        ];

        foreach ($patterns as $i => $pattern) {
            $author = $students->random();
            $createdAt = Carbon::now()->subHours($pattern['hoursAgo']);

            $thread = $this->createThread(
                certification: $certification,
                author: $author,
                title: "{$certification->name}についての質問 #{$i}",
                status: $pattern['status'],
                createdAt: $createdAt,
            );

            $this->attachReplies($thread, $students, $pattern['replyCount'], $createdAt);
        }
    }

    /**
     * @param Collection<int, Certification> $certifications
     * @param Collection<int, User> $students
     */
    private function seedFixedStudentThreads(Collection $certifications, Collection $students): void
    {
        $fixedStudent = User::query()->where('email', 'student@certify-lms.test')->first();

        if ($fixedStudent === null) {
            $this->command?->warn('QaThreadSeeder: 固定studentアカウントが見つかりません。');

            return;
        }

        // 固定studentの「自分の質問」一覧・解決マークの動線を確認できるよう、未解決/解決済を1件ずつ用意
        $target = $certifications->first();

        if ($target === null) {
            return;
        }

        $this->createThread(
            certification: $target,
            author: $fixedStudent,
            title: '固定受講生による未解決の質問',
            status: QaThreadStatus::Open,
            createdAt: Carbon::now()->subHours(1),
        );

        $resolvedThread = $this->createThread(
            certification: $target,
            author: $fixedStudent,
            title: '固定受講生による解決済の質問',
            status: QaThreadStatus::Resolved,
            createdAt: Carbon::now()->subDays(2),
        );

        $this->attachReplies($resolvedThread, $students, 1, Carbon::now()->subDays(2));
    }

    private function createThread(
        Certification $certification,
        User $author,
        string $title,
        QaThreadStatus $status,
        Carbon $createdAt,
    ): QaThread {
        $thread = QaThread::create([
            'certification_id' => $certification->id,
            'user_id' => $author->id,
            'title' => $title,
            'body' => "{$title} の本文です。教材のどの部分でつまずいたかのサンプルテキストです。",
            'status' => $status,
            'resolved_at' => $status === QaThreadStatus::Resolved ? $createdAt->clone()->addHours(6) : null,
        ]);

        $thread->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->save();

        return $thread;
    }

    /**
     * @param Collection<int, User> $students
     */
    private function attachReplies(QaThread $thread, Collection $students, int $count, Carbon $threadCreatedAt): void
    {
        for ($i = 0; $i < $count; $i++) {
            $replyCreatedAt = $threadCreatedAt->clone()->addHours($i + 1);

            $reply = QaReply::create([
                'qa_thread_id' => $thread->id,
                'user_id' => $students->random()->id,
                'body' => "回答本文のサンプルです。#{$i}",
            ]);

            $reply->forceFill(['created_at' => $replyCreatedAt, 'updated_at' => $replyCreatedAt])->save();
        }
    }
}
