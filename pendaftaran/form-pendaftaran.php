<?php
require "../config.php";

// Ambil daftar mapel (program)
$qMapel = $conn->query("SELECT id_mapel, nama_mapel FROM mapel ORDER BY nama_mapel ASC");

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // ====================== AMBIL DATA FORM ======================
    $nama = htmlspecialchars(trim($_POST['nama']));
    $jk = $_POST['jk'];
    $tmp = htmlspecialchars(trim($_POST['tmp']));
    $tgl = $_POST['tgl'];
    $alamat = htmlspecialchars(trim($_POST['alamat']));
    $email = htmlspecialchars(trim($_POST['email']));
    $telp = htmlspecialchars(trim($_POST['telp']));
    $sekolah = htmlspecialchars(trim($_POST['sekolah']));
    $program = implode(", ", $_POST['program'] ?? []);
    $ortu = htmlspecialchars(trim($_POST['ortu']));
    $ortu_telp = htmlspecialchars(trim($_POST['ortu_telp']));
    $pekerjaan = htmlspecialchars(trim($_POST['pekerjaan']));
    $username = htmlspecialchars(trim($_POST['username']));
    $password = md5($_POST['password']); // MD5

    // ====================== CEK DUPLIKASI USERNAME ======================
    $cekUser = $conn->prepare("SELECT id_user FROM users WHERE username=?");
    $cekUser->bind_param("s", $username);
    $cekUser->execute();
    $cekUser->store_result();
    if($cekUser->num_rows > 0){
        die("<div class='alert alert-danger'>Username sudah digunakan, silakan pilih yang lain.</div>");
    }
    $cekUser->close();

    // ====================== UPLOAD FILES ======================
    $folders = [
        'foto' => '../uploads/foto/',
        'kartu' => '../uploads/kartu/',
        'rapor' => '../uploads/rapor/',
        'bukti' => '../uploads/bukti/'
    ];

    foreach ($folders as $f) {
        if (!is_dir($f)) mkdir($f, 0777, true);
    }

    $files = ['foto', 'kartu', 'rapor', 'bukti'];
    $uploaded = [];
    $allowedExt = ['jpg','jpeg','png','pdf'];
    $maxSize = 2 * 1024 * 1024; // 2MB

    foreach ($files as $file) {
        if (!empty($_FILES[$file]['name'])) {
            $ext = strtolower(pathinfo($_FILES[$file]['name'], PATHINFO_EXTENSION));
            if(!in_array($ext, $allowedExt)) {
                die("<div class='alert alert-danger'>File $file harus berekstensi jpg/jpeg/png/pdf</div>");
            }
            if($_FILES[$file]['size'] > $maxSize) {
                die("<div class='alert alert-danger'>File $file terlalu besar, maksimal 2MB</div>");
            }
            $filename = $file . "_" . uniqid() . "." . $ext; // unik
            if (!move_uploaded_file($_FILES[$file]['tmp_name'], $folders[$file] . $filename)) {
                die("<div class='alert alert-danger'>Gagal upload file $file</div>");
            }
            $uploaded[$file] = $filename;
        } else {
            $uploaded[$file] = "";
        }
    }

    // ====================== INSERT KE TABEL SISWA ======================
    $stmt = $conn->prepare("INSERT INTO siswa 
        (nama, jk, tmp_lahir, tgl_lahir, alamat, email, telepon, sekolah, program, ortu, ortu_telp, pekerjaan, foto, kartu, rapor, bukti_pembayaran, status_bayar, status_aktif)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'belum bayar','tidak aktif')
    ");
    $stmt->bind_param(
        "ssssssssssssssss",
        $nama, $jk, $tmp, $tgl, $alamat, $email, $telp, $sekolah, $program, $ortu, $ortu_telp, $pekerjaan,
        $uploaded['foto'], $uploaded['kartu'], $uploaded['rapor'], $uploaded['bukti']
    );

    if($stmt->execute()) {
        $idSiswa = $stmt->insert_id; // ambil ID siswa baru
        $stmt->close();

        // ====================== INSERT KE TABEL USERS ======================
        $role = 'siswa';
        $stmtUser = $conn->prepare("INSERT INTO users (username, password, role, id_siswa) VALUES (?, ?, ?, ?)");
        $stmtUser->bind_param("sssi", $username, $password, $role, $idSiswa);
        $stmtUser->execute();
        $stmtUser->close();

        echo "<script>
            alert('Pendaftaran berhasil! Silakan tunggu verifikasi pembayaran. NIB & kelas akan diberikan setelah diverifikasi.');
            window.location='../index.php';
        </script>";

    } else {
        echo "<div class='alert alert-danger'>Terjadi kesalahan: " . $stmt->error . "</div>";
    }
}
?>


