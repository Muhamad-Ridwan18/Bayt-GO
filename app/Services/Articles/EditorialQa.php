<?php

namespace App\Services\Articles;

use App\Models\Article;

/**
 * Menilai kelayakan terbit artikel hasil automation.
 *
 * Skor 0-100 menilai mutu artikel bahasa Indonesia saja. Terjemahan Inggris
 * dilaporkan sebagai fakta (`english_ready`: punya URL /en/ atau tidak) dan
 * tidak ikut dinilai, karena artikel berbahasa Indonesia saja tetap sah dan
 * bernilai penuh untuk pasar utama.
 */
final class EditorialQa
{
    public const DECISION_PUBLISH = 'publish';

    public const DECISION_DRAFT = 'draft';

    public const DECISION_BLOCKED = 'blocked';

    /** Klaim yang tidak boleh terbit berapa pun skornya. */
    private const FORBIDDEN_CLAIMS = [
        'jamin 100%' => '/di?jamin\s*100\s*%/i',
        'pasti berangkat' => '/pasti\s+berangkat/i',
        'termurah se-Indonesia' => '/termurah\s+se-?\s?indonesia/i',
        'tanpa syarat' => '/tanpa\s+syarat/i',
        'dijamin lolos' => '/di?jamin\s+(lolos|diterima)/i',
    ];

    private const GENERIC_HEADINGS = 'pendahuluan|introduction|kesimpulan|conclusion|penutup|ringkasan|overview';

    private const CLICHE_OPENINGS = 'dalam artikel ini|artikel ini akan membahas|pada kesempatan kali ini|berikut ini adalah pembahasan|di era digital seperti sekarang|seperti yang kita ketahui';

    /** Artikel di bawah panjang ini bukan artikel. */
    private const MINIMUM_BODY_LENGTH = 300;

    /**
     * @param  array<string, mixed>  $translations
     * @return array<string, mixed>
     */
    public function evaluate(array $translations, string $keywords = ''): array
    {
        $id = $this->localeBlock($translations, 'id');
        $raw = $this->rawBody($id);
        $plain = $this->plainText($raw);
        $title = trim((string) ($id['title'] ?? ''));
        $excerpt = trim($this->plainText((string) ($id['excerpt'] ?? '')));

        $notes = [];
        $score = 0;

        $score += $this->scoreStructure($raw, $notes);
        $score += $this->scoreDepth($plain, $notes);
        $score += $this->scoreOpening($raw, $notes);
        $score += $this->scoreExcerpt($excerpt, $notes);
        $score += $this->scoreSources($raw, $notes);

        $primaryKeyword = $this->primaryKeyword($keywords);
        $keywordCount = $this->countKeyword($plain, $primaryKeyword);
        $score += $this->scoreKeyword($primaryKeyword, $keywordCount, $notes);

        $links = $this->analyseInternalLinks($raw);
        $score += $this->scoreInternalLinks($links, $notes);

        $score = max(0, min(100, $score));

        $hardFailReasons = $this->hardFailReasons($title, $plain, $raw);
        $minScore = (int) config('articles.qa.min_score', 75);
        $autoPublishScore = (int) config('articles.qa.autopublish_min_score', 85);

        return [
            'score' => $score,
            'min_score' => $minScore,
            'autopublish_min_score' => $autoPublishScore,
            'decision' => $this->decide($score, $hardFailReasons !== [], $minScore, $autoPublishScore),
            'hard_fail' => $hardFailReasons !== [],
            'hard_fail_reasons' => $hardFailReasons,
            'notes' => $notes,
            'metrics' => [
                'body_length' => mb_strlen($plain),
                'excerpt_length' => mb_strlen($excerpt),
                'heading_count' => $this->countHeadings($raw),
                'internal_links' => $links['total'],
                'internal_article_links' => $links['articles'],
                'internal_commercial_links' => $links['commercial'],
                'broken_internal_links' => $links['broken'],
                'primary_keyword' => $primaryKeyword,
                'primary_keyword_count' => $keywordCount,
                'english_ready' => $this->localeIsRoutable($translations, 'en'),
            ],
        ];
    }

