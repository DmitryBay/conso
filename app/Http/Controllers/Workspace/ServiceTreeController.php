<?php

namespace App\Http\Controllers\Workspace;

use App\Enums\ServiceNodeType;
use App\Http\Controllers\Controller;
use App\Models\BackgroundImage;
use App\Models\ServiceNode;
use App\Support\BaliDistrictGuides;
use App\Support\ServiceOptionCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ServiceTreeController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = $request->user()->company_id;
        $nodes = ServiceNode::where('company_id', $companyId)->with(['backgroundImage', 'children.backgroundImage', 'children.children.backgroundImage', 'children.children.children.backgroundImage'])->orderBy('sort_order')->orderBy('name')->get();
        $roots = $nodes->whereNull('parent_id');
        $categories = $nodes->where('type', ServiceNodeType::Category);
        $backgroundImages = $request->user()->company->backgroundSet?->images()->where('is_active', true)->get() ?? collect();

        $serviceOptions = ServiceOptionCatalog::OPTIONS;

        return view('workspace.services.index', compact('roots', 'categories', 'nodes', 'backgroundImages', 'serviceOptions'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $companyId = $request->user()->company_id;
        $this->validateBackground($request, $data['background_image_id'] ?? null);
        $parent = $this->validatedParent($companyId, $data['parent_id'] ?? null);

        $priceMinor = $data['type'] === ServiceNodeType::Service->value ? $this->moneyToMinor($data['price'] ?? null, $request->user()->company->currency) : null;

        ServiceNode::create([
            ...$data,
            'company_id' => $companyId,
            'parent_id' => $parent?->id,
            'price_minor' => $priceMinor,
            'payment_method' => $priceMinor > 0 ? $data['payment_method'] : null,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        return back()->with('success', __('workspace.service_added'));
    }

    public function installBaliGuides(Request $request): RedirectResponse
    {
        $companyId = $request->user()->company_id;
        $root = ServiceNode::query()
            ->where('company_id', $companyId)
            ->whereNull('parent_id')
            ->whereIn('name', ['Гид по районам Бали', 'Бали и острова: гид'])
            ->first() ?? new ServiceNode(['company_id' => $companyId, 'parent_id' => null]);
        $root->fill([
            'type' => ServiceNodeType::Category,
            'name' => 'Бали и острова: гид',
            'description' => 'Подробные гайды по районам Бали, соседним островам и направлениям для отдельных поездок.',
            'translations' => [
                'en' => ['name' => 'Bali & islands guide', 'description' => 'Detailed guides to Bali areas, nearby islands and destinations for separate trips.'],
                'id' => ['name' => 'Panduan Bali & pulau', 'description' => 'Panduan lengkap area Bali, pulau sekitar, dan destinasi untuk perjalanan khusus.'],
            ],
            'icon' => 'bi-map',
            'is_active' => true,
            'sort_order' => 70,
        ])->save();

        foreach (BaliDistrictGuides::all() as $index => $guide) {
            ServiceNode::query()->updateOrCreate(
                ['company_id' => $companyId, 'parent_id' => $root->id, 'name' => $guide['name']],
                [
                    'type' => ServiceNodeType::Guide,
                    'description' => $guide['description'],
                    'translations' => $guide['translations'],
                    'external_links' => BaliDistrictGuides::mapsFor($guide['name']),
                    'icon' => $guide['icon'],
                    'price_minor' => null,
                    'sla_minutes' => null,
                    'is_active' => true,
                    'sort_order' => ($index + 1) * 10,
                ]
            );
        }

        return back()->with('success', __('workspace.bali_guides_installed'));
    }

    public function update(Request $request, ServiceNode $serviceNode): RedirectResponse
    {
        $this->ensureTenant($request, $serviceNode);
        $data = $this->validated($request);
        $this->validateBackground($request, $data['background_image_id'] ?? null);
        $parent = $this->validatedParent($request->user()->company_id, $data['parent_id'] ?? null);

        abort_if($parent?->id === $serviceNode->id || $this->isDescendant($parent, $serviceNode), 422, 'Нельзя переместить категорию внутрь самой себя.');

        $priceMinor = $data['type'] === ServiceNodeType::Service->value ? $this->moneyToMinor($data['price'] ?? null, $request->user()->company->currency) : null;

        $serviceNode->update([
            ...$data,
            'parent_id' => $parent?->id,
            'price_minor' => $priceMinor,
            'payment_method' => $priceMinor > 0 ? $data['payment_method'] : null,
        ]);

        return back()->with('success', __('workspace.service_updated'));
    }

    public function destroy(Request $request, ServiceNode $serviceNode): RedirectResponse
    {
        $this->ensureTenant($request, $serviceNode);
        $serviceNode->delete();

        return back()->with('success', __('workspace.service_deleted'));
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'type' => ['required', Rule::enum(ServiceNodeType::class)],
            'name' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:3000'],
            'translations' => ['nullable', 'array'],
            'translations.*.name' => ['nullable', 'string', 'max:160'],
            'translations.*.description' => ['nullable', 'string', 'max:4000'],
            'icon' => ['nullable', 'string', 'max:60'],
            'background_image_id' => ['nullable', 'integer'],
            'parent_id' => ['nullable', 'integer'],
            'price' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            'payment_method' => ['nullable', Rule::in(ServiceNode::PAYMENT_METHODS)],
            'sla_minutes' => ['nullable', 'integer', 'min:1', 'max:10080'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65000'],
            'is_active' => ['nullable', 'boolean'],
            'option_keys' => ['nullable', 'array'],
            'option_keys.*' => ['string', 'distinct', Rule::in(ServiceOptionCatalog::keys())],
        ]) + ['is_active' => $request->boolean('is_active')];

        $isService = ($data['type'] ?? null) === ServiceNodeType::Service->value;
        $data['option_keys'] = $isService ? ServiceOptionCatalog::normalize($data['option_keys'] ?? []) : null;

        if ($isService && $this->moneyToMinor($data['price'] ?? null, $request->user()->company->currency) > 0 && empty($data['payment_method'])) {
            throw ValidationException::withMessages(['payment_method' => __('workspace.payment_method_required')]);
        }

        if ($request->has('translations')) {
            $data['translations'] = collect($data['translations'] ?? [])
                ->only(['en', 'id', 'uk', 'ar', 'he', 'zh', 'ko'])
                ->map(fn (array $translation) => [
                    'name' => trim((string) ($translation['name'] ?? '')) ?: null,
                    'description' => trim((string) ($translation['description'] ?? '')) ?: null,
                ])
                ->filter(fn (array $translation) => $translation['name'] || $translation['description'])
                ->all();
        } else {
            unset($data['translations']);
        }

        return $data;
    }

    private function validateBackground(Request $request, ?int $backgroundImageId): void
    {
        if (! $backgroundImageId) {
            return;
        }

        $company = $request->user()->company;
        abort_unless(BackgroundImage::query()
            ->whereKey($backgroundImageId)
            ->where('background_set_id', $company->background_set_id)
            ->where('is_active', true)
            ->exists(), 404);
    }

    private function validatedParent(int $companyId, ?int $parentId): ?ServiceNode
    {
        if (! $parentId) {
            return null;
        }

        return ServiceNode::where('company_id', $companyId)->where('type', ServiceNodeType::Category)->findOrFail($parentId);
    }

    private function ensureTenant(Request $request, ServiceNode $node): void
    {
        abort_unless($node->company_id === $request->user()->company_id, 404);
    }

    private function isDescendant(?ServiceNode $candidate, ServiceNode $node): bool
    {
        while ($candidate) {
            if ($candidate->id === $node->id) {
                return true;
            }
            $candidate = $candidate->parent;
        }

        return false;
    }

    private function moneyToMinor(mixed $value, string $currency): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) round((float) $value * ($currency === 'IDR' ? 1 : 100));
    }
}
