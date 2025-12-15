<?php
session_start();

require "../config.php";

error_reporting(E_ALL);
ini_set('display_errors', 1);

// hanya admin
if (!isset($_SESSION['login']) || $_SESSION['role'] !== "admin") {
    header("Location: ../index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nama       = mysqli_real_escape_string($conn, trim($_POST['nama_tutor']));
    $email      = mysqli_real_escape_string($conn, trim($_POST['email']));
    $telepon    = mysqli_real_escape_string($conn, trim($_POST['telepon']));
    $id_mapel   = intval($_POST['id_mapel']);
    $pendidikan = mysqli_real_escape_string($conn, trim($_POST['pendidikan']));
    $status     = mysqli_real_escape_string($conn, trim($_POST['status']));
    $alamat     = mysqli_real_escape_string($conn, trim($_POST['alamat']));
    $pengalaman = intval($_POST['pengalaman']);
    $username   = mysqli_real_escape_string($conn, trim($_POST['username']));

    if ($nama === "" || $email === "" || $username === "") {
        $_SESSION['error'] = "Nama, Email, dan Username wajib diisi.";
        header("Location: tambahtutor.php");
        exit;
    }

    // cek username
    $cek = mysqli_query($conn, "SELECT 1 FROM users WHERE username='$username' LIMIT 1");
    if (mysqli_num_rows($cek) > 0) {
        $_SESSION['error'] = "Username sudah digunakan.";
        header("Location: tambahtutor.php");
        exit;
    }

    // password default MD5
    $raw_password = "12345";
    $password_md5 = md5($raw_password);

    // insert tutor
    $insertTutor = mysqli_query($conn, "
        INSERT INTO tutor
        (nama_tutor, email, telepon, id_mapel, pendidikan, status, pengalaman, alamat, created_at)
        VALUES
        ('$nama', '$email', '$telepon', '$id_mapel', '$pendidikan', '$status', '$pengalaman', '$alamat', NOW())
    ");

    if (!$insertTutor) {
        die("Gagal insert tutor: " . mysqli_error($conn));
    }

    $id_tutor = mysqli_insert_id($conn);

    // insert users
    $insertUser = mysqli_query($conn, "
        INSERT INTO users (username, password, role, id_tutor)
        VALUES ('$username', '$password_md5', 'tutor', '$id_tutor')
    ");

    if (!$insertUser) {
        die("Gagal insert users: " . mysqli_error($conn));
    }

    $_SESSION['success'] = "Tutor berhasil ditambahkan.
    Username: $username
    Password: $raw_password";

    header("Location: tutor.php");
    exit;
}
?>


<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Tutor - Sistem Bimbel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>
    <div class="container-fluid">
        <div class="row">

            <!-- SIDEBAR -->
            <div class="col-md-2 sidebar p-0">
                <div class="text-center text-white py-4">
                    <i class="bi bi-person-badge" style="font-size: 2.5rem;"></i>
                    <h5 class="mt-2">Bimbel System</h5>
                    <small>Admin Panel</small>
                </div>

                <nav class="nav flex-column">
                    <a class="nav-link" href="dashboardadmin.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                    <a class="nav-link" href="siswa/siswa.php"><i class="bi bi-people"></i> Data Siswa</a>
                    <a class="nav-link active" href="tutor.php"><i class="bi bi-person-badge"></i> Data Tutor</a>
                    <a class="nav-link" href="kelas/kelas.php"><i class="bi bi-calendar3"></i> Jadwal Kelas</a>
                    <a class="nav-link" href="pembayaran/index.php"><i class="bi bi-cash-coin"></i> Pembayaran</a>
                    <a class="nav-link" href="mapel.php"><i class="bi bi-journal-text"></i> Mata Pelajaran</a>
                    <a class="nav-link" href="laporan/index.php"><i class="bi bi-file-earmark-text"></i> Laporan</a>
                    <a class="nav-link" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
                </nav>
            </div>

            <!-- MAIN -->
            <div class="col-md-10 content-wrapper">

                <div class="page-header d-flex justify-content-between align-items-center">
                    <div>
                        <h2>Tambah Tutor Baru</h2>
                        <p class="text-muted">Isi form berikut untuk menambah tutor</p>
                    </div>

                    <a href="tutor.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Kembali
                    </a>
                </div>

                <div class="card shadow-sm">
                    <div class="card-body">

                        <form method="POST">

                            <div class="row">

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Nama Lengkap</label>
                                    <input type="text" name="nama_tutor" class="form-control" required>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" class="form-control" required>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">No Telepon</label>
                                    <input type="text" name="telepon" class="form-control" required>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Mata Pelajaran</label>
                                    <select name="id_mapel" class="form-select" required>
                                        <option value="">-- Pilih Mapel --</option>
                                        <?php
                                        $mapelQuery = mysqli_query($conn, "SELECT id_mapel, nama_mapel FROM mapel");
                                        while ($m = mysqli_fetch_assoc($mapelQuery)) {
                                            echo "<option value='{$m['id_mapel']}'>{$m['nama_mapel']}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label>Pendidikan Terakhir</label>
                                    <input type="text" name="pendidikan" class="form-control" required>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label>Status Tutor</label>
                                    <select name="status" class="form-select" required>
                                        <option value="aktif">Aktif</option>
                                        <option value="nonaktif">Tidak Aktif</option>
                                        <option value="cuti">Cuti</option>
                                    </select>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label>Pengalaman</label>
                                    <input type="number" name="pengalaman" class="form-control" required>
                                </div>

                                <div class="col-md-12 mb-3">
                                    <label>Alamat</label>
                                    <textarea name="alamat" class="form-control" required></textarea>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label>Username Tutor</label>
                                    <input type="text" name="username" class="form-control" required>
                                </div>

                            </div>

                            <!-- 🔥 TOMBOL HARUS DI DALAM FORM -->
                            <button type="submit" name="tambah" class="btn btn-primary mt-3">
                                <i class="bi bi-save"></i> Simpan Data
                            </button>

                        </form>

                    </div>
                </div>

            </div>

        </div>

    </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>