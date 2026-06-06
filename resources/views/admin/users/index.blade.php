@php
    use App\Enums\UserRole;

    $filterParams = function (?string $r) use ($q): array {
        $p = [];
        if ($q !== '') {
            $p['q'] = $q;
        }
        if ($r !== null && $r !== '' && $r !== 'all') {
            $p['role'] = $r;
        }

        return $p;
    };
@endphp

<x-app-layout>
    <x-ui.app-page>
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_80%_40%_at_50%_-10%,rgba(120,53,15,0.06),transparent)]"></div>
        <x-page-container class="ui-stack relative">
            <x-ui.page-hero :badge="__('admin.users.badge')" :title="__('admin.users.title')" :subtitle="__('admin.users.subtitle')">
                <x-slot:icon>
                    <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.813-2.387M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>
                </x-slot:icon>
                <x-slot:actions>
                    <a href="{{ route('dashboard') }}" class="inline-flex shrink-0 items-center gap-2 rounded-2xl border border-white/20 bg-white/10 px-4 py-2.5 text-sm font-semibold text-white backdrop-blur-sm transition hover:bg-white/20">
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M9.293 2.293a1 1 0 011.414 0l7 7A1 1 0 0117 11h-1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-3a1 1 0 00-1-1H9a1 1 0 00-1 1v3a1 1 0 01-1 1H5a1 1 0 01-1-1v-6H3a1 1 0 01-.707-1.707l7-7z" clip-rule="evenodd" /></svg>
                        {{ __('admin.users.back_dashboard') }}
                    </a>
                </x-slot:actions>
                <x-slot:stats>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <a href="{{ route('admin.users.index', $filterParams('all')) }}" class="rounded-2xl border border-white/10 bg-white/[0.07] p-4 backdrop-blur-sm transition hover:bg-white/[0.11] {{ ($roleFilter === '' || $roleFilter === 'all') ? 'ring-2 ring-emerald-400/50' : '' }}">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-white/55">{{ __('admin.users.stat_total') }}</p>
                        <p class="mt-2 text-2xl font-bold tabular-nums text-white">{{ $stats['total'] }}</p>
                    </a>
                    <a href="{{ route('admin.users.index', $filterParams(UserRole::Admin->value)) }}" class="rounded-2xl border border-white/10 bg-white/[0.07] p-4 backdrop-blur-sm transition hover:bg-white/[0.11] {{ $roleFilter === UserRole::Admin->value ? 'ring-2 ring-emerald-400/50' : '' }}">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-white/55">{{ UserRole::Admin->label() }}</p>
                        <p class="mt-2 text-2xl font-bold tabular-nums text-white">{{ $stats['admin'] }}</p>
                    </a>
                    <a href="{{ route('admin.users.index', $filterParams(UserRole::Customer->value)) }}" class="rounded-2xl border border-white/10 bg-white/[0.07] p-4 backdrop-blur-sm transition hover:bg-white/[0.11] {{ $roleFilter === UserRole::Customer->value ? 'ring-2 ring-emerald-400/50' : '' }}">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-white/55">{{ UserRole::Customer->label() }}</p>
                        <p class="mt-2 text-2xl font-bold tabular-nums text-white">{{ $stats['customer'] }}</p>
                    </a>
                    <a href="{{ route('admin.users.index', $filterParams(UserRole::Muthowif->value)) }}" class="rounded-2xl border border-white/10 bg-white/[0.07] p-4 backdrop-blur-sm transition hover:bg-white/[0.11] {{ $roleFilter === UserRole::Muthowif->value ? 'ring-2 ring-emerald-400/50' : '' }}">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-white/55">{{ UserRole::Muthowif->label() }}</p>
                        <p class="mt-2 text-2xl font-bold tabular-nums text-white">{{ $stats['muthowif'] }}</p>
                    </a>
                </div>
                </x-slot:stats>
            </x-ui.page-hero>

            <x-ui.data-table :empty="$users->isEmpty() ? __('admin.users.empty') : null">
                <x-slot:toolbar>
                <form method="get" action="{{ route('admin.users.index') }}" class="px-5 py-4">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                        <div class="min-w-0 flex-1">
                            <label for="user-q" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('admin.users.search_label') }}</label>
                            <input id="user-q" type="search" name="q" value="{{ $q }}" placeholder="{{ __('admin.users.search_placeholder') }}" class="mt-1.5 w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                        </div>
                        <div class="w-full sm:w-48">
                            <label for="user-role" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('admin.users.role_filter') }}</label>
                            <select id="user-role" name="role" class="mt-1.5 w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                <option value="all" @selected($roleFilter === '' || $roleFilter === 'all')>{{ __('admin.users.role_all') }}</option>
                                <option value="{{ UserRole::Admin->value }}" @selected($roleFilter === UserRole::Admin->value)>{{ UserRole::Admin->label() }}</option>
                                <option value="{{ UserRole::Customer->value }}" @selected($roleFilter === UserRole::Customer->value)>{{ UserRole::Customer->label() }}</option>
                                <option value="{{ UserRole::Muthowif->value }}" @selected($roleFilter === UserRole::Muthowif->value)>{{ UserRole::Muthowif->label() }}</option>
                            </select>
                        </div>
                        <div class="flex gap-2">
                            <x-submit-button class="flex-1 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 sm:flex-none">
                                {{ __('admin.users.apply') }}
                            </x-submit-button>
                            <a href="{{ route('admin.users.index') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                                {{ __('admin.users.reset') }}
                            </a>
                        </div>
                    </div>
                </form>
                </x-slot:toolbar>

                @if (! $users->isEmpty())
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="px-4 py-3">{{ __('admin.users.col_name') }}</th>
                                    <th class="px-4 py-3">{{ __('admin.users.col_contact') }}</th>
                                    <th class="px-4 py-3">{{ __('admin.users.col_role') }}</th>
                                    <th class="px-4 py-3">{{ ('customer type') }}</th>
                                    <th class="px-4 py-3 whitespace-nowrap">{{ __('admin.users.col_registered') }}</th>
                                    <th class="px-4 py-3 text-right">{{ __('admin.users.col_actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($users as $u)
                                    <tr class="transition hover:bg-brand-50/40">
                                        <td class="px-4 py-3">
                                            <p class="font-medium text-slate-900">{{ $u->name }}</p>
                                            @if ($u->id === auth()->id())
                                                <span class="mt-0.5 inline-flex rounded-full bg-brand-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-brand-800">{{ __('admin.users.you') }}</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-slate-600">
                                            <p>{{ $u->email ?? '—' }}</p>
                                            <p class="text-xs text-slate-500">{{ $u->phone ?? '—' }}</p>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold
                                                @class([
                                                    'bg-violet-100 text-violet-900' => $u->role === UserRole::Admin,
                                                    'bg-sky-100 text-sky-900' => $u->role === UserRole::Customer,
                                                    'bg-amber-100 text-amber-900' => $u->role === UserRole::Muthowif,
                                                ])">
                                                {{ $u->role->label() }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            {{ $u->customer_type ?? '-' }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-600">
                                            {{ $u->created_at?->format('d/m/Y H:i') ?? '—' }}
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                <a href="{{ route('admin.users.edit', $u) }}" class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-800 shadow-sm transition hover:border-brand-300 hover:text-brand-800">
                                                    {{ __('admin.users.edit') }}
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                @endif
            </x-ui.data-table>
            @if (! $users->isEmpty())
                <div class="rounded-2xl border border-t-0 border-slate-200/90 bg-white px-4 py-3 shadow-sm ring-1 ring-slate-100/80">
                    {{ $users->links() }}
                </div>
            @endif
        </x-page-container>
</x-ui.app-page>
</x-app-layout>
