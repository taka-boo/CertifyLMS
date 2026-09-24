<?php

declare(strict_types=1);

namespace App\UseCases\QaReply;

use App\Models\QaReply;

/** 投稿者本人による質問掲示板の回答削除Action */
final class DestroyAction
{
    public function __invoke(QaReply $reply): void
    {
        $reply->delete();
    }
}
