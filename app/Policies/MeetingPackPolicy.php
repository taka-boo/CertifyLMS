<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\MeetingPack;
use App\Models\User;

/**
 * 面談パックマスタの認可ルール。全操作 admin のみ（student / coach は完全に拒否）。
 *
 * ルートは `role:admin` ミドルウェアで入口レベルの制御を行うが、CONTRIBUTING.md の規約に従い
 * FormRequest がある操作（store / update）では authorize() 内で Policy を呼ぶことを省略しない。
 * FormRequest がない操作（destroy / publish / archive / unarchive）は Controller 内で直接 authorize() を呼ぶ。
 */
class MeetingPackPolicy
{
    public function viewAny(User $auth): bool
    {
        return $auth->role === UserRole::Admin;
    }

    public function view(User $auth, MeetingPack $meetingPack): bool
    {
        return $auth->role === UserRole::Admin;
    }

    public function create(User $auth): bool
    {
        return $auth->role === UserRole::Admin;
    }

    public function update(User $auth, MeetingPack $meetingPack): bool
    {
        return $auth->role === UserRole::Admin;
    }

    public function delete(User $auth, MeetingPack $meetingPack): bool
    {
        return $auth->role === UserRole::Admin;
    }

    public function publish(User $auth, MeetingPack $meetingPack): bool
    {
        return $auth->role === UserRole::Admin;
    }

    public function archive(User $auth, MeetingPack $meetingPack): bool
    {
        return $auth->role === UserRole::Admin;
    }

    public function unarchive(User $auth, MeetingPack $meetingPack): bool
    {
        return $auth->role === UserRole::Admin;
    }
}
