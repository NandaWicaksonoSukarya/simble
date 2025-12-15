<?php
session_start();
// echo "<pre>";
// print_r($_SESSION);
// echo "</pre>";
// exit;

require "../config.php";

// proteksi login tutor
if (!isset($_SESSION['login']) || $_SESSION['role'] !== "tutor") {

    header("Location: ../index.php");
}

$username = $_SESSION['username'] ?? "";
if ($username === "") {
    echo "Session username tutor tidak tersedia. Silakan logout lalu login ulang.";
    exit;
}

$username = mysqli_real_escape_string($conn, $username);

// ambil data tutor berdasarkan username dari tabel users
$q = mysqli_query($conn, "
    SELECT t.*, m.nama_mapel, u.username
    FROM tutor t
    INNER JOIN users u ON t.id_tutor = u.id_tutor
    LEFT JOIN mapel m ON t.id_mapel = m.id_mapel
    WHERE u.username = '$username'
    LIMIT 1
");

if (!$q || mysqli_num_rows($q) === 0) {
    die("Data tutor tidak ditemukan. Hubungi admin.");
}

$id_tutor = $_SESSION['id_tutor']; // ID tutor dari session
$flash = null;

// ----------------- Handle profile update -----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $nama_tutor = mysqli_real_escape_string($conn, $_POST['nama_tutor'] ?? '');
    $email = mysqli_real_escape_string($conn, $_POST['email'] ?? '');
    $telepon = mysqli_real_escape_string($conn, $_POST['telepon'] ?? '');
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat'] ?? '');
    $pendidikan = mysqli_real_escape_string($conn, $_POST['pendidikan'] ?? '');
    $pengalaman = mysqli_real_escape_string($conn, $_POST['pengalaman'] ?? '');
    $id_mapel = mysqli_real_escape_string($conn, $_POST['id_mapel'] ?? '');

    // update pada tabel tutor
    $sql = "UPDATE tutor SET 
                nama_tutor = '$nama_tutor',
                email = '$email',
                telepon = '$telepon',
                alamat = '$alamat',
                pendidikan = '$pendidikan',
                pengalaman = '$pengalaman',
                id_mapel = '$id_mapel'
            WHERE id_tutor = '$id_tutor'";

    if (mysqli_query($conn, $sql)) {
        $_SESSION['flash'] = "Profil berhasil diperbarui.";
        // Update session nama
        $_SESSION['nama'] = $nama_tutor;
    } else {
        $_SESSION['flash'] = "Gagal memperbarui profil: " . mysqli_error($conn);
    }
    header("Location: profil.php");
    exit;
}

// ----------------- Handle password change -----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    // ambil password hash dari tabel users
    $res_u = mysqli_query($conn, "SELECT password FROM users WHERE id_tutor = '$id_tutor' LIMIT 1");
    if ($res_u && mysqli_num_rows($res_u) > 0) {
        $rowu = mysqli_fetch_assoc($res_u);
        $hash = $rowu['password'];

        if (!password_verify($current, $hash)) {
            $_SESSION['flash'] = "Password saat ini salah.";
        } elseif (strlen($new) < 6) {
            $_SESSION['flash'] = "Password baru minimal 6 karakter.";
        } elseif ($new !== $confirm) {
            $_SESSION['flash'] = "Konfirmasi password tidak cocok.";
        } else {
            $new_hash = password_hash($new, PASSWORD_DEFAULT);
            if (mysqli_query($conn, "UPDATE users SET password = '" . mysqli_real_escape_string($conn, $new_hash) . "' WHERE id_tutor = '$id_tutor'")) {
                $_SESSION['flash'] = "Password berhasil diubah.";
            } else {
                $_SESSION['flash'] = "Gagal mengubah password: " . mysqli_error($conn);
            }
        }
    } else {
        $_SESSION['flash'] = "Akun pengguna tidak ditemukan.";
    }
    header("Location: profil.php");
    exit;
}

