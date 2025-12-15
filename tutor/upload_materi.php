<?php
session_start();
include "../config.php"; // file config kamu

// tentukan variable config yang tersedia (beberapa project pakai $conn, beberapa $config)
if (isset($conn) && $conn instanceof mysqli) {
    $db = $conn;
} elseif (isset($conn) && $conn instanceof mysqli) {
    $db = $config;
} else {
    die("Database connection not found. Periksa file config.php");
}

// Pastikan tutor sudah login
if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit;
}
$username = $_SESSION['username'];

// Hanya proses jika request POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // jika dipanggil langsung, redirect kembali ke halaman materi
    header("Location: materi.php");
    exit;
}

// Ambil data POST dengan safe default (hindari passing null ke mysqli_real_escape_string)
$judul     = isset($_POST['judul']) ? trim($_POST['judul']) : '';
$mapel     = isset($_POST['id_mapel']) ? trim($_POST['id_mapel']) : '';
$kelas     = isset($_POST['id_kelas']) ? trim($_POST['id_kelas']) : '';
$deskripsi = isset($_POST['deskripsi']) ? trim($_POST['deskripsi']) : ''; // optional

// Escape semua string sebelum query
$judul     = mysqli_real_escape_string($db, (string)$judul);
$mapel     = mysqli_real_escape_string($db, (string)$mapel);
$kelas     = mysqli_real_escape_string($db, (string)$kelas);
$deskripsi = mysqli_real_escape_string($db, (string)$deskripsi);
$tutor     = mysqli_real_escape_string($db, (string)$username);

// Siapkan data file
$file_path = null;
$file_type = null;
$file_size = 0;

if (isset($_FILES['lampiran']) && $_FILES['lampiran']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = __DIR__ . "/../uploads/materi/"; // pastikan folder ini ada dan writable
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $originalName = basename($_FILES['lampiran']['name']);
    $ext = pathinfo($originalName, PATHINFO_EXTENSION);
    // buat nama file unik
    $newFileName = time() . "_" . bin2hex(random_bytes(6)) . ($ext ? "." . $ext : "");
    $targetPath = $uploadDir . $newFileName;

    if (move_uploaded_file($_FILES['lampiran']['tmp_name'], $targetPath)) {
        // simpan path relatif dari root project (sesuaikan bila perlu)
        $file_path = "uploads/materi/" . $newFileName;
        $file_type = $_FILES['lampiran']['type'];
        $file_size = (int) $_FILES['lampiran']['size'];
    } else {
        // gagal upload
        $_SESSION['error'] = "Gagal mengupload file lampiran.";
        header("Location: materi.php");
        exit;
    }
}

// NOTE: berdasarkan struktur materi yang kamu kirim, kolom tabel adalah:
// id, judul, mapel, kelas, deskripsi, file_path, file_type, file_size, tutor, created_at
// jadi query INSERT sesuai kolom-kolom itu (tanpa 'deadline' karena tidak ada di tabel)

// Siapkan nilai untuk query (gunakan NULL untuk file_path jika tidak ada file)
$file_path_sql = ($file_path !== null) ? "'" . mysqli_real_escape_string($db, $file_path) . "'" : "NULL";
$file_type_sql = ($file_type !== null) ? "'" . mysqli_real_escape_string($db, $file_type) . "'" : "NULL";
$file_size_sql = (int) $file_size;

$query = "
    INSERT INTO materi
    (judul, id_mapel, id_kelas, deskripsi, file_path, file_type, file_size, tutor, created_at)
    VALUES
    ('$judul', '$mapel', '$kelas', '$deskripsi', $file_path_sql, $file_type_sql, $file_size_sql, '$tutor', NOW())
";

if (mysqli_query($db, $query)) {
    $_SESSION['success'] = "Materi berhasil diupload.";
    header("Location: materi.php?success=1");
    exit;
} else {
    // debugging info (hapus/ubah jadi log di production)
    $err = mysqli_error($db);
    $_SESSION['error'] = "Database error: $err";
    header("Location: materi.php?error=1");
    exit;
}


// Insert ke database
$query = "
    INSERT INTO materi 
    (judul, id_mapel, id_kelas, deskripsi, file_path, file_type, file_size, id_tutor, created_at)
    VALUES 
    ('$judul', '$mapel', '$kelas', '$deskripsi', '$deadline', '$lampiran_path', '$lampiran_type', '$lampiran_size', '$username', '$status', NOW())
";

if (mysqli_query($conn, $query)) {
    header("Location: materi.php?success=1");
    exit;
} else {
    echo "Error: " . mysqli_error($conn);
}
