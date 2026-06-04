<?php

namespace App\Http\Controllers\Muthowif;

use App\Http\Controllers\Controller;
use App\Models\MuthowifPortfolio;
use App\Models\MuthowifPortfolioImage;
use App\Services\UploadedImageOptimizer;
use Illuminate\Http\File;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Support\StoredImageResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Maestroerror\HeicToJpg;
use Symfony\Component\HttpFoundation\Response;

class MuthowifPortfolioController extends Controller
{
    public function index(Request $request): View
    {
        $profile = $request->user()->muthowifProfile;
        $portfolios = $profile->portfolios()
            ->with('images')
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->paginate(6)
            ->withQueryString();

        return view('muthowif.portfolio.index', compact('portfolios'));
    }

    public function store(Request $request): RedirectResponse
    {
        $profile = $request->user()->muthowifProfile;

        Log::info('--- MuthowifPortfolio Store Request Started ---', [
            'muthowif_id' => $profile->id ?? null,
            'title' => $request->input('title'),
            'has_file_key' => $request->hasFile('image'),
            'files_array' => array_keys($request->allFiles()),
            'server_post_limit' => ini_get('post_max_size'),
            'server_upload_limit' => ini_get('upload_max_filesize'),
        ]);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'images' => ['required', 'array', 'min:1', 'max:20'],
            'images.*' => $this->imageValidationRules(required: true),
        ], [
            'title.required' => 'Judul foto wajib diisi.',
            'images.required' => 'Minimal unggah satu foto.',
            'images.min' => 'Minimal unggah satu foto.',
            'images.max' => 'Maksimal 20 foto per judul.',
            'images.*.max' => 'Ukuran gambar maksimal adalah 10 MB.',
        ]);

        Log::info('Validation: Request passed validation.');

        $portfolio = $profile->portfolios()->create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'image_path' => '',
            'sort_order' => $profile->portfolios()->count() + 1,
        ]);

        $files = $request->file('images', []);
        if (! is_array($files)) {
            $files = array_filter([$files]);
        }

        $coverPath = null;
        foreach ($files as $index => $file) {
            if (! $file || ! $file->isValid()) {
                continue;
            }

            $path = $this->storePortfolioImage($file, $profile->id);
            $portfolio->images()->create([
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'sort_order' => $index,
            ]);

            $coverPath ??= $path;
        }

        if (is_string($coverPath)) {
            $portfolio->image_path = $coverPath;
            $portfolio->save();
        }

        Log::info('--- MuthowifPortfolio Store Request Completed Successfully ---');

        return redirect()
            ->route('muthowif.portfolio.index')
            ->with('status', 'Foto portofolio berhasil ditambahkan!');
    }

    public function update(Request $request, MuthowifPortfolio $portfolio): RedirectResponse
    {
        $profile = $request->user()->muthowifProfile;

        if ($portfolio->muthowif_profile_id !== $profile->id) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'images' => ['nullable', 'array', 'max:20'],
            'images.*' => $this->imageValidationRules(required: false),
            'delete_image_ids' => ['nullable', 'array', 'max:20'],
            'delete_image_ids.*' => ['string'],
        ], [
            'title.required' => 'Judul foto wajib diisi.',
            'images.*.max' => 'Ukuran gambar maksimal adalah 10 MB.',
        ]);

        $portfolio->title = $validated['title'];
        $portfolio->description = $validated['description'] ?? null;

        $deleteImageIds = array_values(array_filter(
            $validated['delete_image_ids'] ?? [],
            static fn (mixed $id): bool => is_string($id) && $id !== ''
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

        $newFiles = $request->file('images', []);
        if (! is_array($newFiles)) {
            $newFiles = array_filter([$newFiles]);
        }
        $nextSortOrder = (int) $portfolio->images()->max('sort_order') + 1;
        foreach ($newFiles as $file) {
            if (! $file || ! $file->isValid()) {
                continue;
            }
            $path = $this->storePortfolioImage($file, $profile->id);
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

        return redirect()
            ->route('muthowif.portfolio.index')
            ->with('status', 'Portofolio berhasil diperbarui!');
    }

    public function destroy(Request $request, MuthowifPortfolio $portfolio): RedirectResponse
    {
        $profile = $request->user()->muthowifProfile;

        // Ensure owner
        if ($portfolio->muthowif_profile_id !== $profile->id) {
            abort(403);
        }

        $portfolio->loadMissing('images');

        foreach ($portfolio->images as $image) {
            if (Storage::disk('local')->exists($image->path)) {
                Storage::disk('local')->delete($image->path);
            }
        }

        if ($portfolio->image_path !== '' && Storage::disk('local')->exists($portfolio->image_path)) {
            Storage::disk('local')->delete($portfolio->image_path);
        }

        $portfolio->delete();

        return redirect()
            ->route('muthowif.portfolio.index')
            ->with('status', 'Foto portofolio berhasil dihapus!');
    }

    public function image(Request $request, MuthowifPortfolioImage $image): Response
    {
        $profile = $request->user()->muthowifProfile;
        $image->loadMissing('portfolio');

        if (! $image->portfolio || $image->portfolio->muthowif_profile_id !== $profile->id) {
            abort(403);
        }

        return StoredImageResponse::fromDisk('local', $image->path, visibility: 'private');
    }

    /**
     * @return array<int, mixed>
     */
    private function imageValidationRules(bool $required): array
    {
        return [
            $required ? 'required' : 'nullable',
            'max:10240',
            function ($attribute, $value, $fail) {
                if (! $value) {
                    return;
                }

                if (! $value->isValid()) {
                    return $fail('Berkas foto tidak valid: '.$value->getErrorMessage());
                }

                $extension = strtolower($value->getClientOriginalExtension());
                $allowedExtensions = ['jpeg', 'jpg', 'png', 'webp', 'heic', 'heif'];
                if (! in_array($extension, $allowedExtensions, true)) {
                    return $fail('Format gambar harus jpeg, jpg, png, webp, heic, atau heif.');
                }

                $mime = $value->getMimeType();
                $allowedMimes = [
                    'image/jpeg', 'image/png', 'image/webp', 'image/heic', 'image/heif',
                    'image/heic-sequence', 'image/heif-sequence', 'application/octet-stream',
                ];
                if (! in_array($mime, $allowedMimes, true) && ! str_starts_with($mime, 'image/')) {
                    return $fail('Berkas yang diunggah harus berupa gambar.');
                }
            },
        ];
    }

    private function storePortfolioImage($file, string $profileId): string
    {
        $tempPath = $file->getRealPath();
        $hasConverter = class_exists('\Maestroerror\HeicToJpg');
        $isHeicOrHeif = false;

        if ($hasConverter) {
            try {
                $isHeicOrHeif = HeicToJpg::isHeic($tempPath)
                    || in_array(strtolower($file->getClientOriginalExtension()), ['heic', 'heif'], true);
            } catch (\Throwable $e) {
                Log::warning('Error checking if portfolio file is HEIC/HEIF: '.$e->getMessage());
            }
        }

        $optimizer = app(UploadedImageOptimizer::class);

        if (! $isHeicOrHeif || ! $hasConverter) {
            return $optimizer->store($file, 'portfolio/'.$profileId, 'local', 'portfolio');
        }

        $tempJpg = tempnam(sys_get_temp_dir(), 'heic_heif_').'.jpg';

        try {
            HeicToJpg::convert($tempPath)->saveAs($tempJpg);

            $path = Storage::disk('local')->putFile('portfolio/'.$profileId, new File($tempJpg));

            return is_string($path)
                ? $optimizer->optimizeStoredPath($path, 'local', 'portfolio')
                : $optimizer->store($file, 'portfolio/'.$profileId, 'local', 'portfolio');
        } catch (\Throwable $e) {
            Log::error('HEIC/HEIF portfolio conversion to JPEG failed: '.$e->getMessage());

            return $optimizer->store($file, 'portfolio/'.$profileId, 'local', 'portfolio');
        } finally {
            if (file_exists($tempJpg)) {
                unlink($tempJpg);
            }
        }
    }
}