    private function decide(int $score, bool $hardFail, int $minScore, int $autoPublishScore): string
    {
        if ($hardFail || $score < $minScore) {
            return self::DECISION_BLOCKED;
        }

        return $score >= $autoPublishScore ? self::DECISION_PUBLISH : self::DECISION_DRAFT;
    }

    /**
     * @param  list<string>  $notes
     */
    private function scoreStructure(string $raw, array &$notes): int
    {
        $headings = $this->countHeadings($raw);

        if ($headings === 0) {
            $notes[] = 'Tidak ada subjudul (+0 dari 20)';

            return 0;
        }

        if ($this->hasGenericHeading($raw)) {
            $notes[] = 'Subjudul masih generik seperti "Pendahuluan"/"Kesimpulan" (+6 dari 20)';

            return 6;
        }

        $notes[] = 'Subjudul spesifik dan deskriptif (+20)';

        return 20;
    }

    /**
     * @param  list<string>  $notes
     */
    private function scoreDepth(string $plain, array &$notes): int
    {
        $length = mb_strlen($plain);

        if ($length >= 3500) {
            $notes[] = 'Kedalaman pembahasan kuat (+20)';

            return 20;
        }

        if ($length >= 2000) {
            $notes[] = 'Kedalaman pembahasan cukup (+13 dari 20)';

            return 13;
        }

        if ($length >= 1200) {
            $notes[] = 'Artikel tipis untuk bersaing di pencarian (+6 dari 20)';

            return 6;
        }

        $notes[] = 'Artikel terlalu pendek (+0 dari 20)';

        return 0;
    }

    /**
     * @param  list<string>  $notes
     */
    private function scoreOpening(string $raw, array &$notes): int
    {
        $opening = $this->plainText($this->stripLeadingHeading($raw));

        if (preg_match('/^\s*(?:'.self::CLICHE_OPENINGS.')/i', $opening) === 1) {
            $notes[] = 'Pembuka klise (+0 dari 10)';

            return 0;
        }

        if (preg_match('/^(?:#{1,3}\s*|<h[123][^>]*>\s*)(?:'.self::GENERIC_HEADINGS.')\b/i', trim($raw)) === 1) {
            $notes[] = 'Artikel dibuka dengan subjudul generik (+0 dari 10)';

            return 0;
        }

        $notes[] = 'Pembuka langsung ke inti (+10)';

        return 10;
    }

    /**
     * @param  list<string>  $notes
     */
    private function scoreExcerpt(string $excerpt, array &$notes): int
    {
        $length = mb_strlen($excerpt);

        // 120-155 karakter adalah rentang yang tampil utuh di hasil pencarian.
        if ($length >= 120 && $length <= 300) {
            $notes[] = 'Panjang excerpt ideal untuk meta description (+15)';

            return 15;
        }

        if ($length >= 80 && $length <= 400) {
            $notes[] = 'Excerpt di luar rentang ideal (+8 dari 15)';

            return 8;
        }

        $notes[] = $length === 0
            ? 'Excerpt kosong, meta description akan diambil dari isi (+0 dari 15)'
            : 'Panjang excerpt tidak optimal (+0 dari 15)';

        return 0;
    }

    /**
     * @param  list<string>  $notes
     */
    private function scoreSources(string $raw, array &$notes): int
    {
        $hasSection = preg_match('/\b(sumber|referensi|daftar pustaka)\b/i', $raw) === 1;
        $externalLinks = $this->countExternalLinks($raw);

        if ($hasSection || $externalLinks >= 2) {
            $notes[] = 'Menyertakan sumber atau rujukan luar (+10)';

            return 10;
        }

        $notes[] = 'Belum ada sumber atau rujukan (+0 dari 10)';

        return 0;
    }

