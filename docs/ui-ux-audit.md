# BaytGo — Audit UI/UX & Task List

> Dokumen bedah struktural views Blade (175 file).  
> Fokus: layout, reusability, container, alert ganda, responsif, orphan code.  
> **Belum implementasi** — ini backlog perbaikan berurutan.

---

## 1. Peta struktur aplikasi

### 1.1 Hierarki layout (shell HTML)

```
┌─────────────────────────────────────────────────────────────────┐
│  STANDALONE (full HTML sendiri)                                 │
│  welcome, legal/terms, docs/moota-webhook, bookings/invoice     │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│  x-app-layout  →  layouts/app.blade.php                         │
│    ├── navigation (x-page-container)                              │
│    ├── GLOBAL flash: session status + error  ←── sumber duplikat │
│    ├── $slot (konten halaman)                                     │
│    └── @auth → global-chat (FAB fixed)                            │
│  Dipakai: admin/*, muthowif/*, bookings/*, support/*, profile   │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│  x-guest-layout  →  layouts/guest.blade.php                       │
│    ├── auth-hero-panel (desktop)                                  │
│    ├── form card (max-w-lg / max-w-3xl wide)                      │
│    └── auth-trust-chips                                           │
│  Dipakai: auth/*                                                  │
│  Catatan: TIDAK ada global flash (aman dari duplikat layout)        │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│  x-marketplace-layout  →  components/marketplace-layout.blade.php │
│    ├── @auth → navigation | @guest → marketing-public-header       │
│    ├── main: fullBleed ? raw slot : x-page-container              │
│    └── footer → x-page-container                                  │
│  Dipakai: layanan/*                                               │
│  Catatan: prop `wide` dideklarasikan tapi TIDAK dipakai           │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│  x-layouts.marketing-public                                       │
│    ├── marketing-public-header                                    │
│    ├── slot (tanpa container default)                             │
│    └── footer → x-page-container                                  │
│  Dipakai: articles/*                                              │
└─────────────────────────────────────────────────────────────────┘

DEAD: App\View\Components\AdminLayout → layouts/admin (file tidak ada)
```

### 1.2 Pola komposisi halaman (tanpa @extends)

Semua halaman memakai **Blade components + @include**, bukan `@extends`.

| Pola | Contoh | Keterangan |
|------|--------|------------|
| **App shell + gradient wrapper** | `admin/users/index`, `muthowif/jadwal` | `min-h-[calc(100vh-4rem)]` + gradient + `x-page-container` |
| **App shell + slot header** | `admin/company-approval/index` | Satu-satunya halaman admin pakai `<x-slot name="header">` |
| **Marketplace full-bleed** | `layanan/index` | Bypass `x-page-container`; manual `max-w-6xl` di dalam |
| **Marketplace contained** | `layanan/show`, `layanan/book` | Slot dibungkus `x-page-container` oleh layout |
| **Partial chain (booking show)** | `bookings/show` → `show-body` → `show-live-dynamic` → … | Live fragment via Reverb/AJAX |
| **Role dashboard branch** | `dashboard.blade.php` | Customer / Muthowif / Admin partial terpisah |

### 1.3 Pohon include (alur utama)

```
layanan/index
  ├── date-search-form
  ├── muthowif-card
  └── marketplace-trust-strip

layanan/show
  ├── profile-show-hero          ← CTA booking di hero
  ├── [INLINE amber warning]     ← duplikat logic profile-booking-cta
  ├── profile-show-packages      ← URL book ada, tombol CTA tidak
  ├── profile-show-addons
  ├── profile-show-reviews
  ├── profile-show-bottom
  └── profile-show-trust-bar

layanan/book
  ├── booking-sidebar (sticky lg)
  └── booking-panel (form berat + validasi ganda)

bookings/show
  └── show-body → show-live-dynamic
        ├── show-detail-card
        ├── show-cancellation-alert    ← bisa overlap sidebar
        ├── emergency-panel
        ├── show-live-extended-main
        └── show-sidebar

dashboard
  ├── dashboard-customer → dashboard-customer-layout
  ├── dashboard-muthowif → dashboard-muthowif-layout
  └── dashboard-admin
```

