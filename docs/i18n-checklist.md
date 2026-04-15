# Checklist dua bahasa (ID / EN)

Dokumen ini memetakan **halaman / area UI** yang sudah memakai `__()` / file `lang/`, yang **sebagian**, dan yang **belum** — supaya perubahan i18n tidak ada yang terlewat.

**Legenda**

| Simbol | Arti |
|--------|------|
| ✅ | Teks UI utama sudah lewat `lang/en` & `lang/id` (atau setara) |
| 🔶 | Sebagian sudah i18n; banyak string masih hardcoded |
| ❌ | Belum i18n (masih bahasa tunggal / campur di Blade) |

**File terjemahan** (non-lengkap): `dashboard.php`, `dashboard_muthowif.php`, `nav.php`, `layanan.php`, `marketplace.php`, `welcome.php`, `guest.php`, `bookings.php`, `enums.php`, `common.php`, `muthowif.php`, `auth_custom.php`, `profile_public.php`, `admin.php`, `auth_otp.php`, `whatsapp.php`, dll.

---

## Publik & landing

| Route / halaman | View utama | Status | Catatan |
|-----------------|------------|--------|---------|
| `/` | `welcome.blade.php` | ✅ | `welcome.*` |
| `/layanan` | `layanan/index.blade.php` + partials | ✅ | `layanan.*` |
| `/layanan/{id}` | `layanan/show.blade.php` + partials | ✅ | `marketplace.*` |
| Layout tamu marketplace | `components/marketplace-layout.blade.php` | ✅ | Footer + nav |

---

## Autentikasi & onboarding

| Route (contoh) | View | Status | Catatan |
|------------------|------|--------|---------|
| `login` | `auth/login.blade.php` | 🔶 | Breeze defaults + sebagian custom |
| `register` | `auth/register.blade.php` + `auth/partials/register-otp.blade.php` | 🔶 | OTP + JS fallback: `auth_otp.*`; form utama & copy muthowif/jamaah masih banyak ID |
| `forgot-password` | `auth/forgot-password.blade.php` | 🔶 | `auth_custom.*` |
| `reset-password` | `auth/reset-password.blade.php` | 🔶 | |
| `verify-email` | `auth/verify-email.blade.php` | 🔶 | |
| `confirm-password` | `auth/confirm-password.blade.php` | 🔶 | |
| `/muthowif/daftar/menunggu` | `auth/muthowif-registration-pending.blade.php` | 🔶 | `auth_custom.*` |
| Layout tamu | `layouts/guest.blade.php` | ✅ | `guest.*` |

---

## Dashboard & navigasi (login)

| Halaman | View | Status | Catatan |
|---------|------|--------|---------|
| `/dashboard` | `dashboard.blade.php` + partials | ✅ | `dashboard.*` & `dashboard_muthowif.*` |
| Semua halaman app | `layouts/navigation.blade.php` | ✅ | `nav.*` + switcher bahasa |
| Shell app | `layouts/app.blade.php` | ✅ | Minim teks user-facing |

---

## Profil akun

| Route | View | Status | Catatan |
|-------|------|--------|---------|
| `/profile` | `profile/edit.blade.php` | 🔶 | |
| | `profile/partials/update-profile-information-form.blade.php` | 🔶 | |
| | `profile/partials/update-password-form.blade.php` | 🔶 | |
| | `profile/partials/update-public-profile-form.blade.php` | ✅ | `profile_public.*` |
| | `profile/partials/delete-user-form.blade.php` | 🔶 | |

---

## Jamaah — booking

| Route | View | Status | Catatan |
|-------|------|--------|---------|
| `/bookings` | `bookings/index.blade.php` | ✅ | `bookings.*` |
| `/bookings/{id}` | `bookings/show.blade.php` | ✅ | `bookings.*` |
| `/bookings/{id}/pembayaran` | `bookings/payment*.blade.php` | ✅ | |
| Invoice | `bookings/invoice.blade.php` | ✅ | |

---

## Muthowif (verified)

| Route | View | Status | Catatan |
|-------|------|--------|---------|
| `/muthowif/pelayanan` | `muthowif/pelayanan/edit.blade.php` | 🔶 | |
| `/muthowif/jadwal` | `muthowif/jadwal/index.blade.php` | 🔶 | |
| `/muthowif/bookings` | `muthowif/bookings/index.blade.php` | ✅ | `muthowif.bookings.*` + `bookings.index.*` |
| `/muthowif/bookings/{id}` | `muthowif/bookings/show.blade.php` | ✅ | `muthowif.booking_show.*` |
| `/muthowif/withdrawals` | `muthowif/withdrawals/index.blade.php` | 🔶 | |

---

## Admin

| Route | View | Status | Catatan |
|-------|------|--------|---------|
| `/admin/keuangan` | `admin/finance/index.blade.php` | ✅ | `admin.finance.*` |
| `/admin/refund-menunggu` | `admin/refunds/index.blade.php` | ✅ | `admin.refunds.*` |
| `/admin/withdrawals` | `admin/withdrawals/index.blade.php` | ✅ | `admin.withdrawals.*` (+ JS template bukti) |
| `/admin/muthowif` | `admin/muthowif/index.blade.php`, `show.blade.php` | ✅ | `admin.muthowif.*` |
| `/logs` | `admin/logs/index.blade.php` | ✅ | `admin.logs.*` |

---

## Di luar Blade

| Area | Status | Catatan |
|------|--------|---------|
| **Enum** `label()` | ✅ | `enums.*` |
| **WhatsApp** (`MuthowifBookingWhatsAppNotifier`) | ✅ | `whatsapp.*` — locale dari `users.locale` penerima; `withLocale()` agar enum konsisten |
| **Pesan validasi** FormRequest | 🔶 | Sebagian sudah `bookings.*`; sisanya perlu audit |
| **Email** | — | Belum ada view email khusus |

---

## Cara memakai checklist ini

1. Saat menyentuh suatu halaman: **string → `__('file.key')` + pasangan `lang/en` & `lang/id`**.
2. Untuk pesan server/WhatsApp: **locale user** bila ada (`users.locale`).
3. Setelah area selesai, ubah baris tabel dari 🔶/❌ menjadi ✅.

*Terakhir diperbarui: 2026-04-15.*
