<x-app-layout>
    <div class="py-8 sm:py-12">
        <div class="mx-auto max-w-2xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div>
                <h1 class="text-lg font-semibold text-slate-900">{{ __('support.create_title') }}</h1>
                <p class="mt-1 text-sm text-slate-600">{{ __('support.create_subtitle') }}</p>
            </div>

            @if ($errors->any())
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('support.store') }}" class="space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                @csrf
                <div>
                    <label for="subject" class="block text-sm font-medium text-slate-700">{{ __('support.subject') }}</label>
                    <input id="subject" name="subject" type="text" value="{{ old('subject') }}" required maxlength="160" class="mt-2 block w-full rounded-xl border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="category" class="block text-sm font-medium text-slate-700">{{ __('support.category') }}</label>
                        <select id="category" name="category" required class="mt-2 block w-full rounded-xl border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                            @foreach ($categories as $case)
                                <option value="{{ $case->value }}" @selected(old('category', $case->value) === $case->value)>{{ $case->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="priority" class="block text-sm font-medium text-slate-700">{{ __('support.priority') }}</label>
                        <select id="priority" name="priority" required class="mt-2 block w-full rounded-xl border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                            @foreach ($priorities as $case)
                                <option value="{{ $case->value }}" @selected(old('priority') === $case->value || (old('priority') === null && $case->value === \App\Enums\SupportTicketPriority::Normal->value))>{{ $case->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label for="body" class="block text-sm font-medium text-slate-700">{{ __('support.message') }}</label>
                    <p class="mt-1 text-xs text-slate-500">{{ __('support.message_hint') }}</p>
                    <textarea id="body" name="body" rows="8" required class="mt-2 block w-full rounded-xl border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">{{ old('body') }}</textarea>
                </div>

                <p class="text-xs font-medium text-amber-900/90">{{ __('support.privacy_warning') }}</p>

                <div class="flex flex-wrap items-center gap-3">
                    <button type="submit" class="inline-flex items-center rounded-2xl bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">{{ __('support.submit_ticket') }}</button>
                    <a href="{{ route('support.index') }}" class="text-sm font-semibold text-brand-700 hover:text-brand-800">{{ __('support.back_list') }}</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
