# 📋 Daftar Lengkap Halaman Sistem Informasi Bimbel

## 🏠 Halaman Utama
- **Login** (`index.html`) - Halaman login dengan pilihan role Admin/Siswa/Tutor

## 👨‍💼 Panel Admin (9 Halaman)

### 1. Dashboard (`admin/dashboard.html`)
- Statistik total siswa, tutor, kelas aktif, pendapatan
- Grafik pendaftaran siswa 6 bulan terakhir
- Grafik status pembayaran (pie chart)
- Kelas hari ini
- Aktivitas terbaru

### 2. Data Siswa (`admin/siswa.html`)
- Filter & search siswa
- Tabel data siswa lengkap
- Modal detail siswa
- Tombol tambah, edit, hapus
- Pagination

### 3. Data Tutor (`admin/tutor.html`)
- Statistik tutor (total, aktif, mengajar hari ini, cuti)
- Card view profil tutor dengan foto
- Informasi kontak dan statistik mengajar
- Modal detail tutor
- Modal tambah tutor baru

### 4. Jadwal Kelas (`admin/kelas.html`)
- Kalender view jadwal bulanan
- List view jadwal kelas
- Filter dan search
- Modal tambah jadwal baru
- Info tutor, ruangan, waktu

### 5. Presensi (`admin/presensi.html`)
- Tab presensi siswa dan tutor
- Form input presensi dengan radio button (Hadir/Izin/Sakit/Alpha)
- Rekapan kehadiran bulanan
- Export ke Excel
- Statistik kehadiran

### 6. Pembayaran (`admin/pembayaran.html`)
- Statistik pembayaran (total pendapatan, lunas, belum lunas, terlambat)
- Filter pembayaran
- Tabel tagihan & pembayaran
- Modal detail pembayaran dengan riwayat
- Modal input pembayaran baru
- Upload bukti transfer

### 7. Materi & Tugas (`admin/materi.html`)
- Tab materi pembelajaran dan tugas
- Card view materi dengan badge mata pelajaran
- Upload materi baru
- Buat tugas baru dengan deadline
- Penilaian tugas siswa
- Statistik pengumpulan tugas

### 8. Laporan (`admin/laporan.html`)
- Tab laporan pembelajaran, pembayaran, kehadiran
- Card untuk generate berbagai jenis laporan
- Riwayat laporan yang telah dibuat
- Filter periode dan format (PDF/Excel)
- Statistik ringkasan

### 9. Notifikasi (`admin/notifikasi.html`)
- Quick actions untuk kirim notifikasi
- Pengaturan notifikasi otomatis (toggle on/off)
- Riwayat notifikasi terkirim
- Modal kirim notifikasi manual (jadwal, pembayaran, pengumuman, tugas)
- Filter channel (WhatsApp/Email)

## 👨‍🎓 Portal Siswa (7 Halaman)

### 1. Dashboard (`siswa/dashboard.html`)
- Profil siswa dengan foto
- Quick stats (kelas minggu ini, tugas aktif, materi tersedia, status pembayaran)
- Jadwal kelas hari ini
- Notifikasi terbaru
- Tugas aktif dengan progress bar
- Nilai terbaru

### 2. Jadwal Kelas (`siswa/jadwal.html`)
- Kalender view jadwal bulanan
- Tabel jadwal minggu ini
- Info waktu, tutor, ruangan
- Status kehadiran (hadir/berlangsung/akan datang)

### 3. Materi Pembelajaran (`siswa/materi.html`)
- Filter mata pelajaran dan search
- Statistik materi (total, PDF, PPT, baru)
- Card view materi terbaru dengan badge "Baru"
- Tabel semua materi
- Modal detail materi lengkap
- Tombol download dan share
- Info tutor dan statistik views/downloads

### 4. Tugas (`siswa/tugas.html`)
- Filter status tugas (semua/aktif/selesai/terlambat)
- Card tugas dengan progress bar deadline
- Badge status deadline (besok/3 hari/1 minggu)
- Modal upload tugas
- Tugas selesai dengan nilai
- Info lampiran soal

### 5. Nilai (`siswa/nilai.html`)
- Summary cards (rata-rata, tertinggi, terendah, total tugas)
- Filter mata pelajaran dan periode
- Card nilai per mata pelajaran dengan tabel detail
- Grafik perkembangan nilai (line chart)
- Statistik per mata pelajaran

### 6. Pembayaran (`siswa/pembayaran.html`)
- Status pembayaran bulan ini (tagihan, dibayar, sisa)
- Badge status (Lunas/Belum Lunas/Terlambat)
- Informasi rekening bank dan e-wallet
- Modal upload bukti transfer
- Riwayat pembayaran lengkap
- Tombol cetak invoice

