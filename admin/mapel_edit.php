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
   MASTER DATA (untuk dropdown)
===================================================== */
$jenjangList = [
    'SD' => 'SD',
    'SMP' => 'SMP'
    
];

$kurikulumList = [
    'Kurikulum 2013' => 'Kurikulum 2013',
    'Kurikulum Merdeka' => 'Kurikulum Merdeka',
    'KTSP' => 'KTSP'
];

/* =====================================================
   GET DATA MAPEL UNTUK EDIT
===================================================== */
$mapelData = null;
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $query = "SELECT * FROM mapel WHERE id_mapel = $id";
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
   PROSES UPDATE MAPEL
===================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_mapel'])) {
    $id = (int)$_POST['id_mapel'];
    $nama = mysqli_real_escape_string($conn, $_POST['nama_mapel']);
    $kode = mysqli_real_escape_string($conn, $_POST['kode_mapel'] ?? '');
    $jenjang = mysqli_real_escape_string($conn, $_POST['jenjang'] ?? '');
    $kurikulum = mysqli_real_escape_string($conn, $_POST['kurikulum'] ?? '');
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi'] ?? '');
    $status = mysqli_real_escape_string($conn, $_POST['status'] ?? 'Aktif');
    
    // Validasi nama tidak boleh kosong
    if (empty(trim($nama))) {
        $_SESSION['flash'][] = [
            'type' => 'danger',
            'text' => 'Nama mapel harus diisi'
        ];
        header("Location: mapel_edit.php?id=$id");
        exit;
    }
    
    // Cek apakah nama mapel sudah ada (kecuali untuk dirinya sendiri)
    $checkQuery = mysqli_query($conn, "SELECT id_mapel FROM mapel WHERE nama_mapel = '$nama' AND id_mapel != $id");
    if (mysqli_num_rows($checkQuery) > 0) {
        $_SESSION['flash'][] = [
            'type' => 'warning',
            'text' => 'Nama mapel sudah digunakan oleh mapel lain'
        ];
        header("Location: mapel_edit.php?id=$id");
        exit;
    }
    
    // UPDATE database
    $query = "UPDATE mapel SET 
              nama_mapel = '$nama',
              kode_mapel = '$kode',
              jenjang = '$jenjang',
              kurikulum = '$kurikulum',
              deskripsi = '$deskripsi',
              status = '$status',
              updated_at = NOW()
              WHERE id_mapel = $id";
    
    if (mysqli_query($conn, $query)) {
        $_SESSION['flash'][] = [
            'type' => 'success',
            'text' => 'Mapel berhasil diperbarui'
        ];
        header("Location: mapel.php");
        exit;
    } else {
        $_SESSION['flash'][] = [
            'type' => 'danger',
            'text' => 'Gagal memperbarui mapel: ' . mysqli_error($conn)
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Mapel - Sistem Informasi Bimbel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .form-container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            padding: 30px;
        }
        
        .page-header {
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 15px;
            margin-bottom: 30px;
        }
        
        .form-label {
            font-weight: 500;
        }
        
        .required::after {
            content: " *";
            color: #dc3545;
        }
        
        .info-box {
            background-color: #f8f9fa;
            border-left: 4px solid #0d6efd;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
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
                <div class="form-container">
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
                                <h2><i class="bi bi-pencil-square text-warning"></i> Edit Mata Pelajaran</h2>
                                <p class="text-muted">Perbarui data mata pelajaran</p>
                            </div>
                            <a href="mapel.php" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left"></i> Kembali
                            </a>
                        </div>
                    </div>
                    
                    <div class="info-box">
                        <h6><i class="bi bi-info-circle"></i> Edit Mapel: <?= h($mapelData['nama_mapel']) ?></h6>
                        <p class="mb-0">ID Mapel: <?= $mapelData['id_mapel'] ?> | Dibuat: <?= date('d M Y', strtotime($mapelData['created_at'])) ?></p>
                    </div>
                    
                    <form method="POST" action="mapel_edit.php">
                        <input type="hidden" name="id_mapel" value="<?= $mapelData['id_mapel'] ?>">
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label required">Nama Mapel</label>
                                <input type="text" name="nama_mapel" class="form-control" 
                                       value="<?= h($mapelData['nama_mapel']) ?>" required>
                                <div class="form-text">Nama mata pelajaran harus unik</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Kode Mapel (Opsional)</label>
                                <input type="text" name="kode_mapel" class="form-control" 
                                       value="<?= h($mapelData['kode_mapel']) ?>"
                                       placeholder="Contoh: MAT-10, BING-12">
                                <div class="form-text">Kode unik untuk identifikasi</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Jenjang</label>
                                <select name="jenjang" class="form-select">
                                    <option value="">Pilih Jenjang (opsional)</option>
                                    <?php foreach ($jenjangList as $key => $value): ?>
                                        <option value="<?= $key ?>" 
                                            <?= ($mapelData['jenjang'] == $key) ? 'selected' : '' ?>>
                                            <?= $value ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Kurikulum</label>
                                <select name="kurikulum" class="form-select">
                                    <option value="">Pilih Kurikulum (opsional)</option>
                                    <?php foreach ($kurikulumList as $key => $value): ?>
                                        <option value="<?= $key ?>" 
                                            <?= ($mapelData['kurikulum'] == $key) ? 'selected' : '' ?>>
                                            <?= $value ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label">Deskripsi Singkat</label>
                                <textarea name="deskripsi" class="form-control" rows="4"><?= h($mapelData['deskripsi']) ?></textarea>
                                <div class="form-text">Deskripsi singkat tentang mata pelajaran</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select" required>
                                    <option value="Aktif" <?= ($mapelData['status'] == 'Aktif') ? 'selected' : '' ?>>Aktif</option>
                                    <option value="Nonaktif" <?= ($mapelData['status'] == 'Nonaktif') ? 'selected' : '' ?>>Nonaktif</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mt-5 pt-3 border-top">
                            <button type="submit" name="update_mapel" class="btn btn-warning btn-lg">
                                <i class="bi bi-save"></i> Perbarui Mapel
                            </button>
                            <a href="mapel_detail.php?id=<?= $mapelData['id_mapel'] ?>" class="btn btn-info btn-lg">
                                <i class="bi bi-eye"></i> Lihat Detail
                            </a>
                            <a href="mapel.php" class="btn btn-outline-secondary btn-lg">
                                <i class="bi bi-x-circle"></i> Batal
                            </a>
                        </div>
                    </form>
                    
                    <div class="alert alert-info mt-4">
                        <h6><i class="bi bi-exclamation-triangle"></i> Perhatian</h6>
                        <ul class="mb-0">
                            <li>Mengubah status menjadi Nonaktif akan menghilangkan mapel dari pilihan kelas baru</li>
                            <li>Mapel yang sudah digunakan oleh kelas tetap akan tampil di kelas tersebut</li>
                            <li>Pastikan tidak ada kelas aktif yang bermasalah sebelum menonaktifkan mapel</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>