# DOKUMEN SPESIFIKASI PRODUK (PRD) & BLUEPRINT ARSITEKTUR
================================================================================
NAMA PROJEK         : KantinKampus (Platform Kuliner Mahasiswa Terbuka)
VERSI SPESIFIKASI   : v1.0.0 (Draf Persetujuan)
ARSITEK SISTEM      : ZettBOT by Zettbos
STATUS DOKUMEN      : DISETUJUI & SELESAI DIIMPLEMENTASIKAN (v1.0.0)
================================================================================

--------------------------------------------------------------------------------
ZONA ATAS: SPESIFIKASI KEBUTUHAN PRODUK (INITIAL BLUEPRINT)
--------------------------------------------------------------------------------

### 1. Problem Statement & Deskripsi Produk
- **Masalah Utama:** Banyak mahasiswa yang memiliki usaha kuliner rumahan/kos atau membuka sistem Pre-Order (Open PO) makanan dan minuman, namun pemasarannya hanya berputar di lingkaran pertemanan terdekat (circle WhatsApp/Instagram story pribadi). Akibatnya, potensi pembeli dari fakultas atau jurusan lain tidak terjangkau.
- **Solusi Produk:** **KantinKampus** adalah web app marketplace kuliner kampus terbuka (Open Discovery Food Hub) di mana seluruh mahasiswa dari berbagai fakultas dapat mendaftar sebagai penjual, memajang menu jualan mereka (baik stok siap saji maupun jadwal Open PO), dan seluruh mahasiswa di kampus dapat menjelajahi, menyaring berdasarkan lokasi/fakultas, serta memesan langsung secara cepat ke WhatsApp penjual.

---

### 2. Peran Pengguna (User Roles)
1. **Pembeli (Mahasiswa Umum):**
   - Mengakses web app tanpa perlu login yang rumit.
   - Menjelajahi katalog makanan dan minuman dari seluruh penjual di kampus.
   - Menggunakan fitur pencarian cepat dan filter multi-kategori (Kategori Makanan, Fakultas Penjual, Status Ready vs Open PO).
   - Memilih menu, menentukan jumlah, memilih titik temu/pengantaran di kampus, dan menekan tombol pesan yang langsung membuka chat WhatsApp resmi penjual dengan draft format pesanan otomatis.
2. **Penjual (Mahasiswa Seller):**
   - Mendaftar akun seller menggunakan Email/NIM, Nama Toko, Nomor WhatsApp aktif, dan Fakultas/Gedung kampus.
   - Mengakses Dashboard Seller khusus untuk:
     * Menambah, mengedit, atau menghapus menu makanan/minuman (foto, nama, harga, deskripsi, porsi/stok, label Ready/Open PO).
     * Mengatur jadwal PO (tanggal pengantaran & batas waktu order).
     * Toggle status buka/tutup warung (Buka / Tutup Sementara).
     * Memantau ringkasan metrik (Total Menu Aktif, Jumlah Klik Pesanan).
3. **Administrator Kampus (Opsional/Superadmin):**
   - Memoderasi konten jualan jika terdapat produk yang melanggar norma/aturan kampus.

---

### 3. Arsitektur & Lingkungan Rekayasa Sistem
- **Pendekatan Arsitektur:** Modular Single Page Application (SPA) Mobile-First & Desktop Responsive.
- **Gaya Desain:** *Anti-AI Clean & Warm Culinary*
  - Mengedepankan estetika buatan desainer manusia: palet warna hangat profesional (Emerald Forest `#0F4C3A`, Warm Amber `#D97706`, Soft Cream Background `#FDFBF7`, Slate Dark Text `#1E293B`).
  - Menggunakan ikon SVG vektor tajam inline (bebas emoji norak berlebihan).
  - Spasi lega (*whitespace*), sudut kartu halus (*rounded-xl*), bayangan lembut (*soft elevation*), dan mikro-interaksi transisi halus.
- **Teknologi Frontend:** Semantic HTML5, CSS3 kustom dengan sistem token desain terpadu, dan JavaScript ES6+ Modular.
- **Manajemen Data & State:**
  - *Client-Side In-Memory Caching:* Perpindahan tab/kategori instan 0 detik tanpa loading berulang.
  - *Persistensi Data:* State Store terintegrasi LocalStorage/IndexedDB yang idempoten, dilengkapi adapter arsitektur backend yang kompatibel dengan Google Apps Script & Google Sheets jika ingin dihubungkan ke spreadsheet cloud gratis.
