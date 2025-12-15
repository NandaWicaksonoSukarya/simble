<?php
include '../config.php'; // sesuaikan dengan koneksi kamu
session_start();

// Pastikan user sudah login
if (!isset($_SESSION['id_siswa'])) {
    die("Akses ditolak!");
}

$id_siswa = $_SESSION['id_siswa'];
$bulan = $_POST['bulan'];
$jumlah = $_POST['jumlah'];

// --- Validasi file ---
if (!isset($_FILES['bukti']) || $_FILES['bukti']['error'] !== UPLOAD_ERR_OK) {
    die("Upload gagal. File tidak ditemukan.");
}

$allowed = ['jpg', 'jpeg', 'png', 'pdf'];
$namaFile = $_FILES['bukti']['name'];
$tmpFile = $_FILES['bukti']['tmp_name'];

$ext = strtolower(pathinfo($namaFile, PATHINFO_EXTENSION));
if (!in_array($ext, $allowed)) {
    die("Format file tidak diperbolehkan.");
}

// Nama file baru
$newName = 'bukti_' . $id_siswa . '_' . time() . '.' . $ext;

// Path simpan
$tujuan = "../uploads/bukti/" . $newName;

// Simpan file
if (!move_uploaded_file($tmpFile, $tujuan)) {
    die("Gagal menyimpan file.");
}

// Masukkan ke database
$query = "INSERT INTO pembayaran (id_siswa, bulan, jumlah, status)
          VALUES ('$id_siswa', '$bulan', '$jumlah', 'Menunggu Verifikasi')";

if (mysqli_query($conn, $query)) {
    header("Location: pembayaran.php?success=1");
    exit();
} else {
    echo "Gagal menyimpan ke database: " . mysqli_error($conn);
}
