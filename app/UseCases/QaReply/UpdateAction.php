<?php

declare(strict_types=1);

namespace App\UseCases\QaReply;

use App\Models\QaReply;

/** 投稿者本人による質問掲示板の回答編集Action */
final class UpdateAction
{
    /**
     * @param array{body: string} $validated
     */
    public function __invoke(QaReply $reply, array $validated): QaReply
    {
        $reply->update([
            'body' => $validated['body'],
        ]);

        return $reply;
    }
}
