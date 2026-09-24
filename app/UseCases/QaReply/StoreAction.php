<?php

declare(strict_types=1);

namespace App\UseCases\QaReply;

use App\Models\QaReply;
use App\Models\QaThread;
use App\Models\User;

/**
 * 受講生・コーチによるQ&A掲示板への回答投稿Action。
 * 権限判定(受講生は無条件可、コーチは担当資格のみ可)はQaReplyPolicy::createで完結している。
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
