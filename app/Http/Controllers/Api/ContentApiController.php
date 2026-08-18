<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Campaign;
use App\Support\ApiContentPayload;
use Illuminate\Http\JsonResponse;

class ContentApiController extends Controller
{
    public function articles(): JsonResponse
    {
        $articles = Article::query()->published()->ordered()->get();

        return response()->json([
            'data' => $articles->map(fn (Article $article) => ApiContentPayload::article($article))->values(),
        ]);
    }

    public function showArticle(string $slug): JsonResponse
    {
        $article = Article::query()->published()->where('slug', $slug)->first();
        if ($article === null) {
            return response()->json(['message' => 'Artikel tidak ditemukan'], 404);
        }

        return response()->json(ApiContentPayload::article($article, true));
    }

    public function showCampaign(string $slug): JsonResponse
    {
        $campaign = Campaign::query()->active()->where('slug', $slug)->first();
        if ($campaign === null) {
            return response()->json(['message' => 'Kampanye tidak ditemukan'], 404);
        }

        return response()->json(ApiContentPayload::campaign($campaign, true));
    }
}
