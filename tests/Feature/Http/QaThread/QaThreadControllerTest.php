<?php

declare(strict_types=1);

namespace Tests\Feature\Http\QaThread;

use App\Enums\QaThreadStatus;
use App\Models\Certification;
use App\Models\QaReply;
use App\Models\QaThread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 質問掲示板スレッド QaThreadController の HTTP 統合テスト。
 * 認可漏れ / FormRequest バリデーション失敗 / 代表的な正常系を網羅する。
 */
class QaThreadControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_shows_threads_for_student(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $certification = Certification::factory()->published()->create();
        $thread = QaThread::factory()->for($student)->for($certification)->create();

        $response = $this->actingAs($student)->get(route('qa-board.index'));

        $response->assertOk();
        $response->assertViewIs('qa-thread.index');
        $response->assertViewHas('threads', function ($threads) use ($thread) {
            return $threads->pluck('id')->contains($thread->id);
        });
    }

    public function test_show_allows_enrolled_student(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $certification = Certification::factory()->published()->create();
        $thread = QaThread::factory()->for($student)->for($certification)->create();

        $response = $this->actingAs($student)->get(route('qa-board.show', $thread));

        $response->assertOk();
        $response->assertViewIs('qa-thread.show');
    }

    public function test_store_creates_thread_for_student(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $certification = Certification::factory()->published()->create();

        $response = $this->actingAs($student)->post(route('qa-board.store'), [
            'certification_id' => $certification->id,
            'title' => 'テストタイトル',
            'body' => 'テスト本文です。',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('qa_threads', [
            'user_id' => $student->id,
            'certification_id' => $certification->id,
            'title' => 'テストタイトル',
            'status' => QaThreadStatus::Open->value,
        ]);
    }

    public function test_store_returns_404_for_unpublished_certification(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $certification = Certification::factory()->draft()->create();

        $response = $this->actingAs($student)->postJson(route('qa-board.store'), [
            'certification_id' => $certification->id,
            'title' => 'テストタイトル',
            'body' => 'テスト本文です。',
        ]);

        $this->assertContains($response->status(), [404, 422]);
        $this->assertDatabaseCount('qa_threads', 0);
    }

    public function test_coach_cannot_access_create_page(): void
    {
        $coach = User::factory()->coach()->inProgress()->create();

        $response = $this->actingAs($coach)->get(route('qa-board.create'));

        $response->assertForbidden();
    }

    public function test_update_allows_owner_student(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $thread = QaThread::factory()->for($student)->create(['title' => '元のタイトル']);

        $response = $this->actingAs($student)->patch(route('qa-board.update', $thread), [
            'title' => '更新後のタイトル',
            'body' => '更新後の本文です。',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('qa_threads', [
            'id' => $thread->id,
            'title' => '更新後のタイトル',
        ]);
    }

    public function test_update_rejects_other_student(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $otherThread = QaThread::factory()->create();

        $response = $this->actingAs($student)->patchJson(route('qa-board.update', $otherThread), [
            'title' => '不正な更新',
            'body' => '不正な本文です。',
        ]);

        $response->assertForbidden();
    }

    public function test_update_does_not_change_certification(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $originalCertification = Certification::factory()->published()->create();
        $thread = QaThread::factory()->for($student)->for($originalCertification)->create();
        $anotherCertification = Certification::factory()->published()->create();

        $this->actingAs($student)->patch(route('qa-board.update', $thread), [
            'certification_id' => $anotherCertification->id,
            'title' => '更新後のタイトル',
            'body' => '更新後の本文です。',
        ]);

        $this->assertDatabaseHas('qa_threads', [
            'id' => $thread->id,
            'certification_id' => $originalCertification->id,
        ]);
    }

    public function test_destroy_succeeds_when_no_replies(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $thread = QaThread::factory()->for($student)->create();

        $response = $this->actingAs($student)->delete(route('qa-board.destroy', $thread));

        $response->assertRedirect(route('qa-board.index'));
        $this->assertDatabaseMissing('qa_threads', ['id' => $thread->id]);
    }

    public function test_destroy_fails_when_replies_exist(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $thread = QaThread::factory()->for($student)->create();
        QaReply::factory()->for($thread)->create();

        $response = $this->actingAs($student)->deleteJson(route('qa-board.destroy', $thread));

        $response->assertStatus(409);
        $this->assertDatabaseHas('qa_threads', ['id' => $thread->id]);
    }

    public function test_resolve_marks_thread_as_resolved(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $thread = QaThread::factory()->for($student)->open()->create();

        $response = $this->actingAs($student)->post(route('qa-board.resolve', $thread));

        $response->assertRedirect();
        $this->assertSame(QaThreadStatus::Resolved, $thread->fresh()->status);
    }

    public function test_coach_cannot_access_unassigned_certification_thread(): void
    {
        $coach = User::factory()->coach()->inProgress()->create();
        $certification = Certification::factory()->published()->create();
        $thread = QaThread::factory()->for($certification)->create();

        $response = $this->actingAs($coach)->getJson(route('qa-board.show', $thread));

        $response->assertForbidden();
    }

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $response = $this->get(route('qa-board.index'));

        $response->assertRedirect(route('login'));
    }
}