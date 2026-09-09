<?php

declare(strict_types=1);

namespace Tests\Feature\Http\QaReply;

use App\Models\Certification;
use App\Models\QaReply;
use App\Models\QaThread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 質問掲示板の回答 QaReplyController の HTTP 統合テスト。
 * 認可漏れ / FormRequest バリデーション失敗 / 代表的な正常系を網羅する。
 */
class QaReplyControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_post_reply(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $thread = QaThread::factory()->create();

        $response = $this->actingAs($student)->post(route('qa-board.replies.store', $thread), [
            'body' => '回答本文です。',
        ]);

        $response->assertRedirect(route('qa-board.show', $thread));
        $this->assertDatabaseHas('qa_replies', [
            'qa_thread_id' => $thread->id,
            'user_id' => $student->id,
            'body' => '回答本文です。',
        ]);
    }

    public function test_coach_can_post_reply(): void
    {
        $coach = User::factory()->coach()->inProgress()->create();
        $thread = QaThread::factory()->create();

        $response = $this->actingAs($coach)->post(route('qa-board.replies.store', $thread), [
            'body' => 'コーチからの回答です。',
        ]);

        $response->assertRedirect(route('qa-board.show', $thread));
        $this->assertDatabaseHas('qa_replies', [
            'qa_thread_id' => $thread->id,
            'user_id' => $coach->id,
        ]);
    }

    public function test_admin_cannot_post_reply(): void
    {
        $admin = User::factory()->admin()->inProgress()->create();
        $thread = QaThread::factory()->create();

        $response = $this->actingAs($admin)->postJson(route('qa-board.replies.store', $thread), [
            'body' => '管理者からの回答です。',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('qa_replies', 0);
    }

    public function test_store_requires_body(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $thread = QaThread::factory()->create();

        $response = $this->actingAs($student)->postJson(route('qa-board.replies.store', $thread), [
            'body' => '',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['body']);
    }

    public function test_owner_can_update_reply(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $thread = QaThread::factory()->create();
        $reply = QaReply::factory()->for($thread)->for($student)->create(['body' => '元の回答']);

        $response = $this->actingAs($student)->patch(
            route('qa-board.replies.update', ['thread' => $thread, 'reply' => $reply]),
            ['body' => '更新後の回答'],
        );

        $response->assertRedirect(route('qa-board.show', $thread));
        $this->assertDatabaseHas('qa_replies', [
            'id' => $reply->id,
            'body' => '更新後の回答',
        ]);
    }

    public function test_other_user_cannot_update_reply(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $thread = QaThread::factory()->create();
        $reply = QaReply::factory()->for($thread)->create();

        $response = $this->actingAs($student)->patchJson(
            route('qa-board.replies.update', ['thread' => $thread, 'reply' => $reply]),
            ['body' => '不正な更新'],
        );

        $response->assertForbidden();
    }

    public function test_owner_can_delete_reply(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $thread = QaThread::factory()->create();
        $reply = QaReply::factory()->for($thread)->for($student)->create();

        $response = $this->actingAs($student)->delete(
            route('qa-board.replies.destroy', ['thread' => $thread, 'reply' => $reply]),
        );

        $response->assertRedirect(route('qa-board.show', $thread));
        $this->assertDatabaseMissing('qa_replies', ['id' => $reply->id]);
    }

    public function test_other_user_cannot_delete_reply(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $thread = QaThread::factory()->create();
        $reply = QaReply::factory()->for($thread)->create();

        $response = $this->actingAs($student)->deleteJson(
            route('qa-board.replies.destroy', ['thread' => $thread, 'reply' => $reply]),
        );

        $response->assertForbidden();
        $this->assertDatabaseHas('qa_replies', ['id' => $reply->id]);
    }

    public function test_admin_can_force_delete_any_reply(): void
    {
        $admin = User::factory()->admin()->inProgress()->create();
        $thread = QaThread::factory()->create();
        $reply = QaReply::factory()->for($thread)->create();

        $response = $this->actingAs($admin)->delete(
            route('admin.qa-board.replies.destroy', ['thread' => $thread, 'reply' => $reply]),
        );

        $response->assertRedirect(route('admin.qa-board.show', $thread));
        $this->assertDatabaseMissing('qa_replies', ['id' => $reply->id]);
    }

    public function test_unauthenticated_user_cannot_post_reply(): void
    {
        $thread = QaThread::factory()->create();

        $response = $this->post(route('qa-board.replies.store', $thread), [
            'body' => '回答本文です。',
        ]);

        $response->assertRedirect(route('login'));
    }
}