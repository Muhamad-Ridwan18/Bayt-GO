<?php

namespace App\Http\Controllers\Api\Muthowif;

use App\Enums\SupportPackageCategory;
use App\Http\Controllers\Controller;
use App\Models\MuthowifSupportPackage;
use App\Support\IndonesianNumber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MuthowifSupportPackageApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $profile = $request->user()->muthowifProfile;
        abort_unless($profile, 403);

        $this->authorize('viewAny', MuthowifSupportPackage::class);

        $packages = $profile->supportPackages()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (MuthowifSupportPackage $p) => [
                'id' => (string) $p->getKey(),
                'name' => $p->name,
                'category' => $p->category->value,
                'category_label' => $p->category->label(),
                'description' => $p->description,
                'price' => (float) $p->price,
                'min_pilgrims' => $p->min_pilgrims,
                'max_pilgrims' => $p->max_pilgrims,
                'is_active' => (bool) $p->is_active,
            ]);

        return response()->json([
            'packages' => $packages,
            'categories' => collect(SupportPackageCategory::ordered())->map(fn ($c) => [
                'value' => $c->value,
                'label' => $c->label(),
            ])->values(),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $profile = $request->user()->muthowifProfile;
        abort_unless($profile, 403);

        $this->authorize('viewAny', MuthowifSupportPackage::class);

        $rows = $this->validatedPackageRows($request);
        $keptIds = [];

        foreach ($rows as $order => $row) {
            if (isset($row['id']) && $row['id'] !== null) {
                $package = $profile->supportPackages()->whereKey($row['id'])->firstOrFail();
                $this->authorize('update', $package);
                $package->update([
                    'name' => $row['name'],
                    'category' => $row['category'],
                    'description' => $row['description'],
                    'price' => $row['price'],
                    'min_pilgrims' => $row['min_pilgrims'],
                    'max_pilgrims' => $row['max_pilgrims'],
                    'is_active' => $row['is_active'],
                    'sort_order' => $order,
                ]);
                $keptIds[] = (string) $package->getKey();
            } else {
                $package = $profile->supportPackages()->create([
                    'name' => $row['name'],
                    'category' => $row['category'],
                    'description' => $row['description'],
                    'price' => $row['price'],
                    'min_pilgrims' => $row['min_pilgrims'],
                    'max_pilgrims' => $row['max_pilgrims'],
                    'is_active' => $row['is_active'],
                    'sort_order' => $order,
                ]);
                $keptIds[] = (string) $package->getKey();
            }
        }

        $profile->supportPackages()->whereNotIn('id', $keptIds)->delete();

        return response()->json(['message' => __('layanan_pendukung.flash.packages_saved')]);
    }

    /**
     * @return list<array{id?: string, name: string, category: string, description: ?string, price: string, min_pilgrims: int, max_pilgrims: int, is_active: bool}>
     */
    private function validatedPackageRows(Request $request): array
    {
        $raw = $request->input('packages', []);
        if (! is_array($raw)) {
            return [];
        }

        $clean = [];
        foreach ($raw as $row) {
            if (! is_array($row)) {
                continue;
            }
            $clean[] = [
                'id' => isset($row['id']) && $row['id'] !== '' ? trim((string) $row['id']) : null,
                'name' => trim((string) ($row['name'] ?? '')),
                'category' => trim((string) ($row['category'] ?? '')),
                'description' => trim((string) ($row['description'] ?? '')),
                'price' => isset($row['price']) ? IndonesianNumber::digitsOnly((string) $row['price']) : '',
                'min_pilgrims' => isset($row['min_pilgrims']) ? IndonesianNumber::digitsOnly((string) $row['min_pilgrims']) : '',
                'max_pilgrims' => isset($row['max_pilgrims']) ? IndonesianNumber::digitsOnly((string) $row['max_pilgrims']) : '',
                'is_active' => filter_var($row['is_active'] ?? true, FILTER_VALIDATE_BOOL),
            ];
        }

        $out = [];
        foreach ($clean as $row) {
            if ($row['name'] === '' && $row['price'] === '') {
                continue;
            }

            if ($row['name'] === '' || $row['price'] === '') {
                throw ValidationException::withMessages([
                    'packages' => [__('layanan_pendukung.validation.package_row_incomplete')],
                ]);
            }

            if (! is_numeric($row['price']) || (float) $row['price'] < 0) {
                throw ValidationException::withMessages([
                    'packages' => [__('layanan_pendukung.validation.package_price_invalid')],
                ]);
            }

            $min = max(1, (int) ($row['min_pilgrims'] !== '' ? $row['min_pilgrims'] : 1));
            $max = max($min, (int) ($row['max_pilgrims'] !== '' ? $row['max_pilgrims'] : $min));

            $category = $row['category'] !== '' ? $row['category'] : SupportPackageCategory::Other->value;
            if (SupportPackageCategory::tryFrom($category) === null) {
                throw ValidationException::withMessages([
                    'packages' => [__('layanan_pendukung.validation.category_invalid')],
                ]);
            }

            $out[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'category' => $category,
                'description' => $row['description'] !== '' ? $row['description'] : null,
                'price' => (string) $row['price'],
                'min_pilgrims' => $min,
                'max_pilgrims' => $max,
                'is_active' => $row['is_active'],
            ];
        }

        return $out;
    }
}
