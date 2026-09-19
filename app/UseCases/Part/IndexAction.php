<?php

declare(strict_types=1);

namespace App\UseCases\Part;

use App\Models\Certification;
use App\Models\Part;
use Illuminate\Database\Eloquent\Collection;

/**
 * 指定資格配下の Part 一覧を、配下 Chapter の Section 件数付きで Eager Load して返すユースケース。
 *
 * ## 変更点・理由（B-B-02）
 * - Part 自体に ordered() が呼ばれておらず、登録順（id 等の暗黙順）で返っていたため、設
 *   定した並び順（order 昇順）を無視して表示されるバグがあった。 Part::scopeOrdered() は
 *   定義済みだったが呼び出されていなかった（デッドコード）。
 *
 */
final class IndexAction
{
    /**
     * @return Collection<int, Part>
     */
    public function __invoke(Certification $certification): Collection
    {
        return $certification->parts()
            ->ordered()
            ->with(['chapters' => fn($q) => $q->ordered()->withCount('sections')])
            ->get();
    }
}
