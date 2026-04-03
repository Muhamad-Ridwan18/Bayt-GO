<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LogsController extends Controller
{
    public function index(Request $request): View
    {
        $n = (int) $request->query('n', 300);
        $n = max(50, min(2000, $n));

        $path = storage_path('logs/laravel.log');
        $error = null;
        $lines = [];

        if (! is_readable($path)) {
            $error = 'File log `storage/logs/laravel.log` tidak ditemukan atau tidak bisa dibaca.';
        } else {
            $lines = $this->tailLines($path, $n);
        }

        return view('admin.logs.index', [
            'lines' => $lines,
            'error' => $error,
            'n' => $n,
        ]);
    }

    public function clear(Request $request): RedirectResponse
    {
        $path = storage_path('logs/laravel.log');
        $n = (int) $request->query('n', 300);
        $n = max(50, min(2000, $n));

        if (is_string($path) && is_file($path) && is_writable($path)) {
            // Truncate file.
            file_put_contents($path, '');
        }

        return redirect()
            ->route('admin.logs.index', ['n' => $n])
            ->with('status', 'Log berhasil dibersihkan.');
    }

    /**
     * Tail N lines tanpa membaca seluruh file.
     *
     * @return list<string>
     */
    private function tailLines(string $path, int $n): array
    {
        $fp = new \SplFileObject($path, 'r');
        $fp->setFlags(\SplFileObject::DROP_NEW_LINE);

        $fp->seek(PHP_INT_MAX);
        $lastIndex = $fp->key();
        $startIndex = max(0, $lastIndex - $n + 1);

        $result = [];
        for ($i = $startIndex; $i <= $lastIndex; $i++) {
            $fp->seek($i);
            $result[] = (string) $fp->current();
        }

        return $result;
    }
}

