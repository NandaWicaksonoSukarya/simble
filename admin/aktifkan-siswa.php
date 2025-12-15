<?php
require "../config.php";
session_start();

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    die("ID tidak valid");
}

// ambil data siswa
$q = mysqli_query($conn, "
    SELECT * FROM siswa 
    WHERE id_siswa = $id
");

if (mysqli_num_rows($q) === 0) {
    die("Siswa tidak ditemukan");
}

$siswa = mysqli_fetch_assoc($q);

// ambil data kelas
$qKelas = mysqli_query($conn, "SELECT * FROM kelas ORDER BY nama_kelas ASC");

// proses submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_kelas = (int)$_POST['id_kelas'];

    if ($id_kelas <= 0) {
        die("Kelas wajib dipilih");
    }

    // generate NIB BERDASARKAN ID (AMAN)
    $nib = "BBL-" . date('Y') . "-" . str_pad($id, 4, '0', STR_PAD_LEFT);

    mysqli_query($conn, "
        UPDATE siswa SET
            id_kelas = $id_kelas,
            nib = '$nib',
            status_aktif = 'aktif',
            status_bayar = 'sudah bayar'
        WHERE id_siswa = $id
    ");

    header("Location: siswa.php?msg=aktif_sukses");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Aktifkan Siswa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="card">
        <div class="card-header bg-success text-white">
            Aktifkan Siswa
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Nama Siswa</label>
                    <input type="text" class="form-control"
                        value="<?= htmlspecialchars($siswa['nama']) ?>" readonly>
                </div>

                <div class="mb-3">
                    <label class="form-label">Pilih Kelas</label>
                    <select name="id_kelas" class="form-select" required>
                        <option value="">-- Pilih Kelas --</option>
                        <?php while ($k = mysqli_fetch_assoc($qKelas)): ?>
                            <option value="<?= $k['id_kelas'] ?>">
                                <?= htmlspecialchars($k['nama_kelas']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <button class="btn btn-success">
                    Aktifkan & Generate NIB
                </button>
                <a href="siswa.php" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>

</body>
</html>
