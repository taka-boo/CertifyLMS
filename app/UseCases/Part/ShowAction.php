<?php

declare(strict_types=1);

namespace App\UseCases\Part;

use App\Models\Part;

/**
 * Part 詳細取得ユースケース。Certification と Chapter を Eager Load する。
 * 
 * ## 変更点・理由（B-B-02）
 * - chapters の Eager Load に ordered() が呼ばれておらず、 Part 詳細画面の Chapter 一覧
 *   が登録順で表示されるバグがあった。 Chapter::scopeOrdered() は定義済みだったが呼び出
 *   されていなかった（デッドコード）。
 * 
 */
final class ShowAction
{
    public function __invoke(Part $part): Part
    {
        return $part->load([
            'certification',
            'chapters' => fn($q) => $q->ordered()->withCount('sections'),
        ]);
    }
}