---

## 2. Inventori komponen reusable

### 2.1 Sudah ada & dipakai

| Komponen | Path | Fungsi |
|----------|------|--------|
| `x-page-container` | `components/page-container.blade.php` | `max-w-7xl px-4 sm:px-6 lg:px-8` |
| `x-text-input`, `x-input-label`, `x-input-error` | `components/*` | Form kit Breeze |
| `x-auth-session-status` | `components/auth-session-status.blade.php` | Status auth (tanpa border) |
| `x-marketplace-layout` | `components/marketplace-layout.blade.php` | Shell marketplace |
| `x-date-range-picker` | `components/date-range-picker.blade.php` | Kalender pencarian |
| `date-search-form` | `layanan/partials/date-search-form.blade.php` | Dipakai index, welcome, dashboard customer |
| `muthowif-card` | `layanan/partials/muthowif-card.blade.php` | Kartu hasil pencarian |
| `support/status-badge` | `support/partials/status-badge.blade.php` | Badge status tiket |

### 2.2 Belum ada — perlu dibuat (gap desain sistem)

| Komponen yang dibutuhkan | Menggantikan |
|--------------------------|--------------|
| `<x-flash-banner type="success\|error\|warning\|info" />` | 20+ blok inline `session('status')` / `session('error')` |
| `<x-page-hero>` (badge + title + subtitle + actions) | Hero gradient copy-paste di admin/muthowif |
| `<x-admin-page>` atau `<x-app-page>` | Wrapper `min-h + gradient + x-page-container` berulang |
| `<x-booking-intent-alert>` | Logic amber guest/missing_dates/jadwal di 3 tempat |
| `<x-validation-summary>` | `$errors->any()` list vs `x-input-error` per field |
| `<x-data-table>` (wrapper `overflow-x-auto`) | Tabel admin tanpa scroll horizontal |
| `<x-sticky-sidebar>` | `lg:sticky lg:top-24` dengan offset konsisten |
| `<x-package-card>` | Kartu group/private di profile-show-packages |
| `<x-empty-state>` | Empty state berbeda-beda di tiap modul |

---

## 3. Temuan per kategori

### 3.1 Alert / flash ganda (prioritas tinggi)

**Akar masalah:** `layouts/app.blade.php` sudah render `session('status')` + `session('error')` di atas `$slot`. Banyak halaman **mengulang** banner yang sama.

| File | Duplikat status | Duplikat error | Layout global |
|------|:---------------:|:--------------:|:-------------:|
| `profile/edit.blade.php` | ✓ | ✓ | ✓ |
| `admin/whatsapp-broadcast/index.blade.php` | ✓ | ✓ | ✓ (+ `broadcast_failures`) |
| `admin/muthowif/show.blade.php` | ✓ | ✓ | ✓ |
| `admin/withdrawals/index.blade.php` | ✓ | ✓ | ✓ |
| `admin/users/index.blade.php` | ✓ | — | ✓ |
| `admin/refunds/index.blade.php` | ✓ | ✓ | ✓ |
| `admin/company-approval/index.blade.php` | ✓ | ✓ | ✓ |
| `admin/articles/index.blade.php` | ✓ | — | ✓ |
| `admin/campaigns/index.blade.php` | ✓ | — | ✓ |
| `admin/muthowif/index.blade.php` | ✓ | — | ✓ |
| `admin/support-tickets/index.blade.php` | ✓ | ✓ | ✓ |
| `admin/support-tickets/show.blade.php` | ✓ | ✓ | ✓ |
| `admin/emergency/show.blade.php` | ✓ | ✓ | ✓ |
| `admin/site-appearance/edit.blade.php` | ✓ | ✓ | ✓ |
| `muthowif/emergency-offers/index.blade.php` | ✓ | ✓ | ✓ |
| `muthowif/withdrawals/index.blade.php` | ✓ | ✓ | ✓ |
| `muthowif/portfolio/index.blade.php` | ✓ | — | ✓ |
| `support/index.blade.php` | ✓ | ✓ | ✓ |
| `support/show.blade.php` | ✓ | ✓ | ✓ |

