<?php
session_start();

require "../config.php";

// Proteksi route: hanya tutor yang boleh akses
if (!isset($_SESSION['login']) || ($_SESSION['role'] !== 'tutor' && $_SESSION['role'] !== 'admin')) {
    header("Location: ../index.php");
    exit;
}

// Ambil ID tugas dari URL
$id_tugas = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_tugas == 0) {
    header("Location: tugas.php");
    exit;
}

// Ambil data tugas berdasarkan ID
if ($_SESSION['role'] === 'admin') {
    $query = mysqli_query($conn, "
        SELECT t.*, m.nama_mapel, k.nama_kelas, tu.nama as tutor_nama
        FROM tugas t
        LEFT JOIN mapel m ON t.id_mapel = m.id_mapel
        LEFT JOIN kelas k ON t.id_kelas = k.id_kelas
        LEFT JOIN tutor tu ON t.id_tutor = tu.id_tutor
        WHERE t.id_tugas = $id_tugas
    ");
} else {
    $id_tutor = intval($_SESSION['id_tutor']);
    $query = mysqli_query($conn, "
        SELECT t.*, m.nama_mapel, k.nama_kelas, tu.nama as tutor_nama
        FROM tugas t
        LEFT JOIN mapel m ON t.id_mapel = m.id_mapel
        LEFT JOIN kelas k ON t.id_kelas = k.id_kelas
        LEFT JOIN tutor tu ON t.id_tutor = tu.id_tutor
        WHERE t.id_tugas = $id_tugas AND t.id_tutor = $id_tutor
    ");
}

$tugas = mysqli_fetch_assoc($query);

// Jika tugas tidak ditemukan
if (!$tugas) {
    echo "<script>
            alert('Tugas tidak ditemukan atau Anda tidak memiliki akses!');
            window.location.href='tugas.php';
          </script>";
    exit;
}

// Hitung statistik
$stat_query = mysqli_query($conn, "
    SELECT 
        COUNT(*) as total_siswa,
        COUNT(CASE WHEN tu.file_path IS NOT NULL AND tu.file_path != '' THEN 1 END) as sudah_kumpul,
        COUNT(CASE WHEN tu.file_path IS NULL OR tu.file_path = '' THEN 1 END) as belum_kumpul,
        COUNT(CASE WHEN n.nilai IS NOT NULL THEN 1 END) as sudah_dinilai,
        AVG(n.nilai) as rata_rata_nilai
    FROM siswa s
    LEFT JOIN tugas_upload tu ON s.id_siswa = tu.id_siswa AND tu.tugas_id = $id_tugas
    LEFT JOIN nilai n ON s.id_siswa = n.id_siswa AND n.id_tugas = $id_tugas
    WHERE s.id_kelas = " . intval($tugas['id_kelas']) . "
");

$statistik = mysqli_fetch_assoc($stat_query);

// Ambil daftar pengumpulan tugas
$submissions_query = mysqli_query($conn, "
    SELECT tu.*, s.nama as siswa_nama, s.id_siswa, n.nilai as nilai_akhir
    FROM tugas_upload tu
    JOIN siswa s ON tu.id_siswa = s.id_siswa
    LEFT JOIN nilai n ON s.id_siswa = n.id_siswa AND n.id_tugas = $id_tugas
    WHERE tu.tugas_id = $id_tugas
    ORDER BY tu.uploaded_at DESC
");

// Format tanggal helper
function format_date($dt, $with_time = false)
{
    if (!$dt || $dt == '0000-00-00 00:00:00') return '-';
    if ($with_time) {
        return date('d M Y, H:i', strtotime($dt));
    }
    return date('d M Y', strtotime($dt));
}

// Tentukan status badge
function get_status_badge($status)
{
    $status = strtolower($status);
    switch ($status) {
        case 'aktif':
            return '<span class="badge bg-success">Aktif</span>';
        case 'selesai':
            return '<span class="badge bg-primary">Selesai</span>';
        case 'nonaktif':
            return '<span class="badge bg-secondary">Nonaktif</span>';
        default:
            return '<span class="badge bg-secondary">' . ucfirst($status) . '</span>';
    }
}

// Tentukan icon berdasarkan ekstensi file
function get_file_icon($filename)
{
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $icons = [
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
    return isset($icons[$ext]) ? $icons[$ext] : 'bi-file-earmark';
}

// Hitung sisa waktu deadline
function get_time_remaining($deadline)
{
    if (!$deadline) return '';

    $now = time();
    $deadline_time = strtotime($deadline);
    $diff = $deadline_time - $now;

    if ($diff <= 0) {
        return '<span class="text-danger">Waktu habis</span>';
    }

    $days = floor($diff / (60 * 60 * 24));
    $hours = floor(($diff % (60 * 60 * 24)) / (60 * 60));

    if ($days > 0) {
        return "<span class='text-success'>$days hari $hours jam lagi</span>";
    } else {
        return "<span class='text-warning'>$hours jam lagi</span>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Tugas - Sistem Informasi Bimbel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .detail-card {
            border-left: 4px solid #0d6efd;
        }

        .stat-card {
            border-radius: 10px;
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .file-info-box {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
        }

        .submission-row {
            border-left: 3px solid transparent;
            transition: all 0.3s;
        }

        .submission-row:hover {
            background-color: #f8f9fa;
            border-left-color: #0d6efd;
        }

        .grade-badge {
            font-size: 0.9em;
            padding: 5px 10px;
        }

        .deadline-box {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 10px;
            padding: 20px;
            text-align: center;
        }

        .progress-sm {
            height: 8px;
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
                    <a class="nav-link active" href="tugas.php"><i class="bi bi-clipboard-check"></i> Tugas & Penilaian</a>
                    <a class="nav-link" href="siswa.php"><i class="bi bi-people"></i> Data Siswa</a>
                    <a class="nav-link" href="profil.php"><i class="bi bi-person"></i> Profil</a>
                    <a class="nav-link" href="../logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 content-wrapper">
                <div class="page-header d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2>Detail Tugas</h2>
                        <p class="text-muted">Informasi lengkap tentang tugas</p>
                    </div>
                    <div>
                        <a href="tugas.php" class="btn btn-outline-secondary me-2">
                            <i class="bi bi-arrow-left"></i> Kembali
                        </a>
                        <a href="tugas_edit.php?id=<?= $id_tugas ?>" class="btn btn-warning me-2">
                            <i class="bi bi-pencil"></i> Edit
                        </a>
                        <a href="tugas_nilai.php?tugas_id=<?= $id_tugas ?>" class="btn btn-primary">
                            <i class="bi bi-star"></i> Nilai Tugas
                        </a>
                    </div>
                </div>

                <!-- Informasi Utama -->
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card detail-card mb-4">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><?= htmlspecialchars($tugas['judul']) ?></h5>
                                <div>
                                    <?= get_status_badge($tugas['status'] ?? '') ?>
                                    <span class="badge bg-info">ID: TGS<?= str_pad($tugas['id_tugas'], 4, '0', STR_PAD_LEFT) ?></span>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="mb-4">
                                    <h6>Deskripsi Tugas:</h6>
                                    <div class="p-3 bg-light rounded">
                                        <?= nl2br(htmlspecialchars($tugas['deskripsi'] ?: 'Tidak ada deskripsi')) ?>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong><i class="bi bi-journal"></i> Mata Pelajaran:</strong><br>
                                            <?= htmlspecialchars($tugas['nama_mapel'] ?: '-') ?></p>

                                        <p><strong><i class="bi bi-people"></i> Kelas:</strong><br>
                                            <?= htmlspecialchars($tugas['nama_kelas'] ?: '-') ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong><i class="bi bi-person"></i> Tutor:</strong><br>
                                            <?= htmlspecialchars($tugas['tutor_nama'] ?: '-') ?></p>

                                        <p><strong><i class="bi bi-calendar"></i> Dibuat:</strong><br>
                                            <?= format_date($tugas['created_at'], true) ?></p>
                                    </div>
                                </div>

                                <?php if (!empty($tugas['lampiran'])): ?>
                                    <div class="file-info-box">
                                        <h6><i class="bi bi-paperclip"></i> Lampiran:</h6>
                                        <a href="../tugas/lampiran/<?= htmlspecialchars($tugas['lampiran']) ?>"
                                            class="btn btn-outline-primary btn-sm"
                                            download>
                                            <i class="bi <?= get_file_icon($tugas['lampiran']) ?>"></i>
                                            <?= htmlspecialchars($tugas['lampiran']) ?>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <!-- Card Deadline -->
                        <div class="card mb-4">
                            <div class="card-header bg-white">
                                <h6 class="mb-0"><i class="bi bi-clock"></i> Deadline</h6>
                            </div>
                            <div class="card-body deadline-box">
                                <h4><?= format_date($tugas['deadline'], true) ?></h4>
                                <div class="mt-3">
                                    <?= get_time_remaining($tugas['deadline']) ?>
                                </div>
                                <?php
                                $deadline_time = strtotime($tugas['deadline']);
                                $now = time();
                                $total = $deadline_time - strtotime($tugas['created_at']);
                                $elapsed = $now - strtotime($tugas['created_at']);
                                $progress = $total > 0 ? min(100, ($elapsed / $total) * 100) : 0;
                                ?>
                                <div class="mt-3">
                                    <div class="d-flex justify-content-between small text-muted mb-1">
                                        <span>Progress Waktu</span>
                                        <span><?= round($progress) ?>%</span>
                                    </div>
                                    <div class="progress progress-sm">
                                        <div class="progress-bar <?= $progress > 80 ? 'bg-danger' : ($progress > 50 ? 'bg-warning' : 'bg-success') ?>"
                                            role="progressbar"
                                            style="width: <?= $progress ?>%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Statistik Card -->
                        <div class="card">
                            <div class="card-header bg-white">
                                <h6 class="mb-0"><i class="bi bi-graph-up"></i> Statistik</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-6 mb-3">
                                        <div class="stat-card p-3 text-center bg-primary bg-opacity-10">
                                            <i class="bi bi-people-fill fs-3 text-primary"></i>
                                            <h4 class="mt-2"><?= $statistik['total_siswa'] ?? 0 ?></h4>
                                            <p class="text-muted mb-0">Total Siswa</p>
                                        </div>
                                    </div>
                                    <div class="col-6 mb-3">
                                        <div class="stat-card p-3 text-center bg-success bg-opacity-10">
                                            <i class="bi bi-check-circle-fill fs-3 text-success"></i>
                                            <h4 class="mt-2"><?= $statistik['sudah_kumpul'] ?? 0 ?></h4>
                                            <p class="text-muted mb-0">Sudah Kumpul</p>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="stat-card p-3 text-center bg-warning bg-opacity-10">
                                            <i class="bi bi-exclamation-circle-fill fs-3 text-warning"></i>
                                            <h4 class="mt-2"><?= $statistik['belum_kumpul'] ?? 0 ?></h4>
                                            <p class="text-muted mb-0">Belum Kumpul</p>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="stat-card p-3 text-center bg-info bg-opacity-10">
                                            <i class="bi bi-star-fill fs-3 text-info"></i>
                                            <h4 class="mt-2"><?= $statistik['sudah_dinilai'] ?? 0 ?></h4>
                                            <p class="text-muted mb-0">Sudah Dinilai</p>
                                        </div>
                                    </div>
                                </div>
                                <?php if ($statistik['rata_rata_nilai']): ?>
                                    <div class="mt-3 p-3 bg-light rounded">
                                        <h6>Rata-rata Nilai:</h6>
                                        <h3 class="text-primary"><?= number_format($statistik['rata_rata_nilai'], 2) ?></h3>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Daftar Pengumpulan -->
                <div class="card">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Pengumpulan Siswa</h5>
                        <span class="badge bg-secondary">
                            <?= $statistik['sudah_kumpul'] ?? 0 ?> dari <?= $statistik['total_siswa'] ?? 0 ?> siswa
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nama Siswa</th>
                                        <th>Tanggal Kumpul</th>
                                        <th>File</th>
                                        <th>Nilai</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (mysqli_num_rows($submissions_query) == 0): ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">
                                                <i class="bi bi-inbox fs-1"></i><br>
                                                Belum ada pengumpulan
                                            </td>
                                        </tr>
                                        <?php else:
                                        mysqli_data_seek($submissions_query, 0);
                                        while ($sub = mysqli_fetch_assoc($submissions_query)):
                                            $nilai = $sub['nilai_akhir'];
                                            $grade_class = '';
                                            if ($nilai !== null) {
                                                if ($nilai >= 85) $grade_class = 'bg-success';
                                                elseif ($nilai >= 70) $grade_class = 'bg-info';
                                                elseif ($nilai >= 60) $grade_class = 'bg-warning';
                                                else $grade_class = 'bg-danger';
                                            }
                                        ?>
                                            <tr class="submission-row">
                                                <td><?= htmlspecialchars($sub['siswa_nama']) ?></td>
                                                <td><?= format_date($sub['uploaded_at'], true) ?></td>
                                                <td>
                                                    <?php if (!empty($sub['file_path'])): ?>
                                                        <a href="../tugas/uploads/<?= htmlspecialchars($sub['file_path']) ?>"
                                                            class="btn btn-sm btn-outline-primary"
                                                            download>
                                                            <i class="bi <?= get_file_icon($sub['file_path']) ?>"></i> Download
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($nilai !== null): ?>
                                                        <span class="badge grade-badge <?= $grade_class ?>">
                                                            <?= $nilai ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if (!empty($sub['file_path'])): ?>
                                                        <span class="badge bg-success">Terkumpul</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Belum</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if (!empty($sub['file_path'])): ?>
                                                        <a href="../tugas/uploads/<?= htmlspecialchars($sub['file_path']) ?>"
                                                            class="btn btn-sm btn-info"
                                                            target="_blank">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    <button class="btn btn-sm btn-warning"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#nilaiModal"
                                                        data-siswa-id="<?= $sub['id_siswa'] ?>"
                                                        data-siswa-nama="<?= htmlspecialchars($sub['siswa_nama']) ?>"
                                                        data-current-nilai="<?= $nilai ?>"
                                                        data-current-feedback="<?= htmlspecialchars($sub['feedback'] ?? '') ?>">
                                                        <i class="bi bi-star"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                    <?php endwhile;
                                    endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="d-flex justify-content-between mt-4">
                    <div>
                        <a href="tugas.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Kembali ke Daftar
                        </a>
                    </div>
                    <div>
                        <a href="tugas_edit.php?id=<?= $id_tugas ?>" class="btn btn-warning me-2">
                            <i class="bi bi-pencil"></i> Edit Tugas
                        </a>
                        <a href="tugas_nilai.php?tugas_id=<?= $id_tugas ?>" class="btn btn-primary me-2">
                            <i class="bi bi-star"></i> Kelola Nilai
                        </a>
                        <a href="hapus_tugas.php?id=<?= $id_tugas ?>"
                            class="btn btn-danger"
                            onclick="return confirm('Yakin ingin menghapus tugas ini?')">
                            <i class="bi bi-trash"></i> Hapus
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal untuk input nilai -->
    <div class="modal fade" id="nilaiModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Beri Nilai</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="simpan_nilai.php" method="POST">
                    <input type="hidden" name="tugas_id" value="<?= $id_tugas ?>">
                    <input type="hidden" id="siswa_id" name="siswa_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Siswa</label>
                            <input type="text" id="siswa_nama" class="form-control" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nilai (0-100)</label>
                            <input type="number" id="nilai_input" name="nilai" class="form-control"
                                min="0" max="100" step="0.1" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Feedback</label>
                            <textarea id="feedback_input" name="feedback" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Nilai</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Modal untuk input nilai
        const nilaiModal = document.getElementById('nilaiModal');
        nilaiModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const siswaId = button.getAttribute('data-siswa-id');
            const siswaNama = button.getAttribute('data-siswa-nama');
            const currentNilai = button.getAttribute('data-current-nilai');
            const currentFeedback = button.getAttribute('data-current-feedback');

            document.getElementById('siswa_id').value = siswaId;
            document.getElementById('siswa_nama').value = siswaNama;
            document.getElementById('nilai_input').value = currentNilai;
            document.getElementById('feedback_input').value = currentFeedback;
        });

        // Validasi input nilai
        document.querySelector('form').addEventListener('submit', function(e) {
            const nilaiInput = document.getElementById('nilai_input');
            const nilai = parseFloat(nilaiInput.value);

            if (isNaN(nilai) || nilai < 0 || nilai > 100) {
                e.preventDefault();
                alert('Nilai harus antara 0 dan 100!');
                nilaiInput.focus();
            }
        });
    </script>
</body>

</html>