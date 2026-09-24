<?php

declare(strict_types=1);

namespace App\UseCases\MeetingPack;

use App\Enums\MeetingPackStatus;
use App\Exceptions\MeetingPack\MeetingPackInvalidTransitionException;
use App\Models\MeetingPack;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * 面談パックを下書きへ戻す（archived → draft）ユースケース。
 * アーカイブ済み以外からの遷移は不正で MeetingPackInvalidTransitionException（409）。
 *
 * 画面（詳細画面 show.blade.php）に「下書きへ戻す」ボタンが archived 状態のみ表示される形で
 * 既に実装されているため、それに対応する Action として archived → draft の一方向遷移のみ扱う。
 */
final class UnarchiveAction
{
    /**
     * @throws MeetingPackInvalidTransitionException アーカイブ済み以外からの呼出
     */
    public function __invoke(MeetingPack $meetingPack, User $admin): MeetingPack
    {
        if ($meetingPack->status !== MeetingPackStatus::Archived) {
            throw MeetingPackInvalidTransitionException::forUnarchive();
        }

        return DB::transaction(function () use ($meetingPack, $admin) {
            $meetingPack->update([
                'status' => MeetingPackStatus::Draft->value,
                'updated_by_user_id' => $admin->id,
            ]);

            return $meetingPack->fresh();
        });
    }
}
