<?php

namespace App\Http\Controllers\Guest;

use App\Enums\ServiceNodeType;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\ServiceNode;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function __invoke(Company $company): View
    {
        $company->load('backgroundSet.images');
        $nodes = ServiceNode::where('company_id', $company->id)->where('is_active', true)
            ->with(['backgroundImage', 'children' => fn ($query) => $query->where('is_active', true)->with(['backgroundImage', 'children' => fn ($query) => $query->where('is_active', true)->with('backgroundImage')])])
            ->orderBy('sort_order')->orderBy('name')->get();
        $categories = $nodes->whereNull('parent_id')->where('type', ServiceNodeType::Category);
        return view('guest.catalog', compact('company', 'categories'));
    }
}
