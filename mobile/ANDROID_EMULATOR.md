# Menjalankan Android Emulator (BaytGo Mobile)

Panduan setup lokal di mesin Windows ini. SDK disimpan di **`D:\Android`** karena drive **C:** hampir penuh.

## Prasyarat

- Java JDK terpasang (`JAVA_HOME` misalnya `C:\Program Files\Java\jdk-21`)
- Expo project di `D:\DEV\laragon\www\baytgo\mobile`
- Paket SDK yang dibutuhkan sudah terpasang di `D:\Android`:
  - `platform-tools`
  - `emulator`
  - `platforms;android-35`
  - `build-tools;35.0.0`
  - `system-images;android-35;google_apis;x86_64`
- AVD: **`Pixel_7_API_35`** (lokasi: `D:\Android\avd`)

## Environment (wajib tiap sesi terminal baru)

Jalankan di PowerShell sebelum `emulator` / `adb` / `expo`:

```powershell
$env:ANDROID_HOME = "D:\Android"
$env:ANDROID_SDK_ROOT = "D:\Android"
$env:ANDROID_AVD_HOME = "D:\Android\avd"
$env:ANDROID_EMULATOR_HOME = "D:\Android\.android"

# Hindari menulis temp ke C: yang penuh
New-Item -ItemType Directory -Path "D:\Temp" -Force | Out-Null
$env:TEMP = "D:\Temp"
$env:TMP = "D:\Temp"

$env:Path = "D:\Android\cmdline-tools\latest\bin;D:\Android\platform-tools;D:\Android\emulator;" + $env:Path
```

Cek cepat:

```powershell
adb version
emulator -list-avds
# harus muncul: Pixel_7_API_35
```

## 1) Start emulator

```powershell
emulator -avd Pixel_7_API_35 -no-metrics -gpu swiftshader_indirect
```

Tunggu sampai home screen Android muncul (cold boot pertama bisa 1–3 menit).

Cek device siap:

```powershell
adb devices
adb shell getprop sys.boot_completed
# siap jika output: 1
```

## Test push notification di emulator

Di app (__DEV__): **Profil → Test Push Notification**

1. **Local notification** — tekan "Kirim lokal (2 detik)". Banner muncul di emulator (jalan di Expo Go & development build). Tap notifikasi untuk uji handler navigasi chat.
2. **Remote push** — butuh development build + (idealnya) Google Play Services:
   - Ambil token di layar test
   - Dari PC:
     ```powershell
     cd D:\DEV\laragon\www\baytgo\mobile
     npm run push:test -- --token "ExponentPushToken[...]"
     ```

Tanpa development build, hanya langkah 1 yang bisa diverifikasi di emulator.

Mulai **Expo SDK 53**, **Expo Go tidak mendukung remote push** (`expo-notifications`).

Pesan yang muncul:

> Android Push notifications (remote notifications) functionality provided by expo-notifications was removed from Expo Go...

**Solusi:** install **development build** (`id.baytgo.app` + `expo-dev-client`), bukan Expo Go.

Emulator tetap berguna, tapi app yang dijalankan harus development build.

### A) Build development APK via EAS (disarankan)

Tidak makan banyak space di C: (build di cloud).

```powershell
cd D:\DEV\laragon\www\baytgo\mobile

# login sekali
npx eas-cli login

# build APK development client
npx eas-cli build -p android --profile development
```

Setelah build selesai, download APK lalu:

```powershell
adb install -r path\to\baytgo-dev.apk
```

Atau biarkan EAS kasih link install.

### B) Build lokal ke emulator (`expo run:android`)

Butuh ruang disk besar (Gradle biasanya di C:). Hanya jika C: longgar.

```powershell
# set env Android dulu (lihat atas)
cd D:\DEV\laragon\www\baytgo\mobile
npx expo run:android
```

Ini akan generate native project, build, dan install `id.baytgo.app` ke emulator yang sedang jalan.

### C) Jalankan Metro setelah development build terpasang

```powershell
cd D:\DEV\laragon\www\baytgo\mobile
npx expo start --dev-client
# lalu tekan `a` atau buka app BaytGo di emulator
```

Jangan pakai `--go` kalau mau test push.

### Catatan FCM di emulator

Push Android memakai Google Play Services (FCM).

