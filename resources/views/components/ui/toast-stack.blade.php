@php
    $statusKey = session('status');
    $errorMessage = session('error');
    $statusMessage = null;

    if (filled($statusKey)) {
        $statusMessage = match ($statusKey) {
            'profile-updated', 'password-updated' => __('profile.saved'),
            'public-profile-updated' => __('profile_public.saved'),
            'verification-link-sent' => __('profile.verification.sent'),
            default => $statusKey,
        };
    }

    $initialToasts = [];
    if ($statusMessage !== null) {
        $initialToasts[] = ['type' => 'success', 'message' => $statusMessage];
    }
    if (filled($errorMessage)) {
        $initialToasts[] = ['type' => 'error', 'message' => $errorMessage];
    }
@endphp

<div
    x-data
    x-init="(@js($initialToasts)).forEach((t) => $store.toasts.add(t.type, t.message))"
    class="ui-toast-stack"
    aria-live="polite"
    aria-relevant="additions removals"
>
    <template x-for="toast in $store.toasts.items" :key="toast.id">
        <div
            x-show="toast.visible"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-2 sm:translate-x-4 sm:translate-y-0"
            x-transition:enter-end="opacity-100 translate-y-0 sm:translate-x-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:translate-x-0"
            x-transition:leave-end="opacity-0 -translate-y-2 sm:translate-x-4 sm:translate-y-0"
            class="ui-toast"
            :class="{
                'ui-toast-success': toast.type === 'success',
                'ui-toast-error': toast.type === 'error',
                'ui-toast-warning': toast.type === 'warning',
                'ui-toast-info': toast.type !== 'success' && toast.type !== 'error' && toast.type !== 'warning',
            }"
            role="status"
        >
            <p class="ui-toast-message" x-text="toast.message"></p>
            <button
                type="button"
                class="ui-toast-dismiss"
                @click="$store.toasts.dismiss(toast.id)"
                aria-label="Tutup"
            >
                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                </svg>
            </button>
        </div>
    </template>
</div>