    /**
     * @param  list<string>  $notes
     */
    private function scoreKeyword(string $primaryKeyword, int $count, array &$notes): int
    {
        if ($primaryKeyword === '') {
            // Tanpa keyword acuan kita tidak bisa menilai; beri nilai netral.
            $notes[] = 'Keyword utama tidak dikirim, penilaian netral (+9 dari 15)';

            return 9;
        }

        if ($count >= 2 && $count <= 12) {
            $notes[] = 'Keyword utama dipakai wajar (+15)';

            return 15;
        }

        if ($count === 1) {
            $notes[] = 'Keyword utama hanya muncul sekali (+5 dari 15)';

            return 5;
        }

        if ($count > 12) {
            $notes[] = 'Keyword utama terlalu sering, berisiko dianggap spam (+3 dari 15)';

            return 3;
        }

        $notes[] = 'Keyword utama tidak muncul di isi artikel (+0 dari 15)';

        return 0;
    }

    /**
     * Tautan internal mengikat artikel ke seluruh situs dan mengalirkan otoritas
     * ke halaman komersial. Tautan ke artikel yang tidak ada justru merugikan,
     * jadi itu membatalkan seluruh nilai kriteria ini.
     *
     * @param  array{total: int, articles: int, commercial: int, broken: list<string>}  $links
     * @param  list<string>  $notes
     */
    private function scoreInternalLinks(array $links, array &$notes): int
    {
        if ($links['broken'] !== []) {
            $notes[] = 'Tautan internal menunjuk artikel yang tidak ada: '
                .implode(', ', $links['broken']).' (+0 dari 10)';

            return 0;
        }

        if ($links['total'] >= 2 && $links['commercial'] >= 1) {
            $notes[] = 'Tautan internal lengkap, termasuk ke halaman layanan (+10)';

            return 10;
        }

        if ($links['total'] >= 2) {
            $notes[] = 'Ada tautan antar artikel, belum menautkan halaman layanan (+7 dari 10)';

            return 7;
        }

        if ($links['total'] === 1) {
            $notes[] = 'Baru satu tautan internal (+4 dari 10)';

            return 4;
        }

        $notes[] = 'Tidak ada tautan internal (+0 dari 10)';

        return 0;
    }

    /**
     * @return list<string>
     */
    private function hardFailReasons(string $title, string $plain, string $raw): array
    {
        $reasons = [];

        if ($title === '') {
            $reasons[] = 'Judul bahasa Indonesia kosong.';
        }

        if (mb_strlen($plain) < self::MINIMUM_BODY_LENGTH) {
            $reasons[] = 'Isi artikel kurang dari '.self::MINIMUM_BODY_LENGTH.' karakter.';
        }

        foreach (self::FORBIDDEN_CLAIMS as $label => $pattern) {
            if (preg_match($pattern, $raw) === 1) {
                $reasons[] = 'Klaim berisiko terdeteksi: "'.$label.'".';
            }
        }

        return $reasons;
    }

    /**
     * @param  array<string, mixed>  $translations
     * @return array<string, mixed>
     */
    private function localeBlock(array $translations, string $locale): array
    {
        $block = $translations[$locale] ?? [];

        return is_array($block) ? $block : [];
    }

    /**
     * Sejalan dengan Article::hasTranslation(): sebuah bahasa hanya punya URL
     * sendiri kalau judul dan isinya benar-benar terisi.
     *
     * @param  array<string, mixed>  $translations
     */
    private function localeIsRoutable(array $translations, string $locale): bool
    {
        $block = $this->localeBlock($translations, $locale);

        return trim((string) ($block['title'] ?? '')) !== ''
            && $this->plainText($this->rawBody($block)) !== '';
    }

    /**
     * @param  array<string, mixed>  $block
     */
    private function rawBody(array $block): string
    {
        $markdown = trim((string) ($block['body_md'] ?? ''));

        return $markdown !== '' ? $markdown : (string) ($block['body'] ?? '');
    }

    private function plainText(string $value): string
    {
        return trim((string) preg_replace('/\s+/', ' ', strip_tags($value)));
    }

