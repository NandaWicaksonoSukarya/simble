<?php
session_start();
require "../config.php";

// proteksi login
if (!isset($_SESSION['login']) || ($_SESSION['role'] !== 'tutor' && $_SESSION['role'] !== 'admin')) {
    header("Location: ../index.php");
    exit;
}

// ambil ID tugas
if (!isset($_GET['id_tugas'])) {
    echo "ID tugas tidak ditemukan!";
    exit;
}

$id_tugas = intval($_GET['id_tugas']);

// ambil detail tugas
$qTugas = mysqli_query($conn, "
    SELECT t.*, k.nama_kelas, m.nama_mapel 
    FROM tugas t
    LEFT JOIN kelas k ON t.id_kelas = k.id_kelas
    LEFT JOIN mapel m ON t.id_mapel = m.id_mapel
    WHERE t.id_tugas = $id_tugas
");

$tugas = mysqli_fetch_assoc($qTugas);
if (!$tugas) {
    echo "Tugas tidak ditemukan!";
    exit;
}

// ambil daftar siswa di kelas tugas ini
$id_kelas = $tugas['id_kelas'];
$qSiswa = mysqli_query($conn, "
    SELECT * FROM siswa WHERE id_kelas = $id_kelas ORDER BY nama ASC
");

// ambil nilai existing
$qNilai = mysqli_query($conn, "
    SELECT * FROM nilai WHERE id_tugas = $id_tugas
");

$nilai_data = [];
while ($n = mysqli_fetch_assoc($qNilai)) {
    $nilai_data[$n['id_siswa']] = $n;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Penilaian Tugas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background-color: #f8f9fa;
        }

        .sidebar {
            width: 230px;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            background: #0d6efd;
            padding: 20px;
            color: white;
        }

        .sidebar h4 {
            color: #fff;
            margin-bottom: 20px;
        }

        .sidebar a {
            display: block;
            padding: 10px 12px;
            margin-bottom: 8px;
            color: #fff;
            text-decoration: none;
            border-radius: 6px;
        }

        .sidebar a:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .content {
            margin-left: 250px;
            padding: 25px;
        }
    </style>
</head>

<body>

    <!-- SIDEBAR -->
    <div class="sidebar">
        <h4>Tutor Panel</h4>
        <a href="index.php">Dashboard</a>
        <a href="tugas.php">Tugas</a>
        <a href="jadwal.php">Jadwal</a>
        <a href="logout.php">Logout</a>
    </div>

    <!-- MAIN CONTENT -->
    <div class="content">

        <div class="card shadow-sm">
            <div class="card-body">

                <h3 class="mb-0">Penilaian Tugas</h3>
                <small class="text-muted">
                    <?= $tugas['judul'] ?> — <?= $tugas['nama_mapel'] ?> (<?= $tugas['nama_kelas'] ?>)
                </small>

                <hr>

                <form action="tugas_nilai_simpan.php" method="POST">
                    <input type="hidden" name="id_tugas" value="<?= $id_tugas ?>">

                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr class="text-center">
                                <th>Nama Siswa</th>
                                <th width="120">Nilai</th>
                                <th>Catatan</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php while ($s = mysqli_fetch_assoc($qSiswa)): ?>
                                <?php
                                $id_siswa = $s['id_siswa'];
                                $nilai = $nilai_data[$id_siswa]['nilai'] ?? '';
                                $catatan = $nilai_data[$id_siswa]['catatan'] ?? '';
                                ?>
                                <tr>
                                    <td><?= $s['nama'] ?></td>

                                    <td>
                                        <input type="number"
                                            name="nilai[<?= $id_siswa ?>]"
                                            class="form-control text-center"
                                            min="0" max="100"
                                            value="<?= $nilai ?>">
                                    </td>

                                    <td>
                                        <input type="text"
                                            name="catatan[<?= $id_siswa ?>]"
                                            class="form-control"
                                            value="<?= $catatan ?>">
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>

                    <button class="btn btn-primary">Simpan Nilai</button>
                    <a href="tugas.php" class="btn btn-secondary">Kembali</a>
                </form>

            </div>
        </div>

    </div>

</body>

</html>