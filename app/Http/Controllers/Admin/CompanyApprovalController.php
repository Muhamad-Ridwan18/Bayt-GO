<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendWhatsAppTextJob;
use App\Models\User;
use App\Support\CompanyApprovedBroadcast;
use App\Support\IntlPhone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompanyApprovalController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status', 'pending');
        if (! in_array($status, ['pending', 'approved', 'all'], true)) {
            $status = 'pending';
        }

        $query = User::query()
            ->whereNotNull('customer_type')
            ->where('customer_type', 'company')
            ->orderByDesc('created_at');

        if ($status === 'pending') {
            $query->where('is_company_approved', false);
        } elseif ($status === 'approved') {
            $query->where('is_company_approved', true);
        }

        $counts = [
            'pending' => User::where('customer_type', 'company')->where('is_company_approved', false)->count(),
            'approved' => User::where('customer_type', 'company')->where('is_company_approved', true)->count(),
        ];

        return view('admin.company-approval.index', [
            'companies' => $query->paginate(15)->withQueryString(),
            'currentStatus' => $status,
            'counts' => $counts,
        ]);
    }

    public function approve(User $user): RedirectResponse
    {
        if (!$user->isCompanyCustomer()) {
            return back()->with('error', 'User bukan customer perusahaan.');
        }

        if ($user->is_company_approved) {
            return back()->with('error', 'Perusahaan ini sudah disetujui sebelumnya.');
        }

        $user->is_company_approved = true;
        $user->save();

        if ($user->phone) {
            $fonnteDial = IntlPhone::fonnteDial($user->phone);
            if ($fonnteDial !== null) {
                $message = "Halo *{$user->name}*,\n\nAkun perusahaan Anda telah *disetujui* oleh Administrator. Anda sekarang sudah bisa masuk (login) ke website ".config('app.name', 'BaytGo')." dan mulai menggunakan layanan kami.\n\nTerima kasih!";
                SendWhatsAppTextJob::dispatchAfterResponse(
                    $fonnteDial['target'],
                    $message,
                    $fonnteDial['country_calling_code'],
                );
            }
        }

        CompanyApprovedBroadcast::afterResponse($user);

        return redirect()->route('admin.company_approval.index')->with('status', 'Akun perusahaan berhasil disetujui.');
    }
}
