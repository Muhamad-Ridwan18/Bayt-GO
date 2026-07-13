# Expo Go / dev client over the internet (multi-device friendly).
# Recommended: EXPO_NGROK_AUTHTOKEN in .env - starts ngrok v3 + expo proxy (stable for many devices).
# Fallback: Cloudflare quick tunnel (URL changes every restart; limited concurrent devices).
# Optional: EXPO_TUNNEL_TARGET=dev-client | EXPO_TUNNEL_PROVIDER=cloudflare|ngrok
param(
  [string]$Provider
)
$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path $PSScriptRoot -Parent
Set-Location $projectRoot

$port = 8081
$toolsDir = Join-Path $projectRoot '.tools'
$cloudflaredBin = Join-Path $toolsDir 'cloudflared.exe'
$ngrokBin = Join-Path $toolsDir 'ngrok.exe'
$ngrokZip = Join-Path $toolsDir 'ngrok-v3.zip'
$ngrokUrl = 'https://bin.equinox.io/c/bNyj1mQVY4c/ngrok-v3-stable-windows-amd64.zip'
$tunnelUrlFile = Join-Path $projectRoot '.expo-tunnel-url'

function Get-EnvValue([string]$name) {
  $envFile = Join-Path $projectRoot '.env'
  if (-not (Test-Path $envFile)) { return $null }
  foreach ($line in Get-Content $envFile) {
    if ($line -match "^\s*$name\s*=\s*(.+)\s*$") {
      return $Matches[1].Trim().Trim('"').Trim("'")
    }
  }
  return $null
}

function Stop-ListenersOnPort([int]$listenPort) {
  Get-NetTCPConnection -LocalPort $listenPort -State Listen -ErrorAction SilentlyContinue |
    ForEach-Object {
      $proc = Get-Process -Id $_.OwningProcess -ErrorAction SilentlyContinue
      if ($proc -and $proc.ProcessName -in @('node')) {
        Stop-Process -Id $proc.Id -Force -ErrorAction SilentlyContinue
      }
    }
}

function Ensure-NgrokV3 {
  if (Test-Path $ngrokBin) {
    $version = & $ngrokBin version 2>&1 | Select-Object -First 1
    if ($version -match 'ngrok version 3\.') { return }
    Remove-Item $ngrokBin -Force -ErrorAction SilentlyContinue
  }
  Write-Host "Downloading ngrok v3..." -ForegroundColor Cyan
  Invoke-WebRequest -Uri $ngrokUrl -OutFile $ngrokZip -UseBasicParsing
  Add-Type -AssemblyName System.IO.Compression.FileSystem
  [System.IO.Compression.ZipFile]::ExtractToDirectory($ngrokZip, $toolsDir)
  Remove-Item $ngrokZip -Force -ErrorAction SilentlyContinue
}

function Get-ExpoGoUrl([string]$publicUrl) {
  $uri = [Uri]$publicUrl
  return "exp://$($uri.Host)"
}

function Write-TunnelUrlFile([string]$publicUrl, [string]$expoGoUrl, [string]$provider) {
  $content = @(
    "provider=$provider"
    "public_url=$publicUrl"
    "expo_url=$expoGoUrl"
    "updated_at=$(Get-Date -Format o)"
    ""
    "Semua device harus pakai URL di atas. Tutup Expo Go lalu scan QR ulang jika tunnel di-restart."
  ) -join "`n"
  Set-Content -Path $tunnelUrlFile -Value $content -Encoding utf8
}

