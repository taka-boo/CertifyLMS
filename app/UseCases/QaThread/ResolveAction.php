<?php

declare(strict_types=1);

namespace App\UseCases\QaThread;

use App\Enums\QaThreadStatus;
use App\Models\QaThread;

/** 投稿者本人による質問掲示板スレッドの解決済みマークAction (user判別はpolicyで実施) */
final class ResolveAction
{
    public function __invoke(QaThread $thread): void
    {
        $thread->update([
            'status' => QaThreadStatus::Resolved,
            'resolved_at' => now(),
        ]);
    }
}
