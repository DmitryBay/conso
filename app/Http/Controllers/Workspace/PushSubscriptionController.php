<?php

namespace App\Http\Controllers\Workspace;

use App\Http\Controllers\Controller;
use App\Notifications\WorkspaceNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PushSubscriptionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => ['required', 'url', 'max:500'],
            'keys.p256dh' => ['required', 'string', 'max:255'],
            'keys.auth' => ['required', 'string', 'max:255'],
            'content_encoding' => ['nullable', Rule::in(['aes128gcm', 'aesgcm'])],
        ]);

        $request->user()->updatePushSubscription(
            $data['endpoint'],
            $data['keys']['p256dh'],
            $data['keys']['auth'],
            $data['content_encoding'] ?? 'aes128gcm',
        );

        return response()->json(['ok' => true]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate(['endpoint' => ['required', 'url', 'max:500']]);
        $request->user()->deletePushSubscription($data['endpoint']);

        return response()->json(['ok' => true]);
    }

    public function test(Request $request): JsonResponse
    {
        abort_unless($request->user()->pushSubscriptions()->exists(), 422, 'Сначала включите push-уведомления.');

        $request->user()->notify(new WorkspaceNotification([
            'title_key' => 'workspace.push_test_title',
            'body_key' => 'workspace.push_test_body',
            'params' => [],
            'request_id' => null,
            'url' => route('workspace.notifications.index'),
            'icon' => 'bi-phone-vibrate',
            'push' => true,
        ]));

        return response()->json(['ok' => true]);
    }
}
