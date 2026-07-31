<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\MailjetService;
use App\Support\MailjetSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class MailjetTestController extends Controller
{
    public function edit(MailjetService $mailjet): View
    {
        return view('admin.mailjet-test.index', [
            'configured' => $mailjet->configured(),
            'settings' => MailjetSettings::valuesForForm(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'api_key' => ['nullable', 'string', 'max:255'],
            'secret_key' => ['nullable', 'string', 'max:255'],
            'from_address' => ['nullable', 'email', 'max:255'],
            'from_name' => ['nullable', 'string', 'max:120'],
        ]);

        if (! MailjetSettings::hasCredentials()) {
            $request->validate([
                'api_key' => ['required', 'string', 'max:255'],
                'secret_key' => ['required', 'string', 'max:255'],
            ]);
        }

        MailjetSettings::saveFromInput($request->all());

        return redirect()
            ->route('admin.mailjet-test.edit')
            ->with('status', __('admin.mailjet_test.settings_saved'));
    }

    public function send(Request $request, MailjetService $mailjet): RedirectResponse
    {
        $validated = $request->validate([
            'from_email' => ['required', 'email', 'max:255'],
            'from_name' => ['nullable', 'string', 'max:120'],
            'to_email' => ['required', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:10000'],
        ]);

        try {
            $result = $mailjet->sendText(
                $validated['to_email'],
                $validated['subject'],
                $validated['body'],
                $validated['from_email'],
                $validated['from_name'] ?? null,
            );
        } catch (RuntimeException $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        } catch (Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->with('error', __('admin.mailjet_test.send_failed_generic'));
        }

        $messageId = data_get($result, 'Messages.0.To.0.MessageID')
            ?? data_get($result, 'Messages.0.MessageID');

        return back()->with('status', __('admin.mailjet_test.send_ok', [
            'to' => $validated['to_email'],
            'id' => $messageId !== null ? (string) $messageId : '—',
        ]));
    }
}