**Inkonsistensi visual antar banner (meski tidak duplikat):**

| Variasi | Contoh |
|---------|--------|
| `rounded-xl` vs `rounded-2xl` | layout vs admin pages |
| `border-emerald-200` vs tanpa border | layout vs beberapa admin |
| `text-emerald-800` vs `text-emerald-900` | muthowif vs admin |
| `border-red-200` vs `border-rose-200` | error states |
| `x-auth-session-status` (plain text) | auth/login — berbeda dari app flash |

### 3.2 Alert semantik ganda (bukan flash, tapi pesan overlap)

| Halaman / area | Masalah |
|----------------|---------|
| `layanan/show.blade.php` | Hero CTA + blok amber inline (L123–141) + logic sama di `profile-booking-cta` (orphan) |
| `layanan/partials/profile-show-hero.blade.php` | Tombol `book_now` vs `open_booking_page` saat `!$canBook` — visual sama, makna beda |
| `layanan/partials/booking-panel.blade.php` | `$errors->any()` ringkasan + `@error` per field → pesan validasi dobel |
| `bookings/show` | `show-cancellation-alert` (main) + teks cancelled di `show-sidebar` |
| `admin/whatsapp-broadcast/index.blade.php` | `status` + `error` + `broadcast_failures` — 3 jenis alert sekaligus |

### 3.3 Container & layout tidak konsisten

| ID | Masalah | File |
|----|---------|------|
| C-01 | **`x-page-container` ditutup prematur (HTML rusak)** | `admin/articles/index.blade.php` L23 — `</x-page-container>` di dalam button group; tabel di luar container |
| C-02 | **`x-page-container` ditutup prematur** | `muthowif/portfolio/index.blade.php` L24 — `</x-page-container>` di dalam `<p>` hero |
| C-03 | Prop `wide` di `x-marketplace-layout` tidak dipakai | `components/marketplace-layout.blade.php`; dipass dari `layanan/show`, `layanan/book` |
| C-04 | `max-w-6xl` manual vs canonical `max-w-7xl` | `layanan/index.blade.php` (fullBleed sections) |
| C-05 | `max-w-7xl` manual duplikat (bukan component) | `welcome.blade.php` (banyak section) |
| C-06 | Padding vertikal tidak terstandar | `py-6`, `py-8`, `py-8 sm:py-12`, `py-12`, `pb-16 pt-8` — acak per halaman |
| C-07 | Sticky offset tidak seragam | Sidebar `lg:top-24` vs profile edit `lg:top-6` |
| C-08 | Nested `x-page-container` | `profile/edit.blade.php`, `layanan/portfolio/index.blade.php` |
| C-09 | Halaman admin tanpa gradient wrapper | `admin/emergency/index`, `muthowif/emergency-offers/index` — terasa “kosong” vs halaman admin lain |
| C-10 | Legacy Breeze header slot | `admin/company-approval/index` — satu-satunya pola lama |

### 3.4 Partial orphan (tidak pernah di-include)

| File | Catatan |
|------|---------|
| `layanan/partials/profile-booking-cta.blade.php` | Logic lengkap booking-intent; diganti inline di `show.blade.php` |
| `bookings/partials/referral-network-alternatives.blade.php` | Tidak direferensikan di codebase |
| `muthowif/bookings/partials/reject-booking-form.blade.php` | Diganti `pending-booking-actions.blade.php` |
| `auth/partials/register-otp.blade.php` | Tidak di-include `register.blade.php` |
| `admin/articles/_ckeditor.blade.php` | Diganti `_editorjs.blade.php` |

