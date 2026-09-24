<?php

declare(strict_types=1);

namespace App\UseCases\MeetingPack;

use App\Models\MeetingPack;

/**
 * admin 用の面談パック詳細を取得するユースケース。作成者 / 最終更新者を Eager Loading で揃える。
 *
 * 購入履歴（Payment）は本チケットの時点で Payment モデルが未実装のため取得しない。
 * 詳細画面（Blade）側は `class_exists(\App\Models\Payment::class)` で防御的に描画するため、
 * Payment 実装（S-A-03: Stripe 連携）が完了した段階で改めて Eager Loading を追加する想定。
 */
final class ShowAction
{
    public function __invoke(MeetingPack $meetingPack): MeetingPack
    {
        return $meetingPack->load(['createdBy', 'updatedBy']);
    }
}
