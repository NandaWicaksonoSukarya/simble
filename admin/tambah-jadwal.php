<?php
session_start();
require "../config.php";

// hanya admin
if (!isset($_SESSION['login']) || $_SESSION['role'] !== "admin") {
    header("Location: ../index.php");
    exit;
}

/**
 * PROSES SIMPAN JADWAL
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id_mapel    = intval($_POST['id_mapel']);
    $id_kelas    = intval($_POST['id_kelas']);
    $id_tutor    = intval($_POST['id_tutor']);
    $tanggal     = $_POST['tanggal'];
    $jam_mulai   = $_POST['jam_mulai'];
    $jam_selesai = $_POST['jam_selesai'];
    $status      = $_POST['status'];
    $id_ruangan = intval($_POST['id_ruangan']);


    // validasi sederhana
    if (!$id_mapel || !$id_kelas || !$id_tutor || !$tanggal || !$id_ruangan) {
        $_SESSION['error'] = "Semua field wajib diisi.";
        header("Location: tambah-jadwal.php");
        exit;
    }


    $sql = "
        INSERT INTO jadwal
        (id_mapel, id_kelas, id_tutor, tanggal, jam_mulai, jam_selesai,id_ruangan, status)
        VALUES
        ('$id_mapel', '$id_kelas', '$id_tutor', '$tanggal', '$jam_mulai', '$jam_selesai', '$id_ruangan', '$status')
    ";

    if (!$conn->query($sql)) {
        die("Gagal simpan jadwal: " . $conn->error);
    }

    $_SESSION['success'] = "Jadwal berhasil ditambahkan.";
    header("Location: kelas.php?added=1");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Tambah Jadwal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="p-4">

    <h3 class="mb-3">Tambah Jadwal Baru</h3>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?= $_SESSION['error'];
            unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="card p-4">

        <!-- MAPEL -->
        <div class="mb-3">
            <label class="form-label">Mata Pelajaran</label>
            <select name="id_mapel" class="form-select" required>
                <option value="">-- Pilih Mapel --</option>
                <?php
                $qMapel = $conn->query("SELECT id_mapel, nama_mapel FROM mapel ORDER BY nama_mapel");
                while ($m = $qMapel->fetch_assoc()):
                ?>
                    <option value="<?= $m['id_mapel'] ?>"><?= $m['nama_mapel'] ?></option>
                <?php endwhile; ?>
            </select>
        </div>

        <!-- KELAS -->
        <div class="mb-3">
            <label class="form-label">Kelas</label>
            <select name="id_kelas" class="form-select" required>
                <option value="">-- Pilih Kelas --</option>
                <?php
                $qKelas = $conn->query("SELECT id_kelas, nama_kelas FROM kelas ORDER BY nama_kelas");
                while ($k = $qKelas->fetch_assoc()):
                ?>
                    <option value="<?= $k['id_kelas'] ?>"><?= $k['nama_kelas'] ?></option>
                <?php endwhile; ?>
            </select>
        </div>

        <!-- TUTOR -->
        <div class="mb-3">
            <label class="form-label">Tutor</label>
            <select name="id_tutor" class="form-select" required>
                <option value="">-- Pilih Tutor --</option>
                <?php
                $qTutor = $conn->query("SELECT id_tutor, nama_tutor FROM tutor ORDER BY nama_tutor");
                while ($t = $qTutor->fetch_assoc()):
                ?>
                    <option value="<?= $t['id_tutor'] ?>"><?= $t['nama_tutor'] ?></option>
                <?php endwhile; ?>
            </select>
        </div>

        <!-- TANGGAL -->
        <div class="mb-3">
            <label class="form-label">Tanggal</label>
            <input type="date" name="tanggal" class="form-control" required>
        </div>

        <!-- JAM -->
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Jam Mulai</label>
                <input type="time" name="jam_mulai" class="form-control" required>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Jam Selesai</label>
                <input type="time" name="jam_selesai" class="form-control" required>
            </div>
        </div>

        <!-- RUANGAN -->
        <div class="mb-3">
            <label class="form-label">Ruangan</label>
            <select name="id_ruangan" class="form-select" required>
                <option value="">-- Pilih Ruangan --</option>
                <?php
                $q = mysqli_query($conn, "SELECT * FROM ruangan ORDER BY nama_ruangan");
                while ($r = mysqli_fetch_assoc($q)) {
                    echo "<option value='{$r['id_ruangan']}'>{$r['nama_ruangan']}</option>";
                }
                ?>
            </select>
        </div>



        <!-- STATUS -->
        <div class="mb-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
                <option value="Aktif">Aktif</option>
                <option value="Berjalan">Berjalan</option>
                <option value="Selesai">Selesai</option>
                <option value="Ditunda">Ditunda</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Simpan</button>
        <a href="kelas.php" class="btn btn-secondary">Kembali</a>
    </form>

</body>

</html>