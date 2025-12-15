# Sistem Informasi Bimbel - UI Slicing

Sistem Informasi Bimbel adalah aplikasi web untuk mengelola bimbingan belajar. Proyek ini merupakan UI slicing menggunakan Bootstrap 5 dengan data dummy, tanpa fungsionalitas backend atau integrasi API.

## 📁 Struktur Folder

```
├── index.html                 # Halaman login
├── assets/
│   ├── css/
│   │   └── style.css         # Custom CSS
│   └── js/
│       └── charts.js         # Chart.js configuration
├── admin/                     # Panel Admin
│   ├── dashboard.html        # Dashboard admin
│   ├── siswa.html           # Manajemen data siswa
│   ├── tutor.html           # Manajemen data tutor
│   ├── kelas.html           # Jadwal kelas
│   ├── presensi.html        # Presensi siswa & tutor
│   ├── pembayaran.html      # Manajemen pembayaran
│   ├── materi.html          # Materi & tugas online
│   ├── laporan.html         # Generate laporan
│   └── notifikasi.html      # Manajemen notifikasi
├── siswa/                     # Portal Siswa
│   ├── dashboard.html        # Dashboard siswa
│   ├── jadwal.html          # Jadwal kelas siswa
│   ├── materi.html          # Materi pembelajaran
│   ├── tugas.html           # Tugas siswa
│   ├── nilai.html           # Nilai & prestasi
│   ├── pembayaran.html      # Status pembayaran
│   └── profil.html          # Profil siswa
├── tutor/                     # Portal Tutor
│   ├── dashboard.html        # Dashboard tutor
│   ├── jadwal.html          # Jadwal mengajar
│   ├── presensi.html        # Presensi siswa
│   ├── materi.html          # Kelola materi
│   ├── tugas.html           # Tugas & penilaian
│   ├── siswa.html           # Data siswa
│   └── profil.html          # Profil tutor
└── pendaftaran/
    └── form-pendaftaran.html # Form pendaftaran siswa baru
```

## 🎯 Fitur Utama

### 1. Manajemen Pendaftaran Siswa
- Form pendaftaran online lengkap
- Upload dokumen (foto, kartu pelajar, rapor)
- Auto-generate ID siswa

### 2. Manajemen Kelas / Jadwal Les
- Jadwal kelas (hari, jam, tutor)
- Kalender les interaktif
- Info kelas yang berlangsung

### 3. Manajemen Tutor / Pengajar
- Profil tutor lengkap
- Penjadwalan tutor
- Kehadiran tutor

### 4. Presensi (Kehadiran)
- Absensi siswa dengan status (Hadir, Izin, Sakit, Alpha)
- Absensi tutor
- Rekapan harian/bulanan

### 5. Pembayaran / Administrasi
- Tagihan les
- Histori pembayaran
- Upload bukti transfer
- Invoice otomatis

### 6. Materi & Tugas Online
- Upload materi pembelajaran
- Tugas siswa dengan deadline
- Penilaian tugas

### 7. Dashboard Admin
- Grafik data siswa
- Grafik pembayaran
- Data kelas & tutor
- Aktivitas terbaru

### 8. Dashboard Siswa
- Lihat jadwal kelas
- Lihat nilai & prestasi
- Lihat dan kumpulkan tugas
- Lihat status pembayaran

### 9. Notifikasi
- WhatsApp/Email pengingat jadwal
- Pengingat pembayaran
- Pengumuman kelas
- Notifikasi otomatis

### 10. Laporan
- Laporan pembelajaran
- Laporan pembayaran
- Laporan kehadiran
- Export ke PDF/Excel

## 🚀 Cara Menggunakan

1. **Clone atau Download** repository ini
2. **Buka file `index.html`** di browser
3. **Navigasi:**
   - Klik "Login sebagai Admin" → Masuk ke panel admin
   - Klik "Login sebagai Siswa" → Masuk ke portal siswa
   - Klik "Login sebagai Tutor" → Masuk ke portal tutor
   - Klik "Daftar di sini" → Form pendaftaran siswa baru

## 🎨 Teknologi yang Digunakan

- **HTML5** - Struktur halaman
- **CSS3** - Styling
- **Bootstrap 5.3.0** - Framework CSS
- **Bootstrap Icons** - Icon library
- **Chart.js** - Grafik dan visualisasi data
- **JavaScript** - Interaksi dasar (modal, tabs, dll)

## 📊 Data Dummy

Semua data yang ditampilkan adalah data dummy untuk keperluan demonstrasi UI:
- Nama siswa, tutor, dan data pribadi
- Nilai, jadwal, dan presensi
- Pembayaran dan tagihan
- Materi dan tugas

## 🎯 Catatan Penting

- **Ini adalah UI slicing saja**, tidak ada fungsionalitas backend
- **Tidak ada integrasi API** atau database
- **Tidak ada validasi form** yang sebenarnya
- **Tidak ada autentikasi** login yang real
- Semua tombol dan link hanya untuk navigasi antar halaman

## 📱 Responsive Design

Website ini menggunakan Bootstrap 5 yang sudah responsive, sehingga dapat diakses dengan baik di:
- Desktop
- Tablet
- Mobile

## 🔗 Navigasi Cepat

### Halaman Admin:
- Dashboard: `admin/dashboard.html`
- Data Siswa: `admin/siswa.html`
- Data Tutor: `admin/tutor.html`
- Jadwal Kelas: `admin/kelas.html`
- Presensi: `admin/presensi.html`
- Pembayaran: `admin/pembayaran.html`
- Materi & Tugas: `admin/materi.html`
- Laporan: `admin/laporan.html`
- Notifikasi: `admin/notifikasi.html`

### Halaman Siswa:
- Dashboard: `siswa/dashboard.html`
- Jadwal: `siswa/jadwal.html`
- Materi: `siswa/materi.html`
- Tugas: `siswa/tugas.html`
- Nilai: `siswa/nilai.html`
- Pembayaran: `siswa/pembayaran.html`
- Profil: `siswa/profil.html`

### Halaman Tutor:
- Dashboard: `tutor/dashboard.html`
- Jadwal Mengajar: `tutor/jadwal.html`
- Presensi: `tutor/presensi.html`
- Materi: `tutor/materi.html`
- Tugas & Penilaian: `tutor/tugas.html`
- Data Siswa: `tutor/siswa.html`
- Profil: `tutor/profil.html`

### Lainnya:
- Login: `index.html`
- Pendaftaran: `pendaftaran/form-pendaftaran.html`

## 📝 Lisensi

Proyek ini dibuat untuk keperluan pembelajaran dan demonstrasi UI/UX design.

## 👨‍💻 Pengembangan Selanjutnya

Untuk mengembangkan sistem ini menjadi aplikasi yang fungsional, Anda perlu:
1. Membuat backend API (Node.js, PHP, Python, dll)
2. Integrasi database (MySQL, PostgreSQL, MongoDB, dll)
3. Implementasi autentikasi dan authorization
4. Validasi form dan error handling
5. Integrasi payment gateway
6. Integrasi WhatsApp/Email API untuk notifikasi
7. Upload file handling
8. Generate PDF untuk invoice dan laporan

---

**Selamat menggunakan! 🎉**
