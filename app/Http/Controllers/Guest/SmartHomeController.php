<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\View\View;

class SmartHomeController extends Controller
{
    public function __invoke(Company $company): View
    {
        return view('guest.smart-home', compact('company'));
    }
}
