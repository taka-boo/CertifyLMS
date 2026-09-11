<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\MeetingPack;

use App\Exceptions\MeetingPack\MeetingPackNotDeletableException;
use App\Models\MeetingPack;
use App\UseCases\MeetingPack\DestroyAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 面談パックの削除条件は Certification と逆（下書き限定ではなく、公開中「以外」なら削除可）である点に注意。
 * 要件: 公開中の面談パックは削除不可（過去の購入履歴の整合性を守るため）。下書き・アーカイブはどちらも削除可。
 */
class DestroyActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_deletes_draft_meeting_pack(): void
    {
        $plan = MeetingPack::factory()->draft()->create();

        (new DestroyAction)($plan);

        $this->assertDatabaseMissing('meeting_packs', ['id' => $plan->id]);
    }

    public function test_deletes_archived_meeting_pack(): void
    {
        $plan = MeetingPack::factory()->archived()->create();

        (new DestroyAction)($plan);

        $this->assertDatabaseMissing('meeting_packs', ['id' => $plan->id]);
    }

    public function test_throws_when_published(): void
    {
        $plan = MeetingPack::factory()->published()->create();

        $this->expectException(MeetingPackNotDeletableException::class);

        (new DestroyAction)($plan);
    }
}
