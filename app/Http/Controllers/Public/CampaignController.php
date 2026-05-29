<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Campaign;

class CampaignController extends Controller
{
    public function show(string $slug)
    {
        $campaign = Campaign::active()->where('slug', $slug)->firstOrFail();
        
        return view('campaigns.show', compact('campaign'));
    }
}