- **Generator Kode Unik:**
  - Seller ID: `SLR-YYYYMM-XXX` (contoh: `SLR-202610-001`)
  - Menu ID: `MNU-YYYYMM-XXX` (contoh: `MNU-202610-001`)
  - Format Referensi Order: `KNT-YYYYMMDD-XXX` (contoh: `KNT-20261007-001`)

---

### 4. Alur Kerja Aplikasi (User Flow)

#### A. Alur Pembeli (Eksplorasi & Direct Checkout WhatsApp)
1. Pembeli membuka web app KantinKampus.
2. Muncul Banner Sambutan Kampus, bar pencarian instan, dan tab filter cepat:
   - Kategori: Semua, Makanan Berat, Camilan/Snack, Minuman, Dessert.
   - Filter Fakultas: Semua Fakultas, Teknik, Ekonomi & Bisnis, Ilmu Budaya, Kedokteran, MIPA, FISIP, dll.
   - Status Badge: Semua, Ready Stock (Siap Santap Hari Ini), Open PO (Pre-Order Terjadwal).
3. Pembeli memilih makanan, melihat detail produk (deskripsi porsi, rasa, asal fakultas seller, estimasi pengantaran).
4. Pembeli klik **"Pesan Sekarang"** atau memasukkan ke Keranjang Belanja Mini.
5. Pembeli mengisi form pemesan singkat (Nama Pembeli, Kontak WA, Titik Temu/Gedung Kampus, Catatan Khusus).
6. Tombol **"Kirim Pesanan ke WhatsApp Penjual"** diklik:
   - Sistem menghasilkan pesan WhatsApp dengan format terstruktur rapi dan membuka aplikasi WhatsApp / WhatsApp Web ke nomor penjual.

#### B. Alur Penjual (Registrasi & Dasbor Toko)
1. Penjual membuka menu navigasi / klik tombol **"Buka Lapak Mahasiswa"**.
2. Penjual melakukan login atau registrasi akun seller (Nama Lengkap, NIM, Nama Lapak/Brand, WhatsApp, Fakultas/Gedung, Password).
3. Setelah masuk, diarahkan ke Dasbor Toko:
   - Tombol toggle: Status Lapak (Buka Lapak / Libur Sementara).
   - Form Tambah Menu: Judul makanan, kategori, harga, link/unggah foto, status (Ready Stock / Open PO), jadwal kirim/deadline PO.
   - Daftar Menu Saya: Kartu tabel responsif dengan aksi Edit Menu, Ubah Status Stok, dan Hapus Menu.
4. Perubahan menu langsung terbit ke katalog utama kampus secara instan.

---

### 5. Desain Basis Data (Skema Data Bersih - Bebas Karakter Underscore pada Nama Entitas)

#### Entitas 1: `DataPenjual`
| No Kolom | Nama Kolom | Tipe Data | Deskripsi & Contoh |
| :--- | :--- | :--- | :--- |
| COL 0 | `SellerId` | String | Format unik `SLR-YYYYMM-XXX` (contoh: `SLR-202610-001`) |
| COL 1 | `NamaLengkap` | String | Nama mahasiswa pemilik (contoh: Ahmad Fauzi) |
| COL 2 | `Nim` | String | Nomor Induk Mahasiswa (contoh: 21081010123) |
| COL 3 | `NamaLapak` | String | Nama brand jualan (contoh: Dapur Kos Barokah) |
| COL 4 | `NomorWhatsapp` | String | Nomor WA valid format 628xxx (contoh: 6281234567890) |
| COL 5 | `Fakultas` | String | Fakultas asal (contoh: Fakultas Teknik) |
| COL 6 | `LokasiGedung` | String | Gedung/Stand biasa standby (contoh: Gedung B Lantai 2) |
| COL 7 | `StatusLapak` | String | `Buka` atau `Tutup` |
| COL 8 | `PasswordHash` | String | Kunci akses seller |
| COL 9 | `TanggalDaftar` | String | Waktu pendaftaran (ISO format) |

#### Entitas 2: `DataMenuMakanan`
| No Kolom | Nama Kolom | Tipe Data | Deskripsi & Contoh |
| :--- | :--- | :--- | :--- |
| COL 0 | `MenuId` | String | Format unik `MNU-YYYYMM-XXX` (contoh: `MNU-202610-001`) |
| COL 1 | `SellerId` | String | Relasi ke `DataPenjual` |
| COL 2 | `NamaMenu` | String | Nama makanan/minuman (contoh: Rice Bowl Ayam Geprek Sambal Matah) |
| COL 3 | `Kategori` | String | Makanan Berat / Camilan / Minuman / Dessert |
| COL 4 | `Harga` | Number | Nominal rupiah (contoh: 15000) |
| COL 5 | `FotoUrl` | String | URL foto produk makanan |
| COL 6 | `Deskripsi` | String | Detail porsi, level pedas, topping |
| COL 7 | `TipeJualan` | String | `ReadyStock` atau `OpenPO` |
| COL 8 | `JadwalPengantaran` | String | Contoh: "Setiap jam makan siang (12:00)" atau "Jumat, 10 Okt" |
| COL 9 | `BatasWaktuPO` | String | Tenggat waktu pemesanan untuk sistem Open PO |
| COL 10 | `StatusMenu` | String | `Tersedia` atau `Habis` |
| COL 11 | `JumlahDipesan` | Number | Counter metrik pemesanan via WA |

