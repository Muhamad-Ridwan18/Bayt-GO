<?php

namespace App\Console\Commands;

use App\Support\PublicMarketplaceMedia;
use Illuminate\Console\Command;

class PublishMarketplaceMediaCommand extends Command
{
    protected $signature = 'marketplace:publish-media';

    protected $description = 'Salin foto profil & portofolio ke disk publik agar web server bisa menyajikan tanpa PHP';

    public function handle(): int
    {
        if (! PublicMarketplaceMedia::enabled()) {
            $this->warn('marketplace.public_media_enabled=false — lewati publish.');

            return self::SUCCESS;
        }

        $stats = PublicMarketplaceMedia::publishAll();

        $this->info(sprintf(
            'Selesai: %d foto profil, %d foto portofolio (%d dilewati).',
            $stats['profiles'],
            $stats['portfolio_images'],
            $stats['skipped'],
        ));

        return self::SUCCESS;
    }
}
