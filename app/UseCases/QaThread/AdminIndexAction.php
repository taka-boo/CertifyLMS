<?php

declare(strict_types=1);

namespace App\UseCases\QaThread;

use App\Models\QaThread;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/** 管理者：質問掲示板スレッド一覧取得Action(全資格対象、公開停止中も含む) */
final class AdminIndexAction
{
    /**
     * @param array{certification_id?: string, status?: QaThreadStatus, keyword?: string} $filters
     */
    public function __invoke(array $filters): LengthAwarePaginator
    {
        return QaThread::query()
            // scopeForUserは使わない(管理者は資格の公開状態に関係なく全件対象のため)
            ->when(
                $filters['certification_id'] ?? null,
                fn ($query, $certificationId) => $query->where('certification_id', $certificationId),
            )
            ->statusFilter($filters['status'] ?? null)
            ->keyword($filters['keyword'] ?? null)
            ->with(['certification', 'user'])
            ->withCount('replies')
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();
    }
}
