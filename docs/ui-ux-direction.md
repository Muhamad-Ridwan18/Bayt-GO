# BaytGo — Arah Visual & UX (Moodboard)

> Pelengkap [ui-ux-audit.md](./ui-ux-audit.md).  
> Ini **bukan** spesifikasi implementasi — ini panduan “harus terasa seperti apa” sebelum kita ubah Blade.

---

## 1. Prinsip desain (5 pilar)

| # | Prinsip | Artinya di BaytGo |
|---|---------|-------------------|
| 1 | **Percaya dulu** | Verifikasi, rating, statistik, dan foto muthowif lebih penting dari dekorasi |
| 2 | **Satu aksi utama** | Tiap layar punya **satu** tombol emerald yang jelas maknanya |
| 3 | **Konteks tidak hilang** | Tanggal perjalanan + nama muthowif selalu terlihat saat user mendekati booking |
| 4 | **Transparan sebelum berat** | Harga & paket jelas **sebelum** upload 5 dokumen |
| 5 | **Tenang, bukan ramai** | Animasi halus; hindari warna alert/gradient berlebihan di satu halaman |

**Rasa yang ditargetkan:** seperti memesan guide profesional (Airbnb Experiences / Traveloka hotel), bukan belanja flash sale.

---

## 2. Bahasa visual (design tokens)

### 2.1 Warna — peran, bukan dekorasi

```
EMERALD (brand 500–700)     → Aksi utama, link penting, ketersediaan OK
SLATE (50–900)              → Teks, border, latar netral
AMBER / GOLD (#C5A059)      → Paket Private Jamaah SAJA — jangan untuk alert umum
EMERALD-50 + ring           → Status sukses / siap booking
AMBER-50 + ring             → Perlu perhatian (ubah tanggal, login dulu) — BUKAN error
RED / ROSE                    → Error validasi & pembatalan — jarang dipakai
SLATE-900 gradient          → Header sidebar checkout & hero gelap (sudah ada di booking-sidebar)
```

**Aturan emas warna:**
- Maks **1** area gradient gelap per halaman (hero ATAU sidebar, tidak keduanya bersaing)
- Tombol primary **hanya** `brand-600/700` — gold tidak untuk CTA utama
- Background halaman: `welcomeCanvas` / putih / `slate-50` — sudah tepat untuk marketplace

### 2.2 Tipografi

| Level | Class | Pemakaian |
|-------|-------|-----------|
| Display | `text-2xl–4xl font-bold tracking-tight` | Nama muthowif (H1) |
| Section | `text-lg–xl font-bold` | Judul paket, review, galeri |
| Label | `text-[11px] font-bold uppercase tracking-wide text-slate-500` | Stat, label form |
| Body | `text-sm leading-relaxed text-slate-600` | Penjelasan, hint |
| Angka | `tabular-nums` | Tanggal, harga, jumlah jemaah |

Font: **Plus Jakarta Sans** (sudah global) — jangan tambah font ketiga.

### 2.3 Bentuk & bayangan

| Token | Nilai | Pemakaian |
|-------|-------|-----------|
| Radius kartu | `rounded-2xl` / `rounded-3xl` checkout | Konsisten — jangan campur `rounded-lg` untuk kartu utama |
| Shadow marketplace | `shadow-market` | Kartu hasil pencarian, sidebar book |
| Shadow halus | `shadow-sm ring-1 ring-slate-100` | Section dalam halaman |
| Border | `border-slate-200/90` | Hampir semua kartu |

### 2.4 Motion

- Hover kartu: `transition` + sedikit `scale` atau border brand — **subtle**
- Panel/modal: `ease-out 200ms` (sudah di booking T&C)
- Tidak ada: bounce, shake, confetti, auto-carousel agresif

---

## 3. Moodboard per layar

### 3.1 Pencarian muthowif (`layanan/index`)

**Perasaan:** “Saya pilih tanggal dulu — sistem yang menampilkan yang benar-benar available.”

```
┌─────────────────────────────────────────────────────────────┐
│  [Hero tipis / kicker Marketplace]                           │
│  ┌─────────────────────────────────────────────────────┐    │
│  │  📅 Date range picker (emerald accent)             │    │
│  │  [Opsional: nama muthowif]  [Cari]                 │    │
│  └─────────────────────────────────────────────────────┘    │
│  Chip: Terverifikasi · Real-time · Pembayaran aman           │
├─────────────────────────────────────────────────────────────┤
│  "12 Muthowif tersedia · 15/06 – 22/06/2026"                │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐                     │
│  │ Foto     │ │ Foto     │ │ Foto     │  ← grid kartu       │
│  │ Nama     │ │ Nama     │ │ Nama     │     shadow-market   │
│  │ ★ 4.8    │ │ Baru     │ │ ★ 5.0    │                     │
│  │ dari Rp… │ │ dari Rp… │ │ dari Rp… │                     │
│  │[Lihat]   │ │[Lihat]   │ │[Lihat]   │                     │
│  └──────────┘ └──────────┘ └──────────┘                     │
└─────────────────────────────────────────────────────────────┘
```