<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Pendaftaran - Sistem Informasi Bimbel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>

<body class="bg-light">
    <div class="container py-5">
        <div class="row">
            <div class="col-md-8 mx-auto">

                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="bi bi-person-plus-fill"></i> Form Pendaftaran Siswa Baru</h4>
                    </div>

                    <div class="card-body p-4">

                        <form method="POST" enctype="multipart/form-data">

                            <!-- ==================== DATA PRIBADI ==================== -->
                            <h5 class="mb-3">Data Pribadi</h5>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Nama Lengkap *</label>
                                    <input name="nama" type="text" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Jenis Kelamin *</label>
                                    <select name="jk" class="form-select" required>
                                        <option disabled selected>Pilih</option>
                                        <option>Laki-laki</option>
                                        <option>Perempuan</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Tempat Lahir *</label>
                                    <input name="tmp" type="text" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Tanggal Lahir *</label>
                                    <input name="tgl" type="date" class="form-control" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Alamat *</label>
                                <textarea name="alamat" class="form-control" rows="2" required></textarea>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Email *</label>
                                    <input name="email" type="email" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">No. Telepon *</label>
                                    <input name="telp" type="tel" class="form-control" required>
                                </div>
                            </div>

                            <hr>

                            <!-- ==================== DATA AKADEMIK ==================== -->
                            <h5 class="mb-3">Data Akademik</h5>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Asal Sekolah *</label>
                                    <input name="sekolah" type="text" class="form-control" required>
                                </div>

                            </div>

                            <div class="mb-3">
                                <label class="form-label">Program yang Diminati *</label><br>

                                <?php while ($m = $qMapel->fetch_assoc()): ?>
                                    <div class="form-check">
                                        <input class="form-check-input"
                                            type="checkbox"
                                            name="program[]"
                                            value="<?= $m['id_mapel'] ?>">
                                        <label class="form-check-label">
                                            <?= $m['nama_mapel'] ?>
                                        </label>
                                    </div>
                                <?php endwhile; ?>

                            </div>

                            <hr>

                            <!-- ==================== DATA ORTU ==================== -->
                            <h5 class="mb-3">Data Orang Tua</h5>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Nama Orang Tua *</label>
                                    <input name="ortu" type="text" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Telp Orang Tua *</label>
                                    <input name="ortu_telp" type="tel" class="form-control" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Pekerjaan Orang Tua</label>
                                <input name="pekerjaan" type="text" class="form-control">
                            </div>

                            <hr>

                            <!-- ==================== UPLOAD DOKUMEN ==================== -->
                            <h5 class="mb-3">Upload Dokumen</h5>

                            <div class="mb-3">
                                <label class="form-label">Foto Siswa *</label>
                                <input name="foto" type="file" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Kartu Pelajar</label>
                                <input name="kartu" type="file" class="form-control">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Rapor Terakhir</label>
                                <input name="rapor" type="file" class="form-control">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Upload Bukti Pembayaran *</label>
                                <input name="bukti" type="file" class="form-control" required>
                            </div>


                            <hr>

                            <div class="alert alert-info">
                                <strong>ID Siswa dibuat otomatis</strong> setelah mendaftar.
                            </div>

                            <hr>

                            <h5 class="mb-3">Akun Login</h5>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Username *</label>
                                    <input name="username" type="text" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Password *</label>
                                    <input name="password" type="password" class="form-control" required>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="../index.php" class="btn btn-secondary">Kembali</a>
                                <button class="btn btn-primary">Daftar Sekarang</button>
                            </div>

                        </form>

                    </div>

                </div>

            </div>
        </div>
    </div>
</body>

