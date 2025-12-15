<?php
require "../config.php";
session_start();

if (!isset($_SESSION['login']) || $_SESSION['role'] !== "admin") {
    header("Location: ../index.php");
    exit;
}

// ambil id tutor dari URL
$id_tutor = $_GET['id_tutor'] ?? null;

// jika id tidak ada
if (!$id_tutor) {
    die("ID tutor tidak ditemukan di URL.");
}

// ambil data tutor
$result = mysqli_query($conn, "SELECT tutor.*, mapel.nama_mapel 
FROM tutor
LEFT JOIN mapel ON tutor.id_mapel = mapel.id_mapel
WHERE tutor.id_tutor = '$id_tutor'
");
$tutor = mysqli_fetch_assoc($result);

// cek apakah data tutor ada
if (!$tutor) {
    die("Data tutor tidak ditemukan.");
}

// membuat inisial aman
$nama = trim($tutor['nama_tutor']);
$parts = explode(" ", $nama);

if (count($parts) >= 2) {
    // 2 kata atau lebih
    $inisial = strtoupper(substr($parts[0], 0, 1) . substr(end($parts), 0, 1));
} else {
    // hanya 1 kata
    $inisial = strtoupper(substr($nama, 0, 1));
}
?>

<!DOCTYPE html>
<html lang="id_tutor">

<head>
    <meta charset="UTF-8">
    <title>Detail Tutor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: #fff;
        }

        .avatar {
            width: 140px;
            height: 140px;
            background: #0d6efd;
            border-radius: 50%;
            font-size: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            margin: auto;
        }

        .section-title {
            font-size: 15px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        table td,
        table th {
            padding: 6px 0;
            font-size: 14px;
        }

        .stat-box h3 {
            font-size: 32px;
            margin: 0;
            font-weight: 600;
        }

        .stat-box small {
            font-size: 13px;
            color: #666;
        }
    </style>

</head>

<body>

    <div class="container py-4">

        <h5 class="mb-3">Detail Tutor</h5>
        <hr>

        <div class="text-center">
            <div class="avatar"><?= $inisial ?></div>
            <h3 class="mt-3 mb-0"><?= $tutor['nama_tutor'] ?></h3>
            <p class="text-muted">Tutor <?= $tutor['nama_mapel'] ?></p>
        </div>

        <div class="row mt-4">
            <div class="col-md-6">
                <div class="section-title">Informasi Pribadi</div>
                <table class="w-100">
                    <tr>
                        <th width="120">Email</th>
                        <td><?= $tutor['email'] ?></td>
                    </tr>
                    <tr>
                        <th>No. Telepon</th>
                        <td><?= $tutor['telepon'] ?></td>
                    </tr>
                    <tr>
                        <th>Alamat</th>
                        <td><?= $tutor['alamat'] ?></td>
                    </tr>
                </table>
            </div>

            <div class="col-md-6">
                <div class="section-title">Informasi Akademik</div>
                <table class="w-100">
                    <tr>
                        <th width="120">Mata Pelajaran</th>
                        <td><?= $tutor['nama_mapel'] ?></td>
                    </tr>
                    <tr>
                        <th>Pendidikan</th>
                        <td><?= $tutor['pendidikan'] ?></td>
                    </tr>
                    <tr>
                        <th>Pengalaman</th>
                        <td><?= $tutor['pengalaman'] ?> Tahun</td>
                    </tr>
                </table>
            </div>
        </div>

        <hr class="my-4">

        <!-- <div class="row text-center">
            <div class="col-md-4 stat-box">
                <h3><?= $tutor['kelas_minggu'] ?></h3>
                <small>Kelas/Minggu</small>
            </div>
            <div class="col-md-4 stat-box">
                <h3><?= $tutor['jumlah_siswa'] ?></h3>
                <small>Total Siswa</small>
            </div>

        </div> -->

        <div class="text-end mt-4">
            <a href="tutor.php" class="btn btn-secondary">Tutup</a>
        </div>

    </div>

</body>

</html>