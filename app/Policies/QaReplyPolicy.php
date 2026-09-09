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
 * - create: 受講生・コーチのみ(管理者不可)。$threadは現時点では判定に使わないが、
 *   Bladeが[QaReply::class, $thread]の形で呼び出すため引数として受け取れるようにしている。
 * - update / delete: 投稿者本人(受講生 or コーチ、どちらでも可)のみ
 */
class QaReplyPolicy
{
    public function create(User $user, QaThread $thread): bool
    {
        return in_array($user->role, [UserRole::Student, UserRole::Coach], true);
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
}
