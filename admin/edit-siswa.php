<?php
require "../config.php";
session_start();

// Cek login admin
if (!isset($_SESSION['login']) || $_SESSION['role'] !== "admin") {
    header("Location: ../index.php");
    exit;
}

// Ambil ID siswa
$id_siswa = intval($_GET['id'] ?? 0);
if ($id_siswa <= 0) {
    header("Location: siswa.php");
    exit;
}

// Ambil data siswa
$qSiswa = mysqli_query($conn, "
    SELECT s.*, 
       k.nama_kelas,
       t.id_tutor,
       t.nama_tutor
FROM siswa s
LEFT JOIN kelas k ON s.id_kelas = k.id_kelas
LEFT JOIN tutor t ON k.id_tutor = t.id_tutor

    WHERE s.id_siswa = $id_siswa
");
$siswa = mysqli_fetch_assoc($qSiswa);
if (!$siswa) {
    header("Location: siswa.php");
    exit;
}

// Data kelas & tutor
$kelas = mysqli_query($conn, "SELECT id_kelas, nama_kelas FROM kelas ORDER BY nama_kelas ASC");
$tutor = mysqli_query($conn, "SELECT id_tutor, nama_tutor FROM tutor ORDER BY nama_tutor ASC");

// Program list
$program_list = ['Reguler', 'Intensif', 'Privat', 'Online'];

// Helper tanggal
function format_for_date_input($date)
{
    return ($date && $date != '0000-00-00') ? date('Y-m-d', strtotime($date)) : '';
}

// =====================
// PROSES SIMPAN
// =====================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nama      = mysqli_real_escape_string($conn, $_POST['nama']);
    $jk        = $_POST['jk'];
    $tmp_lahir = mysqli_real_escape_string($conn, $_POST['tmp_lahir']);
    $tgl_lahir = $_POST['tgl_lahir'] ?: NULL;
    $alamat    = mysqli_real_escape_string($conn, $_POST['alamat']);
    $sekolah   = mysqli_real_escape_string($conn, $_POST['sekolah']);
    $program   = $_POST['program'];
    $id_kelas  = $_POST['id_kelas'] ?: NULL;
    $status_aktif    = $_POST['status_aktif'];
    $email     = mysqli_real_escape_string($conn, $_POST['email']);
    $telepon   = mysqli_real_escape_string($conn, $_POST['telepon']);
    $ortu      = mysqli_real_escape_string($conn, $_POST['ortu']);
    $ortu_telp = mysqli_real_escape_string($conn, $_POST['ortu_telp']);
    $pekerjaan = mysqli_real_escape_string($conn, $_POST['pekerjaan']);
    $password  = $_POST['password'];
    $id_tutor = $_POST['id_tutor'] ?? null;

    // cari kelas dari tutor
    if ($id_tutor) {
        $qKelasTutor = mysqli_query($conn, "
        SELECT id_kelas 
        FROM kelas 
        WHERE id_tutor = '$id_tutor'
        LIMIT 1
    ");
        $dataKelas = mysqli_fetch_assoc($qKelasTutor);
        $id_kelas = $dataKelas['id_kelas'] ?? null;
    }


    $sql = "
        UPDATE siswa SET
            nama='$nama',
            jk='$jk',
            tmp_lahir='$tmp_lahir',
            tgl_lahir=" . ($tgl_lahir ? "'$tgl_lahir'" : "NULL") . ",
            alamat='$alamat',
            sekolah='$sekolah',
            program='$program',
            id_kelas=" . ($id_kelas ? "'$id_kelas'" : "NULL") . ",
            status_aktif='$status_aktif',
            email='$email',
            telepon='$telepon',
            ortu='$ortu',
            ortu_telp='$ortu_telp',
            pekerjaan='$pekerjaan',
            updated_at=NOW()
    ";

    // Update password jika diisi
    if (!empty($password)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $sql .= ", password='$hash'";
    }

    $sql .= " WHERE id_siswa=$id_siswa";

    mysqli_query($conn, $sql);
    header("Location: siswa.php?edit=success");
    exit;
}
?>


<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Siswa - Sistem Informasi Bimbel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .edit-card {
            border-left: 4px solid #0d6efd;
        }

        .avatar-section {
            text-align: center;
            padding: 20px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .avatar-placeholder {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2.5rem;
            margin: 0 auto 15px;
        }

        .info-box {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .section-title {
            border-bottom: 2px solid #0d6efd;
            padding-bottom: 8px;
            margin-bottom: 20px;
            color: #0d6efd;
        }

        .form-label {
            font-weight: 500;
        }

        .required::after {
            content: " *";
            color: #dc3545;
        }

        .timeline-info {
            font-size: 0.875rem;
            color: #6c757d;
        }

        .file-preview {
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            margin-top: 10px;
        }

        .file-icon {
            font-size: 3rem;
            color: #6c757d;
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar p-0">
                <div class="text-center text-white py-4">
                    <i class="bi bi-book-fill" style="font-size: 2.5rem;"></i>
                    <h5 class="mt-2">Bimbel System</h5>
                    <small>Admin Panel</small>
                </div>
                <nav class="nav flex-column">
                    <a class="nav-link" href="dashboardadmin.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                    <a class="nav-link active" href="siswa.php"><i class="bi bi-people"></i> Data Siswa</a>
                    <a class="nav-link" href="tutor.php"><i class="bi bi-person-badge"></i> Data Tutor</a>
                    <a class="nav-link" href="kelas.php"><i class="bi bi-calendar3"></i> Jadwal Kelas</a>
                    <a class="nav-link" href="pembayaran.php"><i class="bi bi-cash-coin"></i> Pembayaran</a>
                    <a class="nav-link" href="mapel.php"><i class="bi bi-journal-text"></i> Mata Pelajaran</a>
                    <a class="nav-link" href="laporan.php"><i class="bi bi-file-earmark-text"></i> Laporan</a>
                    <a class="nav-link" href="../logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 content-wrapper">
                <div class="page-header d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2>Edit Data Siswa</h2>
                        <p class="text-muted">Perbarui informasi data siswa</p>
                    </div>
                    <div>
                        <a href="siswa.php" class="btn btn-outline-secondary me-2">
                            <i class="bi bi-arrow-left"></i> Kembali
                        </a>
                        <a href="hapus-siswa.php?id=<?= $id_siswa ?>" class="btn btn-danger">
                            <i class="bi bi-trash"></i> Hapus
                        </a>
                    </div>
                </div>

                <!-- Informasi Siswa -->
                <div class="row mb-4">
                    <div class="col-lg-4">
                        <div class="avatar-section">
                            <div class="avatar-placeholder">
                                <i class="bi bi-person"></i>
                            </div>
                            <h4><?= htmlspecialchars($siswa['nama']) ?></h4>
                            <p class="text-muted">ID: <?= $siswa['id_siswa'] ?></p>
                            <span class="badge <?= $siswa['status_aktif'] == 'Aktif' ? 'bg-success' : 'bg-danger' ?> fs-6">
                                <?= $siswa['status_aktif'] ?>
                            </span>
                        </div>

                        <!-- File Uploads Preview -->
                        <div class="info-box">
                            <h6><i class="bi bi-files"></i> Dokumen Siswa</h6>
                            <?php
                            $files = [
                                'foto' => ['label' => 'Foto', 'icon' => 'bi-camera'],
                                'kartu' => ['label' => 'Kartu Pelajar', 'icon' => 'bi-card-text'],
                                'rapor' => ['label' => 'Rapor', 'icon' => 'bi-file-text']
                            ];

                            foreach ($files as $key => $file_info):
                                if (!empty($siswa[$key])):
                            ?>
                                    <div class="file-preview mb-2">
                                        <i class="bi <?= $file_info['icon'] ?> file-icon"></i>
                                        <p class="mb-1"><?= $file_info['label'] ?></p>
                                        <small class="text-muted"><?= basename($siswa[$key]) ?></small>
                                        <div class="mt-2">
                                            <a href="../uploads/siswa/<?= $siswa[$key] ?>"
                                                class="btn btn-sm btn-outline-primary"
                                                target="_blank">
                                                <i class="bi bi-eye"></i> Lihat
                                            </a>
                                            <a href="../uploads/siswa/<?= $siswa[$key] ?>"
                                                class="btn btn-sm btn-outline-success"
                                                download>
                                                <i class="bi bi-download"></i> Download
                                            </a>
                                        </div>
                                    </div>
                                <?php
                                else:
                                ?>
                                    <div class="alert alert-warning py-2 mb-2">
                                        <i class="bi bi-exclamation-triangle"></i>
                                        <?= $file_info['label'] ?> belum diupload
                                    </div>
                            <?php
                                endif;
                            endforeach;
                            ?>
                        </div>

                        <div class="info-box">
                            <h6><i class="bi bi-clock-history"></i> Timeline</h6>
                            <div class="timeline-info">
                                <?php if (!empty($siswa['created_at'])): ?>
                                    <p><strong>Dibuat di Sistem:</strong><br>
                                        <?= date('d M Y H:i', strtotime($siswa['created_at'])) ?></p>
                                <?php endif; ?>

                                <?php if (!empty($siswa['updated_at']) && $siswa['updated_at'] != '0000-00-00 00:00:00'): ?>
                                    <p><strong>Terakhir Update:</strong><br>
                                        <?= date('d M Y H:i', strtotime($siswa['updated_at'])) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-8">
                        <!-- Form Edit -->
                        <div class="card edit-card">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">Form Edit Data Siswa</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="" enctype="multipart/form-data">
                                    <div class="row">
                                        <!-- Kolom 1 -->
                                        <div class="col-md-6">
                                            <h5 class="section-title">Informasi Pribadi</h5>

                                            <!-- Nama Lengkap -->
                                            <div class="mb-3">
                                                <label class="form-label required">Nama Lengkap</label>
                                                <input type="text" name="nama" class="form-control"
                                                    value="<?= htmlspecialchars($siswa['nama']) ?>" required>
                                            </div>

                                            <!-- Jenis Kelamin -->
                                            <div class="mb-3">
                                                <label class="form-label required">Jenis Kelamin</label>
                                                <select name="jk" class="form-select" required>
                                                    <option value="">-- Pilih --</option>
                                                    <option value="Laki-laki" <?= ($siswa['jk'] == 'Laki-laki') ? 'selected' : '' ?>>Laki-laki</option>
                                                    <option value="Perempuan" <?= ($siswa['jk'] == 'Perempuan') ? 'selected' : '' ?>>Perempuan</option>
                                                </select>
                                            </div>

                                            <!-- Tempat Lahir -->
                                            <div class="mb-3">
                                                <label class="form-label">Tempat Lahir</label>
                                                <input type="text" name="tmp_lahir" class="form-control"
                                                    value="<?= htmlspecialchars($siswa['tmp_lahir']) ?>">
                                            </div>

                                            <!-- Tanggal Lahir -->
                                            <div class="mb-3">
                                                <label class="form-label">Tanggal Lahir</label>
                                                <input type="date" name="tgl_lahir" class="form-control"
                                                    value="<?= format_for_date_input($siswa['tgl_lahir']) ?>">
                                            </div>

                                            <!-- Alamat -->
                                            <div class="mb-3">
                                                <label class="form-label">Alamat Lengkap</label>
                                                <textarea name="alamat" class="form-control" rows="3"><?= htmlspecialchars($siswa['alamat']) ?></textarea>
                                            </div>
                                        </div>

                                        <!-- Kolom 2 -->
                                        <div class="col-md-6">
                                            <h5 class="section-title">Informasi Akademik</h5>

                                            <!-- Sekolah Asal -->
                                            <div class="mb-3">
                                                <label class="form-label">Sekolah Asal</label>
                                                <input type="text" name="sekolah" class="form-control"
                                                    value="<?= htmlspecialchars($siswa['sekolah']) ?>">
                                            </div>





                                            <!-- Kelas Bimbel -->
                                            <div class="mb-3">
                                                <label class="form-label">Kelas Bimbel</label>
                                                <select name="id_kelas" class="form-select">
                                                    <option value="">-- Tidak Terdaftar --</option>
                                                    <?php
                                                    mysqli_data_seek($kelas, 0);
                                                    while ($k = mysqli_fetch_assoc($kelas)):
                                                    ?>
                                                        <option value="<?= $k['id_kelas'] ?>"
                                                            <?= ($siswa['id_kelas'] == $k['id_kelas']) ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($k['nama_kelas']) ?>
                                                        </option>
                                                    <?php endwhile; ?>
                                                </select>
                                            </div>

                                            <!-- status_aktif -->
                                            <div class="mb-3">
                                                <label class="form-label required">Status Siswa</label>
                                                <select name="status_aktif" class="form-select" required>
                                                    <option value="">-- Pilih Status --</option>
                                                    <option value="aktif" <?= ($siswa['status_aktif'] == 'aktif') ? 'selected' : '' ?>>Aktif</option>
                                                    <option value="tidak aktif" <?= ($siswa['status_aktif'] == 'tidak aktif') ? 'selected' : '' ?>>Tidak Aktif</option>
                                                    <option value="lulus" <?= ($siswa['status_aktif'] == 'lulus') ? 'selected' : '' ?>>Lulus</option>
                                                    <option value="berhenti" <?= ($siswa['status_aktif'] == 'berhenti') ? 'selected' : '' ?>>Berhenti</option>
                                                </select>
                                            </div>

                                        </div>
                                    </div>

                                    <hr>

                                    <div class="row">
                                        <!-- Kolom 3 -->
                                        <div class="col-md-6">
                                            <h5 class="section-title">Informasi Kontak</h5>

                                            <!-- Email -->
                                            <div class="mb-3">
                                                <label class="form-label required">Email</label>
                                                <input type="email" name="email" class="form-control"
                                                    value="<?= htmlspecialchars($siswa['email']) ?>" required>
                                                <div class="form-text">Digunakan untuk login dan notifikasi</div>
                                            </div>

                                            <!-- Telepon -->
                                            <div class="mb-3">
                                                <label class="form-label required">No. Telepon</label>
                                                <input type="tel" name="telepon" class="form-control"
                                                    value="<?= htmlspecialchars($siswa['telepon']) ?>"
                                                    pattern="[0-9+]+" title="Hanya angka dan tanda +" required>
                                            </div>

                                            <!-- NIB -->
                                            <input type="hidden" name="nib" value="<?= htmlspecialchars($siswa['nib']) ?>">

                                            <input type="text" class="form-control"
                                                value="<?= htmlspecialchars($siswa['nib'] ?? '') ?>"
                                                disabled>

                                        </div>

                                        <!-- Kolom 4 -->
                                        <div class="col-md-6">
                                            <h5 class="section-title">Informasi Orang Tua</h5>

                                            <!-- Nama Orang Tua -->
                                            <div class="mb-3">
                                                <label class="form-label">Nama Orang Tua/Wali</label>
                                                <input type="text" name="ortu" class="form-control"
                                                    value="<?= htmlspecialchars($siswa['ortu']) ?>">
                                            </div>

                                            <!-- Telepon Orang Tua -->
                                            <div class="mb-3">
                                                <label class="form-label">No. Telepon Orang Tua</label>
                                                <input type="tel" name="ortu_telp" class="form-control"
                                                    value="<?= htmlspecialchars($siswa['ortu_telp']) ?>"
                                                    pattern="[0-9+]+" title="Hanya angka dan tanda +">
                                            </div>

                                            <!-- Pekerjaan Orang Tua -->
                                            <div class="mb-3">
                                                <label class="form-label">Pekerjaan Orang Tua</label>
                                                <input type="text" name="pekerjaan" class="form-control"
                                                    value="<?= htmlspecialchars($siswa['pekerjaan']) ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <hr>

                                    <div class="row">
                                        <div class="col-md-12">
                                            <h5 class="section-title">Akun Login</h5>
                                            <div class="alert alert-warning">
                                                <i class="bi bi-exclamation-triangle"></i>
                                                <strong>Perhatian:</strong> Hanya isi password jika ingin mengubah password siswa. Biarkan kosong jika tidak ingin mengubah.
                                            </div>

                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Username</label>
                                                        <input type="text" class="form-control"
                                                            value="<?= htmlspecialchars($siswa['username'] ?? '') ?>" readonly>
                                                        <div class="form-text">Username tidak dapat diubah</div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Password Baru</label>
                                                        <input type="password" name="password" class="form-control"
                                                            minlength="6">
                                                        <div class="form-text">Minimal 6 karakter</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Informasi Tambahan -->
                                    <div class="alert alert-info mt-4">
                                        <h6><i class="bi bi-info-circle"></i> Informasi Penting:</h6>
                                        <ul class="mb-0">
                                            <li>Field dengan tanda <span class="text-danger">*</span> wajib diisi</li>
                                            <li>Email dan NIB harus unik untuk setiap siswa</li>
                                            <li>Password hanya diisi jika ingin mengubah</li>
                                            <li>Perubahan status mempengaruhi akses siswa ke sistem</li>
                                        </ul>
                                    </div>

                                    <!-- Tombol Aksi -->
                                    <div class="d-flex justify-content-between mt-4">
                                        <div>
                                            <a href="siswa.php" class="btn btn-outline-secondary">
                                                <i class="bi bi-x-circle"></i> Batal
                                            </a>
                                            <a href="siswa.php" class="btn btn-outline-info ms-2">
                                                <i class="bi bi-list"></i> Daftar Siswa
                                            </a>
                                        </div>
                                        <div>
                                            <button type="reset" class="btn btn-warning me-2">
                                                <i class="bi bi-arrow-clockwise"></i> Reset
                                            </button>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="bi bi-save"></i> Simpan Perubahan
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistik Singkat -->
                <?php
                // Ambil statistik siswa
                $stat_query = mysqli_query($conn, "
                    SELECT 
                        COUNT(DISTINCT p.id_siswa) as total_presensi,
                        COUNT(DISTINCT n.id_siswa) as total_nilai,
                        AVG(n.nilai) as rata_nilai
                    FROM siswa s
                    LEFT JOIN presensi p ON s.id_siswa = p.id_siswa
                    LEFT JOIN penilaian_tugas n ON s.id_siswa = n.id_siswa
                    WHERE s.id_siswa = $id_siswa
                ");
                $stat = mysqli_fetch_assoc($stat_query);

                // Ambil data pembayaran
                $pembayaran_query = mysqli_query($conn, "
                    SELECT COUNT(*) as total_bayar, 
                           SUM(nominal) as total_nominal
                    FROM pembayaran 
                    WHERE id_siswa = $id_siswa 
                    AND status = 'Lunas'
                ");
                $pembayaran = mysqli_fetch_assoc($pembayaran_query);
                ?>

                <div class="row">
                    <div class="col-md-3">
                        <div class="card text-center mb-3">
                            <div class="card-body">
                                <i class="bi bi-calendar-check fs-4 text-primary"></i>
                                <h4 class="mt-2"><?= $stat['total_presensi'] ?? 0 ?></h4>
                                <p class="text-muted mb-0">Total Presensi</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center mb-3">
                            <div class="card-body">
                                <i class="bi bi-star fs-4 text-warning"></i>
                                <h4 class="mt-2"><?= $stat['total_nilai'] ?? 0 ?></h4>
                                <p class="text-muted mb-0">Nilai Terekam</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center mb-3">
                            <div class="card-body">
                                <i class="bi bi-graph-up fs-4 text-info"></i>
                                <h4 class="mt-2">
                                    <?= $stat['rata_nilai'] ? number_format($stat['rata_nilai'], 2) : '-' ?>
                                </h4>
                                <p class="text-muted mb-0">Rata-rata Nilai</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center mb-3">
                            <div class="card-body">
                                <i class="bi bi-cash-coin fs-4 text-success"></i>
                                <h4 class="mt-2">
                                    <?= $pembayaran['total_bayar'] ?? 0 ?>
                                </h4>
                                <p class="text-muted mb-0">Pembayaran Lunas</p>
                                <small class="text-muted">
                                    Rp <?= number_format($pembayaran['total_nominal'] ?? 0, 0, ',', '.') ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Peringatan Status -->
                <?php if ($siswa['status_aktif'] == 'Tidak Aktif'): ?>
                    <div class="alert alert-warning">
                        <h6><i class="bi bi-exclamation-triangle"></i> Peringatan Status</h6>
                        <p>Saat ini siswa ini berstatus <strong>Tidak Aktif</strong>. Jika mengubah status menjadi <strong>Aktif</strong>, siswa akan kembali bisa mengakses sistem.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validasi client-side sederhana
        document.querySelector('form').addEventListener('submit', function(e) {
            let isValid = true;

            // // Validasi NIB format
            // const nibInput = document.querySelector('input[name="nib"]');
            // if (nibInput.value && !/^\d{12}$/.test(nibInput.value)) {
            //     alert('NIB harus terdiri dari 12 digit angka!');
            //     nibInput.focus();
            //     isValid = false;
            // }

            // Validasi telepon
            const teleponInput = document.querySelector('input[name="telepon"]');
            if (!/^[0-9+]+$/.test(teleponInput.value)) {
                alert('Nomor telepon hanya boleh mengandung angka dan tanda +!');
                teleponInput.focus();
                isValid = false;
            }

            // Validasi password minimal 6 karakter jika diisi
            const passwordInput = document.querySelector('input[name="password"]');
            if (passwordInput.value && passwordInput.value.length < 6) {
                alert('Password minimal 6 karakter!');
                passwordInput.focus();
                isValid = false;
            }

            // Validasi status konfirmasi
            const statusSelect = document.querySelector('select[name="status"]');
            const oldStatus = '<?= $siswa['status'] ?>';
            const newStatus = statusSelect.value;

            if (oldStatus === 'Aktif' && (newStatus === 'Tidak Aktif' || newStatus === 'Berhenti')) {
                if (!confirm('Mengubah status dari Aktif ke ' + newStatus + ' akan menonaktifkan akses siswa ke sistem. Lanjutkan?')) {
                    isValid = false;
                }
            } else if ((oldStatus === 'Tidak Aktif' || oldStatus === 'Berhenti') && newStatus === 'Aktif') {
                if (!confirm('Mengubah status ke Aktif akan mengaktifkan kembali akses siswa ke sistem. Lanjutkan?')) {
                    isValid = false;
                }
            }

            if (!isValid) {
                e.preventDefault();
            }
        });

        // Validasi input real-time
        document.querySelectorAll('input[type="tel"]').forEach(function(input) {
            input.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9+]/g, '');
            });
        });

        // Validasi NIB input real-time
        document.querySelector('input[name="nib"]').addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 12);
        });

        // Preview TTL
        function updateTTLPreview() {
            const tmpLahir = document.querySelector('input[name="tmp_lahir"]').value;
            const tglLahir = document.querySelector('input[name="tgl_lahir"]').value;
            let ttl = '';

            if (tmpLahir) {
                ttl += tmpLahir;
            }
            if (tglLahir) {
                const date = new Date(tglLahir);
                const formattedDate = date.toLocaleDateString('id-ID', {
                    day: 'numeric',
                    month: 'long',
                    year: 'numeric'
                });
                ttl += (ttl ? ', ' : '') + formattedDate;
            }

            const previewElement = document.getElementById('ttlPreview');
            if (previewElement) {
                previewElement.textContent = ttl || '-';
            }
        }

        // Attach event listeners for TTL preview
        document.querySelector('input[name="tmp_lahir"]').addEventListener('input', updateTTLPreview);
        document.querySelector('input[name="tgl_lahir"]').addEventListener('change', updateTTLPreview);

        // Initialize TTL preview
        updateTTLPreview();
    </script>
</body>

</html>