### 3.5 Duplikasi UI paralel (bukan orphan, tapi maintenance burden)

| Area customer | Area muthowif | Rekomendasi |
|-------------|---------------|-------------|
| `bookings/partials/show-detail-card` | `muthowif/bookings/partials/show-detail-card` | Satu partial + prop `perspective` |
| `bookings/partials/show-sidebar` | `muthowif/bookings/partials/show-sidebar` | Idem |
| `bookings/partials/index-body` | `muthowif/bookings/partials/booking-request-card` | Pola kartu booking serupa |
| Hero gradient admin | Hero gradient muthowif | Ekstrak `<x-page-hero>` |

### 3.6 Responsif & mobile

| ID | Risiko | File |
|----|--------|------|
| R-01 | `w-screen max-w-[100vw]` breakout — overflow horizontal | `layanan/index`, `dashboard-customer-layout`, `dashboard-admin` |
| R-02 | Tabel admin tanpa `overflow-x-auto` | `admin/articles/index`, `admin/campaigns/index`, `admin/emergency/index` |
| R-03 | Global chat FAB `fixed bottom-6 right-6` overlap CTA bawah | `partials/global-chat.blade.php` |
| R-04 | Chat panel fixed `h-[500px] w-80` — tinggi viewport kecil | `global-chat.blade.php` |
| R-05 | Dashboard muthowif: 7 quick-action `min-w-[4.25rem]` satu baris | `dashboard-muthowif-layout.blade.php` |
| R-06 | Carousel kartu fixed width tanpa indikator scroll | `welcome.blade.php`, `dashboard-customer-layout` |
| R-07 | Sticky sidebar hanya `lg:` — tidak ada layout `md` | `layanan/book`, `bookings/payment`, `show-sidebar` |
| R-08 | Input lebar fixed | `booking-panel` (`w-[7rem]`), `date-search-form` (`lg:w-[20rem]`) |
| R-09 | `truncate` + `max-w-[11rem]` di tabel — konten hilang di mobile | `bookings/index-body`, `support/index` |
| R-10 | Tidak ada sticky bottom CTA di profil/booking mobile | `layanan/show`, `layanan/book` |
| R-11 | Marketing header: nav utama `hidden lg:flex` — gap md | `marketing-public-header` |

### 3.7 Marketplace / booking flow (UX alur)

| ID | Masalah | File |
|----|---------|------|
| F-01 | Kartu paket tanpa tombol "Pilih" (`groupBookUrl`/`privateBookUrl` tidak dipakai) | `profile-show-packages.blade.php` |
| F-02 | `profile-booking-cta` tidak dipasang — tidak ada sticky CTA | `profile-booking-cta.blade.php` (orphan) |
| F-03 | Halaman profil panjang sebelum konversi | `layanan/show.blade.php` urutan section |
| F-04 | Form booking tanpa progress step | `booking-panel.blade.php` |
| F-05 | Emoji 🌐 di hero tidak konsisten dengan ikon SVG | `profile-show-hero.blade.php` |
| F-06 | `marketplace-layout` tidak include global-chat untuk user login | Chat hanya di `x-app-layout` |

---

## 4. Task list perbaikan

Legenda status: `[ ]` belum | `[~]` partial | `[x]` selesai  
Prioritas: **P0** blocker/bug | **P1** dampak UX tinggi | **P2** konsistensi | **P3** nice-to-have

**Progress (Juni 2026):** Fase 0–2 dan sebagian besar Fase 3–4 sudah diimplementasi. Lihat `docs/design-system.md` untuk token & komponen.

---

### Fase 0 — Fondasi desain sistem (kerjakan dulu)