</html><?php
        require "../config.php";

        // Ambil daftar mapel (program)
        $qMapel = $conn->query("SELECT id_mapel, nama_mapel FROM mapel ORDER BY nama_mapel ASC");

        if ($_SERVER["REQUEST_METHOD"] == "POST") {

            // ====================== AMBIL DATA FORM ======================
            $nama = htmlspecialchars(trim($_POST['nama']));
            $jk = $_POST['jk'];
            $tmp = htmlspecialchars(trim($_POST['tmp']));
            $tgl = $_POST['tgl'];
            $alamat = htmlspecialchars(trim($_POST['alamat']));
            $email = htmlspecialchars(trim($_POST['email']));
            $telp = htmlspecialchars(trim($_POST['telp']));
            $sekolah = htmlspecialchars(trim($_POST['sekolah']));
            $program = implode(", ", $_POST['program'] ?? []);
            $ortu = htmlspecialchars(trim($_POST['ortu']));
            $ortu_telp = htmlspecialchars(trim($_POST['ortu_telp']));
            $pekerjaan = htmlspecialchars(trim($_POST['pekerjaan']));
            $username = htmlspecialchars(trim($_POST['username']));
            $password = $_POST['password'] ?? '';
            $password2 = $_POST['password2'] ?? '';

            // ====================== VALIDASI INPUT ======================
            $errors = [];
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email tidak valid.";
            if (!preg_match('/^\d+$/', $telp)) $errors[] = "No. Telepon harus angka.";
            if (!preg_match('/^\d+$/', $ortu_telp)) $errors[] = "Telp Orang Tua harus angka.";
            if ($password !== $password2) $errors[] = "Password dan Konfirmasi Password tidak sama.";
            if (empty($program)) $errors[] = "Pilih minimal satu program.";

            if (!empty($errors)) {
                echo "<div class='alert alert-danger'><ul><li>" . implode("</li><li>", $errors) . "</li></ul></div>";
            } else {

                $passMD5 = md5($password);

                // ====================== CEK DUPLIKASI USERNAME ======================
                $cekUser = $conn->prepare("SELECT id_user FROM users WHERE username=?");
                $cekUser->bind_param("s", $username);
                $cekUser->execute();
                $cekUser->store_result();
                if ($cekUser->num_rows > 0) {
                    echo "<div class='alert alert-danger'>Username sudah digunakan, silakan pilih yang lain.</div>";
                } else {
                    $cekUser->close();

                    // ====================== UPLOAD FILES ======================
                    $folders = [
                        'foto' => '../uploads/foto/',
                        'kartu' => '../uploads/kartu/',
                        'rapor' => '../uploads/rapor/',
                        'bukti' => '../uploads/bukti/'
                    ];

                    foreach ($folders as $f) if (!is_dir($f)) mkdir($f, 0777, true);

                    $files = ['foto', 'kartu', 'rapor', 'bukti'];
                    $uploaded = [];
                    $allowedExt = ['jpg', 'jpeg', 'png', 'pdf'];
                    $allowedMime = ['image/jpeg', 'image/png', 'application/pdf'];
                    $maxSize = 2 * 1024 * 1024; // 2MB
                    $fileError = false;

                    foreach ($files as $file) {
                        if (!empty($_FILES[$file]['name'])) {
                            $ext = strtolower(pathinfo($_FILES[$file]['name'], PATHINFO_EXTENSION));
                            $mime = mime_content_type($_FILES[$file]['tmp_name']);
                            if (!in_array($ext, $allowedExt) || !in_array($mime, $allowedMime)) {
                                echo "<div class='alert alert-danger'>File $file tidak valid.</div>";
                                $fileError = true;
                                break;
                            }
                            if ($_FILES[$file]['size'] > $maxSize) {
                                echo "<div class='alert alert-danger'>File $file terlalu besar (max 2MB).</div>";
                                $fileError = true;
                                break;
                            }
                            $filename = $file . "_" . uniqid() . "." . $ext;
                            if (!move_uploaded_file($_FILES[$file]['tmp_name'], $folders[$file] . $filename)) {
                                echo "<div class='alert alert-danger'>Gagal upload file $file.</div>";
                                $fileError = true;
                                break;
                            }
                            $uploaded[$file] = $filename;
                        } else {
                            $uploaded[$file] = "";
                        }
                    }

                    if (!$fileError) {
                        // ====================== TRANSACTION ======================
                        $conn->begin_transaction();
                        try {
                            // Insert siswa
                            $stmt = $conn->prepare("INSERT INTO siswa 
                    (nama, jk, tmp_lahir, tgl_lahir, alamat, email, telepon, sekolah, program, ortu, ortu_telp, pekerjaan, foto, kartu, rapor, bukti_pembayaran, status, username, password)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'belum bayar', ?, ?)
                    ");
                            $stmt->bind_param(
                                "ssssssssssssssssss",
                                $nama,
                                $jk,
                                $tmp,
                                $tgl,
                                $alamat,
                                $email,
                                $telp,
                                $sekolah,
                                $program,
                                $ortu,
                                $ortu_telp,
                                $pekerjaan,
                                $uploaded['foto'],
                                $uploaded['kartu'],
                                $uploaded['rapor'],
                                $uploaded['bukti'],
                                $username,
                                $passMD5
                            );
                            $stmt->execute();
                            $idSiswa = $stmt->insert_id;
                            $stmt->close();

                            // Insert user
                            $role = 'siswa';
                            $stmtUser = $conn->prepare("INSERT INTO users (username, password, role, id_siswa) VALUES (?, ?, ?, ?)");
                            $stmtUser->bind_param("sssi", $username, $passMD5, $role, $idSiswa);
                            $stmtUser->execute();
                            $stmtUser->close();

                            $conn->commit();

                            echo "<script>
                        alert('Pendaftaran berhasil! Silakan tunggu verifikasi pembayaran.');
                        window.location='../index.php';
                    </script>";
                        } catch (Exception $e) {
                            $conn->rollback();
                            echo "<div class='alert alert-danger'>Terjadi kesalahan: " . $e->getMessage() . "</div>";
                        }
                    }
                }
            }
        }
        ?>

