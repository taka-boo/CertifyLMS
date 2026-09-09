<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\CertificationStatus;
use App\Enums\UserRole;
use App\Models\QaThread;
use App\Models\User;

/**
 * QaThread リソースに対する認可ポリシー。
 *
 * - view: 受講生(公開済資格なら受講の有無を問わず可) / 担当コーチ / admin
 * - update / delete / resolve / unresolve: 投稿者本人(受講生)のみ
 * - create: 受講生のみ(ルートmiddlewareに加え、FormRequestからも二重チェック)
 *
 * coachの担当判定はcertification.coachesリレーション(certification_coach_assignments経由)で行う。
 */
class QaThreadPolicy
{
    public function view(User $user, QaThread $thread): bool
    {
        return match ($user->role) {
            UserRole::Admin => true,
            UserRole::Student => $this->isCertificationPublished($thread),
            UserRole::Coach => $this->isAssignedCoach($thread, $user),
        };
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::Student;
    }

    public function update(User $user, QaThread $thread): bool
    {
        return $user->role === UserRole::Student
            && $thread->user_id === $user->id;
    }

    public function delete(User $user, QaThread $thread): bool
    {
        return match ($user->role) {
            UserRole::Admin => true,
            UserRole::Student => $thread->user_id === $user->id,
            default => false,
        };
    }

    public function resolve(User $user, QaThread $thread): bool
    {
        return $user->role === UserRole::Student
            && $thread->user_id === $user->id;
    }

    public function unresolve(User $user, QaThread $thread): bool
    {
        return $user->role === UserRole::Student
            && $thread->user_id === $user->id;
    }

    private function isCertificationPublished(QaThread $thread): bool
    {
        $thread->loadMissing('certification');

        return $thread->certification?->status === CertificationStatus::Published;
    }

    private function isAssignedCoach(QaThread $thread, User $coach): bool
    {
        $thread->loadMissing('certification.coaches');

        return $thread->certification?->coaches->contains('id', $coach->id) ?? false;
    }
}