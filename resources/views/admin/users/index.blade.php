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
    <div class="relative min-h-[calc(100vh-4rem)] overflow-hidden bg-gradient-to-b from-slate-100 via-slate-50 to-white py-8 sm:py-12">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_80%_40%_at_50%_-10%,rgba(120,53,15,0.06),transparent)]"></div>
        <div class="relative mx-auto max-w-6xl space-y-8 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900 shadow-sm">
                    {{ session('status') }}
                </div>
            @endif

            <div class="relative overflow-hidden rounded-[1.75rem] bg-gradient-to-br from-slate-900 via-brand-900 to-slate-950 p-6 text-white shadow-[0_25px_50px_-12px_rgba(15,23,42,0.4)] ring-1 ring-white/10 sm:rounded-3xl sm:p-8">
                <div class="pointer-events-none absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width=\'40\' height=\'40\' viewBox=\'0 0 40 40\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.03\'%3E%3Cpath d=\'M20 20h20v20H20zM0 0h20v20H0z\'/%3E%3C/g%3E%3C/svg%3E')] opacity-60"></div>
                <div class="pointer-events-none absolute -right-16 top-1/2 h-64 w-64 -translate-y-1/2 rounded-full bg-violet-500/20 blur-3xl"></div>
                <div class="relative flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
                    <div class="flex items-start gap-4">
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-white/15 ring-1 ring-white/25" aria-hidden="true">
                            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.813-2.387M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>
                        </span>
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wider text-brand-200/90">{{ __('admin.users.badge') }}</p>
                            <h1 class="mt-1 text-2xl font-bold tracking-tight sm:text-3xl">{{ __('admin.users.title') }}</h1>
                            <p class="mt-2 max-w-xl text-sm leading-relaxed text-white/75">{{ __('admin.users.subtitle') }}</p>
                        </div>
                    </div>
                    <a href="{{ route('dashboard') }}" class="inline-flex shrink-0 items-center gap-2 self-start rounded-2xl border border-white/20 bg-white/10 px-4 py-2.5 text-sm font-semibold text-white backdrop-blur-sm transition hover:bg-white/20">
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M9.293 2.293a1 1 0 011.414 0l7 7A1 1 0 0117 11h-1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-3a1 1 0 00-1-1H9a1 1 0 00-1 1v3a1 1 0 01-1 1H5a1 1 0 01-1-1v-6H3a1 1 0 01-.707-1.707l7-7z" clip-rule="evenodd" /></svg>
                        {{ __('admin.users.back_dashboard') }}
                    </a>
                </div>

                <div class="relative mt-8 grid grid-cols-2 gap-3 sm:grid-cols-4">
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
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm ring-1 ring-slate-100/80">
                <form method="get" action="{{ route('admin.users.index') }}" class="border-b border-slate-100 bg-slate-50/80 px-5 py-4">
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
                            <button type="submit" class="inline-flex flex-1 items-center justify-center rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 sm:flex-none">
                                {{ __('admin.users.apply') }}
                            </button>
                            <a href="{{ route('admin.users.index') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                                {{ __('admin.users.reset') }}
                            </a>
                        </div>
                    </div>
                </form>

                @if ($users->isEmpty())
                    <p class="p-10 text-center text-sm text-slate-500">{{ __('admin.users.empty') }}</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="px-4 py-3">{{ __('admin.users.col_name') }}</th>
                                    <th class="px-4 py-3">{{ __('admin.users.col_contact') }}</th>
                                    <th class="px-4 py-3">{{ __('admin.users.col_role') }}</th>
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
                                        <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-600">
                                            {{ $u->created_at?->format('d/m/Y H:i') ?? '—' }}
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <a href="{{ route('admin.users.edit', $u) }}" class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-800 shadow-sm transition hover:border-brand-300 hover:text-brand-800">
                                                {{ __('admin.users.edit') }}
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t border-slate-100 px-4 py-3">
                        {{ $users->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
