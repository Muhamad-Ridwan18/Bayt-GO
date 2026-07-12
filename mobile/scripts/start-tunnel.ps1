# Expo Go tunnel for physical devices (QR / exp://).
# Preferred: Cloudflare quick tunnel (no account). Fallback: ngrok v3 + EXPO_NGROK_AUTHTOKEN in .env
# Optional: EXPO_TUNNEL_TARGET=dev-client (default: go)
$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path $PSScriptRoot -Parent
Set-Location $projectRoot

$port = 8081
$toolsDir = Join-Path $projectRoot '.tools'
$cloudflaredBin = Join-Path $toolsDir 'cloudflared.exe'
$ngrokBin = Join-Path $toolsDir 'ngrok.exe'
$ngrokZip = Join-Path $toolsDir 'ngrok-v3.zip'
$ngrokUrl = 'https://bin.equinox.io/c/bNyj1mQVY4c/ngrok-v3-stable-windows-amd64.zip'

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
  $tunnelHost = ([Uri]$publicUrl).Host
  return "exp://${tunnelHost}:80"
}

function Show-ConnectPage([string]$expoGoUrl, [string]$target) {
  $encoded = [Uri]::EscapeDataString($expoGoUrl)
  $qr = "https://api.qrserver.com/v1/create-qr-code/?size=400x400&data=$encoded"
  $androidHelp = if ($target -eq 'dev-client') {
    'Buka app dev build BaytGo, lalu Enter URL manually, paste link di bawah'
  } else {
    'Buka Expo Go, tap Scan QR code, arahkan ke QR di browser ini'
  }
  $iosHelp = if ($target -eq 'dev-client') {
    'Buka app dev build BaytGo, lalu Enter URL manually, paste link di bawah'
  } else {
    'Buka Camera iPhone, scan QR di browser ini, tap notifikasi Open in Expo Go'
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
    code, a { word-break: break-all; }
    .box { background: #f4f7fb; border-radius: 12px; padding: 16px; text-align: left; margin-top: 16px; }
  </style>
</head>
<body>
  <h1>BaytGo Dev Connect</h1>
  <img src="$qr" width="400" height="400" alt="QR Code" />
  <p><a href="$expoGoUrl">$expoGoUrl</a></p>
  <div class="box">
    <p><strong>Android:</strong> $androidHelp</p>
    <p><strong>iPhone:</strong> $iosHelp</p>
    <p>QR di terminal Windows sering susah discan. Pakai QR besar di halaman ini.</p>
  </div>
</body>
</html>
"@
  $path = Join-Path $projectRoot '.expo-connect.html'
  Set-Content -Path $path -Value $html -Encoding utf8
  Start-Process $path
  Write-Host "QR besar dibuka di browser: $path" -ForegroundColor Cyan
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

function Start-NgrokTunnel([string]$token) {
  Ensure-NgrokV3
  if (-not (Test-Path $ngrokBin)) { return $null }

  Write-Host "Starting ngrok v3 on port $port..." -ForegroundColor Cyan
  $env:NGROK_AUTHTOKEN = $token
  $logPath = Join-Path $env:TEMP "expo-ngrok-$port.log"
  Set-Content -Path $logPath -Value '' -Encoding ascii

  $proc = Start-Process -FilePath $ngrokBin -ArgumentList @(
    'http', "$port", '--log=stdout'
  ) -RedirectStandardOutput $logPath -PassThru -WindowStyle Hidden

  $deadline = (Get-Date).AddSeconds(45)
  while ((Get-Date) -lt $deadline) {
    try {
      $tunnels = (Invoke-RestMethod 'http://127.0.0.1:4040/api/tunnels' -TimeoutSec 2).tunnels
      $https = $tunnels | Where-Object { $_.proto -eq 'https' } | Select-Object -First 1
      if ($https.public_url) {
        return @{
          Url = $https.public_url
          Process = $proc
          Provider = 'ngrok'
        }
      }
      $http = $tunnels | Where-Object { $_.proto -eq 'http' } | Select-Object -First 1
      if ($http.public_url) {
        return @{
          Url = $http.public_url
          Process = $proc
          Provider = 'ngrok'
        }
      }
    } catch {}

    if ($proc.HasExited) {
      $tail = (Get-Content $logPath -Tail 8 -ErrorAction SilentlyContinue) -join "`n"
      throw "Ngrok exited early.`n$tail"
    }
    Start-Sleep -Milliseconds 500
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

Get-Process ngrok, cloudflared -ErrorAction SilentlyContinue | Stop-Process -Force -ErrorAction SilentlyContinue
Stop-ListenersOnPort $port

$tunnel = $null
try {
  $tunnel = Start-CloudflareTunnel
  if (-not $tunnel) {
    $token = Get-EnvValue 'EXPO_NGROK_AUTHTOKEN'
    if (-not $token) {
      throw "Cloudflare tunnel failed and EXPO_NGROK_AUTHTOKEN is missing in mobile/.env"
    }
    $tunnel = Start-NgrokTunnel $token
  }
  if (-not $tunnel) {
    throw "No tunnel could be started. Check internet connection and ngrok token."
  }

  $publicUrl = $tunnel.Url
  $expoGoUrl = Get-ExpoGoUrl $publicUrl

  $target = Get-EnvValue 'EXPO_TUNNEL_TARGET'
  if (-not $target) { $target = 'go' }

  Write-Host "Provider: $($tunnel.Provider)" -ForegroundColor Green
  Write-Host "Tunnel URL: $publicUrl" -ForegroundColor Green
  Write-Host "Expo URL:   $expoGoUrl" -ForegroundColor Green
  if ($target -eq 'dev-client') {
    Write-Host "Android/iPhone: buka app dev build BaytGo, Enter URL manually, paste Expo URL di atas." -ForegroundColor Yellow
  } else {
    Write-Host "Android: Expo Go - Scan QR code | iPhone: Camera app - tap notifikasi Expo Go" -ForegroundColor Yellow
    Write-Host "Expo Go tidak punya Enter URL manually. Pakai QR di browser yang akan dibuka." -ForegroundColor Yellow
  }
  Show-ConnectPage $expoGoUrl $target

  $env:EXPO_PACKAGER_PROXY_URL = $publicUrl
  Remove-Item Env:EXPO_NO_REDIRECT_PAGE -ErrorAction SilentlyContinue
  Remove-Item Env:EXPO_TUNNEL_SUBDOMAIN -ErrorAction SilentlyContinue

  $expoArgs = @('start', '--lan', '--port', "$port")
  if ($target -eq 'dev-client') {
    $expoArgs += '--dev-client'
    Write-Host "Target: development build" -ForegroundColor Cyan
  } else {
    $expoArgs += '--go'
    Write-Host "Target: Expo Go" -ForegroundColor Cyan
  }

  Write-Host "npx expo $($expoArgs -join ' ')" -ForegroundColor Cyan
  npx expo @expoArgs @args
} finally {
  Stop-TunnelProcess $tunnel
}
