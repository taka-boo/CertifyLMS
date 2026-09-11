<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests\MeetingPack;

use App\Models\MeetingPack;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * 面談パック更新 UpdateRequest のバリデーション検証。
 * ルールは StoreRequest と同一項目（status は含まない）。authorize は admin のみ true。
 */
class UpdateRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_validation_passes_with_valid_payload(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = MeetingPack::factory()->draft()->create();

        $response = $this->actingAs($admin)->patch(route('admin.meeting-packs.update', $plan), [
            'name' => '更新後の名前',
            'description' => '更新後の説明',
            'meeting_count' => 10,
            'price' => 21000,
            'stripe_price_id' => 'price_xyz789',
            'sort_order' => 30,
        ]);

        $response->assertSessionDoesntHaveErrors();
        $response->assertStatus(302);
        $this->assertDatabaseHas('meeting_packs', ['id' => $plan->id, 'name' => '更新後の名前']);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    #[DataProvider('invalidPayloads')]
    public function test_validation_fails(array $overrides, string $expectedErrorField): void
    {
        $admin = User::factory()->admin()->create();
        $plan = MeetingPack::factory()->draft()->create();
        $payload = array_merge([
            'name' => 'サンプルパック',
            'meeting_count' => 5,
            'price' => 10000,
        ], $overrides);

        $response = $this->actingAs($admin)->patchJson(route('admin.meeting-packs.update', $plan), $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors($expectedErrorField);
    }

    public function test_status_field_is_ignored(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = MeetingPack::factory()->draft()->create();

        $this->actingAs($admin)->patch(route('admin.meeting-packs.update', $plan), [
            'name' => '更新後の名前',
            'meeting_count' => 5,
            'price' => 10000,
            'status' => 'published',
        ]);

        $plan->refresh();
        $this->assertSame('draft', $plan->status->value);
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

    /**
     * @return array<string, array{0: array<string, mixed>, 1: string}>
     */
    public static function invalidPayloads(): array
    {
        return [
            'name が空' => [['name' => ''], 'name'],
            'meeting_count が0' => [['meeting_count' => 0], 'meeting_count'],
            'price が負数' => [['price' => -1], 'price'],
        ];
    }
}