**Yang harus terasa:**
- Date picker = **pusat** halaman (sudah mendekati ini)
- Kartu muthowif: foto dominan, badge verifikasi kecil, harga “dari Rp X/hari”
- Empty state ramah: “Pilih tanggal lalu tekan Cari” — bukan halaman kosong dingin

**Hindari:** full-bleed `w-screen` yang bikin scroll horizontal; terlalu banyak chip filter yang tidak berfungsi

---

### 3.2 Profil muthowif (`layanan/show`)

**Perasaan:** “Saya kenal orangnya, percaya, dan tahu berapa biayanya — siap lanjut.”

```
┌─────────────────────────────────────────────────────────────┐
│  ← Cari muthowif / Nama          [chip: 15/06 – 22/06]      │
├──────────────────────────┬──────────────────────────────────┤
│                          │  MUTHOWIF TERVERIFIKASI           │
│      FOTO BESAR          │  Nama Lengkap                    │
│      (4:5 / hero)        │  Tagline singkat                 │
│      [✓ Terverifikasi]   │  Bahasa · ★ 4.9 (12)            │
│                          │  ┌────┬────┬────┬────┐           │
│                          │  │Exp │Jemaah│Bahasa│Rating│     │
│                          │  └────┴────┴────┴────┘           │
│                          │  [══════ PESAN SEKARANG ══════]  │ ← satu primary
├──────────────────────────┴──────────────────────────────────┤
│  ┌─ STATUS BOOKING (satu kartu) ─────────────────────────┐  │
│  │ ✓ Jadwal tersedia · 15/06–22/06  [Lanjut ke formulir →]│  │
│  └────────────────────────────────────────────────────────┘  │
│  ATAU jika blocked: amber card + satu link perbaikan saja     │
├─────────────────────────────────────────────────────────────┤
│  PAKET LAYANAN                                               │
│  ┌─────────────────┐  ┌─────────────────┐                   │
│  │ GROUP (emerald) │  │ PRIVATE (gold)  │                   │
│  │ Rp X / hari     │  │ Rp Y / hari     │                   │
│  │ • fitur         │  │ • fitur         │                   │
│  │ [Pilih Group]   │  │ [Pilih Private] │  ← WAJIB ada      │
│  └─────────────────┘  └─────────────────┘                   │
├─────────────────────────────────────────────────────────────┤
│  Review (3 teratas) · Galeri (preview) · Detail (collapsed)  │
└─────────────────────────────────────────────────────────────┘

Mobile: [sticky bottom bar]
  Nama · tanggal · [Pesan] 
```

**Hierarchy yang benar:**
1. Hero (identitas + trust)
2. **Satu** status/CTA card (ganti `profile-booking-cta`, buang duplikat amber)
3. Paket + harga + tombol pilih
4. Social proof (review, galeri)
5. Detail panjang (addon, blocked dates) — collapsible

**Tombol hero:**
- `canBook` → **“Pesan sekarang”** (emerald solid, `min-h-12`)
- `!canBook` → **bukan** tombol solid sama; pakai outline atau teks link “Lihat syarat pemesanan”

**Hindari:** banner amber kedua di bawah hero; emoji 🌐; CTA di hero dan sidebar tanpa perbedaan peran

---

### 3.3 Halaman checkout (`layanan/book`)

**Perasaan:** “Form resmi tapi tidak menakutkan — saya tahu progress dan ringkasan di kanan.”

```
Desktop:
┌────────────────────────────────┬──────────────────┐
│  ← Profil / Checkout           │  STICKY SIDEBAR  │
│  Step: ●──○──○                 │  Foto + nama     │
│  (1.Paket 2.Dokumen 3.Kirim)   │  Tanggal         │
│                                │  Status: Siap ✓  │
│  ┌─ Form booking-panel ─────┐  │  (tanpa tombol   │
│  │ Tanggal (readonly chip)  │  │   submit di sini)│
│  │ Pilih paket (radio)      │  │                  │
│  │ Jumlah jemaah           │  │                  │
│  │ Addon (collapsible)      │  │                  │
│  │ Dokumen (pre-upload ✓)   │  │                  │
│  │ [Setuju T&C] → Submit    │  │                  │
│  └──────────────────────────┘  │                  │
└────────────────────────────────┴──────────────────┘

Mobile:
  Ringkasan collapsible di atas
  Form full width
  [Sticky: Setuju & kirim permintaan]
```

**Sidebar (`booking-sidebar`) — sudah bagus:**
- Header gelap = “mode checkout”
- Foto kecil + nama + chip tanggal + status pill
- **Jangan** duplikasi pesan error yang sudah ada di form

**Form (`booking-panel`):**
- Step indicator di atas (belum ada — perlu ditambah)
- Dokumen: tampilkan “✓ Terunggah” per file (sudah ada setelah optimasi AJAX)
- Validasi: **satu** cara tampil error (field-level ATAU ringkasan, tidak keduanya)
- T&C modal: pertahankan — pola consent yang baik untuk jasa ibadah

---

