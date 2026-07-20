@props(['page'])

<section id="cara-kerja" class="home-section-pad scroll-mt-24 border-t border-slate-100 py-10 sm:py-12" aria-labelledby="welcome-work-heading">
    <x-home.section-heading
        align="center"
        :title="__('welcome.work_title')"
        title-id="welcome-work-heading"
        :subtitle="__('welcome.work_sub')"
    />
    <div class="grid gap-4 sm:grid-cols-3 sm:gap-6">
        @foreach ($page->workSteps as $i => $step)
            <article class="relative rounded-2xl border border-slate-100 bg-white p-5 text-center shadow-sm sm:p-6">
                <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-baytgo text-sm font-bold text-white">{{ $i + 1 }}</span>
                <h3 class="mt-4 text-base font-bold text-slate-900">{{ $step['title'] ?? '' }}</h3>
                <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $step['desc'] ?? '' }}</p>
            </article>
        @endforeach
    </div>
</section>
