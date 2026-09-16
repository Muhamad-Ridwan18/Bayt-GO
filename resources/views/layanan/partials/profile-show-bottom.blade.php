@php
    use App\Support\PublicBioText;
    use Illuminate\Support\Str;

    $bio = filled($profile->reference_text)
        ? PublicBioText::withoutContactNumbers($profile->reference_text)
        : ($group && filled($group->description)
            ? PublicBioText::withoutContactNumbers(Str::limit(trim(strip_tags($group->description)), 400))
            : ($private && filled($private->description)
                ? PublicBioText::withoutContactNumbers(Str::limit(trim(strip_tags($private->description)), 400))
                : __('marketplace.card.bio_fallback')));

    $bio = $bio ?: __('marketplace.card.bio_fallback');

    $specializations = collect([$group?->name, $private?->name])
        ->filter()
        ->merge(collect($profile->languagesForDisplay())->take(3))
        ->unique()
        ->values();

    $educations = $profile->educationsForDisplay();
    $experiences = $profile->workExperiencesForDisplay();
    $cvDocuments = $profile->relationLoaded('supportingDocuments')
        ? $profile->supportingDocuments
        : $profile->supportingDocuments()->orderBy('sort_order')->orderBy('created_at')->get();
@endphp

<section class="grid gap-6 lg:grid-cols-3">
    <x-ui.card pad="md" class="block">
        <h2 class="text-lg font-bold text-slate-900">{{ __('marketplace.show.about_heading') }}</h2>
        <p class="mt-4 text-sm leading-relaxed text-slate-600 whitespace-pre-line">{{ $bio }}</p>
        @if ($specializations->isNotEmpty())
            <p class="mt-5 text-xs font-bold uppercase tracking-wide text-slate-500">{{ __('marketplace.show.specialization') }}</p>
            <ul class="mt-2 flex flex-wrap gap-2">
                @foreach ($specializations as $tag)
                    <li class="rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-900 ring-1 ring-brand-100">{{ $tag }}</li>
                @endforeach
            </ul>
        @endif
    </x-ui.card>

    <x-ui.card pad="md" class="block">
        <h2 class="text-lg font-bold text-slate-900">{{ __('marketplace.show.timeline_heading') }}</h2>
        @if ($educations === [] && $experiences === [])
            <p class="mt-4 text-sm text-slate-500">{{ __('marketplace.show.not_filled') }}</p>
        @else
            <ol class="relative mt-5 ui-stack-compact border-s border-brand-200/80 ps-5">
                @if ($educations !== [])
                    <li class="relative">
                        <span class="absolute -start-[1.35rem] top-1 flex h-3 w-3 rounded-full bg-brand-600 ring-4 ring-white" aria-hidden="true"></span>
                        <p class="text-xs font-bold uppercase tracking-wide text-brand-800">{{ __('marketplace.show.timeline_education') }}</p>
                        <ul class="mt-2 space-y-1.5">
                            @foreach ($educations as $item)
                                <li class="text-sm text-slate-700">{{ $item }}</li>
                            @endforeach
                        </ul>
                    </li>
                @endif
                @if ($experiences !== [])
                    <li class="relative {{ $educations !== [] ? 'mt-5' : '' }}">
                        <span class="absolute -start-[1.35rem] top-1 flex h-3 w-3 rounded-full bg-gold ring-4 ring-white" aria-hidden="true"></span>
                        <p class="text-xs font-bold uppercase tracking-wide text-amber-900">{{ __('marketplace.show.timeline_experience') }}</p>
                        <ul class="mt-2 space-y-1.5">
                            @foreach ($experiences as $item)
                                <li class="text-sm text-slate-700">{{ $item }}</li>
                            @endforeach
                        </ul>
                    </li>
                @endif
            </ol>
        @endif
    </x-ui.card>

    <x-ui.card
        pad="md"
        class="block"
        x-data="{
            lightboxOpen: false,
            activeImages: [],
            activeIndex: 0,
            activeTitle: '',
            openAlbum(images, title) {
                this.activeImages = images;
                this.activeIndex = 0;
                this.activeTitle = title;
                this.lightboxOpen = true;
            },
            next() { if (this.activeImages.length) this.activeIndex = (this.activeIndex + 1) % this.activeImages.length; },
            prev() { if (this.activeImages.length) this.activeIndex = (this.activeIndex - 1 + this.activeImages.length) % this.activeImages.length; }
        }"
    >
        <h2 class="text-lg font-bold text-slate-900">{{ __('marketplace.show.gallery_heading') }}</h2>
        @if ($profile->portfolios->isEmpty())
            <p class="mt-4 text-sm text-slate-500">Muthowif belum menambahkan foto portfolio.</p>
        @else
            <div class="mt-4 grid grid-cols-3 gap-2">
                @foreach ($profile->portfolios->take(3) as $portfolio)
                    @php
                        $portfolioImages = $portfolio->images;
                        $previewImage = $portfolio->images->first();
                        $previewUrl = $previewImage ? $previewImage->publicUrl() : $portfolio->coverUrl();
                        $albumUrls = $portfolioImages->isNotEmpty()
                            ? $portfolioImages->map(fn ($image) => $image->publicUrl())->values()
                            : collect([$previewUrl]);
                    @endphp
                    <button
                        type="button"
                        @click="openAlbum(@js($albumUrls), @js($portfolio->title))"
                        class="group aspect-square overflow-hidden rounded-xl border border-slate-200 bg-slate-100 focus:outline-none focus:ring-2 focus:ring-brand-500"
                    >
                        <img src="{{ $previewUrl }}" alt="{{ $portfolio->title }}" class="h-full w-full object-cover transition duration-300 group-hover:scale-105" loading="lazy">
                    </button>
                @endforeach
            </div>
            @if ((int) ($profile->portfolios_count ?? $profile->portfolios->count()) > 3)
                <a href="{{ route('layanan.portfolio.index', $profile) }}" class="mt-4 inline-block text-sm font-semibold text-brand-700 hover:text-brand-800">
                    {{ __('marketplace.show.gallery_see_all') }} ({{ (int) ($profile->portfolios_count ?? $profile->portfolios->count()) }})
                </a>
            @endif

            <div x-show="lightboxOpen" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/90 p-4" @keydown.escape.window="lightboxOpen = false">
                <div class="relative max-w-3xl w-full overflow-hidden rounded-2xl bg-white shadow-2xl" @click.away="lightboxOpen = false">
                    <div class="relative bg-slate-950">
                        <img :src="activeImages[activeIndex]" :alt="activeTitle" class="max-h-[70vh] w-full object-contain">
                        <button type="button" @click="lightboxOpen = false" class="absolute top-3 right-3 rounded-full bg-slate-950/60 p-2 text-white hover:bg-slate-950/80">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                    <p class="p-4 text-sm font-semibold text-slate-900" x-text="activeTitle"></p>
                </div>
            </div>
        @endif
    </x-ui.card>
