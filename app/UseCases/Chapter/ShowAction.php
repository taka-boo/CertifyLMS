<?php

declare(strict_types=1);

namespace App\UseCases\Chapter;

use App\Models\Chapter;

/**
 * Chapter 詳細取得ユースケース。親 Part / Certification と配下 Section を Eager Load する。
 * 
 * ## 変更点・理由（B-B-02）
 * - sections の Eager Load に ordered() が呼ばれておらず、 Chapter 詳細画面の Section
 * 　一覧が登録順で表示されるバグがあった。 Section::scopeOrdered() は定義済みだったが呼び
 *   出されていなかった（デッドコード）。
 * 
 */

final class ShowAction
{
    public function __invoke(Chapter $chapter): Chapter
    {
        return $chapter->load([
            'part.certification',
            'sections' => fn($q) => $q->ordered(),
        ]);
    }
}
