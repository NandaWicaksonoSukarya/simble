<?php
require "../config.php";
session_start();

// ====== Proteksi Tutor ======
if (!isset($_SESSION['login']) || ($_SESSION['role'] !== "tutor" && $_SESSION['role'] !== "admin")) {
    header("Location: ../index.php");
    exit;
}

$id_tutor = $_SESSION['id_tutor'] ?? null;

// ====== Cek ID di URL ======
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<script>alert('ID jadwal tidak valid.'); window.location='kelas.php';</script>";
    exit;
}

$id = (int)$_GET['id'];

// ====== Cek apakah jadwal ada ======
$q = mysqli_query($conn, "SELECT * FROM jadwal_kelas WHERE id = $id");
$cek = mysqli_fetch_assoc($q);

if (!$cek) {
    echo "<script>alert('Jadwal dengan ID $id tidak ditemukan.'); window.location='kelas.php';</script>";
    exit;
}

// ====== Admin boleh hapus semua ======
if ($_SESSION['role'] === "admin") {
    $hapus = mysqli_query($conn, "DELETE FROM jadwal_kelas WHERE id = $id");

    if ($hapus) {
        echo "<script>alert('Jadwal berhasil dihapus.'); window.location='kelas.php';</script>";
    } else {
        $e = mysqli_error($conn);
        echo "<script>alert('Gagal menghapus: $e'); window.location='kelas.php';</script>";
    }
    exit;
}

// ====== Tutor hanya boleh hapus jadwal miliknya ======
if ($cek['tutor'] != $id_tutor) {
    echo "<script>alert('Anda tidak boleh menghapus jadwal yang bukan milik Anda.'); window.location='kelas.php';</script>";
    exit;
}

// ====== Hapus ======
$hapus = mysqli_query($conn, "DELETE FROM jadwal_kelas WHERE id = $id");

if ($hapus) {
    echo "<script>alert('Jadwal berhasil dihapus.'); window.location='kelas.php';</script>";
} else {
    $err = mysqli_error($conn);
    echo "<script>alert('Gagal menghapus: $err'); window.location='kelas.php';</script>";
}
