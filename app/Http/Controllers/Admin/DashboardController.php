<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CompanyStatus;
use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $stats = [
            'companies' => Company::count(),
            'active' => Company::where('status', CompanyStatus::Active)->count(),
            'trial' => Company::where('status', CompanyStatus::Trial)->count(),
            'rooms' => Company::sum('rooms_count'),
        ];

        $companies = Company::with('owner')->latest()->limit(5)->get();

        return view('admin.dashboard', compact('stats', 'companies'));
    }
}
