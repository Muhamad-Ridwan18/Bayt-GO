<x-app-layout>
    @section('title', $campaign->title . ' - BaytGO')
    @php
        $themeColor = $campaign->theme_color ?? '#10b981';
    @endphp

    <div class="relative bg-slate-50 min-h-screen pb-12">
        {{-- Banner Section --}}
        @if($campaign->desktop_banner)
            <div class="w-full relative h-[400px] md:h-[500px] lg:h-[600px] overflow-hidden">
                @if($campaign->mobile_banner)
                    <img src="{{ Storage::url($campaign->mobile_banner) }}" alt="{{ $campaign->title }}" class="absolute inset-0 w-full h-full object-cover md:hidden">
                    <img src="{{ Storage::url($campaign->desktop_banner) }}" alt="{{ $campaign->title }}" class="absolute inset-0 w-full h-full object-cover hidden md:block">
                @else
                    <img src="{{ Storage::url($campaign->desktop_banner) }}" alt="{{ $campaign->title }}" class="absolute inset-0 w-full h-full object-cover">
                @endif
                <div class="absolute inset-0 bg-gradient-to-t from-slate-900/80 via-slate-900/20 to-transparent"></div>
                <div class="absolute bottom-0 w-full p-6 md:p-12 text-white">
                    <x-page-container>
                        <h1 class="text-3xl md:text-5xl font-bold drop-shadow-md">{{ $campaign->title }}</h1>
                    </x-page-container>
                </div>
            </div>
        @else
            <div class="w-full relative py-20" style="background-color: {{ $themeColor }}">
                <div class="absolute inset-0 opacity-20 bg-[radial-gradient(circle_at_center,rgba(255,255,255,0.8)_0,transparent_100%)]"></div>
                <x-page-container class="relative z-10 px-6 text-center text-white">
                    <h1 class="text-3xl md:text-5xl font-bold">{{ $campaign->title }}</h1>
                </x-page-container>
            </div>
        @endif

        <x-page-container class="-mt-8 relative z-20">
            <div class="bg-white rounded-3xl shadow-xl p-6 md:p-10 border border-slate-100 flex flex-col md:flex-row gap-8 items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-slate-800">Mulai Dalam:</h3>
                    <div class="mt-4 flex gap-4 text-center" id="countdown-timer">
                        <div class="bg-slate-50 border border-slate-100 rounded-xl p-3 min-w-[70px]">
                            <div class="text-2xl font-bold" style="color: {{ $themeColor }}" id="days">00</div>
                            <div class="text-xs text-slate-500 uppercase tracking-wider mt-1">Hari</div>
                        </div>
                        <div class="bg-slate-50 border border-slate-100 rounded-xl p-3 min-w-[70px]">
                            <div class="text-2xl font-bold" style="color: {{ $themeColor }}" id="hours">00</div>
                            <div class="text-xs text-slate-500 uppercase tracking-wider mt-1">Jam</div>
                        </div>
                        <div class="bg-slate-50 border border-slate-100 rounded-xl p-3 min-w-[70px]">
                            <div class="text-2xl font-bold" style="color: {{ $themeColor }}" id="minutes">00</div>
                            <div class="text-xs text-slate-500 uppercase tracking-wider mt-1">Menit</div>
                        </div>
                        <div class="bg-slate-50 border border-slate-100 rounded-xl p-3 min-w-[70px]">
                            <div class="text-2xl font-bold" style="color: {{ $themeColor }}" id="seconds">00</div>
                            <div class="text-xs text-slate-500 uppercase tracking-wider mt-1">Detik</div>
                        </div>
                    </div>
                </div>
                
                @if($campaign->cta_text)
                    <div class="w-full md:w-auto">
                        <a href="{{ $campaign->cta_url }}" class="block w-full text-center px-8 py-4 rounded-2xl text-white font-bold shadow-lg transition transform hover:-translate-y-1" style="background-color: {{ $themeColor }}; shadow-color: {{ $themeColor }}80">
                            {{ $campaign->cta_text }}
                        </a>
                    </div>
                @endif
            </div>

            @if($campaign->body)
                <div class="mt-8 bg-white rounded-3xl shadow-sm border border-slate-100 p-6 md:p-10 prose prose-slate max-w-none">
                    {!! nl2br(e($campaign->body)) !!}
                </div>
            @endif
        </x-page-container>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const endDate = new Date("{{ $campaign->end_date->toIso8601String() }}").getTime();
            
            const timer = setInterval(function() {
                const now = new Date().getTime();
                const distance = endDate - now;
                
                if (distance < 0) {
                    clearInterval(timer);
                    document.getElementById("countdown-timer").innerHTML = "<div class='text-red-500 font-bold'>Promo telah berakhir</div>";
                    return;
                }
                
                const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);
                
                document.getElementById("days").innerText = days.toString().padStart(2, '0');
                document.getElementById("hours").innerText = hours.toString().padStart(2, '0');
                document.getElementById("minutes").innerText = minutes.toString().padStart(2, '0');
                document.getElementById("seconds").innerText = seconds.toString().padStart(2, '0');
            }, 1000);
        });
    </script>
    @endpush
</x-app-layout>