function Show-ConnectPage([string]$expoGoUrl, [string]$publicUrl, [string]$target, [string]$provider) {
  $encoded = [Uri]::EscapeDataString($expoGoUrl)
  $qr = 'https://api.qrserver.com/v1/create-qr-code/?size=400x400&data=' + $encoded
  $androidHelp = if ($target -eq 'dev-client') {
    'Buka app dev build BaytGo - Enter URL manually - paste Expo URL'
  } else {
    'Expo Go - Scan QR code (atau Camera di iPhone)'
  }
  $multiDevice = if ($provider -eq 'cloudflare') {
    '<p><strong>Multi-device:</strong> Cloudflare gratis kadang putus jika banyak HP sekaligus. Jika error Could not connect, scan QR ulang dari sesi tunnel terbaru atau pakai ngrok.</p>'
  } else {
    '<p><strong>Multi-device:</strong> Scan QR yang sama di semua HP. Jika tunnel di-restart, semua device wajib scan ulang (URL lama tidak valid).</p>'
  }
  $html = @"
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>BaytGo Connect</title>
  <style>
    body { font-family: Segoe UI, sans-serif; max-width: 520px; margin: 0 auto; padding: 24px; text-align: center; }
    img { display: block; margin: 20px auto; border: 1px solid #ddd; border-radius: 12px; }
    code, a { word-break: break-all; font-size: 13px; }
    .box { background: #f4f7fb; border-radius: 12px; padding: 16px; text-align: left; margin-top: 16px; font-size: 14px; }
    .warn { background: #fff7ed; border: 1px solid #fdba74; }
  </style>
</head>
<body>
  <h1>BaytGo Dev Connect</h1>
  <p>Provider: <strong>$provider</strong></p>
  <img src="$qr" width="400" height="400" alt="QR Code" />
  <p><a href="$expoGoUrl">$expoGoUrl</a></p>
  <p><code>$publicUrl</code></p>
  <div class="box warn">$multiDevice</div>
  <div class="box">
    <p><strong>Android:</strong> $androidHelp</p>
    <p><strong>iPhone:</strong> $androidHelp</p>
    <p>Scan QR dari terminal PC (bukan halaman browser ini).</p>
    <p>Expo Go butuh HTTPS. ERR_NGROK_3200 = tunnel mati atau masih pakai http:// saja.</p>
  </div>
</body>
</html>
"@
  $path = Join-Path $projectRoot '.expo-connect.html'
  Set-Content -Path $path -Value $html -Encoding utf8
  Start-Process $path
  Write-Host "QR besar: $path" -ForegroundColor Cyan
}

function Start-NgrokTunnel([string]$token) {
  Ensure-NgrokV3
  & $ngrokBin config add-authtoken $token 2>&1 | Out-Null

  Write-Host "Starting ngrok tunnel on port $port..." -ForegroundColor Cyan
  $proc = Start-Process -FilePath $ngrokBin -ArgumentList @(
    'http', "127.0.0.1:$port", '--host-header=rewrite', '--log', 'stdout', '--log-format', 'json'
  ) -PassThru -WindowStyle Hidden

  $deadline = (Get-Date).AddSeconds(45)
  while ((Get-Date) -lt $deadline) {
    try {
      $resp = Invoke-RestMethod -Uri 'http://127.0.0.1:4040/api/tunnels' -TimeoutSec 2 -ErrorAction Stop
      $url = ($resp.tunnels | Where-Object { $_.proto -eq 'https' } | Select-Object -First 1).public_url
      if (-not $url) {
        $url = ($resp.tunnels | Where-Object { $_.proto -eq 'http' } | Select-Object -First 1).public_url
      }
      if ($url) {
        return @{
          Url = $url
          Process = $proc
          Provider = 'ngrok'
        }
      }
    } catch {}
    if ($proc.HasExited) { break }
    Start-Sleep -Milliseconds 400
  }

  if ($proc -and -not $proc.HasExited) {
    Stop-Process -Id $proc.Id -Force -ErrorAction SilentlyContinue
  }
  return $null
}

function Start-CloudflareTunnel {
  if (-not (Test-Path $cloudflaredBin)) { return $null }

  Write-Host "Starting Cloudflare tunnel on port $port..." -ForegroundColor Cyan
  $logPath = Join-Path $env:TEMP "expo-cloudflared-$port.log"
  Set-Content -Path $logPath -Value '' -Encoding ascii

  $proc = Start-Process -FilePath $cloudflaredBin -ArgumentList @(
    'tunnel', '--url', "http://127.0.0.1:$port", '--no-autoupdate'
  ) -RedirectStandardError $logPath -PassThru -WindowStyle Hidden

  $deadline = (Get-Date).AddSeconds(45)
  while ((Get-Date) -lt $deadline) {
    $text = Get-Content $logPath -Raw -ErrorAction SilentlyContinue
    if ($text -match '(https://[a-z0-9-]+\.trycloudflare\.com)') {
      return @{
        Url = $Matches[1]
        Process = $proc
        Provider = 'cloudflare'
      }
    }
    if ($proc.HasExited) { break }
    Start-Sleep -Milliseconds 400
  }

  if ($proc -and -not $proc.HasExited) {
    Stop-Process -Id $proc.Id -Force -ErrorAction SilentlyContinue
  }
  return $null
}

function Stop-TunnelProcess($tunnel) {
  if (-not $tunnel) { return }
  if ($tunnel.Process -and -not $tunnel.Process.HasExited) {
    Stop-Process -Id $tunnel.Process.Id -Force -ErrorAction SilentlyContinue
  }
  if ($tunnel.Provider -eq 'ngrok') {
    Get-Process ngrok -ErrorAction SilentlyContinue | Stop-Process -Force -ErrorAction SilentlyContinue
  }
  if ($tunnel.Provider -eq 'cloudflare') {
    Get-Process cloudflared -ErrorAction SilentlyContinue | Stop-Process -Force -ErrorAction SilentlyContinue
  }
}

function Start-ExpoWithProxy([string]$publicUrl, [string]$target, [string]$provider) {
  $hostName = ([Uri]$publicUrl).Host
  $expoGoUrl = Get-ExpoGoUrl $publicUrl

  $env:EXPO_PACKAGER_PROXY_URL = $publicUrl
  $env:REACT_NATIVE_PACKAGER_HOSTNAME = $hostName
  $env:EXPO_NO_REDIRECT_PAGE = '1'
  Remove-Item Env:EXPO_TUNNEL_SUBDOMAIN -ErrorAction SilentlyContinue
  Remove-Item Env:EXPO_NGROK_AUTHTOKEN -ErrorAction SilentlyContinue
  Remove-Item Env:NGROK_AUTHTOKEN -ErrorAction SilentlyContinue

  Write-TunnelUrlFile $publicUrl $expoGoUrl $provider

  $expoArgs = @('start', '--lan', '--port', "$port")
  if ($target -eq 'dev-client') {
    $expoArgs += '--dev-client'
  } else {
    $expoArgs += '--go'
  }

  Write-Host "Tunnel host: $hostName" -ForegroundColor Green
  Write-Host "Expo URL:    $expoGoUrl" -ForegroundColor Green
  Write-Host "Proxy URL:   $publicUrl" -ForegroundColor Green
  Write-Host "Expo Go: Enter URL manually -> $expoGoUrl" -ForegroundColor Cyan
  Write-Host "Jangan buka link /_expo/loading di Safari (error expo-platform)." -ForegroundColor Yellow
  Write-Host "Scan QR dari dalam app Expo Go, bukan Camera iPhone." -ForegroundColor Yellow
  Write-Host "npx expo $($expoArgs -join ' ')" -ForegroundColor Cyan
  npx expo @expoArgs @args
}

Get-Process ngrok, cloudflared -ErrorAction SilentlyContinue | Stop-Process -Force -ErrorAction SilentlyContinue
Stop-ListenersOnPort $port

$target = Get-EnvValue 'EXPO_TUNNEL_TARGET'
if (-not $target) { $target = 'go' }

$provider = if ($Provider) { $Provider } else { Get-EnvValue 'EXPO_TUNNEL_PROVIDER' }
$ngrokToken = Get-EnvValue 'EXPO_NGROK_AUTHTOKEN'
if (-not $ngrokToken) { $ngrokToken = Get-EnvValue 'NGROK_AUTHTOKEN' }

if (-not $provider -and $ngrokToken) {
  $provider = 'ngrok'
}

$tunnel = $null
try {
  if ($provider -eq 'ngrok') {
    if (-not $ngrokToken) {
      throw 'EXPO_TUNNEL_PROVIDER=ngrok but EXPO_NGROK_AUTHTOKEN is missing in mobile/.env'
    }
    Write-Host "Using ngrok v3 tunnel (recommended for multiple devices)..." -ForegroundColor Cyan
    $tunnel = Start-NgrokTunnel $ngrokToken
    if (-not $tunnel) {
      Write-Host "ngrok failed, falling back to Cloudflare..." -ForegroundColor Yellow
      $tunnel = Start-CloudflareTunnel
    }
  } else {
    $tunnel = Start-CloudflareTunnel
    if (-not $tunnel -and $ngrokToken) {
      Write-Host "Cloudflare unavailable, falling back to ngrok..." -ForegroundColor Yellow
      $tunnel = Start-NgrokTunnel $ngrokToken
    }
  }

  if (-not $tunnel) {
    throw "No tunnel available. Install cloudflared in mobile/.tools or set EXPO_NGROK_AUTHTOKEN in .env"
  }

  Write-Host "Scan QR dari terminal Expo (bukan riwayat lama). URL berubah tiap restart." -ForegroundColor Yellow
  Start-ExpoWithProxy $tunnel.Url $target $tunnel.Provider
} finally {
  Stop-TunnelProcess $tunnel
}
