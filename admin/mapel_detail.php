<?php
session_start();
require "../config.php";

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

function h($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

/* =====================================================
   GET DATA MAPEL UNTUK DETAIL
===================================================== */
$mapelData = null;
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    $query = "
        SELECT 
            m.*,
            COUNT(DISTINCT k.id_kelas) as jumlah_kelas,
            GROUP_CONCAT(DISTINCT k.nama_kelas ORDER BY k.nama_kelas SEPARATOR ', ') as daftar_kelas,
            (SELECT COUNT(*) FROM materi mt JOIN kelas k ON mt.id_kelas = k.id_kelas WHERE k.id_mapel = m.id_mapel) as total_materi,
            (SELECT COUNT(*) FROM tugas t WHERE t.id_mapel = m.id_mapel) as total_tugas
        FROM mapel m
        LEFT JOIN kelas k ON m.id_mapel = k.id_mapel AND k.status = 'Aktif'
        WHERE m.id_mapel = $id
        GROUP BY m.id_mapel
    ";
    
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) > 0) {
        $mapelData = mysqli_fetch_assoc($result);
    } else {
        $_SESSION['flash'][] = [
            'type' => 'danger',
            'text' => 'Data mapel tidak ditemukan'
        ];
        header("Location: mapel.php");
        exit;
    }
} else {
    header("Location: mapel.php");
    exit;
}

