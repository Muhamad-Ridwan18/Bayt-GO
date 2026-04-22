# Uji cepat: URL acak *.trycloudflare.com — tidak perlu `tunnel login` / DNS.
# Pastikan Laravel sudah jalan: php artisan serve (default http://127.0.0.1:8000)
param(
    [string] $LocalUrl = "http://127.0.0.1:8000"
)

$exe = Join-Path $env:USERPROFILE ".cloudflared\cloudflared.exe"
if (-not (Test-Path $exe)) {
    Write-Error "cloudflared belum ada di $exe — unduh dulu dari https://github.com/cloudflare/cloudflared/releases (Windows amd64) atau jalankan langkah di README tunnel."
    exit 1
}

Write-Host "Membuka tunnel ke $LocalUrl (biarkan jendela ini terbuka). Tekan Ctrl+C untuk hentikan." -ForegroundColor Cyan
& $exe tunnel --url $LocalUrl
