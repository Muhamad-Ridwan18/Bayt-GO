<?php

namespace App\Http\Controllers\Muthowif;

use App\Enums\SupportPackageCategory;
use App\Http\Controllers\Controller;
use App\Models\MuthowifSupportPackage;
use App\Support\IndonesianNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SupportPackageController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', MuthowifSupportPackage::class);

        $profile = $request->user()->muthowifProfile;
        abort_unless($profile, 403);

        $categoryFilter = SupportPackageCategory::tryFrom((string) $request->query('category', ''));

        $packagesQuery = $profile->supportPackages()
            ->orderBy('sort_order')
            ->orderBy('name');

        if ($categoryFilter !== null) {
            $packagesQuery->where('category', $categoryFilter->value);
        }

        $packages = $packagesQuery->get();

        return view('muthowif.pelayanan-pendukung.index', [
            'packages' => $packages,
            'categoryFilter' => $categoryFilter,
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', MuthowifSupportPackage::class);

        $prefillCategory = SupportPackageCategory::tryFrom((string) $request->query('category', ''));

        return view('muthowif.pelayanan-pendukung.create', [
            'categories' => SupportPackageCategory::ordered(),
            'prefillCategory' => $prefillCategory,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', MuthowifSupportPackage::class);

        $profile = $request->user()->muthowifProfile;
        abort_unless($profile, 403);

        $data = $this->validatedPackage($request);

        $profile->supportPackages()->create([
            ...$data,
            'sort_order' => $profile->supportPackages()->count() + 1,
        ]);

        $redirectParams = [];
        if (SupportPackageCategory::tryFrom($data['category']) !== null) {
            $redirectParams['category'] = $data['category'];
        }

        return redirect()
            ->route('muthowif.pelayanan-pendukung.index', $redirectParams)
            ->with('status', __('layanan_pendukung.flash.package_created'));
    }

    public function edit(MuthowifSupportPackage $supportPackage): View
    {
        $this->authorize('update', $supportPackage);

        return view('muthowif.pelayanan-pendukung.edit', [
            'package' => $supportPackage,
            'categories' => SupportPackageCategory::ordered(),
        ]);
    }

    public function update(Request $request, MuthowifSupportPackage $supportPackage): RedirectResponse
    {
        $this->authorize('update', $supportPackage);

        $data = $this->validatedPackage($request);
        $supportPackage->update($data);

        $redirectParams = [];
        if (SupportPackageCategory::tryFrom($data['category']) !== null) {
            $redirectParams['category'] = $data['category'];
        }

        return redirect()
            ->route('muthowif.pelayanan-pendukung.index', $redirectParams)
            ->with('status', __('layanan_pendukung.flash.package_updated'));
    }

    public function destroy(MuthowifSupportPackage $supportPackage): RedirectResponse
    {
        $this->authorize('delete', $supportPackage);

        $category = $supportPackage->category?->value;
        $supportPackage->delete();

        $redirectParams = [];
        if (is_string($category) && SupportPackageCategory::tryFrom($category) !== null) {
            $redirectParams['category'] = $category;
        }

        return redirect()
            ->route('muthowif.pelayanan-pendukung.index', $redirectParams)
            ->with('status', __('layanan_pendukung.flash.package_deleted'));
    }

    /**
     * @return array{name: string, category: string, description: ?string, price: string, min_pilgrims: int, max_pilgrims: int, is_active: bool}
     */
    private function validatedPackage(Request $request): array
    {
        $priceRaw = IndonesianNumber::digitsOnly((string) $request->input('price', ''));
        $minRaw = IndonesianNumber::digitsOnly((string) $request->input('min_pilgrims', ''));
        $maxRaw = IndonesianNumber::digitsOnly((string) $request->input('max_pilgrims', ''));

        $request->merge([
            'price' => $priceRaw,
            'min_pilgrims' => $minRaw !== '' ? $minRaw : '1',
            'max_pilgrims' => $maxRaw !== '' ? $maxRaw : ($minRaw !== '' ? $minRaw : '1'),
            'is_active' => $request->boolean('is_active'),
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', Rule::enum(SupportPackageCategory::class)],
            'description' => ['nullable', 'string', 'max:2000'],
            'price' => ['required', 'numeric', 'min:0'],
            'min_pilgrims' => ['required', 'integer', 'min:1'],
            'max_pilgrims' => ['required', 'integer', 'min:1'],
            'is_active' => ['sometimes', 'boolean'],
        ], [
            'price.required' => __('layanan_pendukung.validation.package_price_invalid'),
            'price.numeric' => __('layanan_pendukung.validation.package_price_invalid'),
            'category.*' => __('layanan_pendukung.validation.category_invalid'),
        ]);

        $min = max(1, (int) $validated['min_pilgrims']);
        $max = max($min, (int) $validated['max_pilgrims']);
        $category = $validated['category'] instanceof SupportPackageCategory
            ? $validated['category']->value
            : (string) $validated['category'];

        return [
            'name' => trim((string) $validated['name']),
            'category' => $category,
            'description' => filled($validated['description'] ?? null) ? trim((string) $validated['description']) : null,
            'price' => (string) $validated['price'],
            'min_pilgrims' => $min,
            'max_pilgrims' => $max,
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ];
    }
}
