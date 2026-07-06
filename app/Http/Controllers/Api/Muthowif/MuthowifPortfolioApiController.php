<?php

namespace App\Http\Controllers\Api\Muthowif;

use App\Http\Controllers\Controller;
use App\Models\MuthowifPortfolio;
use App\Models\MuthowifPortfolioImage;
use App\Services\UploadedImageOptimizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class MuthowifPortfolioApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $profile = $request->user()->muthowifProfile;
        abort_unless($profile, 403);

        $portfolios = $profile->portfolios()
            ->with('images')
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (MuthowifPortfolio $p) => [
                'id' => (string) $p->getKey(),
                'title' => $p->title,
                'description' => $p->description,
                'cover_url' => $p->image_path ? url('/api/muthowif/portfolio/cover/'.$p->getKey()) : null,
                'images_count' => $p->images->count(),
            ]);

        return response()->json(['portfolios' => $portfolios]);
    }

    public function store(Request $request): JsonResponse
    {
        $profile = $request->user()->muthowifProfile;
        abort_unless($profile, 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'images' => ['required', 'array', 'min:1', 'max:10'],
            'images.*' => ['image', 'max:10240'],
        ]);

        $portfolio = $profile->portfolios()->create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'image_path' => '',
            'sort_order' => $profile->portfolios()->count() + 1,
        ]);

        $optimizer = app(UploadedImageOptimizer::class);
        $coverPath = null;
        foreach ($request->file('images', []) as $index => $file) {
            if (! $file?->isValid()) {
                continue;
            }
            $path = $optimizer->store($file, 'portfolio/'.$profile->id, 'local', 'portfolio');
            $portfolio->images()->create([
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'sort_order' => $index,
            ]);
            $coverPath ??= $path;
        }

        if ($coverPath) {
            $portfolio->update(['image_path' => $coverPath]);
        }

        return response()->json(['message' => 'Portofolio berhasil ditambahkan', 'id' => (string) $portfolio->getKey()], 201);
    }

    public function show(Request $request, MuthowifPortfolio $portfolio): JsonResponse
    {
        $profile = $request->user()->muthowifProfile;
        abort_unless($profile && (string) $portfolio->muthowif_profile_id === (string) $profile->getKey(), 403);

        $portfolio->load('images');

        return response()->json([
            'portfolio' => [
                'id' => (string) $portfolio->getKey(),
                'title' => $portfolio->title,
                'description' => $portfolio->description,
                'images' => $portfolio->images->map(fn (MuthowifPortfolioImage $img) => [
                    'id' => (string) $img->getKey(),
                    'original_name' => $img->original_name,
                    'url' => url('/api/muthowif/portfolio/images/'.$img->getKey()),
                ])->values(),
            ],
        ]);
    }

    public function update(Request $request, MuthowifPortfolio $portfolio): JsonResponse
    {
        $profile = $request->user()->muthowifProfile;
        abort_unless($profile && (string) $portfolio->muthowif_profile_id === (string) $profile->getKey(), 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'images' => ['nullable', 'array', 'max:20'],
            'images.*' => ['image', 'max:10240'],
            'delete_image_ids' => ['nullable', 'array', 'max:20'],
            'delete_image_ids.*' => ['string'],
        ]);

        $portfolio->title = $validated['title'];
        $portfolio->description = $validated['description'] ?? null;

        $deleteImageIds = array_values(array_filter(
            $validated['delete_image_ids'] ?? [],
            static fn (mixed $id): bool => is_string($id) && $id !== '',
        ));

        if ($deleteImageIds !== []) {
            $imagesToDelete = $portfolio->images()->whereKey($deleteImageIds)->get();
            foreach ($imagesToDelete as $image) {
                if (Storage::disk('local')->exists($image->path)) {
                    Storage::disk('local')->delete($image->path);
                }
                $image->delete();
            }
        }

        $optimizer = app(UploadedImageOptimizer::class);
        $nextSortOrder = (int) $portfolio->images()->max('sort_order') + 1;
        foreach ($request->file('images', []) as $file) {
            if (! $file?->isValid()) {
                continue;
            }
            $path = $optimizer->store($file, 'portfolio/'.$profile->id, 'local', 'portfolio');
            $portfolio->images()->create([
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'sort_order' => $nextSortOrder++,
            ]);
        }

        $coverPath = $portfolio->images()->orderBy('sort_order')->value('path');
        if (is_string($coverPath) && $coverPath !== '') {
            $portfolio->image_path = $coverPath;
        }

        $portfolio->save();

        return response()->json(['message' => 'Portofolio berhasil diperbarui']);
    }

    public function image(Request $request, MuthowifPortfolioImage $image): Response
    {
        $profile = $request->user()->muthowifProfile;
        $image->loadMissing('portfolio');
        abort_unless(
            $profile
            && $image->portfolio
            && (string) $image->portfolio->muthowif_profile_id === (string) $profile->getKey(),
            403,
        );

        if (! Storage::disk('local')->exists($image->path)) {
            abort(404);
        }

        return Storage::disk('local')->response($image->path, basename($image->path), [], 'inline');
    }

    public function cover(Request $request, MuthowifPortfolio $portfolio): Response
    {
        $profile = $request->user()->muthowifProfile;
        abort_unless($profile && (string) $portfolio->muthowif_profile_id === (string) $profile->getKey(), 403);

        $path = $portfolio->image_path;
        if ($path === null || $path === '' || ! Storage::disk('local')->exists($path)) {
            abort(404);
        }

        return Storage::disk('local')->response($path, basename($path), [], 'inline');
    }

    public function destroy(Request $request, MuthowifPortfolio $portfolio): JsonResponse
    {
        $profile = $request->user()->muthowifProfile;
        abort_unless($profile && (string) $portfolio->muthowif_profile_id === (string) $profile->getKey(), 403);

        $portfolio->loadMissing('images');
        foreach ($portfolio->images as $image) {
            if (Storage::disk('local')->exists($image->path)) {
                Storage::disk('local')->delete($image->path);
            }
        }
        if ($portfolio->image_path && Storage::disk('local')->exists($portfolio->image_path)) {
            Storage::disk('local')->delete($portfolio->image_path);
        }
        $portfolio->delete();

        return response()->json(['message' => 'Portofolio dihapus']);
    }
}
