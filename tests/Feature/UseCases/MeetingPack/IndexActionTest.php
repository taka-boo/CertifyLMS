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

    /**
     * PM フィードバック（S-B-02）: 公開中を優先して表示し、その中で並び順、その後は下書きが並び順で続く。
     * ステータスをまたいだ並び順（公開中 → 下書き → アーカイブ）を検証する。
     */
    public function test_orders_published_first_then_draft_then_archived(): void
    {
        $draft = MeetingPack::factory()->draft()->create(['sort_order' => 1]);
        $archived = MeetingPack::factory()->archived()->create(['sort_order' => 1]);
        $published = MeetingPack::factory()->published()->create(['sort_order' => 1]);

        $result = (new IndexAction)(keyword: null, status: null);

        $ids = $result->items();
        $this->assertSame($published->id, $ids[0]->id);
        $this->assertSame($draft->id, $ids[1]->id);
        $this->assertSame($archived->id, $ids[2]->id);
    }

    /**
     * 同一ステータス内では sort_order 昇順で並ぶことを検証する。
     */
    public function test_orders_by_sort_order_within_same_status(): void
    {
        $second = MeetingPack::factory()->published()->create(['sort_order' => 20]);
        $first = MeetingPack::factory()->published()->create(['sort_order' => 10]);
        $third = MeetingPack::factory()->published()->create(['sort_order' => 30]);

        $result = (new IndexAction)(keyword: null, status: null);

        $ids = $result->items();
        $this->assertSame($first->id, $ids[0]->id);
        $this->assertSame($second->id, $ids[1]->id);
        $this->assertSame($third->id, $ids[2]->id);
    }
}
