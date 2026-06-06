<x-app-layout>
    <x-ui.app-page>
        <x-page-container class="ui-stack relative">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-brand-700">{{ __('admin.articles.badge') }}</p>
                    <h1 class="mt-1 text-2xl font-bold text-slate-900 sm:text-3xl">{{ __('admin.articles.title') }}</h1>
                    <p class="mt-2 max-w-xl text-sm text-slate-600">{{ __('admin.articles.subtitle') }}</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('admin.settings.index') }}" class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300">
                        {{ __('admin.articles.back_settings') }}
                    </a>
                    <a href="{{ route('admin.articles.create') }}" class="inline-flex items-center rounded-xl bg-baytgo px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-baytgo/20 transition hover:bg-baytgo-800">
                        {{ __('admin.articles.new') }}
                    </a>
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                @if ($articles->isEmpty())
                    <p class="p-10 text-center text-sm text-slate-600">{{ __('admin.articles.empty') }}</p>
                @else
                    <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left font-semibold text-slate-700">{{ __('admin.articles.col_slug') }}</th>
                                <th scope="col" class="px-4 py-3 text-left font-semibold text-slate-700">{{ __('admin.articles.col_title') }}</th>
                                <th scope="col" class="px-4 py-3 text-center font-semibold text-slate-700">{{ __('admin.articles.col_status') }}</th>
                                <th scope="col" class="px-4 py-3 text-left font-semibold text-slate-700">{{ __('admin.articles.col_updated') }}</th>
                                <th scope="col" class="px-4 py-3 text-right font-semibold text-slate-700">{{ __('admin.articles.col_actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($articles as $row)
                                @php
                                    app()->setLocale('id');
                                    $title = $row->localized('title');
                                @endphp
                                <tr class="hover:bg-slate-50/80">
                                    <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-slate-800">{{ $row->slug }}</td>
                                    <td class="max-w-xs truncate px-4 py-3 text-slate-900" title="{{ $title }}">{{ $title }}</td>
                                    <td class="px-4 py-3 text-center">
                                        @if ($row->is_published)
                                            <span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-800">{{ __('admin.articles.status_published') }}</span>
                                        @else
                                            <span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600">{{ __('admin.articles.status_draft') }}</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $row->updated_at?->translatedFormat('d M Y H:i') }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right">
                                        <div class="flex justify-end gap-2">
                                            @if ($row->is_published && $row->published_at && $row->published_at->isPast())
                                                <a href="{{ route('articles.show', ['slug' => $row->slug]) }}" target="_blank" rel="noopener" class="rounded-lg px-2 py-1 text-xs font-semibold text-brand-700 hover:bg-brand-50">{{ __('admin.articles.view_public') }}</a>
                                            @endif
                                            <a href="{{ route('admin.articles.edit', $row) }}" class="rounded-lg px-2 py-1 text-xs font-semibold text-baytgo hover:bg-baytgo/10">{{ __('admin.articles.edit') }}</a>
                                            <form action="{{ route('admin.articles.destroy', $row) }}" method="post" class="inline" onsubmit="return confirm(@json(__('admin.articles.delete_confirm')));">
                                                @csrf
                                                @method('DELETE')
                                                <x-submit-button class="rounded-lg px-2 py-1 text-xs font-semibold text-red-600 hover:bg-red-50">{{ __('admin.articles.delete') }}</x-submit-button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    </div>
                @endif
            </div>
        </x-page-container>
</x-ui.app-page>
</x-app-layout>
