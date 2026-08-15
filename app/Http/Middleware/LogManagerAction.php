<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Models\ManagerActionLog;
use App\Models\ServiceRequest;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogManagerAction
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $user = $request->user();
        $routeName = $request->route()?->getName();

        if (
            $user?->role === UserRole::Manager
            && ! in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)
            && $response->getStatusCode() < 400
            && is_string($routeName)
            && (str_starts_with($routeName, 'workspace.requests.')
                || str_starts_with($routeName, 'workspace.stays.')
                || str_starts_with($routeName, 'workspace.services.'))
        ) {
            $serviceRequest = $request->route('serviceRequest');
            $serviceRequestId = $serviceRequest instanceof ServiceRequest
                ? $serviceRequest->id
                : ($request->attributes->getInt('audit_service_request_id') ?: null);

            ManagerActionLog::create([
                'company_id' => $user->company_id,
                'user_id' => $user->id,
                'service_request_id' => $serviceRequestId,
                'action' => $routeName,
                'metadata' => collect($request->only([
                    'status', 'assigned_to', 'refund_type', 'refund_amount', 'archived',
                    'guest_name', 'room_number', 'service_node_id', 'service_request_item_id',
                    'price', 'comment', 'extra_nights',
                ]))->reject(fn ($value) => $value === null || $value === '')->all(),
                'ip_address' => $request->ip(),
                'created_at' => now(),
            ]);
        }

        return $response;
    }
}
