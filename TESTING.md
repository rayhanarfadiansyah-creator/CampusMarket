\# CampusMarket — Panduan Pengujian



\## Tujuan

Dokumen ini berisi langkah-langkah pengujian manual untuk memeriksa fitur utama CampusMarket.



\## Persiapan

1\. Buka folder proyek CampusMarket.

2\. Jalankan aplikasi sesuai petunjuk di `README.md`.

3\. Pastikan layanan atau konfigurasi yang diperlukan sudah tersedia.



\## Skenario Pengujian



\### 1. Tampilan aplikasi

\- Buka halaman utama CampusMarket.

\- Periksa apakah daftar menu dan informasi toko ditampilkan.

\- Periksa tampilan pada layar desktop dan perangkat seluler.



\### 2. Jumlah pesanan dan stok

\- Pilih menu yang memiliki stok tersedia.

\- Tambah dan kurangi jumlah pesanan.

\- Pastikan jumlah pesanan tidak kurang dari satu.

\- Pastikan jumlah pesanan tidak melebihi stok yang tersedia.

\- Periksa perilaku aplikasi ketika stok menu bernilai nol.



\### 3. Validasi nomor WhatsApp

\- Uji nomor WhatsApp dengan format Indonesia, misalnya nomor yang diawali `08` atau `62`.

\- Uji nilai kosong atau nomor dengan format yang tidak memenuhi validasi.

\- Pastikan aplikasi menampilkan pesan kesalahan ketika nomor penjual tidak valid.



\### 4. Alur pemesanan

\- Pilih menu dan jumlah pesanan.

\- Periksa total harga.

\- Periksa pesan pesanan dan tujuan WhatsApp sebelum menyelesaikan alur.



\## Catatan Hasil

Catat hasil setiap skenario setelah pengujian dilakukan.



| Skenario | Hasil | Catatan |

|---|---|---|

| Tampilan aplikasi | Belum diuji | Isi setelah pengujian |

| Jumlah pesanan dan stok | Belum diuji | Isi setelah pengujian |

| Validasi WhatsApp | Belum diuji | Isi setelah pengujian |

| Alur pemesanan | Belum diuji | Isi setelah pengujian |



