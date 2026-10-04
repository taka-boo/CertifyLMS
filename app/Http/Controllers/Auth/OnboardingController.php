<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\OnboardingRequest;
use App\Models\Invitation;
use App\Services\InvitationTokenService;
use App\UseCases\Auth\OnboardAction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
// ▼ 追加: Response を使えるようにする
use Illuminate\Http\Response;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class OnboardingController extends Controller
{
    // B-B-06: 無効な招待は Response(410)、有効な招待は View を返すため、戻り値の型を両方にする
    public function show(Request $request, Invitation $invitation, InvitationTokenService $tokenService): View|Response
    {
        if (!$tokenService->verify($request, $invitation)) {
            // B-B-06: 無効な招待は、チケットの期待どおり 410(Gone)で返す。
            return response()->view('auth.invitation-invalid', [], 410);
        }

        $postUrl = URL::temporarySignedRoute(
            'onboarding.store',
            $invitation->expires_at,
            ['invitation' => $invitation->id],
        );

        return view('auth.onboarding', [
            'invitation' => $invitation,
            'postUrl' => $postUrl,
        ]);
    }

    public function store(
        Invitation $invitation,
        OnboardingRequest $request,
        OnboardAction $action,
    ): RedirectResponse {
        $action($invitation, $request->validated());

        return redirect()->route('dashboard.index');
    }
}
