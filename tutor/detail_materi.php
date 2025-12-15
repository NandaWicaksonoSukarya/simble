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

// Ambil data materi berdasarkan ID dengan join ke tabel mapel dan kelas
$query_materi = mysqli_query($conn, "
    SELECT m.*, mp.nama_mapel, k.nama_kelas 
    FROM materi m 
    LEFT JOIN mapel mp ON m.id_mapel = mp.id_mapel 
    LEFT JOIN kelas k ON m.id_kelas = k.id_kelas 
    WHERE m.id_materi = '$id_materi' AND m.tutor = '$tutor'
");
$materi = mysqli_fetch_assoc($query_materi);

// Jika materi tidak ditemukan atau bukan milik tutor
if (!$materi) {
    echo "<script>alert('Materi tidak ditemukan atau Anda tidak memiliki akses!'); window.location.href='materi.php';</script>";
    exit();
}

// Format tanggal
$created_date = date('d M Y H:i', strtotime($materi['created_at']));
$updated_date = !empty($materi['updated_at']) && $materi['updated_at'] != '0000-00-00 00:00:00'
    ? date('d M Y H:i', strtotime($materi['updated_at']))
    : 'Belum pernah diupdate';

// Tentukan icon berdasarkan ekstensi file
$file_path = $materi['file_path'];
$file_ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));

$file_icons = [
    'pdf' => 'bi-file-earmark-pdf',
    'doc' => 'bi-file-earmark-word',
    'docx' => 'bi-file-earmark-word',
    'ppt' => 'bi-file-earmark-ppt',
    'pptx' => 'bi-file-earmark-ppt',
    'txt' => 'bi-file-earmark-text',
    'jpg' => 'bi-file-earmark-image',
    'jpeg' => 'bi-file-earmark-image',
    'png' => 'bi-file-earmark-image',
    'zip' => 'bi-file-earmark-zip',
    'rar' => 'bi-file-earmark-zip'
];

$file_icon = isset($file_icons[$file_ext]) ? $file_icons[$file_ext] : 'bi-file-earmark';

