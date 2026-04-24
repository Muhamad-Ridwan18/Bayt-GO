<?php

namespace App\Http\Controllers\Api\Muthowif;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MuthowifService;

class MuthowifServiceController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        if (!$user->muthowifProfile) {
            return response()->json(['message' => 'Muthowif profile not found'], 404);
        }

        $services = $user->muthowifProfile->services()->with('addOns')->get()->map(function ($service) {
            return [
                'id' => $service->id,
                'name' => $service->name ?? $service->type->name,
                'type' => $service->type->value,
                'daily_price' => (float)$service->daily_price,
                'same_hotel_price_per_day' => (float)$service->same_hotel_price_per_day,
                'transport_price_flat' => (float)$service->transport_price_flat,
                'min_pilgrims' => $service->min_pilgrims,
                'max_pilgrims' => $service->max_pilgrims,
                'description' => $service->description ?? 'Layanan bimbingan jamaah',
                'status' => 'Aktif',
                'add_ons' => $service->addOns->map(function($addon) {
                    return [
                        'id' => $addon->id,
                        'name' => $addon->name,
                        'price' => (float)$addon->price
                    ];
                })
            ];
        });

        return response()->json([
            'services' => $services
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'daily_price' => 'required|numeric',
            'same_hotel_price_per_day' => 'required|numeric',
            'transport_price_flat' => 'required|numeric',
            'min_pilgrims' => 'required|integer',
            'max_pilgrims' => 'required|integer',
            'description' => 'nullable|string',
            'add_ons' => 'nullable|array',
            'add_ons.*.name' => 'required_with:add_ons|string',
            'add_ons.*.price' => 'required_with:add_ons|numeric',
        ]);

        $user = $request->user();
        $service = $user->muthowifProfile->services()->findOrFail($id);

        $service->update([
            'name' => $request->name,
            'daily_price' => $request->daily_price,
            'same_hotel_price_per_day' => $request->same_hotel_price_per_day,
            'transport_price_flat' => $request->transport_price_flat,
            'min_pilgrims' => $request->min_pilgrims,
            'max_pilgrims' => $request->max_pilgrims,
            'description' => $request->description,
        ]);

        // Sync Add Ons for Private Service
        if ($service->type->value === 'private' || $service->type->value === 'private_jamaah') {
            $service->addOns()->delete();
            if ($request->has('add_ons')) {
                foreach ($request->add_ons as $index => $addon) {
                    if (!empty($addon['name'])) {
                        $service->addOns()->create([
                            'name' => $addon['name'],
                            'price' => $addon['price'],
                            'sort_order' => $index
                        ]);
                    }
                }
            }
        }

        return response()->json([
            'message' => 'Layanan berhasil diperbarui',
            'service' => $service->load('addOns')
        ]);
    }
}
