<?php
require "../config.php";
session_start();

if (!isset($_SESSION['login']) || $_SESSION['role'] !== "admin") {
    header("Location: ../index.php");
    exit;
}

$id_tutor = $_GET['id_tutor'] ?? 0;

$q = mysqli_query($conn, "SELECT * FROM tutor WHERE id_tutor='$id_tutor'");
$data = mysqli_fetch_assoc($q);

if (isset($_POST['submit'])) {

    $nama        = $_POST['nama'];
    $email       = $_POST['email'];
    $telepon     = $_POST['telepon'];
    $mapel       = $_POST['mapel'];
    $pendidikan  = $_POST['pendidikan'];
    $status      = $_POST['status'];

    mysqli_query($conn, "UPDATE tutor SET 
        nama_tutor='$nama',
        email='$email',
        telepon='$telepon',
        id_mapel='$mapel',
        pendidikan='$pendidikan',
       
        status='$status'
    WHERE id_tutor='$id_tutor'");

    header("Location: tutor.php?msg=updated");
    exit;
}

$qSiswa = mysqli_query($conn, "
    SELECT COUNT(DISTINCT s.id_siswa) AS total_siswa
    FROM jadwal j
    JOIN siswa s ON s.id_kelas = j.id_kelas
    WHERE j.id_tutor = '$id_tutor'
");
$siswa = mysqli_fetch_assoc($qSiswa);

$qKelasMinggu = mysqli_query($conn, "
    SELECT COUNT(*) AS total_kelas
    FROM jadwal
    WHERE id_tutor = '$id_tutor'
");
$kelasMinggu = mysqli_fetch_assoc($qKelasMinggu);



$mapelList = mysqli_query($conn, "SELECT * FROM mapel ORDER BY nama_mapel ASC");

?>
<!DOCTYPE html>
<html>

<head>
    <title>Edit Tutor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-4">

        <h3>Edit Tutor</h3>
        <a href="tutor.php" class="btn btn-secondary btn-sm mb-3">Kembali</a>

        <form method="POST">
            <div class="card p-3">

                <div class="mb-3">
                    <label>Nama</label>
                    <input type="text" name="nama" value="<?= $data['nama_tutor'] ?>" class="form-control">
                </div>

                <div class="mb-3">
                    <label>Email</label>
                    <input type="email" name="email" value="<?= $data['email'] ?>" class="form-control">
                </div>

                <div class="mb-3">
                    <label>Telepon</label>
                    <input type="text" name="telepon" value="<?= $data['telepon'] ?>" class="form-control">
                </div>

                <div class="mb-3">
                    <label>Mata Pelajaran</label>
                    <select name="mapel" class="form-select">
                        <option value="">-- Pilih Mata Pelajaran --</option>
                        <?php while ($m = mysqli_fetch_assoc($mapelList)) { ?>
                            <option value="<?= $m['id_mapel'] ?>"
                                <?= $data['id_mapel'] == $m['id_mapel'] ? 'selected' : '' ?>>
                                <?= $m['nama_mapel'] ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>


                <div class="mb-3">
                    <label>Pendidikan</label>
                    <input type="text" name="pendidikan" value="<?= $data['pendidikan'] ?>" class="form-control">
                </div>

                <div class="mb-3">
                    <label>Jumlah Siswa</label>
                    <input type="number"
                        class="form-control"
                        value="<?= $siswa['total_siswa'] ?>"
                        readonly>
                </div>

                <div class="mb-3">
                    <label>Kelas per Minggu</label>
                    <input type="number"
                        class="form-control"
                        value="<?= $kelasMinggu['total_kelas'] ?>"
                        readonly>
                </div>


                <div class="mb-3">
                    <label>Status</label>
                    <select name="status" class="form-select">
                        <option value="Aktif" <?= $data['status'] == "Aktif" ? "selected" : "" ?>>Aktif</option>
                        <option value="Tidak Aktif" <?= $data['status'] == "Tidak Aktif" ? "selected" : "" ?>>Tidak Aktif</option>
                    </select>
                </div>

                <button type="submit" name="submit" class="btn btn-primary">Simpan Perubahan</button>

            </div>
        </form>

    </div>
</body>

</html>