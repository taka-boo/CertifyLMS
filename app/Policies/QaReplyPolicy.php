<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\QaReply;
use App\Models\QaThread;
use App\Models\User;

/**
 * QaReply リソースに対する認可ポリシー。
 *
 * - create: 受講生は無条件で可。コーチは担当資格のスレッドのみ可(担当外は403)。管理者は不可。
 * - update / delete: 投稿者本人(受講生 or コーチ、どちらでも可)のみ。管理者はdeleteのみ常時可。
 */
class QaReplyPolicy
{
    public function create(User $user, QaThread $thread): bool
    {
        return match ($user->role) {
            UserRole::Student => true,
            UserRole::Coach => $this->isAssignedCoach($thread, $user),
            default => false,
        };
    }

    public function update(User $user, QaReply $reply): bool
    {
        return $reply->user_id === $user->id;
    }

    public function delete(User $user, QaReply $reply): bool
    {
        return match ($user->role) {
            UserRole::Admin => true,
            default => $reply->user_id === $user->id,
        };
    }

    private function isAssignedCoach(QaThread $thread, User $coach): bool
    {
        $thread->loadMissing('certification.coaches');

        return $thread->certification?->coaches->contains('id', $coach->id) ?? false;
    }
}
