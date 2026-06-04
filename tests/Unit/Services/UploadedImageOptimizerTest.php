<?php

namespace Tests\Unit\Services;

use App\Services\UploadedImageOptimizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UploadedImageOptimizerTest extends TestCase
{
    public function test_pdf_upload_is_stored_without_reencoding(): void
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->create('scan.pdf', 120, 'application/pdf');
        $path = app(UploadedImageOptimizer::class)->store($file, 'docs', 'local', 'document');

        Storage::disk('local')->assertExists($path);
        $this->assertStringEndsWith('.pdf', $path);
    }

    public function test_image_upload_is_stored_as_jpeg_when_optimization_enabled(): void
    {
        if (! extension_loaded('gd') && ! extension_loaded('imagick')) {
            $this->markTestSkipped('GD or Imagick required for image optimization.');
        }

        Storage::fake('local');
        config(['image-upload.enabled' => true]);

        $file = UploadedFile::fake()->image('avatar.png', 1600, 1200);
        $path = app(UploadedImageOptimizer::class)->store($file, 'avatars', 'local', 'profile');

        Storage::disk('local')->assertExists($path);
        $this->assertStringEndsWith('.jpg', $path);

        $storedMime = Storage::disk('local')->mimeType($path);
        $this->assertSame('image/jpeg', $storedMime);
    }

    public function test_gif_is_not_treated_as_optimizable(): void
    {
        $file = UploadedFile::fake()->create('anim.gif', 50, 'image/gif');
        $optimizer = app(UploadedImageOptimizer::class);

        $this->assertFalse($optimizer->isOptimizableImage($file));
    }
}
