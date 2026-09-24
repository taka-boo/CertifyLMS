<?php

declare(strict_types=1);

namespace Tests\Feature\Http\MeetingPack;

use App\Models\MeetingPack;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_creates_meeting_pack_as_draft(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('admin.meeting-packs.store'), [
            'name' => '5 回パック',
            'description' => '本試験前にまとめて相談したい方向け。',
            'meeting_count' => 5,
            'price' => 12000,
            'sort_order' => 20,
        ]);

        $plan = MeetingPack::query()->where('name', '5 回パック')->firstOrFail();
        $response->assertRedirect(route('admin.meeting-packs.show', $plan));
        $this->assertSame('draft', $plan->status->value);
        $this->assertSame($admin->id, $plan->created_by_user_id);
    }

    public function test_coach_cannot_create(): void
    {
        $coach = User::factory()->coach()->create();

        $response = $this->actingAs($coach)->post(route('admin.meeting-packs.store'), [
            'name' => '5 回パック',
            'meeting_count' => 5,
            'price' => 12000,
        ]);

        $response->assertForbidden();
    }

    public function test_student_cannot_create(): void
    {
        $student = User::factory()->student()->create();

        $response = $this->actingAs($student)->post(route('admin.meeting-packs.store'), [
            'name' => '5 回パック',
            'meeting_count' => 5,
            'price' => 12000,
        ]);

        $response->assertForbidden();
    }
}
