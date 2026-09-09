<?php

declare(strict_types=1);

namespace App\UseCases\QaThread;

use App\Enums\CertificationStatus;
use App\Enums\QaThreadStatus;
use App\Models\Certification;
use App\Models\QaThread;
use App\Models\User;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * 受講生による質問掲示板スレッドの新規投稿Action。
 * 受講中かどうかは問わない(未受講の資格にも質問できる。create.blade.php参照)。
 */
final class StoreAction
{
    /**
     * @param array{certification_id: string, title: string, body: string} $validated
     */
    public function __invoke(User $student, array $validated): QaThread
    {
        $certification = Certification::query()
            ->where('id', $validated['certification_id'])
            ->where('status', CertificationStatus::Published->value)
            ->first();

        // 対象資格が published(公開済) 以外の場合は 404
        if ($certification === null) {
            throw new NotFoundHttpException('指定された資格には投稿できません。');
        }

        return QaThread::create([
            'certification_id' => $certification->id,
            'user_id' => $student->id,
            'title' => $validated['title'],
            'body' => $validated['body'],
            'status' => QaThreadStatus::Open,
            'resolved_at' => null,
        ]);
    }
}
