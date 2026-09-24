<?php

declare(strict_types=1);

namespace App\UseCases\QaThread;

use App\Models\QaThread;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/** 質問掲示板のスレッド一覧取得Action(受講生・コーチ共通) */
final class IndexAction
{
    /**
     * @param array{certification_id?: string, resolved?: bool, keyword?: string} $filters
     */
    public function __invoke(User $user, array $filters): LengthAwarePaginator
    {
        return QaThread::query()
            ->forUser($user)
            ->when(
                $filters['certification_id'] ?? null,
                fn ($query, $certificationId) => $query->where('certification_id', $certificationId),
            )
            ->statusFilter($filters['status'] ?? null)
            ->keyword($filters['keyword'] ?? null)
            // N+1問題を防ぐため、一覧表示に必要な関連データを事前に読み込む
            ->with(['certification', 'user'])
            ->withCount('replies')
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();
    }
}
