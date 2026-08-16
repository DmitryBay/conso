<?php

namespace App\Http\Controllers\Workspace;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        $notifications = $request->user()->notifications()->paginate(20);

        return view('workspace.notifications.index', compact('notifications'));
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('success', __('workspace.notifications_read'));
    }

    public function read(Request $request, string $notification): RedirectResponse
    {
        $item = $request->user()->notifications()->findOrFail($notification);
        $item->markAsRead();

        return redirect($item->data['url'] ?? route('workspace.notifications.index'));
    }
}
