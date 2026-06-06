@php
    $steps = [
        1 => ['label' => __('marketplace.panel.step_package'), 'desc' => __('marketplace.panel.step_package_desc')],
        2 => ['label' => __('marketplace.panel.step_documents'), 'desc' => __('marketplace.panel.step_documents_desc')],
        3 => ['label' => __('marketplace.panel.step_confirm'), 'desc' => __('marketplace.panel.step_confirm_desc')],
    ];
@endphp

<nav aria-label="{{ __('marketplace.panel.stepper_aria') }}" class="ui-booking-stepper" x-data="bookingStepper()">
    <ol class="flex items-start gap-0">
        @foreach ($steps as $num => $step)
            <li class="flex min-w-0 flex-1 items-start">
                <div class="flex min-w-0 flex-1 flex-col items-center text-center">
                    <div
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-sm font-bold"
                        :class="stepClass({{ $num }})"
                    >
                        <span x-show="!isDone({{ $num }})">{{ $num }}</span>
                        <svg x-show="isDone({{ $num }})" x-cloak class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" /></svg>
                    </div>
                    <p class="mt-2 text-xs font-bold leading-tight" :class="labelClass({{ $num }})">{{ $step['label'] }}</p>
                    <p class="mt-0.5 hidden text-[11px] leading-snug text-slate-500 sm:block">{{ $step['desc'] }}</p>
                </div>
            </li>
            @if (! $loop->last)
                <li class="flex shrink-0 items-center px-1 pt-4 sm:px-2" aria-hidden="true">
                    <svg class="h-4 w-4 text-slate-300" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" /></svg>
                </li>
            @endif
        @endforeach
    </ol>
</nav>
