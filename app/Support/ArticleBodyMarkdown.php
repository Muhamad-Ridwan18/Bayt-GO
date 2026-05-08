<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Str;
use League\CommonMark\Extension\Attributes\AttributesExtension;
use League\CommonMark\Extension\DescriptionList\DescriptionListExtension;
use League\CommonMark\Extension\ExternalLink\ExternalLinkExtension;
use League\CommonMark\Extension\ExternalLink\ExternalLinkProcessor;
use League\CommonMark\Extension\Footnote\FootnoteExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkProcessor;
use League\CommonMark\Extension\Highlight\HighlightExtension;
use League\CommonMark\Extension\SmartPunct\SmartPunctExtension;
use League\CommonMark\Extension\TableOfContents\TableOfContentsBuilder;
use League\CommonMark\Extension\TableOfContents\TableOfContentsExtension;

/**
 * Markdown → HTML untuk isi artikel: **GitHub Flavored Markdown** (tabel, task list, coret, autolink)
 * ditambah ekstensi CommonMark resmi: footnote, sorotan ==teks==, smart typography, daftar definisi,
 * atribut `{#id .class}`, Daftar Isi otomatis, ID judul untuk anchor, dan tautan luar aman (noopener).
 */
final class ArticleBodyMarkdown
{
    public static function toHtml(string $markdown): string
    {
        if (trim($markdown) === '') {
            return '';
        }

        $host = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost';

        $config = [
            'allow_unsafe_links' => false,
            'table_of_contents' => [
                'html_class' => 'article-toc',
                'position' => TableOfContentsBuilder::POSITION_TOP,
                'style' => 'bullet',
                'min_heading_level' => 2,
                'max_heading_level' => 3,
                'normalize' => 'relative',
            ],
            'heading_permalink' => [
                'min_heading_level' => 2,
                'max_heading_level' => 6,
                'insert' => HeadingPermalinkProcessor::INSERT_BEFORE,
                'apply_id_to_heading' => true,
                'id_prefix' => '',
                'fragment_prefix' => '',
                'html_class' => 'article-heading-anchor',
                'title' => __('articles.permalink_title'),
                'symbol' => '#',
            ],
            'external_link' => [
                'internal_hosts' => array_values(array_unique(array_filter([$host, 'localhost', '127.0.0.1']))),
                'open_in_new_window' => true,
                'html_class' => 'article-ext-link',
                'noopener' => ExternalLinkProcessor::APPLY_EXTERNAL,
                'noreferrer' => ExternalLinkProcessor::APPLY_EXTERNAL,
            ],
        ];

        $extensions = [
            new AttributesExtension,
            new DescriptionListExtension,
            new FootnoteExtension,
            new SmartPunctExtension,
            new HighlightExtension,
            new HeadingPermalinkExtension,
            new TableOfContentsExtension,
            new ExternalLinkExtension,
        ];

        return Str::markdown($markdown, $config, $extensions);
    }
}
