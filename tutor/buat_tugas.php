<?php
session_start();
require "../config.php";

// Proteksi login
if (!isset($_SESSION['login']) || ($_SESSION['role'] !== 'tutor' && $_SESSION['role'] !== 'admin')) {
    header("Location: ../index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Ambil data POST dengan aman
    $judul     = mysqli_real_escape_string($conn, $_POST['judul'] ?? '');
    $id_mapel  = intval($_POST['id_mapel'] ?? 0);
    $id_kelas  = intval($_POST['id_kelas'] ?? 0);
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi'] ?? '');
    $deadline  = !empty($_POST['deadline']) ? $_POST['deadline'] : NULL;

    // ID tutor ambil dari session (benar)
    $id_tutor = intval($_SESSION['id_tutor'] ?? 0);

    // validasi wajib
    if ($judul === '' || $id_mapel === 0 || $id_kelas === 0) {
        $_SESSION['error'] = "Semua field wajib diisi!";
        header("Location: tugas.php");
        exit;
    }

    // handle lampiran
    $lampiran_path = NULL;

    if (isset($_FILES['lampiran']) && $_FILES['lampiran']['error'] === 0) {

        $file = $_FILES['lampiran'];
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        $allowed = ['pdf', 'doc', 'docx', 'ppt', 'pptx'];

        if (in_array($ext, $allowed)) {

            $upload_dir = "../uploads/tugas/";
            if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);

            $new_name = time() . "_" . uniqid() . "." . $ext;
            $dest = $upload_dir . $new_name;

            if (move_uploaded_file($file['tmp_name'], $dest)) {
                $lampiran_path = "uploads/tugas/" . $new_name;
            }
        }
    }

    // Query sesuai kondisi ada / tidak ada lampiran
    if ($lampiran_path !== NULL) {
        $query = "
            INSERT INTO tugas 
            (judul, id_mapel, id_kelas, deskripsi, deadline, lampiran_path, id_tutor, status, created_at)
            VALUES
            ('$judul', $id_mapel, $id_kelas, '$deskripsi', " .
            ($deadline ? "'$deadline'" : "NULL") . ", 
            '$lampiran_path', $id_tutor, 'Aktif', NOW())
        ";
    } else {
        $query = "
            INSERT INTO tugas 
            (judul, id_mapel, id_kelas, deskripsi, deadline, id_tutor, status, created_at)
            VALUES
            ('$judul', $id_mapel, $id_kelas, '$deskripsi', " .
            ($deadline ? "'$deadline'" : "NULL") . ", 
            $id_tutor, 'Aktif', NOW())
        ";
    }

    if (mysqli_query($conn, $query)) {
        $_SESSION['success'] = "Tugas berhasil dibuat!";
    } else {
        $_SESSION['error'] = "Gagal membuat tugas: " . mysqli_error($conn);
    }

    header("Location: tugas.php");
    exit;
} else {
    header("Location: tugas.php");
    exit;
}