---

### 6. Aturan Validasi & Logika Bisnis Khusus
1. **Validasi Nomor WhatsApp:** Nomor otomatis dinormalisasi ke format internasional diawali kode negara `62` (menggantikan angka `0` di awal) untuk mencegah kegagalan redirect tautan `wa.me`.
2. **Template Rekap WhatsApp:**
   ```
   Halo kak di [NamaLapak], saya mau pesan dari KantinKampus:
   -------------------------------------------------
   No Pesanan: KNT-20261007-001
   Pesanan   : [NamaMenu] (x[Qty]) - Rp [Subtotal]
   -------------------------------------------------
   Total Bayar: Rp [Total]
   
   Data Pemesan:
   - Nama      : [Nama Pemesan]
   - Fakultas  : [Fakultas Pemesan]
   - Titik Temu: [Lokasi Kampus / Ruang Kelas]
   - Catatan   : [Catatan Tambahan]
   -------------------------------------------------
   Mohon konfirmasinya ya kak, terima kasih!
   ```
3. **Penyaringan Multi-Faset:**
   - Filter pencarian teks langsung mencocokkan nama menu, nama lapak, dan deskripsi secara *case-insensitive*.
   - Filter fakultas hanya menampilkan produk dari penjual di fakultas yang dipilih (atau seluruh kampus jika opsi 'Semua' aktif).
   - Filter status instan memisahkan makanan yang siap santap detik itu juga vs PO batch.
4. **Keamanan Lapak:**
   - Sesi seller tersimpan aman di client session.
   - Penjual hanya memiliki otoritas untuk memodifikasi dan menghapus menu milik tokonya sendiri.

---

### 7. Rencana Antarmuka Pengguna (UI Wireframe Blueprint)
1. **Header & Navigasi Kampus:**
   - Logo KantinKampus dengan aksen hangat kuliner.
   - Badge kampus / indikator jumlah menu aktif hari ini.
   - Tombol cepat: "Cari Makanan" & "Buka Lapak Mahasiswa (Seller Area)".
2. **Hero Discovery & Kampus Highlights:**
   - Banner sambutan penjelajah kuliner mahasiswa.
   - Input pencarian interaktif dengan ikon SVG pencarian.
   - Bilah chip filter horizontal: Kategori & Status (Ready vs Open PO).
   - Dropdown pemilihan Fakultas / Lokasi Gedung.
3. **Grid Produk Makanan Responsif (2 Kolom di HP, 3-4 Kolom di Laptop):**
   - Kartu makanan bergaya human-crafted: Foto menggugah selera, badge status warna emerald/amber, harga tercetak tebal, nama toko & fakultas asal.
   - Tombol aksi "Pesan via WhatsApp".
4. **Modal Checkout / Detail Pesanan:**
   - Ringkasan pesanan, form input data pemesan & titik COD kampus.
   - Tombol hijau modern "Lanjutkan ke WhatsApp Penjual".
5. **Panel Dasbor Seller (Auth Modal & Manajemen Toko):**
   - Tab Login & Tab Daftar Seller baru.
   - Tampilan profil lapak & status buka/tutup.
   - Form input menu makanan baru (dengan preview langsung).
   - Tabel/kartu daftar menu dengan tombol Edit dan Hapus.

---

### 8. Kriteria Pengujian (Acceptance Criteria Checklist)
- [x] Pengguna tanpa login dapat melihat semua katalog jualan dari penjual berbeda di kampus.
- [x] Fitur filter fakultas dan kategori berfungsi menyaring daftar makanan secara instan.
- [x] Tombol pemesanan berhasil memicu pembuatan link `wa.me` dengan format pesan pesanan yang terisi otomatis dan rapi.
- [x] Penjual dapat mendaftarkan lapak baru dengan data NIM, WA, dan Fakultas.
- [x] Penjual yang telah login dapat menambah menu baru, dan menu tersebut langsung muncul di katalog utama.
- [x] Penjual dapat mengubah status lapak (Buka/Tutup) dan status menu (Tersedia/Habis).
- [x] Tampilan antarmuka nyaman diakses di layar ponsel (mobile-friendly) maupun monitor laptop (desktop-responsive) tanpa ada elemen kepotong atau layout rusak.