// ----------------- Handle photo upload -----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_foto'])) {
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        if (in_array($_FILES['foto']['type'], $allowed_types) && $_FILES['foto']['size'] <= $max_size) {
            $upload_dir = "../uploads/tutor/";
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $new_filename = "tutor_" . $id_tutor . "_" . time() . "." . $ext;
            $file_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['foto']['tmp_name'], $file_path)) {
                // Hapus foto lama jika ada
                $old_foto_query = mysqli_query($conn, "SELECT foto FROM tutor WHERE id_tutor = '$id_tutor'");
                if ($old_foto = mysqli_fetch_assoc($old_foto_query)) {
                    if (!empty($old_foto['foto']) && file_exists("../" . $old_foto['foto'])) {
                        unlink("../" . $old_foto['foto']);
                    }
                }
                
                // Update path foto di database (simpan path relatif dari root)
                $relative_path = "uploads/tutor/" . $new_filename;
                mysqli_query($conn, "UPDATE tutor SET foto = '$relative_path' WHERE id_tutor = '$id_tutor'");
                $_SESSION['flash'] = "Foto profil berhasil diupload.";
            } else {
                $_SESSION['flash'] = "Gagal mengupload foto.";
            }
        } else {
            $_SESSION['flash'] = "File harus berupa gambar (JPG/PNG) maksimal 2MB.";
        }
    } else {
        $_SESSION['flash'] = "Silakan pilih file foto.";
    }
    header("Location: profil.php");
    exit;
}

// ambil flash jika ada
if (isset($_SESSION['flash'])) {
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
}

// Ambil data tutor
$query = "
    SELECT 
        t.*, 
        m.nama_mapel,
        m.id_mapel
    FROM tutor t
    LEFT JOIN mapel m ON t.id_mapel = m.id_mapel
    WHERE t.id_tutor = '$id_tutor'
";

$mapelQuery = mysqli_query($conn, "SELECT * FROM mapel WHERE status = 'aktif' ORDER BY nama_mapel ASC");
$mapelList = [];
while ($row = mysqli_fetch_assoc($mapelQuery)) {
    $mapelList[] = $row;
}

$result = mysqli_query($conn, $query);
$tutor = mysqli_fetch_assoc($result);

// jika tidak ditemukan, redirect ke login
if (!$tutor) {
    session_destroy();
    header("Location: ../index.php");
    exit;
}

