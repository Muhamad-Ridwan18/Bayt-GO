<?php

namespace Tests\Unit\Support;

use App\Support\PrivateDocumentStorage;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PrivateDocumentStorageTest extends TestCase
{
    public function test_exists_and_delete_prefer_local_then_public(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $path = 'muthowif_documents/u1/ktp.jpg';
        Storage::disk('public')->put($path, 'legacy');

        $this->assertTrue(PrivateDocumentStorage::exists($path));

        PrivateDocumentStorage::delete($path);

        $this->assertFalse(Storage::disk('public')->exists($path));
        $this->assertFalse(PrivateDocumentStorage::exists($path));
    }

    public function test_exists_on_local_disk(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $path = 'muthowif_documents/u1/doc.pdf';
        Storage::disk('local')->put($path, 'private');

        $this->assertTrue(PrivateDocumentStorage::exists($path));
    }
}
