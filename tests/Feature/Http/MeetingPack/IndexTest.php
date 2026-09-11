<?php

declare(strict_types=1);

namespace Tests\Feature\Http\MeetingPack;

use App\Models\MeetingPack;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_index(): void
    {
        $admin = User::factory()->admin()->create();
        MeetingPack::factory()->count(3)->create();

        $response = $this->actingAs($admin)->get(route('admin.meeting-packs.index'));

        $response->assertOk();
        $response->assertViewIs('meeting-pack.management.index');
    }

    public function test_index_filters_by_keyword(): void
    {
        $admin = User::factory()->admin()->create();
        MeetingPack::factory()->create(['name' => '5 回パック']);
        MeetingPack::factory()->create(['name' => 'お試しプラン']);

        $response = $this->actingAs($admin)->get(route('admin.meeting-packs.index', ['keyword' => 'パック']));

        $response->assertOk();
        $response->assertViewHas('plans', fn ($plans) => $plans->total() === 1);
    }

    public function test_coach_cannot_view_index(): void
    {
        $coach = User::factory()->coach()->create();

        $response = $this->actingAs($coach)->get(route('admin.meeting-packs.index'));

        $response->assertForbidden();
    }

    public function test_student_cannot_view_index(): void
    {
        $student = User::factory()->student()->create();

        $response = $this->actingAs($student)->get(route('admin.meeting-packs.index'));

        $response->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('admin.meeting-packs.index'));

        $response->assertRedirect(route('login'));
    }
}
