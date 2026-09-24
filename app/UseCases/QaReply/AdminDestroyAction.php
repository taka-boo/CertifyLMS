<?php

declare(strict_types=1);

namespace App\UseCases\QaReply;

use App\Models\QaReply;

/** 管理者による質問掲示板の回答強制削除Action(モデレーション目的) */
final class AdminDestroyAction
{
    public function __invoke(QaReply $reply): void
    {
        $reply->delete();
    }
}
