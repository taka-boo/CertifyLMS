<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests\MeetingPack;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * 面談パック新規作成 StoreRequest のバリデーション検証。
 * 必須 name / meeting_count / price と、任意項目 description / stripe_price_id / sort_order の
 * 上下限を valid + invalid で網羅する。authorize は admin のみ true を検証する。
 */
class StoreRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_validation_passes_with_valid_payload(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('admin.meeting-packs.store'), [
            'name' => '5 回パック',
            'description' => 'テスト説明',
            'meeting_count' => 5,
            'price' => 12000,
            'stripe_price_id' => 'price_abc123',
            'sort_order' => 10,
        ]);

        $response->assertSessionDoesntHaveErrors();
        $response->assertStatus(302);
        $this->assertDatabaseHas('meeting_packs', ['name' => '5 回パック']);
    }

    public function test_validation_passes_with_only_required_fields(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('admin.meeting-packs.store'), [
            'name' => '1 回パック',
            'meeting_count' => 1,
            'price' => 3000,
        ]);

        $response->assertSessionDoesntHaveErrors();
    }

    public function test_validation_passes_with_large_sort_order(): void
    {
        // sort_order は上限を撤廃済み（PM フィードバック）。DB カラムの unsignedInteger 範囲内であれば通る。
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->postJson(route('admin.meeting-packs.store'), [
            'name' => '大きい並び順パック',
            'meeting_count' => 5,
            'price' => 10000,
            'sort_order' => 999999,
        ]);

        $response->assertStatus(302);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    #[DataProvider('invalidPayloads')]
    public function test_validation_fails(array $overrides, string $expectedErrorField): void
    {
        $admin = User::factory()->admin()->create();
        $payload = array_merge([
            'name' => 'サンプルパック',
            'meeting_count' => 5,
            'price' => 10000,
        ], $overrides);

        $response = $this->actingAs($admin)->postJson(route('admin.meeting-packs.store'), $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors($expectedErrorField);
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

    /**
     * @return array<string, array{0: array<string, mixed>, 1: string}>
     */
    public static function invalidPayloads(): array
    {
        return [
            'name が空' => [['name' => ''], 'name'],
            'name が101文字' => [['name' => str_repeat('あ', 101)], 'name'],
            'description が2001文字' => [['description' => str_repeat('あ', 2001)], 'description'],
            'meeting_count が空' => [['meeting_count' => ''], 'meeting_count'],
            'meeting_count が0' => [['meeting_count' => 0], 'meeting_count'],
            'meeting_count が101' => [['meeting_count' => 101], 'meeting_count'],
            'price が空' => [['price' => ''], 'price'],
            'price が負数' => [['price' => -1], 'price'],
            'price が1000001' => [['price' => 1000001], 'price'],
            'stripe_price_id が256文字' => [['stripe_price_id' => str_repeat('a', 256)], 'stripe_price_id'],
            'sort_order が負数' => [['sort_order' => -1], 'sort_order'],
        ];
    }
}