### 3.4 Kartu paket (spesifikasi komponen)

**Group — emerald lane**
```
Header: bg-brand-700, label PUTIH uppercase kecil
Body:   bg gradient brand-50 → white
Harga:  text-2xl font-bold brand-800 + "/hari"
CTA:    rounded-xl bg-brand-600 text-white w-full py-3
State selected: ring-2 ring-brand-500 + check icon kanan atas
```

**Private — gold lane**
```
Header: bg-gold (#C5A059)
Body:   amber-50 gradient
Harga:  text-amber-950
CTA:    rounded-xl bg-gold atau amber-800 text-white
State selected: ring-2 ring-gold
```

**Fitur list:** max 4 bullet + “lihat detail di formulir” — jangan paragraf panjang di kartu.

---

### 3.5 Area aplikasi (customer / muthowif / admin)

**Perasaan:** “Dashboard kerja” — lebih padat dari marketplace, tapi **tetap** pakai token yang sama.

| Area | Beda dengan marketplace |
|------|-------------------------|
| Customer bookings | Status pill berwarna (pending/paid/done); kartu booking bukan foto besar |
| Muthowif dashboard | Quick stats + daftar permintaan masuk; aksi approve/reject jelas |
| Admin | Tabel + filter; hero gelap boleh tapi **seragam** via `<x-page-hero>` |

**Satu aturan:** flash banner **hanya sekali** (setelah Fase 0 audit).

---

## 4. Pola komponen yang harus terasa “BaytGo”

### Status pill (booking intent)

| State | Warna | Contoh teks |
|-------|-------|-------------|
| Siap | `emerald-50` + ring | Jadwal tersedia |
| Perlu login | `slate-100` | Masuk untuk memesan |
| Perlu tanggal | `amber-50` | Pilih tanggal perjalanan |
| Tidak tersedia | `amber-50` | Jadwal penuh pada tanggal ini |
| Error | `red-50` | Tanggal tidak valid |

Satu komponen `<x-booking-intent-status>`, bukan 3 blade berbeda.

### Primary button

```
Default:  bg-brand-600 hover:bg-brand-500 text-white font-bold rounded-xl min-h-12
Disabled: bg-slate-200 text-slate-500 cursor-not-allowed (BUKAN brand pudar)
Secondary: border border-slate-200 bg-white text-slate-800
Destructive: rose/red — hanya cancel/refund
```

### Trust signals (urutan prioritas)

1. Badge verifikasi (emerald)
2. Rating + jumlah review
3. Jumlah jemaah dilayani
4. Chip pembayaran aman / marketplace (footer/index)
5. Galeri foto portfolio

---

## 5. Responsif — aturan cepat

| Breakpoint | Perilaku |
|------------|----------|
| `< sm` | Satu kolom; bottom sticky CTA di profil & book; tabel → kartu |
| `sm–lg` | Form lebar penuh; sidebar di bawah form (bukan sticky) |
| `≥ lg` | Sidebar sticky `top-24`; grid 2 kolom profil hero |

**Safe area:** FAB chat + bottom CTA tidak overlap (`bottom-20` offset jika keduanya ada).

---

## 6. Do & Don't (cheat sheet)

### ✅ Lakukan
- Satu CTA emerald per viewport
- Chip tanggal mengikuti user ke profil & book
- Harga per hari + range jemaah sebelum form dokumen
- Foto muthowif besar di profil
- Collapse section sekunder (blocked dates, addon detail)
- Ikon SVG konsisten (Heroicons style yang sudah dipakai)

### ❌ Jangan
- Dua banner flash setelah redirect
- Tombol “Pesan” hijau saat user belum bisa booking
- Tiga penjelasan amber untuk alasan yang sama
- Gold/emerald untuk alert error
- Gradient hero di setiap section admin
- Form 5 file tanpa indikator progress/upload status

---

## 7. Mapping ke task audit

| Arah visual ini | Task di ui-ux-audit.md |
|-----------------|------------------------|
| Satu CTA / status card | MK-01, MK-02, MK-03, AL-02 |
| Stepper checkout | MK-06 |
| Sticky mobile CTA | MK-04, RS-10 |
| Flash sekali | DS-01, DS-02, AL-01 |
| Kartu paket + CTA | MK-02 |
| Sidebar checkout | Pertahankan `booking-sidebar`; jangan duplikasi alert |
| Tabel mobile | RS-01, RS-06 |

---

## 8. Referensi mood (bukan copy)

| Referensi | Ambil apa | Jangan ambil |
|-----------|-----------|--------------|
| **Traveloka** | Date-first search, chip filter, kartu hasil | Terlalu banyak promo banner |
| **Airbnb listing** | Hero foto, host trust, sticky book bar | Map-heavy UI |
| **Airbnb Experiences** | Paket + harga + satu CTA | Rating sebagai satu-satunya fokus |
| **Halal booking / umrah apps** | Tone sopan, consent, dokumen jelas | UI kuno / terlalu padat |

---

*Dokumen arah visual — revisi bersamaan dengan implementasi Fase 0–2 di ui-ux-audit.md.*
