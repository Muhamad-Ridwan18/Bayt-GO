# BaytGo Design System — Aturan Konsistensi

> **Wajib** untuk semua UI baru dan refactor.  
> Implementasi: `resources/css/app.css` (class `ui-*`) + `resources/views/components/ui/*`.

Pelengkap: [ui-ux-direction.md](./ui-ux-direction.md) · [ui-ux-audit.md](./ui-ux-audit.md)

---

## 1. Radius (satu skala)

| Level | Class | Pemakaian |
|-------|-------|-----------|
| **Kartu / section** | `rounded-2xl` | Default SEMUA kartu, alert, panel konten |
| **Checkout / hero besar** | `rounded-3xl` | Sidebar booking, hero marketplace checkout saja |
| **Tombol / input** | `rounded-xl` | Button, text field, chip tanggal |
| **Pill / badge** | `rounded-full` | Status pill, badge verifikasi |
| **Jangan** | `rounded-lg` | Hanya untuk elemen kecil dalam tabel (aksi row) — bukan kartu utama |

**❌ Dilarang:** `rounded-md` untuk kartu baru · campur `rounded-xl` dan `rounded-2xl` pada kartu sejenis dalam satu halaman.

---

## 2. Warna (peran tetap)

| Peran | Background | Border | Teks | Class utility |
|-------|------------|--------|------|---------------|
| **Primary action** | `brand-600` | — | putih | `ui-btn-primary` |
| **Secondary** | putih | `slate-200` | `slate-800` | `ui-btn-secondary` |
| **Success / flash OK** | `emerald-50` | `emerald-200` | `emerald-900` | `ui-alert-success` |
| **Error** | `red-50` | `red-200` | `red-900` | `ui-alert-error` |
| **Warning / perlu aksi** | `amber-50` | `amber-200` | `amber-950` | `ui-alert-warning` |
| **Info** | `sky-50` | `sky-200` | `sky-900` | `ui-alert-info` |
| **Paket Group** | `brand-50` / header `brand-700` | `brand-200` | `brand-800` | — |
| **Paket Private** | `amber-50` / header `gold` | `gold/40` | `amber-950` | — |

**❌ Dilarang:**
- `rose-*` untuk error (pakai `red-*` saja)
- `emerald` untuk tombol selain success state
- `gold` untuk alert umum
- `gray-*` / `indigo-*` (legacy Breeze) — ganti `slate` / `brand`

---

## 3. Kartu

Gunakan **salah satu**:

```blade
<x-ui.card>...</x-ui.card>
```

atau class:

```html
<div class="ui-card">...</div>
```

Definisi: `rounded-2xl border-slate-200/90 bg-white shadow-sm ring-1 ring-slate-100/80`

Marketplace kartu hasil: tambahkan `shadow-market` (sudah di token Tailwind).

---

## 4. Alert & flash

**Satu banner** per redirect — hanya di `layouts/app.blade.php` via `<x-ui.flash-banner>`.

Halaman **tidak boleh** menulis `session('status')` / `session('error')` sendiri kecuali:
- `x-guest-layout` (auth)
- Alert **kontekstual** non-flash (mis. `broadcast_failures`) → `<x-ui.alert type="warning">`

```blade
<x-ui.alert type="success|error|warning|info">Pesan</x-ui.alert>
```

---

## 5. Tombol

| Komponen | Kapan |
|----------|-------|
| `<x-primary-button>` | Submit form (sudah `ui-btn-primary`) |
| `<x-secondary-button>` | Batal, kembali |
| `<x-danger-button>` | Hapus, batalkan booking |
| Class `ui-btn-primary` | Link styled as button `<a class="ui-btn-primary">` |

Primary marketplace CTA: `min-h-12` (48px touch target).

---

## 6. Tipografi label

| Peran | Class |
|-------|-------|
| Kicker / section label | `ui-kicker` → `text-[11px] font-bold uppercase tracking-wide text-slate-500` |
| Kicker brand | `ui-kicker-brand` → `text-brand-700` |
| Section H2 | `text-lg font-bold text-slate-900 sm:text-xl` |
| Body hint | `text-sm leading-relaxed text-slate-600` |

---

## 7. Spacing (padding & margin)

**Satu skala** — jangan campur `py-6`/`py-8` atau `space-y-6`/`space-y-8` sembarangan.

