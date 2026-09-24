<?php

declare(strict_types=1);

namespace App\UseCases\QaThread;

use App\Exceptions\QaThread\QaThreadHasRepliesException;
use App\Models\QaThread;

/** 投稿者本人による質問掲示板スレッドの削除Action */
final class DestroyAction
{
    /**
     * @throws QaThreadHasRepliesException
     */
    public function __invoke(QaThread $thread): void
    {
        // 回答が1件でも付いている場合は削除不可
        if ($thread->replies()->exists()) {
            throw QaThreadHasRepliesException::make();
        }

        $thread->delete();
    }
}