### 7. Profil (`siswa/profil.html`)
- Foto profil dengan tombol ganti foto
- Statistik siswa (kehadiran, nilai, tugas, pembayaran)
- Form edit informasi pribadi
- Informasi orang tua/wali
- Form ubah password

## 👨‍🏫 Portal Tutor (7 Halaman)

### 1. Dashboard (`tutor/dashboard.html`)
- Profil tutor dengan foto
- Quick stats (kelas minggu ini, tugas aktif, perlu dinilai, materi diupload)
- Jadwal mengajar hari ini dengan tombol isi presensi
- Quick actions (upload materi, buat tugas, isi presensi, input nilai)
- Tugas perlu dinilai
- Statistik kelas (rata-rata nilai per kelas)

### 2. Jadwal Mengajar (`tutor/jadwal.html`)
- Statistik jadwal (kelas minggu ini, hari ini, total siswa, jam/minggu)
- Kalender view jadwal bulanan
- Tabel jadwal minggu ini dengan materi
- Status kelas (selesai/berlangsung/akan datang)
- Tombol isi presensi untuk kelas berlangsung

### 3. Presensi (`tutor/presensi.html`)
- Filter tanggal, kelas, waktu
- Form presensi dengan radio button per siswa
- Status kehadiran (Hadir/Izin/Sakit/Alpha)
- Kolom keterangan
- Tombol simpan presensi

### 4. Materi (`tutor/materi.html`)
- Statistik materi (total, views, downloads, upload minggu ini)
- Tombol upload materi baru
- Tabel daftar materi dengan statistik
- Modal upload materi
- Tombol lihat, edit, hapus

### 5. Tugas & Penilaian (`tutor/tugas.html`)
- Tab daftar tugas dan penilaian
- Card tugas dengan statistik pengumpulan
- Tombol buat tugas baru
- Modal buat tugas dengan deadline
- Tabel penilaian dengan input nilai
- Download file tugas siswa

### 6. Data Siswa (`tutor/siswa.html`)
- Statistik siswa per kelas
- Filter dan search siswa
- Tabel data siswa dengan kehadiran dan nilai
- Modal detail siswa dengan statistik lengkap

### 7. Profil (`tutor/profil.html`)
- Foto profil dengan tombol ganti foto
- Statistik tutor (total siswa, kehadiran, materi, pengalaman)
- Form edit informasi pribadi
- Informasi pendidikan dan pengalaman
- Form ubah password

## 📝 Halaman Pendaftaran

### Form Pendaftaran (`pendaftaran/form-pendaftaran.html`)
- Form lengkap data pribadi
- Data akademik (asal sekolah, kelas, program diminati)
- Data orang tua/wali
- Upload dokumen (foto, kartu pelajar, rapor)
- Info auto-generate ID siswa
- Validasi form

---

## 📊 Total Halaman: 24 Halaman

- **Halaman Utama:** 1
- **Panel Admin:** 9
- **Portal Siswa:** 7
- **Portal Tutor:** 7

## 🎨 Fitur UI/UX

### Komponen Bootstrap yang Digunakan:
- ✅ Cards
- ✅ Tables (responsive)
- ✅ Modals
- ✅ Forms (input, select, textarea, file upload, radio, checkbox)
- ✅ Buttons & Button Groups
- ✅ Badges
- ✅ Progress Bars
- ✅ Tabs
- ✅ Pagination
- ✅ Alerts
- ✅ Dropdowns
- ✅ Navigation (sidebar)

### Fitur Interaktif:
- ✅ Grafik dengan Chart.js (line chart, doughnut chart)
- ✅ Kalender view untuk jadwal
- ✅ Filter & Search
- ✅ Modal pop-up
- ✅ Tab navigation
- ✅ Responsive design
- ✅ Icon dari Bootstrap Icons

### Data Dummy:
- ✅ 5+ siswa dummy
- ✅ 6+ tutor dummy
- ✅ 8+ jadwal kelas
- ✅ 10+ materi pembelajaran
- ✅ 5+ tugas
- ✅ Data pembayaran
- ✅ Data presensi
- ✅ Data nilai

## 🚀 Cara Akses

1. **Buka `index.html`** di browser
2. **Pilih role:**
   - Klik "Login sebagai Admin" → `admin/dashboard.html`
   - Klik "Login sebagai Siswa" → `siswa/dashboard.html`
   - Klik "Login sebagai Tutor" → `tutor/dashboard.html`
3. **Navigasi** menggunakan sidebar menu

## 📱 Responsive

Semua halaman responsive dan dapat diakses dengan baik di:
- 💻 Desktop
- 📱 Tablet
- 📱 Mobile

---

**Status:** ✅ Semua halaman sudah lengkap dan siap digunakan!
