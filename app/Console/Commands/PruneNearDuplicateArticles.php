<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Support\WelcomePageCache;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Turunkan artikel hampir-identik yang terbit beruntun.
 * Google sering menahan indeks saat banyak URL AI bertema sama muncul dalam sehari.
 */
class PruneNearDuplicateArticles extends Command
{
    protected $signature = 'seo:prune-duplicate-articles
                            {--dry-run : Tampilkan yang akan di-unpublish tanpa mengubah DB}';

    protected $description = 'Unpublish artikel hampir-duplikat agar discovery/indexing fokusih';

    /**
     * Slug yang dipertahankan → daftar slug yang dinonaktifkan.
     *
     * @var array<string, list<string>>
     */
    private const CLUSTERS = [
        'menjaga-kesehatan-jamaah-lansia-saat-umroh-tips-muthowif' => [
            'panduan-kesehatan-jamaah-lansia-umroh',
            'peran-muthowif-menjaga-kesehatan-jamaah-lansia-keluarga',
            'strategi-fisik-jamaah-lansia-tawaf-sai-mencegah-dehidrasi',
        ],
        'mengenal-peran-muthowif-pendamping-ibadah-umrah' => [
            'pentingnya-muthowif-terpercaya-ibadah-umroh',
        ],
    ];

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $toUnpublish = [];

        foreach (self::CLUSTERS as $keeperSlug => $dupes) {
            $keeper = Article::query()->where('slug', $keeperSlug)->first();

            if ($keeper === null) {
                $this->warn("Keeper tidak ditemukan, cluster dilewati: {$keeperSlug}");

                continue;
            }

            $this->line("<info>Keeper:</info> {$keeperSlug}");

            foreach ($dupes as $slug) {
                $article = Article::query()->where('slug', $slug)->first();

                if ($article === null) {
                    $this->line("  - {$slug} (tidak ada)");

                    continue;
                }

                if (! $article->is_published) {
                    $this->line("  - {$slug} (sudah draft)");

                    continue;
                }

                $toUnpublish[] = $article;
                $this->line("  - akan unpublish: {$slug}");
            }
        }

        if ($toUnpublish === []) {
            $this->info('Tidak ada artikel yang perlu di-unpublish.');

            return self::SUCCESS;
        }

        if ($dry) {
            $this->comment('dry-run — tidak ada perubahan.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($toUnpublish): void {
            foreach ($toUnpublish as $article) {
                $article->update(['is_published' => false]);
            }

            // Segarkan lastmod keeper supaya sitemap berubah dan Google terdorong recrawl.
            foreach (array_keys(self::CLUSTERS) as $keeperSlug) {
                Article::query()->where('slug', $keeperSlug)->update(['updated_at' => now()]);
            }
        });

        WelcomePageCache::forget();

        $this->info(sprintf('Unpublish %d artikel. Cache homepage dibersihkan.', count($toUnpublish)));

        return self::SUCCESS;
    }
}
