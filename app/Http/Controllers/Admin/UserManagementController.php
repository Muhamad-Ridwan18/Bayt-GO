<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdminUpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    public function index(Request $request): View
    {
        $roleFilter = $request->query('role');
        $q = trim((string) $request->query('q', ''));

        $query = User::query()->orderByDesc('created_at');

        if ($q !== '') {
            $like = '%'.$q.'%';
            $query->where(function ($sub) use ($like): void {
                $sub->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('phone', 'like', $like);
            });
        }

        if (is_string($roleFilter) && $roleFilter !== '' && $roleFilter !== 'all') {
            $role = UserRole::tryFrom($roleFilter);
            if ($role) {
                $query->where('role', $role);
            }
        }

        $users = $query->paginate(20)->withQueryString();

        $stats = [
            'total' => 0,
            'admin' => 0,
            'customer' => 0,
            'muthowif' => 0,
        ];
        $roleRows = User::query()
            ->select('role', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('role')
            ->get();
        foreach ($roleRows as $row) {
            $c = (int) $row->aggregate;
            $stats['total'] += $c;
            $role = $row->role instanceof UserRole ? $row->role : UserRole::from((string) $row->role);
            match ($role) {
                UserRole::Admin => $stats['admin'] += $c,
                UserRole::Customer => $stats['customer'] += $c,
                UserRole::Muthowif => $stats['muthowif'] += $c,
            };
        }

        return view('admin.users.index', [
            'users' => $users,
            'stats' => $stats,
            'roleFilter' => is_string($roleFilter) ? $roleFilter : '',
            'q' => $q,
        ]);
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', [
            'editUser' => $user,
        ]);
    }

    public function update(AdminUpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }

        unset($data['password'], $data['password_confirmation']);

        $roleRaw = $data['role'] ?? null;
        $role = $roleRaw instanceof UserRole ? $roleRaw : UserRole::from((string) $roleRaw);

        if ($role !== UserRole::Customer) {
            $data['customer_type'] = null;
            $data['ppui_number'] = null;
        }

        $user->fill($data);
        $user->save();

        return redirect()
            ->route('admin.users.index')
            ->with('status', __('admin.users.updated'));
    }
}