- Lebih andal di **HP fisik**
- Emulator lebih baik pakai system image **Google Play** (`google_apis_playstore`), bukan hanya `google_apis`
- Di emulator tanpa Play Services, token push bisa gagal meski development build sudah benar

Install image Play Store (opsional):

```powershell
sdkmanager --sdk_root=D:\Android "system-images;android-35;google_apis_playstore;x86_64"
echo no | avdmanager create avd -n Pixel_7_API_35_Play -k "system-images;android-35;google_apis_playstore;x86_64" --force
emulator -avd Pixel_7_API_35_Play -no-metrics -gpu swiftshader_indirect
```

## 2) Expo Go (hanya UI cepat — TANPA remote push)

```powershell
curl.exe -L -o D:\Android\apk\expo-go.apk `
  https://github.com/expo/expo-go-releases/releases/download/Expo-Go-54.0.8/Expo-Go-54.0.8.apk
adb install -r D:\Android\apk\expo-go.apk

cd D:\DEV\laragon\www\baytgo\mobile
npx expo start --go --android
```

## 3) Alternatif: HP fisik + development build

Paling praktis untuk push:

```powershell
npx eas-cli build -p android --profile development
# install APK ke HP
npx expo start --dev-client
# scan QR / buka app BaytGo
```

Atau tunnel:

```powershell
npm run start:tunnel
```

(pastikan yang dibuka app development build, bukan Expo Go)

## Install / update paket SDK (jika perlu)

```powershell
$env:ANDROID_HOME = "D:\Android"
$env:Path = "D:\Android\cmdline-tools\latest\bin;" + $env:Path

sdkmanager --sdk_root=D:\Android --licenses

sdkmanager --sdk_root=D:\Android `
  "platform-tools" `
  "emulator" `
  "platforms;android-35" `
  "build-tools;35.0.0" `
  "system-images;android-35;google_apis;x86_64"
```

## Buat ulang AVD

```powershell
$env:ANDROID_HOME = "D:\Android"
$env:ANDROID_AVD_HOME = "D:\Android\avd"
$env:Path = "D:\Android\cmdline-tools\latest\bin;D:\Android\emulator;" + $env:Path

echo no | avdmanager create avd `
  -n Pixel_7_API_35 `
  -k "system-images;android-35;google_apis;x86_64" `
  --force
```

Opsional: kecilkan disk AVD agar lebih hemat ruang

Edit `D:\Android\avd\Pixel_7_API_35.avd\config.ini`:

```ini
disk.dataPartition.size=2G
hw.ramSize=1536
```

## Troubleshooting

### `sdkmanager` / `emulator` tidak dikenali

Path belum di-set di sesi terminal itu. Jalankan blok **Environment** di atas, atau buka terminal baru setelah PATH user di-update.

### `Could not determine SDK root`

Struktur yang benar:

```text
D:\Android\cmdline-tools\latest\bin\sdkmanager.bat
```

Atau selalu pakai:

```powershell
sdkmanager --sdk_root=D:\Android ...
```

### `not enough disk space` saat start emulator

- Drive **C:** harus punya ruang cukup, atau
- Pastikan AVD + TEMP di **D:** (`ANDROID_AVD_HOME`, `TEMP`/`TMP` seperti di atas)

Cek ruang:

```powershell
wmic logicaldisk get caption,freespace,size
```

### Emulator `offline` di `adb devices`

Tunggu boot selesai, lalu:

```powershell
adb kill-server
adb start-server
adb devices
```

### Vulkan / GPU warning (AMD)

Normal di mesin ini. Pakai:

```powershell
emulator -avd Pixel_7_API_35 -no-metrics -gpu swiftshader_indirect
```

### Alternatif tanpa emulator (lebih cepat untuk push)

Pakai **HP fisik + development build** (bukan Expo Go):

```powershell
cd D:\DEV\laragon\www\baytgo\mobile
npx eas-cli build -p android --profile development
# install APK hasil build ke HP, lalu:
npx expo start --dev-client
# atau
npm run start:tunnel
```

## Referensi path penting

| Item | Path |
|------|------|
| SDK root | `D:\Android` |
| cmdline-tools | `D:\Android\cmdline-tools\latest\bin` |
| platform-tools (`adb`) | `D:\Android\platform-tools` |
| emulator | `D:\Android\emulator` |
| AVD home | `D:\Android\avd` |
| Temp disarankan | `D:\Temp` |
| Project mobile | `D:\DEV\laragon\www\baytgo\mobile` |