/* =====================================================
   GET KELAS YANG MENGGUNAKAN MAPEL INI
===================================================== */
$kelasQuery = mysqli_query($conn, "
    SELECT k.*, t.nama_tutor
    FROM kelas k
    LEFT JOIN tutor t ON k.id_tutor = t.id_tutor
    WHERE k.id_mapel = {$mapelData['id_mapel']} AND k.status = 'Aktif'
    ORDER BY k.nama_kelas
");

$kelasList = [];
while ($row = mysqli_fetch_assoc($kelasQuery)) {
    $kelasList[] = $row;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Mapel - Sistem Informasi Bimbel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .detail-container {
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .page-header {
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 15px;
            margin-bottom: 30px;
        }
        
        .info-card {
            border-left: 4px solid #0d6efd;
            border-radius: 8px;
        }
        
        .stat-card {
            border-radius: 8px;
            transition: transform 0.2s;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
        }
        
        .kelas-list {
            max-height: 300px;
            overflow-y: auto;
        }
        
        .kelas-item {
            border-left: 3px solid #20c997;
            padding: 10px 15px;
            margin-bottom: 10px;
            background-color: #f8f9fa;
            border-radius: 5px;
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
                    <a class="nav-link" href="siswa.php"><i class="bi bi-people"></i> Data Siswa</a>
                    <a class="nav-link" href="tutor.php"><i class="bi bi-person-badge"></i> Data Tutor</a>
                    <a class="nav-link" href="kelas.php"><i class="bi bi-calendar3"></i> Jadwal Kelas</a>
                    <a class="nav-link" href="pembayaran.php"><i class="bi bi-cash-coin"></i> Pembayaran</a>
                    <a class="nav-link" href="materi.php"><i class="bi bi-journal-text"></i> Materi & Tugas</a>
                    <a class="nav-link active" href="mapel.php"><i class="bi bi-book"></i> Data Mapel</a>
                    <a class="nav-link" href="laporan.php"><i class="bi bi-file-earmark-text"></i> Laporan</a>
                    <a class="nav-link" href="../logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 content-wrapper p-4">
                <div class="detail-container">
                    <!-- Flash messages -->
                    <?php if (!empty($_SESSION['flash'])): ?>
                        <div class="mb-4">
                            <?php foreach ($_SESSION['flash'] as $f): ?>
                                <div class="alert alert-<?= h($f['type']) ?> alert-dismissible fade show">
                                    <?= h($f['text']) ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endforeach; ?>
                            <?php $_SESSION['flash'] = []; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="page-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h2><i class="bi bi-book text-primary"></i> Detail Mata Pelajaran</h2>
                                <p class="text-muted">Informasi lengkap mata pelajaran</p>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="mapel_edit.php?id=<?= $mapelData['id_mapel'] ?>" class="btn btn-warning">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>
                                <a href="mapel.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-left"></i> Kembali
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Header Info -->
                    <div class="card info-card mb-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h3 class="mb-1"><?= h($mapelData['nama_mapel']) ?></h3>
                                    <?php if ($mapelData['kode_mapel']): ?>
                                        <p class="text-muted mb-0">Kode: <?= h($mapelData['kode_mapel']) ?></p>
                                    <?php endif; ?>
                                </div>
                                <span class="badge <?= $mapelData['status'] == 'Aktif' ? 'bg-success' : 'bg-secondary' ?>" 
                                      style="font-size: 1rem; padding: 5px 15px;">
                                    <?= h($mapelData['status']) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Statistik -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card stat-card border-primary">
                                <div class="card-body text-center">
                                    <h1 class="text-primary"><?= (int)$mapelData['jumlah_kelas'] ?></h1>
                                    <p class="text-muted mb-0">Kelas</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stat-card border-info">
                                <div class="card-body text-center">
                                    <h1 class="text-info"><?= (int)$mapelData['total_materi'] ?></h1>
                                    <p class="text-muted mb-0">Materi</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stat-card border-warning">
                                <div class="card-body text-center">
                                    <h1 class="text-warning"><?= (int)$mapelData['total_tugas'] ?></h1>
                                    <p class="text-muted mb-0">Tugas</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stat-card border-secondary">
                                <div class="card-body text-center">
                                    <h5 class="text-secondary">ID: <?= $mapelData['id_mapel'] ?></h5>
                                    <p class="text-muted mb-0">ID Mapel</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Informasi Detail -->
                    <div class="row">
                        <div class="col-md-8">
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Informasi Mapel</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <table class="table table-sm">
                                                <tr>
                                                    <td width="120"><strong>Jenjang:</strong></td>
                                                    <td><?= $mapelData['jenjang'] ? h($mapelData['jenjang']) : '<span class="text-muted">-</span>' ?></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Kurikulum:</strong></td>
                                                    <td><?= $mapelData['kurikulum'] ? h($mapelData['kurikulum']) : '<span class="text-muted">-</span>' ?></td>
                                                </tr>
                                            </table>
                                        </div>
                                        <div class="col-md-6">
                                            <table class="table table-sm">
                                                <tr>
                                                    <td width="120"><strong>Dibuat:</strong></td>
                                                    <td><?= date('d M Y H:i', strtotime($mapelData['created_at'])) ?></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Diperbarui:</strong></td>
                                                    <td>
                                                        <?= $mapelData['updated_at'] ? 
                                                            date('d M Y H:i', strtotime($mapelData['updated_at'])) : 
                                                            '<span class="text-muted">Belum pernah</span>' ?>
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                    
                                    <hr>
                                    
                                    <h6>Deskripsi</h6>
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <?= $mapelData['deskripsi'] ? 
                                                nl2br(h($mapelData['deskripsi'])) : 
                                                '<p class="text-muted mb-0"><i>Tidak ada deskripsi</i></p>' ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0"><i class="bi bi-people"></i> Kelas yang Menggunakan</h5>
                                </div>
                                <div class="card-body kelas-list">
                                    <?php if (!empty($kelasList)): ?>
                                        <?php foreach ($kelasList as $kelas): ?>
                                        <div class="kelas-item">
                                            <h6 class="mb-1"><?= h($kelas['nama_kelas']) ?></h6>
                                            <p class="text-muted small mb-1">
                                                <i class="bi bi-person"></i> Tutor: <?= h($kelas['nama_tutor']) ?>
                                            </p>
                                            <p class="text-muted small mb-0">
                                                <i class="bi bi-calendar"></i> 
                                                <?= date('d M Y', strtotime($kelas['tanggal_mulai'])) ?> - 
                                                <?= date('d M Y', strtotime($kelas['tanggal_selesai'])) ?>
                                            </p>
                                        </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="text-center py-4">
                                            <i class="bi bi-people display-1 text-muted"></i>
                                            <p class="text-muted mt-3">Belum digunakan di kelas manapun</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="card-footer">
                                    <small class="text-muted">
                                        Total: <?= count($kelasList) ?> kelas aktif menggunakan mapel ini
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Aksi -->
                    <div class="card mt-4">
                        <div class="card-body text-center">
                            <div class="btn-group" role="group">
                                <a href="mapel_edit.php?id=<?= $mapelData['id_mapel'] ?>" class="btn btn-warning">
                                    <i class="bi bi-pencil"></i> Edit Mapel
                                </a>
                                <?php if ($mapelData['status'] == 'Aktif'): ?>
                                    <a href="mapel.php?hapus=<?= $mapelData['id_mapel'] ?>" 
                                       class="btn btn-danger"
                                       onclick="return confirm('Nonaktifkan mapel <?= h($mapelData['nama_mapel']) ?>?')">
                                        <i class="bi bi-x-circle"></i> Nonaktifkan
                                    </a>
                                <?php else: ?>
                                    <a href="mapel.php?aktifkan=<?= $mapelData['id_mapel'] ?>" 
                                       class="btn btn-success"
                                       onclick="return confirm('Aktifkan mapel <?= h($mapelData['nama_mapel']) ?>?')">
                                        <i class="bi bi-check-circle"></i> Aktifkan
                                    </a>
                                <?php endif; ?>
                                <a href="kelas.php?filter_mapel=<?= $mapelData['id_mapel'] ?>" class="btn btn-info">
                                    <i class="bi bi-eye"></i> Lihat Kelas
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info mt-4">
                        <h6><i class="bi bi-lightbulb"></i> Catatan</h6>
                        <ul class="mb-0">
                            <li>Halaman ini hanya menampilkan informasi, tidak ada aksi pembelajaran</li>
                            <li>Materi dan tugas terpisah di menu Materi & Tugas</li>
                            <li>Mapel dapat digunakan oleh beberapa kelas sekaligus</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>