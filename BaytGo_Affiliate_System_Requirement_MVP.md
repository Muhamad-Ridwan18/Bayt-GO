# BaytGo Affiliate System Requirement (MVP)

## Tujuan
Membangun sistem affiliate yang memungkinkan pengguna membagikan kode atau link referral BaytGo dan memperoleh komisi sebesar **1% dari nilai transaksi**, yang diambil dari **platform fee BaytGo**.

## Business Rules

- Setiap affiliate memiliki kode affiliate yang unik.
- Affiliate dapat membagikan link atau kode affiliate.
- Komisi sebesar **1%** diberikan untuk setiap booking yang berstatus **Completed**.
- Komisi diambil dari platform fee BaytGo.
- Satu booking hanya dapat memiliki satu affiliate.
- Affiliate tidak dapat menggunakan kode miliknya sendiri.
- Booking yang dibatalkan atau direfund tidak menghasilkan komisi.

## Fitur

### 1. Registrasi Affiliate
- Pengguna dapat mendaftar sebagai affiliate.
- Admin dapat menyetujui atau menolak pendaftaran (opsional).

### 2. Link Affiliate

Contoh:
```
https://baytgo.id/r/RIDWAN
https://baytgo.id/?ref=RIDWAN123
```

### 3. Tracking Referral
- Affiliate
- Jamaah
- Booking
- Waktu referral
- Status referral

### 4. Perhitungan Komisi

Contoh:
```
Total Booking : Rp1.000.000
Platform Fee : 15%
Affiliate    : 1%
BaytGo       : 14%
```

### 5. Dashboard Affiliate
- Total Klik
- Total Booking
- Booking Berhasil
- Pending Commission
- Available Balance
- Total Withdraw
- Riwayat Komisi
- Riwayat Withdraw

### 6. Withdraw
Syarat:
- Saldo mencukupi
- Minimal withdraw (mis. Rp100.000)
- Rekening telah diverifikasi

Status:
- Requested
- Approved
- Rejected
- Paid

## Dashboard Admin

- Total Affiliate
- Total Referral
- Total Booking Affiliate
- Total Komisi
- Pending Commission
- Withdraw Request
- Top Affiliate
- Detail transaksi affiliate

Admin dapat:
- Mengubah persentase komisi
- Menonaktifkan affiliate
- Menyetujui withdraw
- Melihat histori pembayaran

## Status Komisi

```
Pending
↓
Booking Completed
↓
Available
↓
Withdraw Requested
↓
Paid
```

Jika dibatalkan:

```
Pending
↓
Cancelled / Refunded
↓
Void
```

## Database

- affiliates
- affiliate_referrals
- affiliate_commissions
- affiliate_withdraws
- affiliate_clicks (opsional)

## Notifikasi

Affiliate menerima notifikasi ketika:
- Referral berhasil booking.
- Komisi ditambahkan.
- Withdraw disetujui.
- Withdraw dibayarkan.

## Future Enhancement

- Level Affiliate
- Campaign Affiliate
- Banner Promosi
- Conversion Rate
- UTM Tracking
- Deep Link BaytGo
- Leaderboard Affiliate
