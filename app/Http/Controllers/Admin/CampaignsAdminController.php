<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CampaignsAdminController extends Controller
{
    public function index()
    {
        $campaigns = Campaign::orderBy('sort_order')->orderByDesc('start_date')->get();
        return view('admin.campaigns.index', compact('campaigns'));
    }

    public function create()
    {
        return view('admin.campaigns.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'desktop_banner' => 'nullable|image|max:2048',
            'mobile_banner' => 'nullable|image|max:2048',
            'is_active' => 'boolean',
            'cta_text' => 'nullable|string|max:255',
            'cta_url' => 'nullable|string|max:255',
            'theme_color' => 'nullable|string|max:50',
            'sort_order' => 'integer',
            'body' => 'nullable|string',
        ]);

        $validated['slug'] = Str::slug($validated['title']) . '-' . uniqid();
        $validated['is_active'] = $request->has('is_active');

        if ($request->hasFile('desktop_banner')) {
            $validated['desktop_banner'] = $request->file('desktop_banner')->store('campaigns', 'public');
        }

        if ($request->hasFile('mobile_banner')) {
            $validated['mobile_banner'] = $request->file('mobile_banner')->store('campaigns', 'public');
        }

        Campaign::create($validated);

        return redirect()->route('admin.campaign.index')->with('status', 'Campaign created successfully.');
    }

    public function edit(Campaign $campaign)
    {
        return view('admin.campaigns.edit', compact('campaign'));
    }

    public function update(Request $request, Campaign $campaign)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'desktop_banner' => 'nullable|image|max:2048',
            'mobile_banner' => 'nullable|image|max:2048',
            'is_active' => 'boolean',
            'cta_text' => 'nullable|string|max:255',
            'cta_url' => 'nullable|string|max:255',
            'theme_color' => 'nullable|string|max:50',
            'sort_order' => 'integer',
            'body' => 'nullable|string',
        ]);

        $validated['is_active'] = $request->has('is_active');

        if ($request->hasFile('desktop_banner')) {
            if ($campaign->desktop_banner) {
                Storage::disk('public')->delete($campaign->desktop_banner);
            }
            $validated['desktop_banner'] = $request->file('desktop_banner')->store('campaigns', 'public');
        }

        if ($request->hasFile('mobile_banner')) {
            if ($campaign->mobile_banner) {
                Storage::disk('public')->delete($campaign->mobile_banner);
            }
            $validated['mobile_banner'] = $request->file('mobile_banner')->store('campaigns', 'public');
        }

        $campaign->update($validated);

        return redirect()->route('admin.campaign.index')->with('status', 'Campaign updated successfully.');
    }

    public function destroy(Campaign $campaign)
    {
        if ($campaign->desktop_banner) {
            Storage::disk('public')->delete($campaign->desktop_banner);
        }
        if ($campaign->mobile_banner) {
            Storage::disk('public')->delete($campaign->mobile_banner);
        }
        $campaign->delete();

        return redirect()->route('admin.campaign.index')->with('status', 'Campaign deleted successfully.');
    }
}
