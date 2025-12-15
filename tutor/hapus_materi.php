<?php
include "../config.php";
session_start();

// Cek apakah user sudah login sebagai tutor
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'tutor') {
    header("Location: ../login.php");
    exit();
}

$tutor = $_SESSION['username'];

// Ambil ID materi dari URL
$id_materi = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_materi == 0) {
    header("Location: materi.php");
    exit();
}

// Ambil data materi untuk mendapatkan informasi file
$query_materi = mysqli_query($conn, "SELECT * FROM materi WHERE id_materi = '$id_materi' AND tutor = '$tutor'");
$materi = mysqli_fetch_assoc($query_materi);

if (!$materi) {
    // Materi tidak ditemukan atau bukan milik tutor
    echo "<script>
            alert('Materi tidak ditemukan atau Anda tidak memiliki akses untuk menghapus!');
            window.location.href='materi.php';
          </script>";
    exit();
}

// Proses penghapusan jika konfirmasi diterima
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['confirm_delete']) && $_POST['confirm_delete'] == 'yes') {

        // Hapus file fisik dari server
        $file_path = "../materi/" . $materi['file_path'];
        if (file_exists($file_path)) {
            if (!unlink($file_path)) {
                echo "<script>
                        alert('Gagal menghapus file fisik!');
                        window.location.href='materi.php';
                      </script>";
                exit();
            }
        }

        // Hapus record dari database
        $delete_query = "DELETE FROM materi WHERE id_materi = '$id_materi' AND tutor = '$tutor'";

        if (mysqli_query($conn, $delete_query)) {
            // Jika ada tabel terkait, hapus juga (opsional)
            // Contoh: hapus data akses materi
            $delete_akses = "DELETE FROM materi WHERE id_materi = '$id_materi'";
            mysqli_query($conn, $delete_akses);

            // Log aktivitas (opsional)
            $log_aktivitas = "INSERT INTO log_aktivitas (user, aksi, keterangan, waktu) 
                              VALUES ('$tutor', 'HAPUS MATERI', 'Menghapus materi: " . mysqli_real_escape_string($conn, $materi['judul']) . "', NOW())";
            mysqli_query($conn, $log_aktivitas);

            echo "<script>
                    alert('Materi berhasil dihapus!');
                    window.location.href='materi.php';
                  </script>";
        } else {
            echo "<script>
                    alert('Gagal menghapus materi: " . addslashes(mysqli_error($conn)) . "');
                    window.location.href='materi.php';
                  </script>";
        }
    } else {
        // Jika tidak konfirmasi, kembali ke materi.php
        header("Location: materi.php");
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hapus Materi - Sistem Informasi Bimbel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .confirmation-box {
            max-width: 600px;
            margin: 0 auto;
            border-left: 4px solid #dc3545;
        }

        .warning-icon {
            font-size: 4rem;
            color: #dc3545;
        }

        .file-info-box {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
        }

        .consequences-list {
            list-style-type: none;
            padding-left: 0;
        }

        .consequences-list li {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }

        .consequences-list li:last-child {
            border-bottom: none;
        }

        .consequences-list i {
            width: 25px;
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
                    <a class="nav-link active" href="materi.php"><i class="bi bi-journal-text"></i> Materi</a>
                    <a class="nav-link" href="tugas.php"><i class="bi bi-clipboard-check"></i> Tugas & Penilaian</a>
                    <a class="nav-link" href="siswa.php"><i class="bi bi-people"></i> Data Siswa</a>
                    <a class="nav-link" href="profil.php"><i class="bi bi-person"></i> Profil</a>
                    <a class="nav-link" href="../logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 content-wrapper">
                <div class="page-header d-flex justify-content-between align-items-center">
                    <div>
                        <h2>Hapus Materi</h2>
                        <p class="text-muted">Konfirmasi penghapusan materi</p>
                    </div>
                    <a href="materi.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Kembali
                    </a>
                </div>

                <!-- Confirmation Card -->
                <div class="card confirmation-box">
                    <div class="card-header bg-white text-center">
                        <i class="bi bi-exclamation-triangle warning-icon"></i>
                        <h4 class="mt-3 text-danger">Konfirmasi Penghapusan</h4>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <p class="lead">Anda yakin ingin menghapus materi ini?</p>
                            <h5 class="text-danger">"<?= htmlspecialchars($materi['judul']) ?>"</h5>
                        </div>

                        <!-- Informasi Materi yang akan dihapus -->
                        <div class="file-info-box">
                            <h6><i class="bi bi-info-circle"></i> Informasi Materi:</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>ID Materi:</strong> MAT<?= str_pad($materi['id_materi'], 4, '0', STR_PAD_LEFT) ?></p>
                                    <p><strong>Upload oleh:</strong> <?= htmlspecialchars($materi['tutor']) ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Tanggal Upload:</strong> <?= date('d M Y H:i', strtotime($materi['created_at'])) ?></p>
                                    <?php if (!empty($materi['updated_at']) && $materi['updated_at'] != '0000-00-00 00:00:00'): ?>
                                        <p><strong>Terakhir Update:</strong> <?= date('d M Y H:i', strtotime($materi['updated_at'])) ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <p><strong>File:</strong> <?= htmlspecialchars($materi['file_path']) ?></p>

                            <!-- Cek apakah file ada -->
                            <?php
                            $full_path = "../materi/" . $materi['file_path'];
                            $file_exists = file_exists($full_path);
                            ?>
                            <p>
                                <strong>Status File:</strong>
                                <?php if ($file_exists): ?>
                                    <span class="badge bg-success">Tersedia</span>
                                <?php else: ?>
                                    <span class="badge bg-warning">Tidak Ditemukan</span>
                                <?php endif; ?>
                            </p>
                        </div>

                        <!-- Dampak Penghapusan -->
                        <div class="alert alert-warning">
                            <h6><i class="bi bi-exclamation-octagon"></i> Dampak Penghapusan:</h6>
                            <ul class="consequences-list">
                                <li><i class="bi bi-x-circle text-danger"></i> File materi akan dihapus permanen dari server</li>
                                <li><i class="bi bi-x-circle text-danger"></i> Data materi akan dihapus dari database</li>
                                <li><i class="bi bi-x-circle text-danger"></i> Riwayat akses materi akan hilang</li>
                                <li><i class="bi bi-x-circle text-danger"></i> Tidak dapat dikembalikan (irreversible)</li>
                                <li><i class="bi bi-x-circle text-danger"></i> Siswa tidak dapat mengakses materi ini lagi</li>
                            </ul>
                        </div>

                        <!-- Statistik (jika ada) -->

                        <!-- Form Konfirmasi -->
                        <form method="POST" action="" onsubmit="return confirmFinal()">
                            <input type="hidden" name="confirm_delete" value="yes">

                            <div class="mb-3">
                                <label for="verification" class="form-label">
                                    <strong>Verifikasi:</strong> Ketik "<span class="text-danger">HAPUS</span>" untuk konfirmasi
                                </label>
                                <input type="text" id="verification" class="form-control"
                                    placeholder="Ketik HAPUS disini" required
                                    onkeyup="checkVerification()">
                                <div class="form-text">Ini untuk memastikan Anda tidak salah klik.</div>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                <a href="materi.php" class="btn btn-secondary me-md-2">
                                    <i class="bi bi-x-circle"></i> Batalkan
                                </a>
                                <button type="submit" id="deleteBtn" class="btn btn-danger" disabled>
                                    <i class="bi bi-trash"></i> Hapus Permanen
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Informasi Tambahan -->
                <div class="card mt-4">
                    <div class="card-header bg-white">
                        <h6><i class="bi bi-lightbulb"></i> Saran</h6>
                    </div>
                    <div class="card-body">
                        <p>Sebelum menghapus, pertimbangkan:</p>
                        <ul>
                            <li>Apakah materi sudah tidak relevan lagi?</li>
                            <li>Apakah ada materi yang lebih baru untuk menggantikan?</li>
                            <li>Apakah materi ini masih digunakan dalam kurikulum?</li>
                            <li>Mungkin lebih baik <strong>edit</strong> daripada menghapus</li>
                        </ul>
                        <p class="mb-0">
                            <a href="edit_materi.php?id=<?= $id_materi ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil"></i> Edit Materi
                            </a>
                            <a href="detail_materi.php?id=<?= $id_materi ?>" class="btn btn-sm btn-outline-info">
                                <i class="bi bi-eye"></i> Lihat Detail
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validasi input verifikasi
        function checkVerification() {
            const verificationInput = document.getElementById('verification');
            const deleteBtn = document.getElementById('deleteBtn');

            if (verificationInput.value.toUpperCase() === 'HAPUS') {
                deleteBtn.disabled = false;
                verificationInput.classList.remove('is-invalid');
                verificationInput.classList.add('is-valid');
            } else {
                deleteBtn.disabled = true;
                verificationInput.classList.remove('is-valid');
                verificationInput.classList.add('is-invalid');
            }
        }

        // Konfirmasi final sebelum submit
        function confirmFinal() {
            const verificationInput = document.getElementById('verification');

            if (verificationInput.value.toUpperCase() !== 'HAPUS') {
                alert('Harap ketik "HAPUS" untuk verifikasi!');
                return false;
            }

            return confirm('⚠️ PERINGATAN AKHIR!\n\nAnda akan menghapus materi ini PERMANEN.\nSemua data terkait akan hilang.\n\nTekan OK untuk melanjutkan atau Cancel untuk membatalkan.');
        }

        // Fokus ke input verifikasi saat halaman dimuat
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('verification').focus();
        });
    </script>
</body>

</html>