| ID | P | Status | Task | File terkait |
|----|---|--------|------|--------------|
| DS-01 | P0 | [x] | Buat `<x-ui.flash-banner>` — satu sumber untuk success/error/warning/info | `components/ui/flash-banner.blade.php` |
| DS-02 | P0 | [x] | Hapus flash inline di halaman; andalkan layout | `layouts/app.blade.php` + ~19 halaman |
| DS-03 | P0 | [x] | **Fix HTML rusak** — `</x-page-container>` prematur | Admin + muthowif indexes |
| DS-04 | P1 | [x] | Buat `<x-ui.app-page>` wrapper | `components/ui/app-page.blade.php` |
| DS-05 | P1 | [~] | Buat `<x-ui.page-hero>` (badge, h1, subtitle, action slot) | `components/ui/page-hero.blade.php`; contoh: `admin/users/index` |
| DS-06 | P1 | [x] | Dokumentasikan token layout | `docs/design-system.md` |
| DS-07 | P2 | [x] | Prop `wide` di `x-marketplace-layout` | `marketplace-layout.blade.php` |
| DS-08 | P2 | [x] | Hapus `AdminLayout` dead component | Dihapus |
| DS-09 | P2 | [~] | Buat `<x-ui.data-table>` dengan `overflow-x-auto` default | `components/ui/data-table.blade.php`; contoh: `admin/users/index` |

---

### Fase 1 — Bug & duplikat kritis

| ID | P | Status | Task | File terkait |
|----|---|--------|------|--------------|
| AL-01 | P0 | [x] | Audit & hapus duplikat flash di 19 halaman `x-app-layout` | Lihat tabel §3.1 |
| AL-02 | P1 | [~] | Satukan booking-intent warning → satu component | `x-ui.alert` di panel; CTA terpisah |
| AL-03 | P1 | [x] | Hapus `$errors->any()` ringkasan; field-level saja | `booking-panel.blade.php` |
| AL-04 | P2 | [ ] | Review pesan cancelled: alert main vs sidebar — satu sumber | `show-cancellation-alert`, `show-sidebar` |
| AL-05 | P2 | [x] | Standarisasi warna error: `red` (bukan `rose`) | Seluruh views |

---

### Fase 2 — Marketplace & booking (konversi)

| ID | P | Status | Task | File terkait |
|----|---|--------|------|--------------|
| MK-01 | P1 | [x] | Pasang `profile-booking-cta` di show | `layanan/show`, `profile-booking-cta` |
| MK-02 | P1 | [x] | CTA "Pilih paket" per kartu group/private | `profile-show-packages.blade.php` |
| MK-03 | P1 | [x] | Hero button state-aware: primary hanya saat `canBook` | `profile-show-hero.blade.php` |
| MK-04 | P1 | [x] | Sticky CTA mobile di profil & halaman book | `profile-show-sticky-cta`, `book-sticky-cta` |
| MK-05 | P2 | [x] | Urutan: hero → CTA → paket → review → sisanya collapsible | `layanan/show.blade.php` |
| MK-06 | P2 | [x] | Stepper form booking (Paket → Dokumen → Konfirmasi) | `booking-panel.blade.php`, `booking-stepper` |
| MK-07 | P2 | [x] | Ganti emoji bahasa dengan ikon SVG | `profile-show-hero.blade.php` |
| MK-08 | P3 | [x] | Global-chat di marketplace-layout untuk user login | `marketplace-layout.blade.php` |

---

### Fase 3 — Container & layout konsistensi

| ID | P | Status | Task | File terkait |
|----|---|--------|------|--------------|
| CT-01 | P1 | [ ] | Standarisasi `layanan/index` fullBleed | `layanan/index.blade.php` |
| CT-02 | P2 | [~] | Refactor `welcome.blade.php` pakai `x-page-container` | Section non-hero; hero tetap full-bleed |
| CT-03 | P2 | [~] | Seragamkan padding halaman app (`ui-page-y`) | Batch admin/muthowif/bookings |
| CT-04 | P2 | [~] | Sticky offset sidebar (`top-24`) | book `md:`, payment `md:` |
| CT-05 | P2 | [ ] | Gradient wrapper halaman admin “telanjang” | `admin/emergency/index`, dll. |
| CT-06 | P3 | [ ] | Migrasi `company-approval` ke pola hero baru | `admin/company-approval/index` |