<!-- ===================== FORM HTML ===================== -->
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Pendaftaran - Sistem Informasi Bimbel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>

<body class="bg-light">
    <div class="container py-5">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="bi bi-person-plus-fill"></i> Form Pendaftaran Siswa Baru</h4>
                    </div>
                    <div class="card-body p-4">

                        <form method="POST" enctype="multipart/form-data">

                            <h5 class="mb-3">Data Pribadi</h5>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Nama Lengkap *</label>
                                    <input name="nama" type="text" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Jenis Kelamin *</label>
                                    <select name="jk" class="form-select" required>
                                        <option disabled selected>Pilih</option>
                                        <option>Laki-laki</option>
                                        <option>Perempuan</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Tempat Lahir *</label>
                                    <input name="tmp" type="text" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Tanggal Lahir *</label>
                                    <input name="tgl" type="date" class="form-control" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Alamat *</label>
                                <textarea name="alamat" class="form-control" rows="2" required></textarea>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Email *</label>
                                    <input name="email" type="email" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">No. Telepon *</label>
                                    <input name="telp" type="tel" class="form-control" required>
                                </div>
                            </div>

                            <hr>

                            <h5 class="mb-3">Data Akademik</h5>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Asal Sekolah *</label>
                                    <input name="sekolah" type="text" class="form-control" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Program yang Diminati *</label><br>
                                <?php while ($m = $qMapel->fetch_assoc()): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="program[]" value="<?= $m['id_mapel'] ?>">
                                        <label class="form-check-label"><?= $m['nama_mapel'] ?></label>
                                    </div>
                                <?php endwhile; ?>
                            </div>

                            <hr>

                            <h5 class="mb-3">Data Orang Tua</h5>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Nama Orang Tua *</label>
                                    <input name="ortu" type="text" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Telp Orang Tua *</label>
                                    <input name="ortu_telp" type="tel" class="form-control" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Pekerjaan Orang Tua</label>
                                <input name="pekerjaan" type="text" class="form-control">
                            </div>

                            <hr>

                            <h5 class="mb-3">Upload Dokumen</h5>
                            <div class="mb-3">
                                <label class="form-label">Foto Siswa *</label>
                                <input name="foto" type="file" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Kartu Pelajar</label>
                                <input name="kartu" type="file" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Rapor Terakhir</label>
                                <input name="rapor" type="file" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Upload Bukti Pembayaran *</label>
                                <input name="bukti" type="file" class="form-control" required>
                            </div>

                            <hr>
                            <div class="alert alert-info">
                                <strong>ID Siswa dibuat otomatis</strong> setelah mendaftar.
                            </div>

                            <hr>

                            <h5 class="mb-3">Akun Login</h5>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Username *</label>
                                    <input name="username" type="text" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Password *</label>
                                    <input name="password" type="password" class="form-control" required>
                                </div>
                                <div class="col-md-6 mt-2">
                                    <label class="form-label">Konfirmasi Password *</label>
                                    <input name="password2" type="password" class="form-control" required>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="../index.php" class="btn btn-secondary">Kembali</a>
                                <button class="btn btn-primary">Daftar Sekarang</button>
                            </div>

                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>