<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\MeetingPack;

use App\Enums\MeetingPackStatus;
use App\Models\MeetingPack;
use App\Models\User;
use App\UseCases\MeetingPack\UpdateAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_updates_basic_fields(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = MeetingPack::factory()->draft()->create(['name' => '旧名']);

        $result = (new UpdateAction)($plan, $admin, [
            'name' => '新名',
            'description' => '更新後の説明',
            'meeting_count' => 8,
            'price' => 20000,
            'stripe_price_id' => 'price_abc123',
            'sort_order' => 5,
        ]);

        $this->assertSame('新名', $result->name);
        $this->assertSame(8, $result->meeting_count);
        $this->assertSame(20000, $result->price);
        $this->assertSame('price_abc123', $result->stripe_price_id);
        $this->assertSame(5, $result->sort_order);
        $this->assertSame($admin->id, $result->updated_by_user_id);
    }

    public function test_does_not_change_status(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = MeetingPack::factory()->published()->create();

        $result = (new UpdateAction)($plan, $admin, [
            'name' => '更新後の名前',
            'meeting_count' => 3,
            'price' => 7000,
        ]);

        $this->assertSame(MeetingPackStatus::Published, $result->status);
    }
}
