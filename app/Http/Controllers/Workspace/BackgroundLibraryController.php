<?php

namespace App\Http\Controllers\Workspace;

use App\Http\Controllers\Controller;
use App\Models\BackgroundImage;
use App\Models\BackgroundSet;
use App\Models\ServiceNode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class BackgroundLibraryController extends Controller
{
    public function index(Request $request): View
    {
        $company = $request->user()->company->load('backgroundSet.images');
        $systemSets = BackgroundSet::query()->where('is_system', true)->with('images')->orderBy('id')->get();
        $customSets = BackgroundSet::query()->where('company_id', $company->id)->with('images')->orderBy('name')->get();

        return view('workspace.backgrounds.index', compact('company', 'systemSets', 'customSets'));
    }

    public function activate(Request $request, BackgroundSet $backgroundSet): RedirectResponse
    {
        $company = $request->user()->company;
        $this->ensureAvailable($company->id, $backgroundSet);
        $backgroundSet->load('images');
        abort_if($backgroundSet->images->where('is_active', true)->isEmpty(), 422, __('workspace.background_set_empty'));

        $oldSetId = $company->background_set_id;
        $targetImages = $backgroundSet->images->where('is_active', true)->keyBy('name');
        $fallback = $targetImages->sortBy('sort_order')->first();

        ServiceNode::query()->where('company_id', $company->id)->with('backgroundImage')->each(function (ServiceNode $node) use ($oldSetId, $targetImages, $fallback) {
            if (! $node->backgroundImage || $node->backgroundImage->background_set_id !== $oldSetId) {
                return;
            }

            $node->update(['background_image_id' => ($targetImages[$node->backgroundImage->name] ?? $fallback)?->id]);
        });

        $company->update(['background_set_id' => $backgroundSet->id]);

        return back()->with('success', __('workspace.background_set_activated', ['name' => $backgroundSet->name]));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);
        $company = $request->user()->company;
        $set = BackgroundSet::firstOrCreate(
            ['company_id' => $company->id, 'slug' => 'custom-library'],
            ['name' => __('workspace.custom_backgrounds'), 'is_system' => false, 'blur_px' => 3, 'overlay_percent' => 52]
        );
        $path = $request->file('image')->store("company-backgrounds/{$company->id}", 'public');

        $set->images()->create([
            'name' => $data['name'],
            'path' => $path,
            'background_position' => 'center',
            'background_size' => 'cover',
            'sort_order' => (($set->images()->max('sort_order') ?? 0) + 10),
            'is_active' => true,
        ]);

        return back()->with('success', __('workspace.background_uploaded'));
    }

    public function destroy(Request $request, BackgroundImage $backgroundImage): RedirectResponse
    {
        $backgroundImage->load('set');
        abort_unless($backgroundImage->set?->company_id === $request->user()->company_id && ! $backgroundImage->set->is_system, 404);

        Storage::disk('public')->delete($backgroundImage->path);
        $backgroundImage->delete();

        return back()->with('success', __('workspace.background_deleted'));
    }

    private function ensureAvailable(int $companyId, BackgroundSet $set): void
    {
        abort_unless($set->is_system || $set->company_id === $companyId, 404);
    }
}
