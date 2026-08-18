<?php

namespace App\Support;

final class TransactionalEmailHtml
{
    public static function wrap(string $plainMessage, ?string $footnote = null): string
    {
        $appName = (string) config('app.name', 'BaytGo');
        $logoUrl = SiteBrand::logoPublicUrl();
        $lines = preg_split("/\r\n|\n|\r/", $plainMessage) ?: [];
        $blocks = [];
        $buffer = [];

        foreach ($lines as $line) {
            $trimmed = trim((string) $line);
            if ($trimmed === '') {
                if ($buffer !== []) {
                    $blocks[] = implode(' ', $buffer);
                    $buffer = [];
                }

                continue;
            }

            $clean = trim(str_replace(['*', '_', '~'], '', $trimmed));
            if ($clean === '') {
                continue;
            }

            if (preg_match('#^https?://#i', $clean) === 1) {
                if ($buffer !== []) {
                    $blocks[] = implode(' ', $buffer);
                    $buffer = [];
                }
                $blocks[] = ['url' => $clean];

                continue;
            }

            $buffer[] = $clean;
        }

        if ($buffer !== []) {
            $blocks[] = implode(' ', $buffer);
        }

        return view('emails.transactional', [
            'appName' => $appName,
            'logoUrl' => $logoUrl,
            'blocks' => $blocks,
            'footnote' => $footnote,
        ])->render();
    }
}
