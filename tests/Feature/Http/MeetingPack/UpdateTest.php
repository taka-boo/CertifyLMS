<?php

declare(strict_types=1);

namespace Tests\Feature\Http\MeetingPack;

use App\Models\MeetingPack;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_updates_basic_fields(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = MeetingPack::factory()->draft()->create(['name' => '旧名']);

        $response = $this->actingAs($admin)->patch(route('admin.meeting-packs.update', $plan), [
            'name' => '新名',
            'description' => '更新後の説明',
            'meeting_count' => 8,
            'price' => 20000,
            'sort_order' => 5,
        ]);

        $response->assertRedirect(route('admin.meeting-packs.show', $plan));
        $this->assertSame('新名', $plan->fresh()->name);
        $this->assertSame($admin->id, $plan->fresh()->updated_by_user_id);
    }

    public function test_update_does_not_change_status(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = MeetingPack::factory()->published()->create();

        $this->actingAs($admin)->patch(route('admin.meeting-packs.update', $plan), [
            'name' => '更新後の名前',
            'meeting_count' => 5,
            'price' => 10000,
        ]);

        $this->assertSame('published', $plan->fresh()->status->value);
    }

    public function test_coach_cannot_update(): void
    {
        $coach = User::factory()->coach()->create();
        $plan = MeetingPack::factory()->draft()->create();

        $response = $this->actingAs($coach)->patch(route('admin.meeting-packs.update', $plan), [
            'name' => '更新後の名前',
            'meeting_count' => 5,
            'price' => 10000,
        ]);

        $response->assertForbidden();
    }
}
