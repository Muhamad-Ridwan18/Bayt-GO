<?php

namespace App\Support;

/**
 * Bersihkan nomor telepon / WhatsApp dari teks bio publik muthowif.
 */
final class PublicBioText
{
    public static function withoutContactNumbers(?string $text): ?string
    {
        if ($text === null) {
            return null;
        }

        $trimmed = trim($text);
        if ($trimmed === '') {
            return null;
        }

        $cleaned = preg_replace(
            '#(?:https?://)?(?:wa\.me|api\.whatsapp\.com)/\S+#iu',
            '',
            $trimmed,
        ) ?? $trimmed;

        // Nomor internasional/lokal: +62… / 08… dengan spasi, strip, atau titik.
        $cleaned = preg_replace(
            '/(?<!\d)(?:\+|00)?(?:\d[\s\-.]*){8,}\d(?!\d)/u',
            '',
            $cleaned,
        ) ?? $cleaned;

        $lines = preg_split('/\R/u', $cleaned) ?: [];
        $kept = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                $kept[] = '';

                continue;
            }

            // Sisa label kontak saja (mis. "📞 WhatsApp:" setelah nomor dihapus).
            if (preg_match(
                '/^(?:[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}\x{FE0F}\x{200D}\s]*)*(?:whatsapp|wa|telp\.?|telepon|hp|no\.?\s*hp|phone|contact|hubungi)[\s:：.\-]*$/iu',
                $line,
            ) === 1) {
                continue;
            }

            $kept[] = $line;
        }

        $result = preg_replace("/\n{3,}/u", "\n\n", implode("\n", $kept)) ?? '';
        $result = trim($result);

        return $result === '' ? null : $result;
    }
}