---

### Fase 4 — Responsif & mobile

| ID | P | Status | Task | File terkait |
|----|---|--------|------|--------------|
| RS-01 | P1 | [~] | `overflow-x-auto` tabel admin | articles, campaigns, emergency + live partials |
| RS-02 | P1 | [~] | Audit `overflow-x-hidden` parent | marketplace pages |
| RS-03 | P1 | [x] | Global chat safe-area (`ui-chat-fab-wrap`, `bottom-20` mobile) | `global-chat.blade.php`, `app.css` |
| RS-04 | P2 | [ ] | Dashboard muthowif quick actions scroll/grid | `dashboard-muthowif-layout` |
| RS-05 | P2 | [x] | Booking/payment sidebar `md:grid` 2 kolom | `layanan/book`, `bookings/payment` |
| RS-06 | P2 | [ ] | Tabel booking/support: card view di `< sm` | `index-body`, `support/index` |
| RS-07 | P3 | [x] | Chat panel `max-h-[min(500px,70dvh)]` | `global-chat.blade.php` |

---

### Fase 5 — Cleanup & konsolidasi kode

| ID | P | Task | File terkait |
|----|---|------|--------------|
| CL-01 | P1 | Hapus atau wire orphan partials (5 file) | Lihat §3.4 |
| CL-02 | P2 | Merge booking show partials customer/muthowif | `show-detail-card`, `show-sidebar` |
| CL-03 | P2 | Ekstrak `booking-intent` PHP logic ke View Composer / dedicated class | Controller + 3 views |
| CL-04 | P3 | Hapus variasi hero gradient duplikat setelah `<x-page-hero>` ada | Admin/muthowif indexes |

---

## 5. Urutan eksekusi yang disarankan

```
Fase 0  →  DS-03 (bug HTML) → DS-01/02 (flash) → DS-04/05 (wrappers)
Fase 1  →  AL-01 → AL-02 → AL-03
Fase 2  →  MK-01 → MK-02 → MK-03 → MK-04
Fase 3  →  CT-01 → CT-03 → CT-04
Fase 4  →  RS-01 → RS-02 → RS-03
Fase 5  →  CL-01 → CL-02
```

**Jangan mulai polish visual** sebelum Fase 0–1 selesai — duplikat alert dan HTML rusak akan mengacaukan QA.

---

## 6. Checklist QA per halaman (template)

Gunakan ini saat menandai task selesai:

- [ ] Hanya **satu** banner flash tampil setelah redirect
- [ ] Konten utama di dalam **satu** `x-page-container` (kecuali fullBleed sengaja)
- [ ] Tidak ada horizontal scroll tak disengaja di 375px / 768px / 1280px
- [ ] Satu **primary CTA** jelas per viewport
- [ ] Sticky element tidak menutupi CTA / input / chat FAB
- [ ] Error validasi tidak muncul **dua kali** untuk field yang sama
- [ ] Tabel/data lebar bisa di-scroll horizontal di mobile

---

## 7. Statistik ringkas

| Metrik | Jumlah |
|--------|--------|
| Total Blade views | 175 |
| Layout shells aktif | 4 |
| Blade components | 31 |
| Halaman dengan flash duplikat (app layout) | 19 |
| Partial orphan | 5 |
| File HTML container rusak | 2 |
| Props layout tidak dipakai | 1 (`wide`) |
| Dead layout component | 0 (AdminLayout dihapus) |

---

*Terakhir diperbarui: bedah struktural awal — belum ada perubahan kode UI/UX dari dokumen ini.*
