<?php

declare(strict_types=1);

namespace App\UseCases\QaThread;

use App\Models\QaThread;

/** 質問掲示板のスレッド詳細取得Action(受講生・コーチ共通)。user判別はpolicyで実施。 */
final class ShowAction
{
    public function __invoke(QaThread $thread): QaThread
    {
        // 詳細画面で必要な関連データ(資格名・投稿者名・回答とその回答者)をまとめて取得
        return $thread->load(['certification', 'user', 'replies.user']);
    }
}
