@php
    use Illuminate\Support\Str;

    $bio = filled($profile->reference_text)
        ? $profile->reference_text
        : ($group && filled($group->description)
            ? Str::limit(trim(strip_tags($group->description)), 400)
            : ($private && filled($private->description)
                ? Str::limit(trim(strip_tags($private->description)), 400)
                : __('marketplace.card.bio_fallback')));

    $specializations = collect([$group?->name, $private?->name])
        ->filter()
        ->merge(collect($profile->languagesForDisplay())->take(3))
        ->unique()
        ->values();

    $educations = $profile->educationsForDisplay();
    $experiences = $profile->workExperiencesForDisplay();
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
                    <li class="relative">
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
