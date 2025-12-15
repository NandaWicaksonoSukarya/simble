<?php
session_start();
require "../config.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: tugas.php");
    exit;
}

$id_tugas = intval($_POST['id_tugas']);
$nilaiArr = $_POST['nilai'] ?? [];
$catatanArr = $_POST['catatan'] ?? [];

foreach ($nilaiArr as $id_siswa => $nilai) {
    $nilai = intval($nilai);
    $catatan = mysqli_real_escape_string($conn, $catatanArr[$id_siswa] ?? '');

    // cek apakah nilai sudah ada
    $cek = mysqli_query($conn, "
        SELECT id_nilai FROM tugas_nilai 
        WHERE id_tugas = $id_tugas AND id_siswa = $id_siswa
    ");

    if (mysqli_num_rows($cek) > 0) {
        // UPDATE
        mysqli_query($conn, "
            UPDATE tugas_nilai 
            SET nilai = $nilai, catatan = '$catatan', updated_at = NOW() 
            WHERE id_tugas = $id_tugas AND id_siswa = $id_siswa
        ");
    } else {
        // INSERT
        mysqli_query($conn, "
            INSERT INTO tugas_nilai (id_tugas, id_siswa, nilai, catatan, updated_at)
            VALUES ($id_tugas, $id_siswa, $nilai, '$catatan', NOW())
        ");
    }
}

$_SESSION['success'] = "Nilai berhasil disimpan!";
header("Location: tugas_nilai.php?id_tugas=$id_tugas");
exit;
