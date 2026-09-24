<?php

declare(strict_types=1);

namespace App\UseCases\QaThread;

use App\Models\QaThread;
use Illuminate\Support\Facades\DB;

/**
 * 管理者による質問掲示板スレッドの削除Action。
 * 受講生本人の削除(DestroyAction)と異なり、回答が付いていても削除できる。
 */
final class AdminDestroyAction
{
    public function __invoke(QaThread $thread): void
    {
        DB::transaction(function () use ($thread) {
            // 回答が残っているとrestrictOnDelete制約でスレッド削除が失敗するため、先に全件削除
            $thread->replies()->delete();
            $thread->delete();
        });
    }
}
