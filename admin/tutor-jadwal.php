<?php
require "../config.php";
session_start();

if (!isset($_SESSION['login']) || $_SESSION['role'] !== "admin") {
    header("Location: ../index.php");
    exit;
}

$id_tutor = $_GET['id_tutor'] ?? 0;
$id_jadwal = $_GET['id_jadwal'] ?? 0;

$tutor = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM tutor WHERE id_tutor='$id_tutor'"));
$jadwal = mysqli_query($conn, "SELECT * FROM jadwal_kelas WHERE id_jadwal='$id_jadwal'");
?>
<!DOCTYPE html>
<html>

<head>
    <title>Jadwal Tutor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-4">

        <h3>Jadwal Tutor: <?= $tutor['nama'] ?></h3>
        <a href="tutor.php" class="btn btn-secondary btn-sm mb-3">Kembali</a>

        <div class="card p-3">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Hari</th>
                        <th>Jam</th>
                        <th>Kelas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($jadwal)) : ?>
                        <tr>
                            <td><?= $row['hari'] ?></td>
                            <td><?= $row['jam'] ?></td>
                            <td><?= $row['kelas'] ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

    </div>
</body>

</html>