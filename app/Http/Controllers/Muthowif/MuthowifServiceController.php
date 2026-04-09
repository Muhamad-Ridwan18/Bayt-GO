<?php

namespace App\Http\Controllers\Muthowif;

use App\Enums\MuthowifServiceType;
use App\Http\Controllers\Controller;
use App\Models\MuthowifService;
use App\Support\IndonesianNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MuthowifServiceController extends Controller
{
    public function edit(Request $request): View
    {
        $this->authorize('viewAny', MuthowifService::class);

        $profile = $request->user()->muthowifProfile;
        [$group, $private] = MuthowifService::ensurePairForProfile($profile);
        $private->load('addOns');

        return view('muthowif.pelayanan.edit', [
            'groupService' => $group,
            'privateService' => $private,
        ]);
    }

    public function updateGroup(Request $request): RedirectResponse
    {
        $profile = $request->user()->muthowifProfile;
        $service = $profile->services()->where('type', MuthowifServiceType::Group)->firstOrFail();
        $this->authorize('update', $service);

        $validated = $this->validatedServicePayload($request, 'group');
        $service->update($this->serviceAttributesFromValidated($request, $validated, 'group'));

        return redirect()
            ->route('muthowif.pelayanan.edit')
            ->with('status', 'Layanan group berhasil disimpan.');
    }

    public function updatePrivate(Request $request): RedirectResponse
    {
        $profile = $request->user()->muthowifProfile;
        $service = $profile->services()->where('type', MuthowifServiceType::PrivateJamaah)->firstOrFail();
        $this->authorize('update', $service);

        $validated = $this->validatedServicePayload($request, 'private');
        $service->update($this->serviceAttributesFromValidated($request, $validated, 'private'));

        $addOnRows = $this->validatedAddOnRows($request);
        $service->addOns()->delete();
        foreach ($addOnRows as $order => $row) {
            $service->addOns()->create([
                'name' => $row['name'],
                'price' => $row['price'],
                'sort_order' => $order,
            ]);
        }

        return redirect()
            ->route('muthowif.pelayanan.edit')
            ->with('status', 'Layanan private berhasil disimpan.');
    }

    /**
     * @return array{name: string, daily_price: float|int, min_pilgrims: int, max_pilgrims: int, description: ?string, same_hotel_price_per_day: float|int|null, transport_price_flat: float|int|null}
     */
    private function validatedServicePayload(Request $request, string $prefix): array
    {
        $this->normalizeNumericInputs($request, $prefix);

        $validated = $request->validate([
            "{$prefix}_name" => ['required', 'string', 'max:160'],
            "{$prefix}_daily_price" => ['required', 'numeric', 'min:0'],
            "{$prefix}_min_pilgrims" => ['required', 'integer', 'min:1', 'max:9999'],
            "{$prefix}_max_pilgrims" => ['required', 'integer', 'min:1', 'max:9999'],
            "{$prefix}_description" => ['nullable', 'string', 'max:5000'],
            "{$prefix}_same_hotel_price_per_day" => ['nullable', 'numeric', 'min:0'],
            "{$prefix}_transport_price_flat" => ['nullable', 'numeric', 'min:0'],
        ]);

        if ($validated["{$prefix}_max_pilgrims"] < $validated["{$prefix}_min_pilgrims"]) {
            throw ValidationException::withMessages([
                "{$prefix}_max_pilgrims" => 'Maksimal jemaah harus lebih besar atau sama dengan minimal jemaah.',
            ]);
        }

        return [
            'name' => $validated["{$prefix}_name"],
            'daily_price' => $validated["{$prefix}_daily_price"],
            'min_pilgrims' => $validated["{$prefix}_min_pilgrims"],
            'max_pilgrims' => $validated["{$prefix}_max_pilgrims"],
            'description' => $validated["{$prefix}_description"] ?? null,
            'same_hotel_price_per_day' => $validated["{$prefix}_same_hotel_price_per_day"] ?? null,
            'transport_price_flat' => $validated["{$prefix}_transport_price_flat"] ?? null,
        ];
    }

    /**
     * @return list<array{name: string, price: string}>
     */
    private function validatedAddOnRows(Request $request): array
    {
        $raw = $request->input('add_ons', []);
        if (! is_array($raw)) {
            return [];
        }

        $clean = [];
        foreach ($raw as $row) {
            if (! is_array($row)) {
                continue;
            }
            $clean[] = [
                'name' => $row['name'] ?? '',
                'price' => isset($row['price']) ? IndonesianNumber::digitsOnly((string) $row['price']) : '',
            ];
        }
        $request->merge(['add_ons' => $clean]);

        $out = [];
        foreach ($clean as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            $priceRaw = $row['price'] ?? null;
            if ($priceRaw === '') {
                $priceRaw = null;
            }

            if ($name === '' && $priceRaw === null) {
                continue;
            }

            if ($name === '' || $priceRaw === null) {
                throw ValidationException::withMessages([
                    'add_ons' => 'Setiap add on harus memiliki nama dan harga, atau kosongkan baris yang tidak dipakai.',
                ]);
            }

            if (! is_numeric($priceRaw) || (float) $priceRaw < 0) {
                throw ValidationException::withMessages([
                    'add_ons' => 'Harga add on harus berupa angka valid.',
                ]);
            }

            $out[] = [
                'name' => $name,
                'price' => (string) $priceRaw,
            ];
        }

        return $out;
    }

    /**
     * @param  array{name: string, daily_price: float|int, min_pilgrims: int, max_pilgrims: int, description: ?string, same_hotel_price_per_day: float|int|null, transport_price_flat: float|int|null}  $validated
     * @return array<string, mixed>
     */
    private function serviceAttributesFromValidated(Request $request, array $validated, string $prefix): array
    {
        return [
            'name' => $validated['name'],
            'daily_price' => (string) $validated['daily_price'],
            'min_pilgrims' => $validated['min_pilgrims'],
            'max_pilgrims' => $validated['max_pilgrims'],
            'description' => $validated['description'],
            'same_hotel_price_per_day' => $validated['same_hotel_price_per_day'] !== null
                ? (string) $validated['same_hotel_price_per_day']
                : null,
            'transport_price_flat' => $validated['transport_price_flat'] !== null
                ? (string) $validated['transport_price_flat']
                : null,
        ];
    }

    private function normalizeNumericInputs(Request $request, string $prefix): void
    {
        $merge = [];
        foreach (['daily_price', 'min_pilgrims', 'max_pilgrims', 'same_hotel_price_per_day', 'transport_price_flat'] as $key) {
            $field = "{$prefix}_{$key}";
            $val = $request->input($field);
            if ($val === null || $val === '') {
                $merge[$field] = null;
            } else {
                $digits = IndonesianNumber::digitsOnly((string) $val);
                $merge[$field] = $digits === '' ? null : $digits;
            }
        }
        if ($merge !== []) {
            $request->merge($merge);
        }
    }
}
