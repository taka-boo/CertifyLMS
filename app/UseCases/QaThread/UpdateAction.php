<?php

declare(strict_types=1);

namespace App\UseCases\QaThread;

use App\Models\QaThread;

/** 投稿者本人による質問掲示板スレッドの編集Action。本人チェックはControllerで実施。 */
final class UpdateAction
{
    /**
     * @param array{title: string, body: string} $validated
     */
    public function __invoke(QaThread $thread, array $validated): QaThread
    {
        // 資格(certification_id)は編集不可のため、title・bodyのみ更新対象とする
        $thread->update([
            'title' => $validated['title'],
            'body' => $validated['body'],
        ]);

        return $thread;
    }
}
