<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\MeetingPack;

use App\Enums\MeetingPackStatus;
use App\Models\MeetingPack;
use App\UseCases\MeetingPack\IndexAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_filters_by_keyword(): void
    {
        MeetingPack::factory()->create(['name' => '5 回パック']);
        MeetingPack::factory()->create(['name' => '10 回パック']);
        MeetingPack::factory()->create(['name' => 'お試しプラン']);

        $result = (new IndexAction)(keyword: 'パック', status: null);

        $this->assertCount(2, $result->items());
    }

    public function test_filters_by_status(): void
    {
        MeetingPack::factory()->draft()->create();
        MeetingPack::factory()->published()->create();
        MeetingPack::factory()->published()->create();
        MeetingPack::factory()->archived()->create();

        $result = (new IndexAction)(keyword: null, status: MeetingPackStatus::Published);

        $this->assertCount(2, $result->items());
    }

    public function test_returns_all_when_no_filter(): void
    {
        MeetingPack::factory()->count(3)->create();

        $result = (new IndexAction)(keyword: null, status: null);

        $this->assertCount(3, $result->items());
    }
}
