<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AppVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LiveStatusController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        return response()->json([
            'unread_notifications' => $request->user()->unreadNotifications()->count(),
            'app_version' => AppVersion::current(),
        ]);
    }
}
