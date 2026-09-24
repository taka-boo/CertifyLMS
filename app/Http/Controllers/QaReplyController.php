<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\QaReply\StoreRequest;
use App\Http\Requests\QaReply\UpdateRequest;
use App\Models\QaReply;
use App\Models\QaThread;
use App\UseCases\QaReply\AdminDestroyAction;
use App\UseCases\QaReply\DestroyAction;
use App\UseCases\QaReply\StoreAction;
use App\UseCases\QaReply\UpdateAction;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * 質問掲示板の回答 Controller。
 *
 * - 受講生・コーチ共通: store(投稿)
 * - 投稿者本人専用: edit/update/destroy(受講生・コーチどちらも可、Policyで判別)
 * - 管理者専用: adminDestroy(role:adminミドルウェアで制限、Policyは使わない)
 */
class QaReplyController extends Controller
{
    public function store(QaThread $thread, StoreRequest $request, StoreAction $action): RedirectResponse
    {
        $action($thread, auth()->user(), $request->validated());

        return redirect()
            ->route('qa-board.show', $thread)
            ->with('success', '回答を投稿しました。');
    }

    public function edit(QaThread $thread, QaReply $reply): View
    {
        $this->authorize('update', $reply);

        return view('qa-thread.reply-edit', [
            'thread' => $thread,
            'reply' => $reply,
        ]);
    }

    public function update(QaThread $thread, QaReply $reply, UpdateRequest $request, UpdateAction $action): RedirectResponse
    {
        $action($reply, $request->validated());

        return redirect()
            ->route('qa-board.show', $thread)
            ->with('success', '回答を更新しました。');
    }

    public function destroy(QaThread $thread, QaReply $reply, DestroyAction $action): RedirectResponse
    {
        $this->authorize('delete', $reply);

        $action($reply);

        return redirect()
            ->route('qa-board.show', $thread)
            ->with('success', '回答を削除しました。');
    }

    public function adminDestroy(QaThread $thread, QaReply $reply, AdminDestroyAction $action): RedirectResponse
    {
        $action($reply);

        return redirect()
            ->route('admin.qa-board.show', $thread)
            ->with('success', '回答を削除しました。');
    }
}
