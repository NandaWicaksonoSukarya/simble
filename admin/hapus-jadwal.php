<?php
// hapus-jadwal.php
session_start();

require "../config.php";

// Pastikan user login admin
if (!isset($_SESSION['login']) || $_SESSION['role'] !== "admin") {
    header("Location: ../index.php");
    exit;
}

// Ambil ID dari parameter GET
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "ID jadwal tidak valid!";
    header("Location: kelas.php");
    exit;
}

$id_jadwal = (int)$_GET['id'];

// Konfirmasi penghapusan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    
    // Hapus jadwal dari database
    $sql_delete = "DELETE FROM jadwal WHERE id_jadwal = ?";
    $stmt = mysqli_prepare($conn, $sql_delete);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $id_jadwal);
        
        if (mysqli_stmt_execute($stmt)) {
            // Cek apakah ada baris yang terpengaruh
            if (mysqli_stmt_affected_rows($stmt) > 0) {
                $_SESSION['success'] = "Jadwal berhasil dihapus!";
            } else {
                $_SESSION['error'] = "Jadwal tidak ditemukan!";
            }
        } else {
            $_SESSION['error'] = "Gagal menghapus jadwal: " . mysqli_error($conn);
        }
        
        mysqli_stmt_close($stmt);
    } else {
        $_SESSION['error'] = "Error menyiapkan query: " . mysqli_error($conn);
    }
    
    header("Location: kelas.php");
    exit;
}

// Ambil data jadwal untuk ditampilkan di konfirmasi
$sql_select = "SELECT 
                jadwal.*,
                mapel.nama_mapel,
                kelas.nama_kelas,
                tutor.nama_tutor,
                ruangan.nama_ruangan
              FROM jadwal
              JOIN mapel ON jadwal.id_mapel = mapel.id_mapel
              JOIN kelas ON jadwal.id_kelas = kelas.id_kelas
              JOIN tutor ON jadwal.id_tutor = tutor.id_tutor
              LEFT JOIN ruangan ON jadwal.id_ruangan = ruangan.id_ruangan
              WHERE jadwal.id_jadwal = $id_jadwal";

$result = mysqli_query($conn, $sql_select);

if (!$result || mysqli_num_rows($result) === 0) {
    $_SESSION['error'] = "Jadwal tidak ditemukan!";
    header("Location: kelas.php");
    exit;
}

$jadwal = mysqli_fetch_assoc($result);

// Fungsi helper untuk escape output
function h($s) {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hapus Jadwal - Sistem Informasi Bimbel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .info-item {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .info-item:last-child {
            border-bottom: none;
        }
        .danger-zone {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
            padding: 20px;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-danger text-white">
                        <h4 class="mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Konfirmasi Penghapusan Jadwal</h4>
                    </div>
                    <div class="card-body">
                        <!-- Informasi Jadwal yang akan dihapus -->
                        <div class="alert alert-warning">
                            <h5 class="alert-heading">PERINGATAN!</h5>
                            <p class="mb-0">Anda akan menghapus jadwal berikut. Tindakan ini tidak dapat dibatalkan!</p>
                        </div>
                        
                        <h5 class="mb-3">Detail Jadwal:</h5>
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="info-item">
                                    <strong>Mata Pelajaran:</strong>
                                    <span class="float-end"><?= h($jadwal['nama_mapel']) ?></span>
                                </div>
                                <div class="info-item">
                                    <strong>Kelas:</strong>
                                    <span class="float-end"><?= h($jadwal['nama_kelas']) ?></span>
                                </div>
                                <div class="info-item">
                                    <strong>Tutor:</strong>
                                    <span class="float-end"><?= h($jadwal['nama_tutor']) ?></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-item">
                                    <strong>Tanggal:</strong>
                                    <span class="float-end"><?= h($jadwal['tanggal']) ?></span>
                                </div>
                                <div class="info-item">
                                    <strong>Waktu:</strong>
                                    <span class="float-end">
                                        <?= h(substr($jadwal['jam_mulai'], 0, 5)) ?> - <?= h(substr($jadwal['jam_selesai'], 0, 5)) ?>
                                    </span>
                                </div>
                                <div class="info-item">
                                    <strong>Ruangan:</strong>
                                    <span class="float-end"><?= h($jadwal['nama_ruangan'] ?? '-') ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <strong>Status:</strong>
                            <span class="float-end">
                                <?php if ($jadwal['status'] == "Aktif"): ?>
                                    <span class="badge bg-success">Aktif</span>
                                <?php elseif ($jadwal['status'] == "Ditunda"): ?>
                                    <span class="badge bg-warning">Ditunda</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary"><?= h($jadwal['status']) ?></span>
                                <?php endif; ?>
                            </span>
                        </div>
                        
                        <!-- Form Konfirmasi -->
                        <div class="danger-zone mt-4">
                            <h5 class="text-danger mb-3"><i class="bi bi-trash3 me-2"></i>Zona Berbahaya</h5>
                            <form method="POST" action="">
                                <p class="mb-4">Apakah Anda yakin ingin menghapus jadwal ini? Data yang sudah dihapus tidak dapat dikembalikan.</p>
                                
                                <div class="d-flex justify-content-between">
                                    <a href="kelas.php" class="btn btn-secondary">
                                        <i class="bi bi-arrow-left me-1"></i> Kembali
                                    </a>
                                    <button type="submit" name="confirm_delete" class="btn btn-danger">
                                        <i class="bi bi-trash me-1"></i> Ya, Hapus Jadwal
                                    </button>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Informasi Tambahan -->
                        <div class="alert alert-info mt-4">
                            <h6><i class="bi bi-info-circle me-2"></i>Informasi:</h6>
                            <ul class="mb-0">
                                <li>Penghapusan jadwal tidak mempengaruhi data siswa, tutor, atau mata pelajaran</li>
                                <li>Jika jadwal ini terkait dengan data lain, pastikan untuk memeriksa kembali</li>
                                <li>Pertimbangkan untuk mengubah status menjadi "Ditunda" daripada menghapus</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Konfirmasi tambahan sebelum submit
        document.querySelector('form').addEventListener('submit', function(e) {
            if (!confirm('Apakah Anda benar-benar yakin ingin menghapus jadwal ini? Tindakan ini tidak dapat dibatalkan!')) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>