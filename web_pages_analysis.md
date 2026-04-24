# Analisis Halaman Web Bayt-GO

Dokumen ini berisi daftar halaman web yang tersedia dalam aplikasi Bayt-GO, dikelompokkan berdasarkan fungsi dan peran pengguna.

## 1. Halaman Publik & Tamu
Halaman yang dapat diakses oleh calon jamaah tanpa perlu masuk ke akun.

| Halaman | Deskripsi | File View |
| :--- | :--- | :--- |
| **Landing Page** | Halaman utama penyambutan. | `welcome.blade.php` |
| **Direktori Layanan** | Daftar semua Muthowif yang terverifikasi. | `layanan/index.blade.php` |
| **Profil Muthowif** | Detail profil, pengalaman, dan form booking. | `layanan/show.blade.php` |

## 2. Sistem Autentikasi
Proses pendaftaran dan masuk untuk semua tipe user.

- **Login & Register**: Standar Laravel Breeze.
- **OTP Verification**: Verifikasi pendaftaran melalui OTP (`auth/partials/register-otp.blade.php`).
- **Muthowif Pending**: Halaman tunggu verifikasi admin bagi pendaftar Muthowif baru.

## 3. Fitur Pelanggan (Jamaah)
Manajemen perjalanan dan pemesanan dari sisi pengguna jasa.

- **Daftar Booking**: List perjalanan (mendatang & riwayat).
- **Detail Booking**: Status, upload dokumen (Paspor/Visa/Tiket), dan akses chat.
- **Pembayaran**: Integrasi instruksi bayar Midtrans dan cetak Invoice.
- **Review**: Memberikan rating dan ulasan setelah perjalanan selesai.

## 4. Fitur Muthowif (Penyedia Jasa)
Manajemen operasional bimbingan dan keuangan.

- **Manajemen Pesanan**: Konfirmasi atau pembatalan pesanan masuk.
- **Kalender Jadwal**: Mengatur ketersediaan dan memblokir tanggal sibuk.
- **Pengaturan Layanan**: Mengatur harga harian, biaya hotel, dan layanan tambahan (add-ons).
- **Keuangan (Wallet)**: Monitoring pendapatan dan pengajuan penarikan dana (Withdraw).

## 5. Panel Administrasi
Pengawasan dan manajemen platform secara keseluruhan.

- **Verifikasi Muthowif**: Validasi dokumen KYC (KTP/Sertifikat) pendaftar baru.
- **Manajemen User**: Kontrol penuh atas data pengguna (Admin/Muthowif/Customer).
- **Manajemen Keuangan**: Approval refund jamaah dan approval withdrawal Muthowif.
- **System Monitoring**: Melihat log aktivitas sistem dan performa keuangan.

## 6. Fitur Komunikasi & Pendukung
- **Live Chat**: Obrolan real-time per pesanan antara jamaah dan Muthowif.
- **Multi-language**: Dukungan bahasa (Indonesian/English) melalui pengalih bahasa di navigasi.
- **Modular Components**: Penggunaan komponen UI yang konsisten (Button, Input, Modal, dll).

---
*Catatan: Struktur file menunjukkan penggunaan fragment/partials yang intensif, mengindikasikan aplikasi menggunakan AJAX untuk pembaruan data yang responsif.*
