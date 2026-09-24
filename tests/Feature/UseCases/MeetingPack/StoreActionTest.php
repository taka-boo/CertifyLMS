<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\MeetingPack;

use App\Enums\MeetingPackStatus;
use App\Models\User;
use App\UseCases\MeetingPack\StoreAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_meeting_pack_as_draft(): void
    {
        $admin = User::factory()->admin()->create();

        $plan = (new StoreAction)($admin, [
            'name' => '5 回パック',
            'description' => 'テスト説明',
            'meeting_count' => 5,
            'price' => 12000,
            'stripe_price_id' => null,
            'sort_order' => 10,
        ]);

        $this->assertSame(MeetingPackStatus::Draft, $plan->status);
        $this->assertSame('5 回パック', $plan->name);
        $this->assertSame(5, $plan->meeting_count);
        $this->assertSame(12000, $plan->price);
        $this->assertSame($admin->id, $plan->created_by_user_id);
        $this->assertSame($admin->id, $plan->updated_by_user_id);
        $this->assertDatabaseHas('meeting_packs', ['id' => $plan->id, 'status' => MeetingPackStatus::Draft->value]);
    }

    public function test_defaults_sort_order_to_zero_when_not_given(): void
    {
        $admin = User::factory()->admin()->create();

        $plan = (new StoreAction)($admin, [
            'name' => '1 回パック',
            'meeting_count' => 1,
            'price' => 3000,
        ]);

        $this->assertSame(0, $plan->sort_order);
    }
}
