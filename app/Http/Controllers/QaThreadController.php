<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\CertificationStatus;
use App\Enums\UserRole;
use App\Http\Requests\QaThread\StoreRequest;
use App\Http\Requests\QaThread\UpdateRequest;
use App\Models\Certification;
use App\Models\QaThread;
use App\UseCases\QaThread\AdminDestroyAction;
use App\UseCases\QaThread\AdminIndexAction;
use App\UseCases\QaThread\CreateAction;
use App\UseCases\QaThread\DestroyAction;
use App\UseCases\QaThread\IndexAction;
use App\UseCases\QaThread\ResolveAction;
use App\UseCases\QaThread\ShowAction;
use App\UseCases\QaThread\StoreAction;
use App\UseCases\QaThread\UnresolveAction;
use App\UseCases\QaThread\UpdateAction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 質問掲示板のスレッド Controller。
 *
 * - 受講生・コーチ共通: index(一覧) / show(詳細) / replies(QaReplyControllerへ分離)
 * - 受講生専用: create/store/edit/update/destroy/resolve/unresolve(ルートmiddlewareで制限)
 * - 管理者専用: adminIndex/adminShow/adminDestroy(role:adminミドルウェアで制限、Policyは使わない)
 */
class QaThreadController extends Controller
{
    public function index(Request $request, IndexAction $action): View
    {
        $filters = $request->only(['certification_id', 'status', 'keyword']);

        $user = auth()->user();
        $threads = $action($user, $filters);

        // 一覧の資格チップは閲覧範囲に合わせる: student=公開済み全件 / coach=担当資格のみ
        $certifications = $user->role === UserRole::Coach
            ? Certification::query()->published()->assignedTo($user)->orderBy('name')->get()
            : Certification::query()->published()->orderBy('name')->get();

        return view('qa-thread.index', [
            'threads' => $threads,
            'filters' => $filters,
            'certifications' => $certifications,
            'publishedStatus' => CertificationStatus::Published,
        ]);
    }

    public function show(QaThread $thread, ShowAction $action): View
    {
        $this->authorize('view', $thread);

        $thread = $action($thread);

        return view('qa-thread.show', [
            'thread' => $thread,
        ]);
    }

    public function create(CreateAction $action): View
    {
        return view('qa-thread.create', [
            'certifications' => $action(),
        ]);
    }

    public function store(StoreRequest $request, StoreAction $action): RedirectResponse
    {
        $thread = $action(auth()->user(), $request->validated());

        return redirect()
            ->route('qa-board.show', $thread)
            ->with('success', '質問を投稿しました。');
    }

    public function edit(QaThread $thread): View
    {
        $this->authorize('update', $thread);

        return view('qa-thread.edit', [
            'thread' => $thread,
        ]);
    }

    public function update(QaThread $thread, UpdateRequest $request, UpdateAction $action): RedirectResponse
    {
        $action($thread, $request->validated());

        return redirect()
            ->route('qa-board.show', $thread)
            ->with('success', '質問を更新しました。');
    }

    public function destroy(QaThread $thread, DestroyAction $action): RedirectResponse
    {
        $this->authorize('delete', $thread);

        $action($thread);

        return redirect()
            ->route('qa-board.index')
            ->with('success', '質問を削除しました。');
    }

    public function resolve(QaThread $thread, ResolveAction $action): RedirectResponse
    {
        $this->authorize('resolve', $thread);

        $action($thread);

        return redirect()
            ->route('qa-board.show', $thread)
            ->with('success', '解決済みにしました。');
    }

    public function unresolve(QaThread $thread, UnresolveAction $action): RedirectResponse
    {
        $this->authorize('unresolve', $thread);

        $action($thread);

        return redirect()
            ->route('qa-board.show', $thread)
            ->with('success', '未解決に戻しました。');
    }

    public function adminIndex(Request $request, AdminIndexAction $action): View
    {
        $filters = $request->only(['certification_id', 'status', 'keyword']);

        $threads = $action($filters);

        return view('qa-thread.index', [   // ← qa-thread.admin-indexではなく、qa-thread.indexを使う点に注意
            'threads' => $threads,
            'filters' => $filters,
            'certifications' => Certification::query()->orderBy('name')->get(),   // ← publishedを付けない(全件)
            'publishedStatus' => CertificationStatus::Published,
        ]);
    }

    public function adminShow(QaThread $thread, ShowAction $action): View
    {
        $thread = $action($thread);

        return view('qa-thread.show', [
            'thread' => $thread,
        ]);
    }

    public function adminDestroy(QaThread $thread, AdminDestroyAction $action): RedirectResponse
    {
        $action($thread);

        return redirect()
            ->route('admin.qa-board.index')
            ->with('success', '質問を削除しました。');
    }
}