// ambil beberapa statistik tambahan
// Hitung jumlah siswa yang diajar oleh tutor ini (berdasarkan kelas yang diajar)
$jumlah_siswa_query = mysqli_query($conn, "
    SELECT COUNT(DISTINCT s.id_siswa) as total_siswa 
    FROM siswa s
    INNER JOIN kelas k ON s.id_kelas = k.id_kelas
    WHERE k.id_tutor = '$id_tutor' AND s.status_aktif = 'aktif'
");
$jumlah_siswa_row = mysqli_fetch_assoc($jumlah_siswa_query);
$jumlah_siswa = $jumlah_siswa_row['total_siswa'] ?? 0;

// Hitung jumlah kelas per minggu
$kelas_minggu_query = mysqli_query($conn, "
    SELECT COUNT(DISTINCT DAYOFWEEK(tanggal)) as hari_aktif 
    FROM jadwal 
    WHERE id_tutor = '$id_tutor' 
    AND status = 'aktif'
    AND tanggal >= DATE_SUB(NOW(), INTERVAL 7 DAY)
");
$kelas_minggu_row = mysqli_fetch_assoc($kelas_minggu_query);
$kelas_minggu = $kelas_minggu_row['hari_aktif'] ?? 0;

// hitung materi yang diupload oleh tutor
$materi_count = 0;
$res = mysqli_query($conn, "SELECT COUNT(*) AS total FROM materi WHERE id_tutor = '$id_tutor'");
if ($res) {
    $materi_row = mysqli_fetch_assoc($res);
    $materi_count = $materi_row['total'] ?? 0;
}

// hitung tugas yang dibuat oleh tutor
$tugas_count = 0;
$res2 = mysqli_query($conn, "SELECT COUNT(*) AS total FROM tugas WHERE id_tutor = '$id_tutor'");
if ($res2) {
    $tugas_row = mysqli_fetch_assoc($res2);
    $tugas_count = $tugas_row['total'] ?? 0;
}

// ambil kehadiran rata-rata tutor
$kehadiran_pct = 'N/A';
$res3 = mysqli_query($conn, "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status='Hadir' THEN 1 ELSE 0 END) as hadir
    FROM presensi_tutor 
    WHERE id_tutor = '$id_tutor'
");
if ($res3) {
    $row3 = mysqli_fetch_assoc($res3);
    if ($row3 && $row3['total'] > 0) {
        $persen = round(($row3['hadir'] / $row3['total']) * 100);
        $kehadiran_pct = $persen . '%';
    }
}

// untuk keamanan, siapkan fungsi helper
$display = function ($key) use ($tutor) {
    return htmlspecialchars($tutor[$key] ?? '', ENT_QUOTES);
};
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - Sistem Informasi Bimbel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .profile-img {
            width: 150px;
            height: 150px;
            object-fit: cover;
        }
        .mapel-badge {
            display: inline-block;
            padding: 4px 10px;
            margin: 3px;
            background: #e9ecef;
            border: 1px solid #dee2e6;
            border-radius: 20px;
            font-size: 0.9em;
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
                    <small>Portal Tutor</small>
                </div>
                <nav class="nav flex-column">
                    <a class="nav-link" href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                    <a class="nav-link" href="jadwal.php"><i class="bi bi-calendar3"></i> Jadwal Mengajar</a>
                    <a class="nav-link" href="presensi.php"><i class="bi bi-check2-square"></i> Presensi</a>
                    <a class="nav-link" href="materi.php"><i class="bi bi-journal-text"></i> Materi</a>
                    <a class="nav-link" href="tugas.php"><i class="bi bi-clipboard-check"></i> Tugas & Penilaian</a>
                    <a class="nav-link" href="siswa.php"><i class="bi bi-people"></i> Data Siswa</a>
                    <a class="nav-link active" href="profil.php"><i class="bi bi-person"></i> Profil</a>
                    <a class="nav-link" href="../logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 content-wrapper">
                <div class="page-header">
                    <h2>Profil Saya</h2>
                    <p class="text-muted">Kelola informasi profil Anda</p>
                </div>

                <?php if ($flash): ?>
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($flash) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-4">
                        <div class="card text-center">
                            <div class="card-body">
                                <?php
                                $foto_path = !empty($tutor['foto']) ? "../" . $tutor['foto'] : "https://ui-avatars.com/api/?name=" . urlencode($tutor['nama_tutor']) . "&size=150&background=0D6EFD&color=fff";
                                ?>
                                <img src="<?= htmlspecialchars($foto_path) ?>" class="rounded-circle profile-img mb-3" alt="Profile">
                                <h4><?= $display('nama_tutor') ?></h4>
                                <p class="text-muted">
                                    <i class="bi bi-mortarboard-fill"></i> 
                                    <?= $display('pendidikan') ?>
                                </p>
                                
                                <div class="mb-3">
                                    <h6>Mata Pelajaran</h6>
                                    <?php if (!empty($tutor['nama_mapel'])): ?>
                                        <span class="mapel-badge"><?= $display('nama_mapel') ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">Belum ada mata pelajaran</span>
                                    <?php endif; ?>
                                </div>

                                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#uploadFotoModal">
                                    <i class="bi bi-camera"></i> Ganti Foto
                                </button>
                            </div>
                        </div>

                        <div class="card mt-3">
                            <div class="card-body">
                                <h6>Statistik Mengajar</h6>
                                <hr>
                                <div class="d-flex justify-content-between mb-2">
                                    <span><i class="bi bi-people"></i> Total Siswa</span>
                                    <strong><?= number_format($jumlah_siswa) ?></strong>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span><i class="bi bi-check-circle"></i> Kehadiran</span>
                                    <strong><?= $kehadiran_pct ?></strong>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span><i class="bi bi-journal-text"></i> Materi Diupload</span>
                                    <strong><?= number_format($materi_count) ?></strong>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span><i class="bi bi-clipboard-check"></i> Tugas Dibuat</span>
                                    <strong><?= number_format($tugas_count) ?></strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span><i class="bi bi-calendar-week"></i> Hari Aktif/Minggu</span>
                                    <strong><?= number_format($kelas_minggu) ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-8">
                        <!-- Informasi Pribadi (form update) -->
                        <div class="card">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="bi bi-person-badge"></i> Informasi Pribadi</h5>
                            </div>
                            <div class="card-body">
                                <form method="post" action="profil.php">
                                    <input type="hidden" name="update_profile" value="1">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Nama Lengkap *</label>
                                            <input type="text" name="nama_tutor" class="form-control" value="<?= $display('nama_tutor') ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Email *</label>
                                            <input type="email" name="email" class="form-control" value="<?= $display('email') ?>" required>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">No. Telepon</label>
                                            <input type="tel" name="telepon" class="form-control" value="<?= $display('telepon') ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Mata Pelajaran</label>
                                            <select name="id_mapel" class="form-select">
                                                <option value="">-- Pilih Mata Pelajaran --</option>
                                                <?php foreach ($mapelList as $mapel): ?>
                                                    <option value="<?= $mapel['id_mapel'] ?>" 
                                                        <?= ($tutor['id_mapel'] == $mapel['id_mapel']) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($mapel['nama_mapel']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Alamat</label>
                                        <textarea name="alamat" class="form-control" rows="3"><?= $display('alamat') ?></textarea>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Pendidikan Terakhir</label>
                                            <input type="text" name="pendidikan" class="form-control" value="<?= $display('pendidikan') ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Pengalaman Mengajar (tahun)</label>
                                            <input type="text" name="pengalaman" class="form-control" value="<?= $display('pengalaman') ?>">
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Status</label>
                                            <input type="text" class="form-control" value="<?= ($tutor['status'] == 'aktif') ? 'Aktif' : 'Tidak Aktif' ?>" readonly>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Bergabung Sejak</label>
                                            <input type="text" class="form-control" value="<?= date('d F Y', strtotime($tutor['created_at'])) ?>" readonly>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-save"></i> Simpan Perubahan
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Ubah Password -->
                        <div class="card mt-3">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="bi bi-key"></i> Ubah Password</h5>
                            </div>
                            <div class="card-body">
                                <form method="post" action="profil.php">
                                    <input type="hidden" name="change_password" value="1">
                                    <div class="mb-3">
                                        <label class="form-label">Password Lama *</label>
                                        <input type="password" name="current_password" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Password Baru *</label>
                                        <input type="password" name="new_password" class="form-control" required minlength="6">
                                        <small class="text-muted">Minimal 6 karakter</small>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Konfirmasi Password Baru *</label>
                                        <input type="password" name="confirm_password" class="form-control" required>
                                    </div>
                                    <button type="submit" class="btn btn-warning">
                                        <i class="bi bi-key-fill"></i> Ubah Password
                                    </button>
                                </form>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: upload foto -->
    <div class="modal fade" id="uploadFotoModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post" action="profil.php" enctype="multipart/form-data">
                    <input type="hidden" name="upload_foto" value="1">
                    <div class="modal-header">
                        <h5 class="modal-title">Ganti Foto Profil</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Pilih Foto</label>
                            <input type="file" name="foto" class="form-control" accept="image/*" required>
                            <small class="text-muted">Format: JPG, PNG. Maksimal: 2MB.</small>
                        </div>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> Foto akan ditampilkan sebagai profil Anda.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Upload Foto</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto dismiss alert setelah 5 detik
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                var alerts = document.querySelectorAll('.alert');
                alerts.forEach(function(alert) {
                    var bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 5000);
        });
    </script>
</body>
</html>