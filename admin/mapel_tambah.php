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
    'SMP' => 'SMP', 
];

$kurikulumList = [
    'Kurikulum 2013' => 'Kurikulum 2013',
    'Kurikulum Merdeka' => 'Kurikulum Merdeka',
    'KTSP' => 'KTSP'
];

/* =====================================================
   PROSES TAMBAH MAPEL
===================================================== */
if (isset($_POST['simpan_mapel'])) {
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
        header("Location: mapel_tambah.php");
        exit;
    }
    
    // Cek apakah nama mapel sudah ada
    $checkQuery = mysqli_query($conn, "SELECT id_mapel FROM mapel WHERE nama_mapel = '$nama'");
    if (mysqli_num_rows($checkQuery) > 0) {
        $_SESSION['flash'][] = [
            'type' => 'warning',
            'text' => 'Nama mapel sudah ada'
        ];
        header("Location: mapel_tambah.php");
        exit;
    }
    
    // INSERT ke database
    $query = "INSERT INTO mapel 
              (nama_mapel, kode_mapel, jenjang, kurikulum, deskripsi, status, created_at)
              VALUES ('$nama', '$kode', '$jenjang', '$kurikulum', '$deskripsi', '$status', NOW())";
    
    if (mysqli_query($conn, $query)) {
        $_SESSION['flash'][] = [
            'type' => 'success',
            'text' => 'Mapel berhasil ditambahkan'
        ];
        header("Location: mapel.php");
        exit;
    } else {
        $_SESSION['flash'][] = [
            'type' => 'danger',
            'text' => 'Gagal menambahkan mapel: ' . mysqli_error($conn)
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Mapel - Sistem Informasi Bimbel</title>
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
                                <h2><i class="bi bi-plus-circle text-primary"></i> Tambah Mata Pelajaran</h2>
                                <p class="text-muted">Form tambah mata pelajaran baru</p>
                            </div>
                            <a href="mapel.php" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left"></i> Kembali
                            </a>
                        </div>
                    </div>
                    
                    <form method="POST" action="mapel_tambah.php">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label required">Nama Mapel</label>
                                <input type="text" name="nama_mapel" class="form-control" 
                                       placeholder="Contoh: Matematika, Bahasa Inggris" required>
                                <div class="form-text">Nama mata pelajaran harus unik</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Kode Mapel (Opsional)</label>
                                <input type="text" name="kode_mapel" class="form-control" 
                                       placeholder="Contoh: MAT-10, BING-12">
                                <div class="form-text">Kode unik untuk identifikasi</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Jenjang</label>
                                <select name="jenjang" class="form-select">
                                    <option value="">Pilih Jenjang (opsional)</option>
                                    <?php foreach ($jenjangList as $key => $value): ?>
                                        <option value="<?= $key ?>"><?= $value ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Kurikulum</label>
                                <select name="kurikulum" class="form-select">
                                    <option value="">Pilih Kurikulum (opsional)</option>
                                    <?php foreach ($kurikulumList as $key => $value): ?>
                                        <option value="<?= $key ?>"><?= $value ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label">Deskripsi Singkat</label>
                                <textarea name="deskripsi" class="form-control" rows="4" 
                                          placeholder="Tujuan mata pelajaran, cakupan materi..."></textarea>
                                <div class="form-text">Deskripsi singkat tentang mata pelajaran</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select" required>
                                    <option value="Aktif" selected>Aktif</option>
                                    <option value="Nonaktif">Nonaktif</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mt-5 pt-3 border-top">
                            <button type="submit" name="simpan_mapel" class="btn btn-primary btn-lg">
                                <i class="bi bi-save"></i> Simpan Mapel
                            </button>
                            <a href="mapel.php" class="btn btn-outline-secondary btn-lg">
                                <i class="bi bi-x-circle"></i> Batal
                            </a>
                        </div>
                    </form>
                    
                    <div class="alert alert-info mt-4">
                        <h6><i class="bi bi-info-circle"></i> Informasi</h6>
                        <ul class="mb-0">
                            <li>Hanya konfigurasi mata pelajaran, bukan konten belajar</li>
                            <li>Tidak ada upload materi, file, deadline, atau tutor spesifik</li>
                            <li>Mapel dapat digunakan oleh beberapa kelas</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>