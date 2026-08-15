<?php

namespace App\Http\Controllers\Workspace;

use App\Http\Controllers\Controller;
use App\Models\ManagerActionLog;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ManagerActionLogController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = $request->user()->company_id;
        $filters = $request->validate([
            'service_request_id' => ['nullable', 'integer'],
            'manager_id' => ['nullable', 'integer'],
        ]);
        $selectedRequest = isset($filters['service_request_id'])
            ? ServiceRequest::where('company_id', $companyId)->findOrFail($filters['service_request_id'])
            : null;
        $logs = ManagerActionLog::where('company_id', $companyId)
            ->with(['manager', 'serviceRequest'])
            ->when($selectedRequest, fn ($query) => $query->where('service_request_id', $selectedRequest->id))
            ->when($filters['manager_id'] ?? null, fn ($query, $managerId) => $query->where('user_id', $managerId))
            ->latest('created_at')
            ->paginate(50)
            ->withQueryString();
        $managers = User::where('company_id', $companyId)->where('role', 'manager')->orderBy('name')->get();

        return view('workspace.manager-actions.index', compact('logs', 'managers', 'selectedRequest'));
    }
}