</section>

<section
    class="mt-6"
    x-data="{
        cvModalOpen: false,
        cvTitle: '',
        cvPreviewUrl: '',
        cvKind: 'pdf',
        openCvPreview(title, url, kind) {
            this.cvTitle = title;
            this.cvKind = kind;
            this.cvPreviewUrl = kind === 'pdf' ? (url + (url.includes('#') ? '' : '#toolbar=0&navpanes=0&scrollbar=0')) : url;
            this.cvModalOpen = true;
        },
        closeCvPreview() {
            this.cvModalOpen = false;
            this.cvPreviewUrl = '';
        }
    }"
    @keydown.escape.window="cvModalOpen && closeCvPreview()"
>
    <x-ui.card pad="md" class="block">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-lg font-bold text-slate-900">{{ __('marketplace.show.cv_heading') }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ __('marketplace.show.cv_sub') }}</p>
            </div>
        </div>

        @if ($cvDocuments->isEmpty())
            <p class="mt-4 text-sm text-slate-500">{{ __('marketplace.show.cv_empty') }}</p>
        @else
            <ul class="mt-4 grid gap-2 sm:grid-cols-2">
                @foreach ($cvDocuments as $document)
                    <li>
                        <button
                            type="button"
                            @click="openCvPreview(@js($document->displayName()), @js($document->publicUrl($profile)), @js($document->previewKind()))"
                            class="flex w-full items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-start transition hover:border-brand-300 hover:bg-brand-50/60"
                        >
                            <span class="flex min-w-0 items-center gap-2.5">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-brand-700 ring-1 ring-slate-200">
                                    @if ($document->isPdf())
                                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                                    @else
                                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                    @endif
                                </span>
                                <span class="min-w-0 truncate text-sm font-semibold text-slate-800">{{ $document->displayName() }}</span>
                            </span>
                            <span class="shrink-0 text-xs font-bold text-brand-700">{{ __('marketplace.show.cv_open') }}</span>
                        </button>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-ui.card>

    <div
        x-show="cvModalOpen"
        x-cloak
        class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6"
        role="dialog"
        aria-modal="true"
        :aria-label="cvTitle"
    >
        <div class="absolute inset-0 bg-slate-900/70 backdrop-blur-[2px]" @click="closeCvPreview()"></div>
        <div class="relative z-10 flex max-h-[92vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-slate-200/90" @click.stop @contextmenu.prevent>
            <div class="flex shrink-0 items-center justify-between gap-3 border-b border-slate-100 px-4 py-3 sm:px-5">
                <h4 class="min-w-0 truncate text-sm font-bold text-slate-900" x-text="cvTitle"></h4>
                <button
                    type="button"
                    class="shrink-0 rounded-lg p-1.5 text-slate-500 transition hover:bg-slate-100 hover:text-slate-800"
                    @click="closeCvPreview()"
                    aria-label="{{ __('marketplace.show.cv_modal_close') }}"
                >
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" /></svg>
                </button>
            </div>
            <div class="min-h-0 flex-1 overflow-auto bg-slate-50/80 p-4 sm:p-5 select-none">
                <img
                    x-show="cvKind === 'image'"
                    x-cloak
                    :src="cvPreviewUrl"
                    :alt="cvTitle"
                    draggable="false"
                    class="mx-auto block h-auto max-h-[min(75vh,36rem)] w-full object-contain pointer-events-none"
                />
                <iframe
                    x-show="cvKind === 'pdf'"
                    x-cloak
                    :src="cvPreviewUrl"
                    :title="cvTitle"
                    class="mx-auto block h-[min(75vh,36rem)] w-full rounded-xl border border-slate-200 bg-white shadow-sm"
                ></iframe>
                <div x-show="cvKind !== 'image' && cvKind !== 'pdf'" x-cloak class="rounded-xl border border-dashed border-slate-200 bg-white px-4 py-8 text-center text-sm text-slate-600">
                    <p>{{ __('marketplace.show.cv_preview_unsupported') }}</p>
                </div>
            </div>
        </div>
    </div>
</section>
