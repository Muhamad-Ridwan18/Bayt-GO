<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Support\ArticleBodyMarkdown;
use App\Support\ArticleSeedDefinitions;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ArticleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (ArticleSeedDefinitions::articles() as $def) {
            /** @var array<string, array{title: string, excerpt: string, category: string, author: string, body_md: string}> $locales */
            $locales = $def['locales'];

            $translations = [];
            foreach ($locales as $locale => $content) {
                app()->setLocale($locale);

                $translations[$locale] = [
                    'title' => $content['title'],
                    'excerpt' => $content['excerpt'],
                    'category' => $content['category'],
                    'author' => $content['author'],
                    'body_md' => $content['body_md'],
                    'body' => ArticleBodyMarkdown::toHtml($content['body_md']),
                ];
            }

            Article::updateOrCreate(
                ['slug' => $def['slug']],
                [
                    'is_published' => true,
                    'is_featured' => $def['is_featured'],
                    'sort_order' => $def['sort_order'],
                    'published_at' => Carbon::now()->subDays(20 - (int) $def['sort_order'] * 9),
                    'translations' => $translations,
                ]
            );
        }
    }
}
