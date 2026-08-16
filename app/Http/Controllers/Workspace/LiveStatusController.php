<?php

namespace App\Http\Controllers\Workspace;

use App\Enums\RequestStatus;
use App\Http\Controllers\Controller;
use App\Models\ServiceRequest;
use App\Support\AppVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LiveStatusController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $newRequests = ServiceRequest::query()
            ->where('company_id', $request->user()->company_id)
            ->forCurrentStays()
            ->where('status', RequestStatus::New)
            ->count();

        return response()->json([
            'new_requests' => $newRequests,
            'unread_notifications' => $request->user()->unreadNotifications()->count(),
            'app_version' => AppVersion::current(),
        ]);
    }
}
