<?php

namespace App\Http\Controllers\Muthowif;

use App\Http\Controllers\Controller;
use App\Models\MuthowifPortfolio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class MuthowifPortfolioController extends Controller
{
    public function index(Request $request): View
    {
        $profile = $request->user()->muthowifProfile;
        $portfolios = $profile->portfolios()
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->paginate(6)
            ->withQueryString();

        return view('muthowif.portfolio.index', compact('portfolios'));
    }

    public function store(Request $request): RedirectResponse
    {
        $profile = $request->user()->muthowifProfile;

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'image' => [
                'required',
                'max:10240',
                function ($attribute, $value, $fail) {
                    if (!$value->isValid()) {
                        return $fail('Berkas foto tidak valid.');
                    }
                    $extension = strtolower($value->getClientOriginalExtension());
                    $allowedExtensions = ['jpeg', 'jpg', 'png', 'webp', 'heic', 'heif'];
                    if (!in_array($extension, $allowedExtensions, true)) {
                        return $fail('Format gambar harus jpeg, jpg, png, webp, heic, atau heif.');
                    }
                    $mime = $value->getMimeType();
                    $allowedMimes = [
                        'image/jpeg', 'image/png', 'image/webp', 'image/heic', 'image/heif',
                        'image/heic-sequence', 'image/heif-sequence', 'application/octet-stream'
                    ];
                    if (!in_array($mime, $allowedMimes, true) && !str_starts_with($mime, 'image/')) {
                        return $fail('Berkas yang diunggah harus berupa gambar.');
                    }
                }
            ],
        ], [
            'title.required' => 'Judul foto wajib diisi.',
            'image.required' => 'Foto wajib diunggah.',
            'image.max' => 'Ukuran gambar maksimal adalah 10 MB.',
        ]);

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $tempPath = $file->getRealPath();
            
            // Check if uploaded file is HEIC/HEIF and converter library is available
            $isHeic = false;
            $hasConverter = class_exists('\Maestroerror\HeicToJpg');

            if ($hasConverter) {
                try {
                    if (\Maestroerror\HeicToJpg::isHeic($tempPath) || in_array(strtolower($file->getClientOriginalExtension()), ['heic', 'heif'], true)) {
                        $isHeic = true;
                    }
                } catch (\Throwable $e) {
                    Log::warning('Error checking if file is HEIC: ' . $e->getMessage());
                }
            } else {
                Log::warning('HEIC conversion library (Maestroerror\HeicToJpg) is not loaded or missing. Skipping HEIC conversion.');
            }

            if ($isHeic && $hasConverter) {
                // Generate a temp path for the converted JPEG
                $tempJpg = tempnam(sys_get_temp_dir(), 'heic_') . '.jpg';
                try {
                    // Convert HEIC to JPG
                    \Maestroerror\HeicToJpg::convert($tempPath)->saveAs($tempJpg);
                    
                    // Store the converted JPG
                    $path = Storage::disk('local')->putFile('portfolio/' . $profile->id, new \Illuminate\Http\File($tempJpg));
                    
                    // Clean up temp file
                    if (file_exists($tempJpg)) {
                        unlink($tempJpg);
                    }
                } catch (\Throwable $e) {
                    Log::error('HEIC conversion to JPEG failed: ' . $e->getMessage());
                    // Fallback to storing original file
                    $path = $file->store('portfolio/' . $profile->id, 'local');
                }
            } else {
                // Standard image file or missing converter library
                $path = $file->store('portfolio/' . $profile->id, 'local');
            }

            $profile->portfolios()->create([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'image_path' => $path,
                'sort_order' => $profile->portfolios()->count() + 1,
            ]);
        }

        return redirect()
            ->route('muthowif.portfolio.index')
            ->with('status', 'Foto portofolio berhasil ditambahkan!');
    }

    public function destroy(Request $request, MuthowifPortfolio $portfolio): RedirectResponse
    {
        $profile = $request->user()->muthowifProfile;

        // Ensure owner
        if ($portfolio->muthowif_profile_id !== $profile->id) {
            abort(403);
        }

        // Delete file from disk
        if (Storage::disk('local')->exists($portfolio->image_path)) {
            Storage::disk('local')->delete($portfolio->image_path);
        }

        $portfolio->delete();

        return redirect()
            ->route('muthowif.portfolio.index')
            ->with('status', 'Foto portofolio berhasil dihapus!');
    }
}