================================================================================
ZONA BAWAH: MASTER CONTEXT & LOGIKA SISTEM TERKINI (LIVING CONTEXT)
================================================================================

- **Identitas Projek Aktif:** CampusMarket (Toko Kuliner Mahasiswa Kampus)
- **Model Operasional Aktif:** Single Admin Store (Toko Terpusat Milik Pengguna)
- **Status Arsitektur Aktif:** Standalone Web Application & Google Apps Script Backend Ready
- **Skema Kolom Database Terkini:**
  - `PengaturanToko`: `[NamaToko, NomorWhatsapp, AdminPassword, StatusToko, LokasiStandby]`
  - `DataMenuMakanan`: `[MenuId, NamaMenu, Kategori, Harga, FotoUrl, Deskripsi, TipeJualan, JadwalPengantaran, BatasWaktuPO, StatusMenu, JumlahDipesan, Stok, CustomWhatsapp]`
- **Logika Bisnis & Perilaku Terkini:**
  - Hak Akses Tambah/Kelola Produk: Murni dipegang oleh Admin/Pemilik Web melalui Panel Admin terproteksi password rahasia (default: `admin123`).
  - Fitur Edit Produk Interaktif: Admin dapat mengubah Nama Produk, Kategori, Harga, Sisa Stok (porsi), Nomor WhatsApp Khusus (titip jual), Tipe Jualan, Status Ketersediaan, Foto, Jadwal, dan Deskripsi.
  - Pemantauan Sisa Stok Real-Time & Kontrol Cepat: Setiap kartu produk di katalog menampilkan badge sisa stok porsi (`⚡ Stok: X porsi`, `🔥 Sisa X porsi`, atau `❌ Habis`). Stok otomatis berkurang saat pesanan dikirim ke WhatsApp (mencegah over-order). Pada Panel Admin, disediakan tombol cepat stepper `[-1]` dan `[+1]` pada setiap baris produk untuk memulihkan porsi stok secara instan jika pesanan di WhatsApp batal, tanpa perlu membuka form edit.
  - Fitur Titip Jual Mahasiswa (Consignment): Header web dilengkapi tombol aksi `💬 Mau Titip Jual? Chat Admin` yang membuka chat WhatsApp Admin dengan format pengajuan menu terstruktur. Produk titip jual dapat memiliki nomor WhatsApp penjual khusus (`CustomWhatsapp`) sehingga pesanan pembeli langsung terhubung ke mahasiswa penitip.
  - Alur Pengunjung/Pembeli: Seluruh mahasiswa kampus dapat langsung menjelajahi katalog tanpa login/registrasi.
  - Alur Pemesanan: Klik "Pesan via WA", mengisi data pemesan & lokasi kelas/gedung di kampus, lalu dialihkan ke chat WhatsApp penjual membawa template order terisi rapi otomatis.
  - Penomoran otomatis unik `MNU-YYYYMM-XXX` dan kode order `CM-YYYYMMDD-XXX`.
  - In-memory caching untuk responsivitas instan tanpa jeda loading.
- **Log Pembaruan Resmi:**
  - *v1.0.0 (2026-10-07):* Inisialisasi spesifikasi sistem awal.
  - *v1.1.0 (2026-10-07):* Rebranding resmi ke CampusMarket.
  - *v1.2.0 (2026-10-07):* Transformasi total ke model Single Admin Store (hanya pemilik yang bisa menginput/mengelola produk), nomor WhatsApp penjual terlampir jelas di katalog, dan sistem direct WhatsApp ordering otomatis untuk seluruh mahasiswa kampus tanpa hambatan registrasi.
  - *v1.3.0 (2026-10-09):* Fitur Edit Produk Lengkap di Panel Admin (Nama, Harga, Sisa Stok, Custom WA per Produk), Indikator Sisa Stok di Katalog, Tombol Pengajuan Titip Jual Mahasiswa via WhatsApp di Header, serta Safe Migration skema kolom `Stok` & `CustomWhatsapp`.
  - *v1.4.0 (2026-10-13):* Penambahan Fitur Quick Stock Stepper (Tombol Cepat `[-1]` dan `[+1] Pulihkan Stok`) di tabel Panel Admin untuk memudahkan pemilik toko memulihkan stok seketika saat pesanan WhatsApp batal atau saat restock produk.
