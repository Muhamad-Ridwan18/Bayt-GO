@props(['campaigns'])

@if($campaigns && $campaigns->isNotEmpty())
<div class="relative w-full overflow-hidden mb-8 group rounded-3xl" x-data="campaignCarousel()">
    <div class="flex transition-transform duration-500 ease-in-out h-full" :style="'transform: translateX(-' + (currentIndex * 100) + '%)'">
        @foreach($campaigns as $campaign)
            <div class="w-full flex-shrink-0 relative cursor-pointer" @click="window.location.href='{{ route('campaigns.show', $campaign->slug) }}'">
                @if($campaign->desktop_banner)
                    <div class="w-full aspect-[21/9] md:aspect-[3/1] lg:aspect-[4/1] relative bg-slate-100">
                        @if($campaign->mobile_banner)
                            <img src="{{ Storage::url($campaign->mobile_banner) }}" alt="{{ $campaign->title }}" class="absolute inset-0 w-full h-full object-cover md:hidden rounded-3xl" loading="lazy">
                            <img src="{{ Storage::url($campaign->desktop_banner) }}" alt="{{ $campaign->title }}" class="absolute inset-0 w-full h-full object-cover hidden md:block rounded-3xl" loading="lazy">
                        @else
                            <img src="{{ Storage::url($campaign->desktop_banner) }}" alt="{{ $campaign->title }}" class="absolute inset-0 w-full h-full object-cover rounded-3xl" loading="lazy">
                        @endif
                    </div>
                @else
                    <div class="w-full aspect-[21/9] md:aspect-[3/1] lg:aspect-[4/1] relative flex items-center justify-center rounded-3xl" style="background-color: {{ $campaign->theme_color ?? '#10b981' }}">
                        <div class="text-white text-center px-4">
                            <h3 class="text-xl md:text-3xl font-bold">{{ $campaign->title }}</h3>
                        </div>
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    @if($campaigns->count() > 1)
        <!-- Prev Button -->
        <button @click="prev()" class="absolute left-4 top-1/2 -translate-y-1/2 bg-white/30 hover:bg-white/50 backdrop-blur-md rounded-full p-2 text-white opacity-0 group-hover:opacity-100 transition-opacity focus:outline-none">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
        </button>
        <!-- Next Button -->
        <button @click="next()" class="absolute right-4 top-1/2 -translate-y-1/2 bg-white/30 hover:bg-white/50 backdrop-blur-md rounded-full p-2 text-white opacity-0 group-hover:opacity-100 transition-opacity focus:outline-none">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
        </button>
        <!-- Indicators -->
        <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex space-x-2">
            @foreach($campaigns as $index => $campaign)
                <button @click="goTo({{ $index }})" :class="{'bg-white w-4': currentIndex === {{ $index }}, 'bg-white/50 w-2': currentIndex !== {{ $index }}}" class="h-2 rounded-full transition-all duration-300 focus:outline-none"></button>
            @endforeach
        </div>
    @endif
</div>

<script>
    function campaignCarousel() {
        return {
            currentIndex: 0,
            count: {{ $campaigns->count() }},
            interval: null,
            init() {
                if (this.count > 1) {
                    this.startAutoplay();
                }
            },
            startAutoplay() {
                this.interval = setInterval(() => {
                    this.next();
                }, 5000); // 5 seconds
            },
            stopAutoplay() {
                clearInterval(this.interval);
            },
            next() {
                this.currentIndex = (this.currentIndex === this.count - 1) ? 0 : this.currentIndex + 1;
            },
            prev() {
                this.currentIndex = (this.currentIndex === 0) ? this.count - 1 : this.currentIndex - 1;
            },
            goTo(index) {
                this.currentIndex = index;
            }
        }
    }
</script>
@endif
