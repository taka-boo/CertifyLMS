<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Certification;
use App\Models\QuestionCategory;
use App\Models\User;

/**
 * 出題分野マスタの認可ポリシー。
 *
 * - admin: 全資格配下を CRUD 可
 * - coach: 担当資格配下のみ CRUD 可
 * 演習問題と模試問題の両系統から参照される共有マスタの管理権限を制御する。
 */
class QuestionCategoryPolicy
{
    public function viewAny(User $auth, Certification $certification): bool
    {
        return $this->canManage($auth, $certification);
    }

    public function create(User $auth, Certification $certification): bool
    {
        return $this->canManage($auth, $certification);
    }

    public function update(User $auth, QuestionCategory $category): bool
    {
        return $this->canManage($auth, $category->certification);
    }

    public function delete(User $auth, QuestionCategory $category): bool
    {
        return $this->canManage($auth, $category->certification);
    }

    private function canManage(User $auth, Certification $certification): bool
    {
        return match ($auth->role) {
            UserRole::Admin => true,
            // 修正前: false固定（B-B-01の原因箇所）
            // 修正後: assignedCoach()で判定
            UserRole::Coach => $this->assignedCoach($auth, $certification),
            default => false,
        };
    }

    // 修正: このファイルにはassignedCoach()自体が存在しなかったため新規追加（他Policyと同じ実装で統一）
    private function assignedCoach(User $coach, Certification $certification): bool
    {
        return $certification->coaches()->where('users.id', $coach->id)->exists();
    }
}