    private function stripLeadingHeading(string $raw): string
    {
        return (string) preg_replace('/^\s*(?:#{1,6}[^\n]*\n+|<h[1-6][^>]*>.*?<\/h[1-6]>\s*)/is', '', $raw);
    }

    private function countHeadings(string $raw): int
    {
        return (int) preg_match_all('/^#{2,3}\s+\S/m', $raw)
            + (int) preg_match_all('/<h[23][\s>]/i', $raw);
    }

    private function hasGenericHeading(string $raw): bool
    {
        $pattern = '/(?:^#{2,3}\s*|<h[23][^>]*>\s*)(?:'.self::GENERIC_HEADINGS.')\b/im';

        return preg_match($pattern, $raw) === 1;
    }

    /**
     * @return list<string>
     */
    private function linkTargets(string $raw): array
    {
        preg_match_all('/(?:\]\(\s*|href\s*=\s*["\'])([^)"\'\s]+)/i', $raw, $matches);

        return array_values(array_filter(
            array_map('trim', $matches[1] ?? []),
            fn (string $url) => $url !== '' && ! str_starts_with($url, '#'),
        ));
    }

    /**
     * @return array{total: int, articles: int, commercial: int, broken: list<string>}
     */
    private function analyseInternalLinks(string $raw): array
    {
        $slugs = [];
        $commercial = 0;

        foreach ($this->internalLinkPaths($raw) as $path) {
            $slug = $this->articleSlugFromPath($path);
            $slug === null ? $commercial++ : $slugs[] = $slug;
        }

        return [
            'total' => count($slugs) + $commercial,
            'articles' => count($slugs),
            'commercial' => $commercial,
            'broken' => $this->brokenArticleSlugs($slugs),
        ];
    }

    /**
     * @return list<string>
     */
    private function internalLinkPaths(string $raw): array
    {
        $host = (string) (parse_url((string) config('app.url'), PHP_URL_HOST) ?: '');

        $paths = [];
        foreach ($this->linkTargets($raw) as $url) {
            if (str_starts_with($url, '//')) {
                continue;
            }

            if (str_starts_with($url, '/')) {
                $paths[] = $url;

                continue;
            }

            if ($host !== '' && preg_match('#^https?://#i', $url) === 1 && str_contains($url, $host)) {
                $paths[] = (string) (parse_url($url, PHP_URL_PATH) ?: '/');
            }
        }

        return $paths;
    }

    private function articleSlugFromPath(string $path): ?string
    {
        $path = (string) (parse_url($path, PHP_URL_PATH) ?: $path);

        return preg_match('#^/(?:en/)?artikel/([^/]+)/?$#', $path, $matches) === 1
            ? urldecode($matches[1])
            : null;
    }

    /**
     * @param  list<string>  $slugs
     * @return list<string>
     */
    private function brokenArticleSlugs(array $slugs): array
    {
        $slugs = array_values(array_unique($slugs));
        if ($slugs === []) {
            return [];
        }

        $published = Article::query()
            ->published()
            ->whereIn('slug', $slugs)
            ->pluck('slug')
            ->all();

        return array_values(array_diff($slugs, $published));
    }

    private function countExternalLinks(string $raw): int
    {
        $host = (string) (parse_url((string) config('app.url'), PHP_URL_HOST) ?: '');

        $count = 0;
        foreach ($this->linkTargets($raw) as $url) {
            if (! preg_match('#^https?://#i', $url)) {
                continue;
            }
            if ($host !== '' && str_contains($url, $host)) {
                continue;
            }
            $count++;
        }

        return $count;
    }

    private function primaryKeyword(string $keywords): string
    {
        foreach (explode(',', $keywords) as $keyword) {
            $keyword = trim($keyword);
            if ($keyword !== '') {
                return $keyword;
            }
        }

        return '';
    }

    private function countKeyword(string $plain, string $keyword): int
    {
        if ($keyword === '') {
            return 0;
        }

        return (int) preg_match_all('/'.preg_quote($keyword, '/').'/iu', $plain);
    }
}