// Hitung ukuran file jika ada
$file_size = '';
$full_path = "../materi/" . $file_path;
if (file_exists($full_path)) {
    $size = filesize($full_path);
    if ($size < 1024) {
        $file_size = $size . ' B';
    } elseif ($size < 1048576) {
        $file_size = round($size / 1024, 2) . ' KB';
    } else {
        $file_size = round($size / 1048576, 2) . ' MB';
    }
}

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Materi - Sistem Informasi Bimbel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .detail-card {
            border-left: 4px solid #0d6efd;
        }

        .file-preview {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            border: 2px dashed #dee2e6;
        }

        .file-icon-large {
            font-size: 4rem;
            color: #0d6efd;
            margin-bottom: 15px;
        }

        .info-box {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }

        .info-label {
            font-weight: 600;
            color: #6c757d;
            min-width: 150px;
            display: inline-block;
        }

        .action-buttons .btn {
            min-width: 120px;
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
                        <h2>Detail Materi Pembelajaran</h2>
                        <p class="text-muted">Informasi lengkap tentang materi Anda</p>
                    </div>
                    <div>
                        <a href="materi.php" class="btn btn-outline-secondary me-2">
                            <i class="bi bi-arrow-left"></i> Kembali
                        </a>
                        <a href="edit_materi.php?id=<?= $id_materi ?>" class="btn btn-warning">
                            <i class="bi bi-pencil"></i> Edit
                        </a>
                    </div>
                </div>

                <!-- Detail Content -->
                <div class="row">
                    <div class="col-lg-8">
                        <!-- Card Informasi Materi -->
                        <div class="card detail-card mb-4">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Informasi Materi</h5>
                                <span class="badge bg-primary">ID: MAT<?= str_pad($materi['id_materi'], 4, '0', STR_PAD_LEFT) ?></span>
                            </div>
                            <div class="card-body">
                                <div class="info-box">
                                    <div class="mb-3">
                                        <span class="info-label">Judul Materi:</span>
                                        <span class="fs-5 fw-bold"><?= htmlspecialchars($materi['judul']) ?></span>
                                    </div>

                                    <div class="mb-3">
                                        <span class="info-label">Mata Pelajaran:</span>
                                        <span class="badge bg-info text-white fs-6">
                                            <i class="bi bi-journal"></i> <?= htmlspecialchars($materi['nama_mapel']) ?>
                                        </span>
                                    </div>

                                    <div class="mb-3">
                                        <span class="info-label">Kelas:</span>
                                        <span class="badge bg-success text-white fs-6">
                                            <i class="bi bi-people"></i> <?= htmlspecialchars($materi['nama_kelas']) ?>
                                        </span>
                                    </div>

                                    <div class="mb-3">
                                        <span class="info-label">Tutor:</span>
                                        <span><?= htmlspecialchars($materi['tutor']) ?></span>
                                    </div>

                                    <div class="mb-3">
                                        <span class="info-label">Deskripsi:</span>
                                        <div class="mt-2 p-3 bg-light rounded">
                                            <?= !empty($materi['deskripsi']) ? nl2br(htmlspecialchars($materi['deskripsi'])) : '<em class="text-muted">Tidak ada deskripsi</em>' ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Statistik -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="info-box text-center">
                                            <i class="bi bi-eye-fill fs-4 text-primary"></i>
                                            <h4 class="mt-2"></h4>
                                            <p class="text-muted mb-0">Siswa Mengakses</p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-box text-center">
                                            <i class="bi bi-download fs-4 text-success"></i>
                                            <h4 class="mt-2"><?= $materi['download_count'] ?? 0 ?></h4>
                                            <p class="text-muted mb-0">Total Download</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <!-- Card File Materi -->
                        <div class="card mb-4">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">File Materi</h5>
                            </div>
                            <div class="card-body">
                                <div class="file-preview mb-4">
                                    <i class="bi <?= $file_icon ?> file-icon-large"></i>
                                    <h5><?= htmlspecialchars($file_path) ?></h5>
                                    <?php if ($file_size): ?>
                                        <p class="text-muted">Ukuran: <?= $file_size ?></p>
                                    <?php endif; ?>
                                    <p class="text-muted">Format: .<?= strtoupper($file_ext) ?></p>
                                </div>

                                <div class="action-buttons">
                                    <a href="../materi/<?= $file_path ?>"
                                        class="btn btn-primary w-100 mb-2"
                                        target="_blank">
                                        <i class="bi bi-eye"></i> Lihat File
                                    </a>

                                    <a href="../materi/<?= $file_path ?>"
                                        class="btn btn-success w-100 mb-2"
                                        download>
                                        <i class="bi bi-download"></i> Download
                                    </a>

                                    <button class="btn btn-outline-secondary w-100" onclick="copyLink()">
                                        <i class="bi bi-link"></i> Salin Link
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Card Timeline -->
                        <div class="card">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">Timeline</h5>
                            </div>
                            <div class="card-body">
                                <div class="timeline">
                                    <div class="d-flex mb-3">
                                        <div class="flex-shrink-0">
                                            <i class="bi bi-plus-circle-fill text-success fs-4"></i>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="mb-1">Materi Diupload</h6>
                                            <p class="text-muted mb-0"><?= $created_date ?></p>
                                        </div>
                                    </div>

                                    <?php if ($updated_date != 'Belum pernah diupdate'): ?>
                                        <div class="d-flex mb-3">
                                            <div class="flex-shrink-0">
                                                <i class="bi bi-arrow-clockwise text-warning fs-4"></i>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <h6 class="mb-1">Terakhir Diupdate</h6>
                                                <p class="text-muted mb-0"><?= $updated_date ?></p>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <div class="d-flex">
                                        <div class="flex-shrink-0">
                                            <i class="bi bi-info-circle text-info fs-4"></i>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="mb-1">Status</h6>
                                            <p class="text-muted mb-0">
                                                <span class="badge bg-success">Aktif</span>
                                                Tersedia untuk siswa
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tindakan Tambahan -->
                <div class="card mt-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Tindakan</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <a href="materi.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-left"></i> Kembali ke Daftar
                                </a>
                            </div>
                            <div>
                                <a href="edit_materi.php?id=<?= $id_materi ?>" class="btn btn-warning me-2">
                                    <i class="bi bi-pencil"></i> Edit Materi
                                </a>
                                <a href="hapus_materi.php?id=<?= $id_materi ?>"
                                    class="btn btn-danger"
                                    onclick="return confirm('Yakin ingin menghapus materi ini?')">
                                    <i class="bi bi-trash"></i> Hapus Materi
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Fungsi untuk menyalin link materi
        function copyLink() {
            const link = window.location.origin + '/bimbel/materi/<?= $file_path ?>';
            navigator.clipboard.writeText(link).then(() => {
                alert('Link berhasil disalin!');
            }).catch(err => {
                console.error('Gagal menyalin link: ', err);
                alert('Gagal menyalin link');
            });
        }

        // Fungsi untuk share materi
        function shareMateri() {
            if (navigator.share) {
                navigator.share({
                        title: '<?= htmlspecialchars($materi['judul']) ?>',
                        text: 'Materi pembelajaran dari Bimbel System',
                        url: window.location.href,
                    })
                    .then(() => console.log('Berhasil dibagikan'))
                    .catch((error) => console.log('Error sharing', error));
            } else {
                alert('Browser tidak mendukung fitur share');
            }
        }
    </script>
</body>

</html>