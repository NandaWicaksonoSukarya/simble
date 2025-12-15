-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Dec 15, 2025 at 05:53 AM
-- Server version: 8.0.30
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `simbles`
--

-- --------------------------------------------------------

--
-- Table structure for table `jadwal`
--

CREATE TABLE `jadwal` (
  `id_jadwal` int NOT NULL,
  `id_kelas` int NOT NULL,
  `id_tutor` int NOT NULL,
  `id_mapel` int NOT NULL,
  `tanggal` date NOT NULL,
  `jam_mulai` time NOT NULL,
  `jam_selesai` time NOT NULL,
  `id_ruangan` int DEFAULT NULL,
  `status` enum('Aktif','Ditunda','Selesai') DEFAULT 'Aktif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `jadwal`
--

INSERT INTO `jadwal` (`id_jadwal`, `id_kelas`, `id_tutor`, `id_mapel`, `tanggal`, `jam_mulai`, `jam_selesai`, `id_ruangan`, `status`) VALUES
(6, 2, 2, 2, '2025-12-15', '13:00:00', '15:00:00', 3, 'Selesai'),
(7, 3, 1, 1, '2025-12-16', '14:00:00', '16:00:00', 2, 'Aktif'),
(8, 1, 3, 3, '2025-12-17', '10:00:00', '12:00:00', 1, 'Aktif');

-- --------------------------------------------------------

--
-- Table structure for table `kelas`
--

CREATE TABLE `kelas` (
  `id_kelas` int NOT NULL,
  `nama_kelas` varchar(50) NOT NULL,
  `id_mapel` int DEFAULT NULL,
  `id_tutor` int DEFAULT NULL,
  `status` enum('aktif','selesai') DEFAULT 'aktif',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `id_ruangan` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `kelas`
--

INSERT INTO `kelas` (`id_kelas`, `nama_kelas`, `id_mapel`, `id_tutor`, `status`, `created_at`, `id_ruangan`) VALUES
(1, 'reguler', NULL, NULL, 'aktif', '2025-12-13 13:17:22', 1),
(2, 'intensif', NULL, NULL, 'aktif', '2025-12-13 13:21:06', 2),
(3, 'private', NULL, NULL, 'aktif', '2025-12-13 13:21:33', 3);

-- --------------------------------------------------------

--
-- Table structure for table `kelas_siswa`
--

CREATE TABLE `kelas_siswa` (
  `id_kelas` int NOT NULL,
  `id_siswa` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `log_aktivitas`
--

CREATE TABLE `log_aktivitas` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `user_role` varchar(50) DEFAULT NULL,
  `aktivitas` varchar(255) DEFAULT NULL,
  `detail` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mapel`
--

CREATE TABLE `mapel` (
  `id_mapel` int NOT NULL,
  `nama_mapel` varchar(50) DEFAULT NULL,
  `kode_mapel` varchar(20) DEFAULT NULL,
  `jenjang` enum('SD','SMP','SMA') DEFAULT NULL,
  `kurikulum` varchar(50) DEFAULT NULL,
  `deskripsi` text,
  `status` enum('Aktif','Nonaktif') DEFAULT 'Aktif',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `mapel`
--

INSERT INTO `mapel` (`id_mapel`, `nama_mapel`, `kode_mapel`, `jenjang`, `kurikulum`, `deskripsi`, `status`, `created_at`, `updated_at`) VALUES
(1, 'IPA', 'IPA-1', 'SD', 'Kurikulum 2013', 'IPA mempelajari ilmiah', 'Aktif', '2025-12-15 04:01:43', NULL),
(2, 'Bahasa Inggris', 'BING-2', 'SD', 'Kurikulum Merdeka', 'Bahasa Asing', 'Aktif', '2025-12-15 04:03:05', NULL),
(3, 'Matematika', 'MTK-3', 'SMP', 'KTSP', 'Mempelajari matematika menengah', 'Aktif', '2025-12-15 04:04:11', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `materi`
--

CREATE TABLE `materi` (
  `id_materi` int NOT NULL,
  `id_kelas` int NOT NULL,
  `id_mapel` int NOT NULL,
  `id_tutor` int DEFAULT NULL,
  `judul` varchar(100) NOT NULL,
  `deskripsi` text,
  `file` varchar(255) DEFAULT NULL,
  `status` enum('Aktif','Nonaktif') DEFAULT 'Aktif',
  `tgl_upload` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pembayaran`
--

CREATE TABLE `pembayaran` (
  `id_pembayaran` int NOT NULL,
  `id_siswa` int NOT NULL,
  `id_kelas` int DEFAULT NULL,
  `nominal` decimal(12,2) NOT NULL,
  `bulan` date DEFAULT NULL,
  `tgl_bayar` datetime DEFAULT CURRENT_TIMESTAMP,
  `status` enum('Lunas','Belum Lunas','Menunggu Konfirmasi') DEFAULT 'Belum Lunas',
  `metode_pembayaran` varchar(20) DEFAULT 'Transfer',
  `bukti_bayar` varchar(255) DEFAULT NULL,
  `keterangan` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pengaturan`
--

CREATE TABLE `pengaturan` (
  `id` int NOT NULL,
  `nama_setting` varchar(100) NOT NULL,
  `nilai_setting` text,
  `keterangan` text,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `penilaian_tugas`
--

CREATE TABLE `penilaian_tugas` (
  `id` int NOT NULL,
  `id_tugas` int NOT NULL,
  `id_siswa` int NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `uploaded_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `nilai` decimal(5,2) DEFAULT NULL,
  `catatan` text,
  `status` enum('Menunggu','Dinilai','Dikembalikan') DEFAULT 'Menunggu',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `presensi`
--

CREATE TABLE `presensi` (
  `id_presensi` int NOT NULL,
  `id_siswa` int NOT NULL,
  `id_kelas` int NOT NULL,
  `id_jadwal` int DEFAULT NULL,
  `tanggal` date NOT NULL,
  `waktu` time DEFAULT NULL,
  `status` enum('Hadir','Izin','Sakit','Alpha') DEFAULT 'Alpha',
  `keterangan` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `presensi`
--

INSERT INTO `presensi` (`id_presensi`, `id_siswa`, `id_kelas`, `id_jadwal`, `tanggal`, `waktu`, `status`, `keterangan`, `created_at`) VALUES
(2, 1, 2, 6, '2025-12-15', '05:07:04', 'Hadir', '', '2025-12-15 05:07:04');

-- --------------------------------------------------------

--
-- Table structure for table `presensi_tutor`
--

CREATE TABLE `presensi_tutor` (
  `id` int NOT NULL,
  `id_tutor` int NOT NULL,
  `id_jadwal` int NOT NULL,
  `status` enum('Hadir','Izin','Sakit','Tidak Hadir') DEFAULT 'Tidak Hadir',
  `waktu_mulai` time DEFAULT NULL,
  `waktu_selesai` time DEFAULT NULL,
  `durasi_jam` decimal(4,2) DEFAULT NULL,
  `catatan` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `presensi_tutor`
--

INSERT INTO `presensi_tutor` (`id`, `id_tutor`, `id_jadwal`, `status`, `waktu_mulai`, `waktu_selesai`, `durasi_jam`, `catatan`, `created_at`) VALUES
(2, 2, 6, 'Hadir', '13:00:00', '15:00:00', '2.00', '', '2025-12-15 05:06:50');

-- --------------------------------------------------------

--
-- Table structure for table `ruangan`
--

CREATE TABLE `ruangan` (
  `id_ruangan` int NOT NULL,
  `nama_ruangan` varchar(50) NOT NULL,
  `keterangan` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `ruangan`
--

INSERT INTO `ruangan` (`id_ruangan`, `nama_ruangan`, `keterangan`) VALUES
(1, 'smart', NULL),
(2, 'juara', NULL),
(3, 'jenius', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `siswa`
--

CREATE TABLE `siswa` (
  `id_siswa` int NOT NULL,
  `nama` varchar(100) NOT NULL,
  `jk` enum('Laki-laki','Perempuan') NOT NULL,
  `tmp_lahir` varchar(50) DEFAULT NULL,
  `tgl_lahir` date DEFAULT NULL,
  `alamat` text,
  `email` varchar(100) DEFAULT NULL,
  `telepon` varchar(20) DEFAULT NULL,
  `sekolah` varchar(100) DEFAULT NULL,
  `program` varchar(255) DEFAULT NULL,
  `ortu` varchar(100) DEFAULT NULL,
  `ortu_telp` varchar(20) DEFAULT NULL,
  `pekerjaan` varchar(50) DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `kartu` varchar(255) DEFAULT NULL,
  `rapor` varchar(255) DEFAULT NULL,
  `bukti_pembayaran` varchar(255) DEFAULT NULL,
  `status_bayar` enum('belum bayar','sudah bayar') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT 'belum bayar',
  `nib` varchar(20) DEFAULT NULL,
  `id_kelas` int DEFAULT NULL,
  `tgl_daftar` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status_aktif` enum('aktif','tidak aktif','lulus','berhenti') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT 'tidak aktif',
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `siswa`
--

INSERT INTO `siswa` (`id_siswa`, `nama`, `jk`, `tmp_lahir`, `tgl_lahir`, `alamat`, `email`, `telepon`, `sekolah`, `program`, `ortu`, `ortu_telp`, `pekerjaan`, `foto`, `kartu`, `rapor`, `bukti_pembayaran`, `status_bayar`, `nib`, `id_kelas`, `tgl_daftar`, `status_aktif`, `updated_at`) VALUES
(1, 'Muhammad Isya', 'Laki-laki', 'tegal', '2006-10-10', 'jl. tegal', 'isya@gmail.com', '085434', 'smk tegal', '2', 'mamat', '0834457', 'pengusaha', 'foto_693f8ecf5e28e.png', 'kartu_693f8ecf5e6f4.png', 'rapor_693f8ecf5eb62.png', 'bukti_693f8ecf5efec.png', 'sudah bayar', 'BBL-2025-0001', 2, '2025-12-15 04:30:07', 'aktif', NULL),
(2, 'dendi ramadhani', 'Perempuan', 'Banjarnegara', '2004-09-09', 'jl. banjar', 'dendi@gmail.com', '0876653', 'sman 1 Banjar', '1', 'aden', '081219212', 'petani', 'foto_693f8f5c206fd.png', 'kartu_693f8f5c20d09.png', 'rapor_693f8f5c212ca.png', 'bukti_693f8f5c218e8.png', 'sudah bayar', 'BBL-2025-0002', 3, '2025-12-15 04:32:28', 'aktif', NULL),
(3, 'saiful hidayat', 'Laki-laki', 'purbalingga', '2025-08-08', 'jl. ipul', 'ipul@gmail.com', '09201021', 'smk 1 pbg', '3', 'cecep', '08212913', 'buruh', 'foto_693f8fe167b90.png', 'kartu_693f8fe168069.png', 'rapor_693f8fe1686dd.png', 'bukti_693f8fe168c2f.png', 'sudah bayar', 'BBL-2025-0003', 1, '2025-12-15 04:34:41', 'aktif', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tugas`
--

CREATE TABLE `tugas` (
  `id_tugas` int NOT NULL,
  `id_kelas` int NOT NULL,
  `id_mapel` int NOT NULL,
  `id_tutor` int NOT NULL,
  `judul` varchar(255) NOT NULL,
  `deskripsi` text,
  `deadline` datetime DEFAULT NULL,
  `lampiran_path` varchar(255) DEFAULT NULL,
  `status` enum('Aktif','Selesai','Terlambat') DEFAULT 'Aktif',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tugas`
--

INSERT INTO `tugas` (`id_tugas`, `id_kelas`, `id_mapel`, `id_tutor`, `judul`, `deskripsi`, `deadline`, `lampiran_path`, `status`, `created_at`, `updated_at`) VALUES
(2, 2, 2, 2, 'reading', 'kerjakan dengan benar', '2025-12-19 12:07:00', 'uploads/tugas/1765775282_693f97b2e75c5.pdf', 'Aktif', '2025-12-15 05:08:02', '2025-12-15 05:08:02');

-- --------------------------------------------------------

--
-- Table structure for table `tutor`
--

CREATE TABLE `tutor` (
  `id_tutor` int NOT NULL,
  `nama_tutor` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `email` varchar(100) NOT NULL,
  `telepon` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `id_mapel` int NOT NULL,
  `pendidikan` varchar(100) NOT NULL,
  `pengalaman` int NOT NULL,
  `alamat` text NOT NULL,
  `status` enum('aktif','nonaktif','cuti') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT 'aktif',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `foto` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tutor`
--

INSERT INTO `tutor` (`id_tutor`, `nama_tutor`, `email`, `telepon`, `id_mapel`, `pendidikan`, `pengalaman`, `alamat`, `status`, `created_at`, `foto`) VALUES
(1, 'Khoirunisa', 'nisa@gmail.com', '08120191021', 1, 'S1 - IPA', 2, 'jl. purbalingga', 'aktif', '2025-12-15 04:05:46', NULL),
(2, 'Nanda Wicaksono', 'nandasukarya22@gmail.com', '081385277154', 2, 'S1 - Informatika', 5, 'jl. utan panjang', 'aktif', '2025-12-15 04:08:30', NULL),
(3, 'Octavia', 'maul@gmail.com', '0819219131', 3, 'S1 - Matematika', 1, 'jl. mlang', 'aktif', '2025-12-15 04:09:43', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id_user` int NOT NULL,
  `id_tutor` int DEFAULT NULL,
  `id_siswa` int DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','siswa','tutor') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id_user`, `id_tutor`, `id_siswa`, `username`, `password`, `role`) VALUES
(1, NULL, NULL, 'admin', '0192023a7bbd73250516f069df18b500', 'admin'),
(8, 1, NULL, 'nisa', '827ccb0eea8a706c4c34a16891f84e7b', 'tutor'),
(9, 2, NULL, 'kevin', '827ccb0eea8a706c4c34a16891f84e7b', 'tutor'),
(10, 3, NULL, 'tata', '827ccb0eea8a706c4c34a16891f84e7b', 'tutor'),
(12, NULL, 1, 'isa', '779b4d5f8313022902638fd6a0d4c2a3', 'siswa'),
(13, NULL, 2, 'dendi', '4f96a26ceb0a8abe566b4c57ea154d42', 'siswa'),
(14, NULL, 3, 'ipul', 'ec59bd496016bb82280c4ee444129d54', 'siswa');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `jadwal`
--
ALTER TABLE `jadwal`
  ADD PRIMARY KEY (`id_jadwal`),
  ADD KEY `id_kelas` (`id_kelas`),
  ADD KEY `id_tutor` (`id_tutor`),
  ADD KEY `id_mapel` (`id_mapel`),
  ADD KEY `jadwal_ibfk_ruangan` (`id_ruangan`);

--
-- Indexes for table `kelas`
--
ALTER TABLE `kelas`
  ADD PRIMARY KEY (`id_kelas`),
  ADD KEY `id_mapel` (`id_mapel`),
  ADD KEY `id_tutor` (`id_tutor`);

--
-- Indexes for table `kelas_siswa`
--
ALTER TABLE `kelas_siswa`
  ADD PRIMARY KEY (`id_kelas`,`id_siswa`),
  ADD KEY `id_siswa` (`id_siswa`);

--
-- Indexes for table `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mapel`
--
ALTER TABLE `mapel`
  ADD PRIMARY KEY (`id_mapel`);

--
-- Indexes for table `materi`
--
ALTER TABLE `materi`
  ADD PRIMARY KEY (`id_materi`),
  ADD KEY `id_kelas` (`id_kelas`),
  ADD KEY `fk_materi_mapel` (`id_mapel`);

--
-- Indexes for table `pembayaran`
--
ALTER TABLE `pembayaran`
  ADD PRIMARY KEY (`id_pembayaran`),
  ADD KEY `idx_pembayaran_siswa` (`id_siswa`),
  ADD KEY `idx_pembayaran_kelas` (`id_kelas`),
  ADD KEY `idx_pembayaran_status` (`status`),
  ADD KEY `idx_pembayaran_bulan` (`bulan`),
  ADD KEY `idx_pembayaran_tgl` (`tgl_bayar`);

--
-- Indexes for table `pengaturan`
--
ALTER TABLE `pengaturan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nama_setting` (`nama_setting`);

--
-- Indexes for table `penilaian_tugas`
--
ALTER TABLE `penilaian_tugas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_tugas_siswa` (`id_tugas`,`id_siswa`),
  ADD KEY `id_siswa` (`id_siswa`);

--
-- Indexes for table `presensi`
--
ALTER TABLE `presensi`
  ADD PRIMARY KEY (`id_presensi`),
  ADD UNIQUE KEY `unique_presensi` (`id_siswa`,`id_kelas`,`tanggal`),
  ADD KEY `id_kelas` (`id_kelas`),
  ADD KEY `id_jadwal` (`id_jadwal`);

--
-- Indexes for table `presensi_tutor`
--
ALTER TABLE `presensi_tutor`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_tutor_jadwal` (`id_tutor`,`id_jadwal`),
  ADD KEY `id_jadwal` (`id_jadwal`);

--
-- Indexes for table `ruangan`
--
ALTER TABLE `ruangan`
  ADD PRIMARY KEY (`id_ruangan`);

--
-- Indexes for table `siswa`
--
ALTER TABLE `siswa`
  ADD PRIMARY KEY (`id_siswa`);

--
-- Indexes for table `tugas`
--
ALTER TABLE `tugas`
  ADD PRIMARY KEY (`id_tugas`),
  ADD KEY `id_kelas` (`id_kelas`),
  ADD KEY `id_mapel` (`id_mapel`),
  ADD KEY `id_tutor` (`id_tutor`);

--
-- Indexes for table `tutor`
--
ALTER TABLE `tutor`
  ADD PRIMARY KEY (`id_tutor`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `id_mapel` (`id_mapel`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `id_siswa` (`id_siswa`),
  ADD KEY `fk_users_tutor` (`id_tutor`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `jadwal`
--
ALTER TABLE `jadwal`
  MODIFY `id_jadwal` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `kelas`
--
ALTER TABLE `kelas`
  MODIFY `id_kelas` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mapel`
--
ALTER TABLE `mapel`
  MODIFY `id_mapel` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `materi`
--
ALTER TABLE `materi`
  MODIFY `id_materi` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `pembayaran`
--
ALTER TABLE `pembayaran`
  MODIFY `id_pembayaran` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `pengaturan`
--
ALTER TABLE `pengaturan`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `penilaian_tugas`
--
ALTER TABLE `penilaian_tugas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `presensi`
--
ALTER TABLE `presensi`
  MODIFY `id_presensi` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `presensi_tutor`
--
ALTER TABLE `presensi_tutor`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `ruangan`
--
ALTER TABLE `ruangan`
  MODIFY `id_ruangan` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `siswa`
--
ALTER TABLE `siswa`
  MODIFY `id_siswa` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tugas`
--
ALTER TABLE `tugas`
  MODIFY `id_tugas` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tutor`
--
ALTER TABLE `tutor`
  MODIFY `id_tutor` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id_user` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `jadwal`
--
ALTER TABLE `jadwal`
  ADD CONSTRAINT `jadwal_ibfk_1` FOREIGN KEY (`id_kelas`) REFERENCES `kelas` (`id_kelas`),
  ADD CONSTRAINT `jadwal_ibfk_2` FOREIGN KEY (`id_tutor`) REFERENCES `tutor` (`id_tutor`),
  ADD CONSTRAINT `jadwal_ibfk_3` FOREIGN KEY (`id_mapel`) REFERENCES `mapel` (`id_mapel`),
  ADD CONSTRAINT `jadwal_ibfk_ruangan` FOREIGN KEY (`id_ruangan`) REFERENCES `ruangan` (`id_ruangan`);

--
-- Constraints for table `kelas`
--
ALTER TABLE `kelas`
  ADD CONSTRAINT `kelas_ibfk_1` FOREIGN KEY (`id_mapel`) REFERENCES `mapel` (`id_mapel`),
  ADD CONSTRAINT `kelas_ibfk_2` FOREIGN KEY (`id_tutor`) REFERENCES `tutor` (`id_tutor`);

--
-- Constraints for table `kelas_siswa`
--
ALTER TABLE `kelas_siswa`
  ADD CONSTRAINT `kelas_siswa_ibfk_1` FOREIGN KEY (`id_kelas`) REFERENCES `kelas` (`id_kelas`),
  ADD CONSTRAINT `kelas_siswa_ibfk_2` FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id_siswa`);

--
-- Constraints for table `materi`
--
ALTER TABLE `materi`
  ADD CONSTRAINT `fk_materi_mapel` FOREIGN KEY (`id_mapel`) REFERENCES `mapel` (`id_mapel`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `materi_ibfk_1` FOREIGN KEY (`id_kelas`) REFERENCES `kelas` (`id_kelas`);

--
-- Constraints for table `pembayaran`
--
ALTER TABLE `pembayaran`
  ADD CONSTRAINT `fk_pembayaran_kelas` FOREIGN KEY (`id_kelas`) REFERENCES `kelas` (`id_kelas`),
  ADD CONSTRAINT `fk_pembayaran_siswa` FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id_siswa`),
  ADD CONSTRAINT `pembayaran_ibfk_1` FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id_siswa`);

--
-- Constraints for table `penilaian_tugas`
--
ALTER TABLE `penilaian_tugas`
  ADD CONSTRAINT `penilaian_tugas_ibfk_1` FOREIGN KEY (`id_tugas`) REFERENCES `tugas` (`id_tugas`),
  ADD CONSTRAINT `penilaian_tugas_ibfk_2` FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id_siswa`);

--
-- Constraints for table `presensi`
--
ALTER TABLE `presensi`
  ADD CONSTRAINT `presensi_ibfk_1` FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id_siswa`),
  ADD CONSTRAINT `presensi_ibfk_2` FOREIGN KEY (`id_kelas`) REFERENCES `kelas` (`id_kelas`),
  ADD CONSTRAINT `presensi_ibfk_3` FOREIGN KEY (`id_jadwal`) REFERENCES `jadwal` (`id_jadwal`);

--
-- Constraints for table `presensi_tutor`
--
ALTER TABLE `presensi_tutor`
  ADD CONSTRAINT `presensi_tutor_ibfk_1` FOREIGN KEY (`id_tutor`) REFERENCES `tutor` (`id_tutor`),
  ADD CONSTRAINT `presensi_tutor_ibfk_2` FOREIGN KEY (`id_jadwal`) REFERENCES `jadwal` (`id_jadwal`);

--
-- Constraints for table `tugas`
--
ALTER TABLE `tugas`
  ADD CONSTRAINT `tugas_ibfk_1` FOREIGN KEY (`id_kelas`) REFERENCES `kelas` (`id_kelas`),
  ADD CONSTRAINT `tugas_ibfk_2` FOREIGN KEY (`id_mapel`) REFERENCES `mapel` (`id_mapel`),
  ADD CONSTRAINT `tugas_ibfk_3` FOREIGN KEY (`id_tutor`) REFERENCES `tutor` (`id_tutor`);

--
-- Constraints for table `tutor`
--
ALTER TABLE `tutor`
  ADD CONSTRAINT `tutor_ibfk_1` FOREIGN KEY (`id_mapel`) REFERENCES `mapel` (`id_mapel`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_tutor` FOREIGN KEY (`id_tutor`) REFERENCES `tutor` (`id_tutor`) ON DELETE CASCADE,
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id_siswa`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
