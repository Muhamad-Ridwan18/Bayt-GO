<?php

namespace App\Http\Requests;

use App\Models\Article;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreExternalArticleRequest extends FormRequest
{
    private ?Article $resolvedExisting = null;

    private bool $resolvedExistingLoaded = false;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->hydrateJsonBody();
        $this->unwrapNestedPayload();

        $translations = $this->input('translations', []);
        if (! is_array($translations)) {
            $decodedTranslations = json_decode((string) $translations, true);
            $translations = is_array($decodedTranslations) ? $decodedTranslations : [];
        }

        $flat = [
            'id' => [
                'title' => 'title',
                'excerpt' => 'excerpt',
                'category' => 'category',
                'author' => 'author',
                'body' => 'body',
                'body_md' => 'body_md',
            ],
            'en' => [
                'title' => 'title_en',
                'excerpt' => 'excerpt_en',
                'category' => 'category_en',
                'author' => 'author_en',
                'body' => 'body_en',
                'body_md' => 'body_md_en',
            ],
            'ar' => [
                'title' => 'title_ar',
                'excerpt' => 'excerpt_ar',
                'category' => 'category_ar',
                'author' => 'author_ar',
                'body' => 'body_ar',
                'body_md' => 'body_md_ar',
            ],
        ];

        foreach ($flat as $locale => $fields) {
            foreach ($fields as $key => $input) {
                if (! $this->exists($input) || $this->input($input) === null) {
                    continue;
                }
                if (isset($translations[$locale][$key]) && $translations[$locale][$key] !== '') {
                    continue;
                }
                $translations[$locale][$key] = $this->input($input);
            }
        }

        $this->merge(['translations' => $translations]);
    }

    private function hydrateJsonBody(): void
    {
        $raw = ltrim((string) $this->getContent(), "\xEF\xBB\xBF");
        if ($raw === '' || ($raw[0] !== '{' && $raw[0] !== '[' && $raw[0] !== '"')) {
            return;
        }

        $decoded = json_decode($raw, true);
        if (is_string($decoded)) {
            $decoded = json_decode($decoded, true);
        }
        $decoded = $this->unwrapArticleArray($decoded);
        if (! is_array($decoded)) {
            return;
        }

        $this->merge($decoded);
    }

    /**
     * @param  mixed  $decoded
     * @return mixed
     */
    private function unwrapArticleArray(mixed $decoded): mixed
    {
        if (! is_array($decoded)) {
            return $decoded;
        }

        if (array_is_list($decoded) && isset($decoded[0]) && is_array($decoded[0])) {
            return $decoded[0];
        }

        return $decoded;
    }

    private function unwrapNestedPayload(): void
    {
        $first = $this->input(0);
        if (is_array($first) && (isset($first['title']) || isset($first['slug']) || isset($first['body_md']))) {
            $this->merge($first);
        }

        foreach (['data', 'json', 'article', 'payload'] as $wrap) {
            $inner = $this->input($wrap);
            if (is_string($inner)) {
                $parsed = json_decode($inner, true);
                $inner = is_array($parsed) ? $parsed : null;
            }
            if (! is_array($inner)) {
                continue;
            }
            if (! isset($inner['title']) && ! isset($inner['slug']) && ! isset($inner['body_md']) && ! isset($inner['translations'])) {
                continue;
            }
            $this->merge($inner);

            return;
        }

        $body = $this->input('body');
        if (is_string($body)) {
            $trimmed = ltrim($body);
            if ($trimmed !== '' && ($trimmed[0] === '{' || $trimmed[0] === '[')) {
                $parsed = json_decode($body, true);
                if (is_array($parsed) && (isset($parsed['title']) || isset($parsed['body_md']))) {
                    $this->merge($parsed);
                }
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $ignoreId = $this->existingArticle()?->id;

        $slugRules = [
            'nullable',
            'string',
            'max:120',
            'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
        ];
        if ($ignoreId === null) {
            $slugRules[] = Rule::unique('articles', 'slug');
        } else {
            $slugRules[] = Rule::unique('articles', 'slug')->ignore($ignoreId);
        }

        return [
            'slug' => $slugRules,
            'title' => ['nullable', 'string', 'max:255'],
            'title_en' => ['nullable', 'string', 'max:255'],
            'title_ar' => ['nullable', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:65535'],
            'excerpt_en' => ['nullable', 'string', 'max:65535'],
            'excerpt_ar' => ['nullable', 'string', 'max:65535'],
            'category' => ['nullable', 'string', 'max:120'],
            'category_en' => ['nullable', 'string', 'max:120'],
            'category_ar' => ['nullable', 'string', 'max:120'],
            'author' => ['nullable', 'string', 'max:120'],
            'author_en' => ['nullable', 'string', 'max:120'],
            'author_ar' => ['nullable', 'string', 'max:120'],
            'body' => ['nullable', 'string'],
            'body_en' => ['nullable', 'string'],
            'body_ar' => ['nullable', 'string'],
            'body_md' => ['nullable', 'string'],
            'body_md_en' => ['nullable', 'string'],
            'body_md_ar' => ['nullable', 'string'],
            'is_published' => ['sometimes', 'boolean'],
            'is_featured' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:99999'],
            'published_at' => ['nullable', 'date'],
            'image_url' => ['nullable', 'string', 'max:2048', 'url:http,https'],
            'image_urls' => ['nullable', 'array', 'max:8'],
            'image_urls.*' => ['nullable', 'string', 'max:2048', 'url:http,https'],
            'translations' => ['sometimes', 'array'],
            'translations.id.title' => ['nullable', 'string', 'max:255'],
            'translations.id.excerpt' => ['nullable', 'string', 'max:65535'],
            'translations.id.category' => ['nullable', 'string', 'max:120'],
            'translations.id.author' => ['nullable', 'string', 'max:120'],
            'translations.id.body' => ['nullable', 'string'],
            'translations.id.body_md' => ['nullable', 'string'],
            'translations.en.title' => ['nullable', 'string', 'max:255'],
            'translations.en.excerpt' => ['nullable', 'string', 'max:65535'],
            'translations.en.category' => ['nullable', 'string', 'max:120'],
            'translations.en.author' => ['nullable', 'string', 'max:120'],
            'translations.en.body' => ['nullable', 'string'],
            'translations.en.body_md' => ['nullable', 'string'],
            'translations.ar.title' => ['nullable', 'string', 'max:255'],
            'translations.ar.excerpt' => ['nullable', 'string', 'max:65535'],
            'translations.ar.category' => ['nullable', 'string', 'max:120'],
            'translations.ar.author' => ['nullable', 'string', 'max:120'],
            'translations.ar.body' => ['nullable', 'string'],
            'translations.ar.body_md' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'max:10240'],
        ];
    }

    public function existingArticle(): ?Article
    {
        if ($this->resolvedExistingLoaded) {
            return $this->resolvedExisting;
        }

        $this->resolvedExistingLoaded = true;
        $routed = $this->route('article');
        if ($routed instanceof Article) {
            return $this->resolvedExisting = $routed;
        }

        $slug = trim((string) $this->input('slug', ''));
        if ($slug === '') {
            return $this->resolvedExisting = null;
        }

        return $this->resolvedExisting = Article::query()->where('slug', $slug)->first();
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $title = trim((string) ($this->input('translations.id.title') ?: $this->input('title') ?: ''));
            if ($title === '' && $this->existingArticle() === null) {
                $validator->errors()->add('title', __('validation.required', ['attribute' => 'title']));
            }
        });
    }
}
