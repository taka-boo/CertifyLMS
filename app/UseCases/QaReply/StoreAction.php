<?php

declare(strict_types=1);

namespace App\UseCases\QaReply;

use App\Models\QaReply;
use App\Models\QaThread;
use App\Models\User;

/**
 * 受講生・コーチによるQ&A掲示板への回答投稿Action。
 * 受講中/担当資格かどうかは問わない(QaThreadのStoreActionと同じ方針。create.blade.php参照)。
 * 管理者は回答不可(QaReplyPolicy::createで判定)。
 */
final class StoreAction
{
    /**
     * @param array{body: string} $validated
     */
    public function __invoke(QaThread $thread, User $author, array $validated): QaReply
    {
        return QaReply::create([
            'qa_thread_id' => $thread->id,
            'user_id' => $author->id,
            'body' => $validated['body'],
        ]);
    }
}