| Token | Class | Nilai | Pemakaian |
|-------|-------|-------|-----------|
| Page Y (default) | `ui-page-y` | `py-8 sm:py-12` | Main marketplace layout, admin/support shell |
| Page Y (compact) | `ui-page-y-compact` | `py-6 sm:py-8` | Profil edit, halaman muthowif padat |
| Stack (sections) | `ui-stack` | `space-y-8` | Jarak antar blok besar dalam halaman |
| Stack compact | `ui-stack-compact` | `space-y-6` | Checkout, form admin, sub-halaman |
| Card pad default | `ui-card-pad` / `pad="md"` | `p-5 sm:p-6` | Kartu standar, CTA booking |
| Card pad large | `ui-card-pad-lg` / `pad="lg"` | `p-6 sm:p-8` | Section marketplace (paket, review) |
| Card pad compact | `ui-card-pad-compact` | `p-4 sm:p-5` | Kartu hasil pencarian (`muthowif-card`) |
| Section body | `ui-section-body` | `mt-6` | Konten di bawah heading section |
| Toolbar | `ui-toolbar` | `px-4 py-3` | Breadcrumb, bar tanggal |
| Marketplace page | `ui-marketplace-page` | `space-y-8` | Stack vertikal halaman profil |
| Sticky mobile CTA | `ui-marketplace-page-sticky` | + `pb-24 lg:pb-0` | Profil show dengan bar bawah |
| Stack tight | `ui-stack-tight` | `space-y-4` | Dashboard, sidebar booking |
| Panel body | `ui-panel-body` | `px-5 py-6 sm:px-8 sm:py-8` | Isi checkout form |
| Checkout shell | `ui-checkout-shell` | `rounded-3xl` + shadow-market | `booking-panel` |
| Flash wrap | `ui-flash-wrap` | `pt-4` | Banner flash global |

```blade
<x-ui.app-page>                    {{-- atau compact --}}
  <x-page-container class="ui-stack">
    <x-ui.card pad="lg">...</x-ui.card>
  </x-page-container>
</x-ui.app-page>
```

**❌ Dilarang:** `p-6 sm:p-8` inline pada kartu baru — pakai `x-ui.card pad="lg"`.

---

## 8. Layout halaman app

```blade
<x-ui.app-page>
  <x-page-container class="ui-stack">
    ...
  </x-page-container>
</x-ui.app-page>
```

- `ui-app-page` = gradient + `min-h-[calc(100vh-4rem)]` + `ui-page-y` (atau `compact` → `ui-page-y-compact`)
- Konten selalu dalam **satu** `x-page-container` (`max-w-7xl`, `wide` → `max-w-[88rem]`)

---

## 9. Status pill (booking intent)

```blade
<x-ui.status-pill type="ready|guest|blocked|neutral">...</x-ui.status-pill>
```

Jangan tulis ulang `rounded-full bg-emerald-50 ring-1...` di tiap partial.

---

## 10. Checklist sebelum merge view baru

- [ ] Kartu pakai `rounded-2xl` + `ui-card` atau setara
- [ ] Alert pakai `x-ui.alert` / tidak duplikat flash layout
- [ ] Tombol pakai komponen atau `ui-btn-*`
- [ ] Error = `red`, warning = `amber`, success = `emerald`
- [ ] Tidak ada `gray-*` / `indigo-*` baru
- [ ] Satu `x-page-container` per halaman (kecuali fullBleed marketplace)
- [ ] Spacing pakai `ui-page-y` / `ui-stack` / `ui-card-pad*` — bukan nilai ad-hoc

---

## 11. Migrasi bertahap

| Fase | Scope |
|------|--------|
| ✅ Fondasi | `ui-*` CSS, `components/ui/*`, layout flash, fix HTML rusak |
| ✅ Spacing | Token `ui-page-y`, `ui-stack`, `ui-card-pad*`, seluruh views batch |
| ✅ Bookings | `booking-panel`, `booking-sidebar`, show/index/payment/refund |
| ✅ Breeze kit | `gray` → `slate`, `rose` → `red` di components |
| Berikutnya | `x-ui.page-hero` untuk admin index |
| Berikutnya | Auth guest flash ke `x-ui.alert` |

Track task di [ui-ux-audit.md](./ui-ux-audit.md) Fase 0 (DS-*).
