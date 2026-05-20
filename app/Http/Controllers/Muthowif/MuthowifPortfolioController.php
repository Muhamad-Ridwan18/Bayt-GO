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
            'image' => [
                'required',
                'max:10240',
                function ($attribute, $value, $fail) {
                    if (!$value) {
                        Log::warning('Validation: Image file object is null.');
                        return $fail('Berkas foto tidak ditemukan.');
                    }

                    Log::info('Validation: File Object Status Checked', [
                        'isValid' => $value->isValid(),
                        'error_code' => $value->getError(),
                        'error_message' => $value->getErrorMessage(),
                        'client_name' => $value->getClientOriginalName(),
                        'client_extension' => $value->getClientOriginalExtension(),
                        'mime_type' => $value->getMimeType(),
                        'size_bytes' => $value->getSize(),
                    ]);

                    if (!$value->isValid()) {
                        Log::error('Validation Failed: Uploaded file is not valid', [
                            'error_code' => $value->getError(),
                            'error_message' => $value->getErrorMessage()
                        ]);
                        return $fail('Berkas foto tidak valid: ' . $value->getErrorMessage());
                    }

                    $extension = strtolower($value->getClientOriginalExtension());
                    $allowedExtensions = ['jpeg', 'jpg', 'png', 'webp', 'heic', 'heif'];
                    if (!in_array($extension, $allowedExtensions, true)) {
                        Log::warning('Validation Failed: Extension not allowed', ['ext' => $extension]);
                        return $fail('Format gambar harus jpeg, jpg, png, webp, heic, atau heif.');
                    }

                    $mime = $value->getMimeType();
                    $allowedMimes = [
                        'image/jpeg', 'image/png', 'image/webp', 'image/heic', 'image/heif',
                        'image/heic-sequence', 'image/heif-sequence', 'application/octet-stream'
                    ];
                    if (!in_array($mime, $allowedMimes, true) && !str_starts_with($mime, 'image/')) {
                        Log::warning('Validation Failed: Mime-type not allowed', ['mime' => $mime]);
                        return $fail('Berkas yang diunggah harus berupa gambar.');
                    }

                    Log::info('Validation: File validated successfully.');
                }
            ],
        ], [
            'title.required' => 'Judul foto wajib diisi.',
            'image.required' => 'Foto wajib diunggah.',
            'image.max' => 'Ukuran gambar maksimal adalah 10 MB.',
        ]);

        Log::info('Validation: Request passed validation.');

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $tempPath = $file->getRealPath();
            
            Log::info('Storage Process: Handling file upload...', [
                'temp_path' => $tempPath,
                'client_name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
            ]);

            // Check if uploaded file is HEIC or HEIF and converter library is available
            $isHeicOrHeif = false;
            $hasConverter = class_exists('\Maestroerror\HeicToJpg');

            Log::info('Converter Check:', [
                'has_converter_class' => $hasConverter,
                'extension' => $file->getClientOriginalExtension(),
            ]);

            if ($hasConverter) {
                try {
                    // Detect by magic signature OR file extension (.heic or .heif)
                    $magicIsHeic = \Maestroerror\HeicToJpg::isHeic($tempPath);
                    $extensionIsHeic = in_array(strtolower($file->getClientOriginalExtension()), ['heic', 'heif'], true);
                    
                    Log::info('HEIC/HEIF Detection Details:', [
                        'magic_is_heic' => $magicIsHeic,
                        'extension_is_heic_or_heif' => $extensionIsHeic
                    ]);

                    if ($magicIsHeic || $extensionIsHeic) {
                        $isHeicOrHeif = true;
                    }
                } catch (\Throwable $e) {
                    Log::warning('Error checking if file is HEIC/HEIF: ' . $e->getMessage());
                }
            } else {
                Log::warning('HEIC/HEIF conversion library (Maestroerror\HeicToJpg) is not loaded or missing. Skipping conversion.');
            }

            if ($isHeicOrHeif && $hasConverter) {
                // Generate a temp path for the converted JPEG
                $tempJpg = tempnam(sys_get_temp_dir(), 'heic_heif_') . '.jpg';
                Log::info('HEIC/HEIF Conversion Triggered:', [
                    'source_path' => $tempPath,
                    'target_temp_jpg' => $tempJpg
                ]);

                try {
                    // Convert both HEIC and HEIF to standard JPEG
                    \Maestroerror\HeicToJpg::convert($tempPath)->saveAs($tempJpg);
                    
                    Log::info('HEIC/HEIF Conversion Success on Temp Path:', [
                        'exists' => file_exists($tempJpg),
                        'size' => file_exists($tempJpg) ? filesize($tempJpg) : 0,
                    ]);

                    // Store the converted JPG
                    $path = Storage::disk('local')->putFile('portfolio/' . $profile->id, new \Illuminate\Http\File($tempJpg));
                    Log::info('Storage Process: Saved converted file to disk', ['path' => $path]);

                    // Clean up temp file
                    if (file_exists($tempJpg)) {
                        unlink($tempJpg);
                        Log::info('Storage Process: Cleaned up temporary JPEG file.');
                    }
                } catch (\Throwable $e) {
                    Log::error('HEIC/HEIF conversion to JPEG failed: ' . $e->getMessage(), [
                        'trace' => $e->getTraceAsString()
                    ]);
                    // Fallback to storing original file
                    $path = $file->store('portfolio/' . $profile->id, 'local');
                    Log::info('Storage Process: Saved fallback original file to disk', ['path' => $path]);
                }
            } else {
                // Standard image file or missing converter library
                Log::info('Storage Process: Treating as standard image / bypassing converter.');
                $path = $file->store('portfolio/' . $profile->id, 'local');
                Log::info('Storage Process: Saved standard file to disk', ['path' => $path]);
            }

            Log::info('Database Process: Creating MuthowifPortfolio record...', [
                'title' => $validated['title'],
                'image_path' => $path,
            ]);

            $portfolioRecord = $profile->portfolios()->create([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'image_path' => $path,
                'sort_order' => $profile->portfolios()->count() + 1,
            ]);

            Log::info('Database Process: Portfolio record created successfully.', [
                'id' => $portfolioRecord->id
            ]);
        } else {
            Log::warning('Storage Process: No file found in request under key "image".');
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
            'image' => $this->imageValidationRules(required: false),
        ], [
            'title.required' => 'Judul foto wajib diisi.',
            'image.max' => 'Ukuran gambar maksimal adalah 10 MB.',
        ]);

        $portfolio->title = $validated['title'];
        $portfolio->description = $validated['description'] ?? null;

        if ($request->hasFile('image')) {
            $newPath = $this->storePortfolioImage($request->file('image'), $profile->id);

            if (Storage::disk('local')->exists($portfolio->image_path)) {
                Storage::disk('local')->delete($portfolio->image_path);
            }

            $portfolio->image_path = $newPath;
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

        // Delete file from disk
        if (Storage::disk('local')->exists($portfolio->image_path)) {
            Storage::disk('local')->delete($portfolio->image_path);
        }

        $portfolio->delete();

        return redirect()
            ->route('muthowif.portfolio.index')
            ->with('status', 'Foto portofolio berhasil dihapus!');
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
                if (!$value) {
                    return;
                }

                if (!$value->isValid()) {
                    return $fail('Berkas foto tidak valid: ' . $value->getErrorMessage());
                }

                $extension = strtolower($value->getClientOriginalExtension());
                $allowedExtensions = ['jpeg', 'jpg', 'png', 'webp', 'heic', 'heif'];
                if (!in_array($extension, $allowedExtensions, true)) {
                    return $fail('Format gambar harus jpeg, jpg, png, webp, heic, atau heif.');
                }

                $mime = $value->getMimeType();
                $allowedMimes = [
                    'image/jpeg', 'image/png', 'image/webp', 'image/heic', 'image/heif',
                    'image/heic-sequence', 'image/heif-sequence', 'application/octet-stream',
                ];
                if (!in_array($mime, $allowedMimes, true) && !str_starts_with($mime, 'image/')) {
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
                $isHeicOrHeif = \Maestroerror\HeicToJpg::isHeic($tempPath)
                    || in_array(strtolower($file->getClientOriginalExtension()), ['heic', 'heif'], true);
            } catch (\Throwable $e) {
                Log::warning('Error checking if portfolio file is HEIC/HEIF: ' . $e->getMessage());
            }
        }

        if (!$isHeicOrHeif || !$hasConverter) {
            return $file->store('portfolio/' . $profileId, 'local');
        }

        $tempJpg = tempnam(sys_get_temp_dir(), 'heic_heif_') . '.jpg';

        try {
            \Maestroerror\HeicToJpg::convert($tempPath)->saveAs($tempJpg);

            return Storage::disk('local')->putFile('portfolio/' . $profileId, new \Illuminate\Http\File($tempJpg));
        } catch (\Throwable $e) {
            Log::error('HEIC/HEIF portfolio conversion to JPEG failed: ' . $e->getMessage());

            return $file->store('portfolio/' . $profileId, 'local');
        } finally {
            if (file_exists($tempJpg)) {
                unlink($tempJpg);
            }
        }
    }
}
