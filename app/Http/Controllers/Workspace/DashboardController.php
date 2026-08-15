<?php

namespace App\Http\Controllers\Workspace;

use App\Enums\RequestStatus;
use App\Http\Controllers\Controller;
use App\Models\ServiceNode;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $companyId = $request->user()->company_id;
        $base = ServiceRequest::where('company_id', $companyId)->forCurrentStays();

        $stats = [
            'new' => (clone $base)->where('status', RequestStatus::New)->count(),
            'active' => (clone $base)->whereIn('status', [RequestStatus::Accepted, RequestStatus::InProgress, RequestStatus::WaitingGuest, RequestStatus::Ready])->count(),
            'overdue' => (clone $base)->whereNotIn('status', [RequestStatus::Ready, RequestStatus::Completed, RequestStatus::Cancelled])->where('due_at', '<', now())->count(),
            'completed_today' => (clone $base)->where('status', RequestStatus::Completed)->whereDate('completed_at', today())->count(),
        ];

        $recent = (clone $base)->with(['assignee', 'service', 'guestStay.room'])->latest()->limit(6)->get();
        $serviceCount = ServiceNode::where('company_id', $companyId)->where('type', 'service')->count();
        $teamCount = User::where('company_id', $companyId)->where('is_active', true)->count();

        return view('workspace.dashboard', compact('stats', 'recent', 'serviceCount', 'teamCount'));
    }
}
