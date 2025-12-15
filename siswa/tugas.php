<?php
session_start();
include "../config.php";

// Helper functions
function h($s)
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function badgeMapel($nama_mapel)
{
    if (empty($nama_mapel)) return "bg-secondary";

    $mapelLower = strtolower($nama_mapel);
    if (strpos($mapelLower, 'matematika') !== false) return "bg-primary";
    if (strpos($mapelLower, 'fisika') !== false) return "bg-success";
    if (strpos($mapelLower, 'kimia') !== false) return "bg-warning";
    if (strpos($mapelLower, 'biologi') !== false) return "bg-danger";
    if (strpos($mapelLower, 'bahasa inggris') !== false) return "bg-info";
    return "bg-secondary";
}

function hitungProgress($deadline)
{
    if (empty($deadline)) return 0;

    $now = time();
    $end = strtotime($deadline);

    if ($end <= $now) return 100;

    // Asumsi tugas diberikan 7 hari sebelum deadline
    $waktuPemberian = $end - (7 * 24 * 60 * 60);
    if ($waktuPemberian > $now) return 0;

    $totalWaktu = $end - $waktuPemberian;
    $waktuBerjalan = $now - $waktuPemberian;

    return min(100, ($waktuBerjalan / $totalWaktu) * 100);
}

function badgeDeadline($deadline)
{
    if (empty($deadline)) return ["Tidak ada deadline", "bg-secondary"];

    $now = time();
    $end = strtotime($deadline);
    $selisih = $end - $now;

    if ($selisih <= 0) return ["Terlambat", "bg-danger"];

    $hari = floor($selisih / 86400);
    $jam = floor(($selisih % 86400) / 3600);

    if ($hari == 0) {
        if ($jam <= 1) return ["1 Jam Lagi", "bg-danger"];
        return ["$jam Jam Lagi", "bg-danger"];
    }
    if ($hari == 1) return ["1 Hari Lagi", "bg-danger"];
    if ($hari <= 3) return ["$hari Hari Lagi", "bg-warning"];
    return ["$hari Hari Lagi", "bg-success"];
}

// Ambil semua tugas dengan join ke tabel mapel untuk mendapatkan nama_mapel
$query = "
    SELECT 
        t.*,
        mp.nama_mapel,
        mp.jenjang,
        mp.kode_mapel,
        k.nama_kelas,
        tr.nama_tutor
    FROM tugas t
    INNER JOIN mapel mp ON t.id_mapel = mp.id_mapel
    LEFT JOIN kelas k ON t.id_kelas = k.id_kelas
    LEFT JOIN tutor tr ON t.id_tutor = tr.id_tutor
    WHERE t.status = 'aktif'
    ORDER BY 
        CASE 
            WHEN t.deadline IS NULL THEN 1
            ELSE 0
        END,
        t.deadline ASC
";

$result = mysqli_query($conn, $query);
$tugasList = [];

if (!$result) {
    echo "Error: " . mysqli_error($conn);
} else {
    while ($row = mysqli_fetch_assoc($result)) {
        $tugasList[] = $row;
    }
}

// Debug: Tampilkan data tugas
// echo "<pre>";
// print_r($tugasList);
// echo "</pre>";
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tugas - Sistem Informasi Bimbel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar p-0">
                <div class="text-center text-white py-4">
                    <i class="bi bi-book-fill" style="font-size: 2.5rem;"></i>
                    <h5 class="mt-2">Bimbel System</h5>
                    <small>Portal Siswa</small>
                </div>
                <nav class="nav flex-column">
                    <a class="nav-link" href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                    <a class="nav-link" href="jadwal.php"><i class="bi bi-calendar3"></i> Jadwal Kelas</a>
                    <a class="nav-link" href="materi.php"><i class="bi bi-journal-text"></i> Materi</a>
                    <a class="nav-link active" href="tugas.php"><i class="bi bi-clipboard-check"></i> Tugas</a>
                    <a class="nav-link" href="nilai.php"><i class="bi bi-bar-chart"></i> Nilai</a>
                    <a class="nav-link" href="pembayaran.php"><i class="bi bi-cash-coin"></i> Pembayaran</a>
                    <a class="nav-link" href="profil.php"><i class="bi bi-person"></i> Profil</a>
                    <a class="nav-link" href="../logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 content-wrapper">
                <div class="page-header">
                    <h2>Tugas Saya</h2>
                    <p class="text-muted">Lihat dan kumpulkan tugas Anda</p>
                </div>

                <!-- Filter Section -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                                    <input type="text" class="form-control" placeholder="Cari tugas..." id="searchTugas">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" id="filterStatus">
                                    <option value="">Semua Status</option>
                                    <option value="pending">Belum Dikerjakan</option>
                                    <option value="progress">Dalam Pengerjaan</option>
                                    <option value="submitted">Sudah Dikumpulkan</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" id="filterMapel">
                                    <option value="">Semua Mata Pelajaran</option>
                                    <?php
                                    // Ambil mata pelajaran unik untuk filter
                                    $mapelQuery = "
                                        SELECT DISTINCT mp.id_mapel, mp.nama_mapel 
                                        FROM tugas t 
                                        INNER JOIN mapel mp ON t.id_mapel = mp.id_mapel 
                                        ORDER BY mp.nama_mapel
                                    ";
                                    $mapelResult = mysqli_query($conn, $mapelQuery);
                                    while ($mapel = mysqli_fetch_assoc($mapelResult)) {
                                        echo "<option value='" . h($mapel['nama_mapel']) . "'>" . h($mapel['nama_mapel']) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="row mb-4">
                    <?php
                    // Hitung statistik
                    $totalTugas = count($tugasList);
                    $deadlineDekat = 0;
                    $deadlineLewat = 0;

                    foreach ($tugasList as $tugas) {
                        if (!empty($tugas['deadline'])) {
                            $now = time();
                            $deadline = strtotime($tugas['deadline']);
                            $selisih = $deadline - $now;
                            $hari = floor($selisih / 86400);

                            if ($selisih <= 0) {
                                $deadlineLewat++;
                            } elseif ($hari <= 3) {
                                $deadlineDekat++;
                            }
                        }
                    }
                    ?>

                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="bi bi-clipboard-check text-primary" style="font-size: 2rem;"></i>
                                <h3 class="mt-2"><?= h($totalTugas) ?></h3>
                                <p class="text-muted mb-0">Total Tugas</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="bi bi-clock text-warning" style="font-size: 2rem;"></i>
                                <h3 class="mt-2"><?= h($deadlineDekat) ?></h3>
                                <p class="text-muted mb-0">Deadline Dekat</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="bi bi-exclamation-triangle text-danger" style="font-size: 2rem;"></i>
                                <h3 class="mt-2"><?= h($deadlineLewat) ?></h3>
                                <p class="text-muted mb-0">Terlambat</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="bi bi-check-circle text-success" style="font-size: 2rem;"></i>
                                <h3 class="mt-2">0</h3>
                                <p class="text-muted mb-0">Selesai</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Daftar Tugas -->
                <div id="tugasContainer">
                    <?php if (empty($tugasList)): ?>
                        <div class="card">
                            <div class="card-body text-center py-5">
                                <i class="bi bi-clipboard-x" style="font-size: 3rem; color: #6c757d;"></i>
                                <h4 class="mt-3">Belum ada tugas</h4>
                                <p class="text-muted">Tidak ada tugas yang harus dikerjakan saat ini.</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($tugasList as $tugas): ?>
                            <?php
                            $progress = hitungProgress($tugas['deadline']);
                            list($labelDeadline, $colorDeadline) = badgeDeadline($tugas['deadline']);

                            // Tentukan status progress bar
                            $progressClass = "bg-success";
                            if ($colorDeadline == "bg-danger") $progressClass = "bg-danger";
                            elseif ($colorDeadline == "bg-warning") $progressClass = "bg-warning";

                            // Format tanggal deadline
                            $deadlineFormatted = !empty($tugas['deadline'])
                                ? date("d M Y, H:i", strtotime($tugas['deadline']))
                                : "Tidak ditentukan";
                            ?>

                            <div class="card mb-3 tugas-item"
                                data-mapel="<?= h($tugas['nama_mapel'] ?? '') ?>"
                                data-status="pending">
                                <div class="card-body">
                                    <div class="row">
                                        <!-- Kolom Kiri: Informasi Tugas -->
                                        <div class="col-md-8">
                                            <div class="d-flex align-items-center mb-2">
                                                <span class="badge <?= badgeMapel($tugas['nama_mapel']) ?> me-2">
                                                    <?= h($tugas['nama_mapel'] ?? 'Mata Pelajaran') ?>
                                                </span>
                                                <span class="badge <?= $colorDeadline ?>">
                                                    <?= h($labelDeadline) ?>
                                                </span>
                                                <?php if ($tugas['jenjang']): ?>
                                                    <span class="badge bg-info ms-2">
                                                        <?= h($tugas['jenjang']) ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>

                                            <h5 class="mb-2"><?= h($tugas['judul']) ?></h5>

                                            <?php if (!empty($tugas['nama_kelas'])): ?>
                                                <p class="text-muted mb-2">
                                                    <i class="bi bi-people"></i>
                                                    Kelas: <?= h($tugas['nama_kelas']) ?>
                                                </p>
                                            <?php endif; ?>

                                            <?php if (!empty($tugas['nama_tutor'])): ?>
                                                <p class="text-muted mb-2">
                                                    <i class="bi bi-person"></i>
                                                    Tutor: <?= h($tugas['nama_tutor']) ?>
                                                </p>
                                            <?php endif; ?>

                                            <?php if (!empty($tugas['deskripsi'])): ?>
                                                <p class="mb-3"><?= h($tugas['deskripsi']) ?></p>
                                            <?php endif; ?>

                                            <div class="d-flex align-items-center text-muted small mb-2">
                                                <i class="bi bi-calendar me-1"></i>
                                                <span>Deadline: <?= h($deadlineFormatted) ?></span>

                                                <?php if (!empty($tugas['lampiran_path'])): ?>
                                                    <span class="mx-2">|</span>
                                                    <i class="bi bi-file-earmark me-1"></i>
                                                    <span>Lampiran: <?= h(basename($tugas['lampiran_path'])) ?></span>
                                                <?php endif; ?>
                                            </div>

                                            <!-- Progress Bar -->
                                            <div class="progress mt-3" style="height: 8px;">
                                                <div class="progress-bar <?= $progressClass ?>"
                                                    style="width: <?= $progress ?>%"
                                                    role="progressbar"
                                                    aria-valuenow="<?= $progress ?>"
                                                    aria-valuemin="0"
                                                    aria-valuemax="100">
                                                </div>
                                            </div>
                                            <small class="text-muted d-block mt-1">
                                                Progress: <?= round($progress) ?>%
                                            </small>
                                        </div>

                                        <!-- Kolom Kanan: Tombol Aksi -->
                                        <div class="col-md-4 text-end">
                                            <div class="d-grid gap-2">
                                                <button class="btn btn-success btn-kumpulkan"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#uploadTugasModal"
                                                    data-tugas-id="<?= h($tugas['id_tugas']) ?>"
                                                    data-tugas-judul="<?= h($tugas['judul']) ?>">
                                                    <i class="bi bi-upload"></i> Kumpulkan Tugas
                                                </button>

                                                <?php if (!empty($tugas['lampiran_path'])): ?>
                                                    <a href="<?= h($tugas['lampiran_path']) ?>"
                                                        class="btn btn-outline-primary"
                                                        download>
                                                        <i class="bi bi-download"></i> Download Soal
                                                    </a>
                                                <?php endif; ?>

                                                <button class="btn btn-outline-info btn-lihat-detail"
                                                    data-tugas-id="<?= h($tugas['id_tugas']) ?>">
                                                    <i class="bi bi-info-circle"></i> Lihat Detail
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Upload Tugas -->
    <div class="modal fade" id="uploadTugasModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Kumpulkan Tugas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6 id="modalTugasJudul">Upload File Tugas</h6>
                    <hr>
                    <form id="formUploadTugas" enctype="multipart/form-data">
                        <input type="hidden" id="id_tugas" name="id_tugas">

                        <div class="mb-3">
                            <label class="form-label">Upload File Jawaban</label>
                            <input type="file" class="form-control" name="file_tugas" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required>
                            <small class="text-muted">Format: PDF, DOC, DOCX, JPG, PNG (Max 10MB)</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Catatan (Opsional)</label>
                            <textarea class="form-control" name="catatan" rows="3" placeholder="Tambahkan catatan untuk tutor..."></textarea>
                        </div>

                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i>
                            Pastikan file benar sebelum dikumpulkan. File tidak dapat diubah setelah dikumpulkan.
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-success" id="btnSubmitTugas">
                        <i class="bi bi-upload"></i> Kumpulkan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Detail Tugas -->
    <div class="modal fade" id="detailTugasModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Tugas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detailTugasBody">
                    <!-- Content akan diisi oleh JavaScript -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Filter tugas
        document.getElementById('searchTugas').addEventListener('input', function() {
            filterTugas();
        });

        document.getElementById('filterStatus').addEventListener('change', function() {
            filterTugas();
        });

        document.getElementById('filterMapel').addEventListener('change', function() {
            filterTugas();
        });

        function filterTugas() {
            const searchText = document.getElementById('searchTugas').value.toLowerCase();
            const filterStatus = document.getElementById('filterStatus').value;
            const filterMapel = document.getElementById('filterMapel').value;

            const tugasItems = document.querySelectorAll('.tugas-item');

            tugasItems.forEach(item => {
                const mapel = item.getAttribute('data-mapel').toLowerCase();
                const status = item.getAttribute('data-status');
                const judul = item.querySelector('h5').textContent.toLowerCase();
                const deskripsi = item.querySelector('p.mb-3')?.textContent.toLowerCase() || '';

                let show = true;

                // Filter berdasarkan pencarian
                if (searchText && !judul.includes(searchText) && !deskripsi.includes(searchText)) {
                    show = false;
                }

                // Filter berdasarkan status
                if (filterStatus && status !== filterStatus) {
                    show = false;
                }

                // Filter berdasarkan mata pelajaran
                if (filterMapel && !mapel.includes(filterMapel.toLowerCase())) {
                    show = false;
                }

                item.style.display = show ? 'block' : 'none';
            });
        }

        // Modal upload tugas
        document.querySelectorAll('.btn-kumpulkan').forEach(button => {
            button.addEventListener('click', function() {
                const tugasId = this.getAttribute('data-tugas-id');
                const tugasJudul = this.getAttribute('data-tugas-judul');

                document.getElementById('id_tugas').value = tugasId;
                document.getElementById('modalTugasJudul').textContent = 'Kumpulkan Tugas: ' + tugasJudul;
            });
        });

        // Tombol submit tugas
        document.getElementById('btnSubmitTugas').addEventListener('click', function() {
            const form = document.getElementById('formUploadTugas');
            const formData = new FormData(form);

            // Simulasi upload (ganti dengan AJAX ke server)
            alert('Tugas berhasil dikumpulkan! (Simulasi)');
            document.getElementById('uploadTugasModal').querySelector('.btn-close').click();
            form.reset();
        });

        // Modal detail tugas
        document.querySelectorAll('.btn-lihat-detail').forEach(button => {
            button.addEventListener('click', function() {
                const tugasId = this.getAttribute('data-tugas-id');
                const card = this.closest('.card');

                // Ambil data dari card
                const judul = card.querySelector('h5').textContent;
                const mapel = card.querySelector('.badge.bg-primary, .badge.bg-success, .badge.bg-warning, .badge.bg-danger, .badge.bg-info').textContent;
                const deadline = card.querySelector('.text-muted.small span:nth-child(2)').textContent.replace('Deadline: ', '');
                const deskripsi = card.querySelector('p.mb-3')?.textContent || 'Tidak ada deskripsi';
                const tutor = card.querySelector('p.text-muted.mb-2:nth-child(3)')?.textContent.replace('Tutor: ', '') || 'Tidak diketahui';
                const kelas = card.querySelector('p.text-muted.mb-2:nth-child(2)')?.textContent.replace('Kelas: ', '') || 'Tidak diketahui';

                // Tampilkan di modal
                const modalBody = document.getElementById('detailTugasBody');
                modalBody.innerHTML = `
                    <div class="row">
                        <div class="col-md-8">
                            <div class="d-flex align-items-center mb-3">
                                <span class="badge bg-primary me-2">${mapel}</span>
                                <span class="badge bg-info">ID: ${tugasId}</span>
                            </div>
                            <h4>${judul}</h4>
                            <hr>
                            <h6><i class="bi bi-card-text"></i> Deskripsi Tugas</h6>
                            <p>${deskripsi}</p>
                            <hr>
                            <h6><i class="bi bi-info-circle"></i> Informasi</h6>
                            <table class="table table-sm">
                                <tr>
                                    <td width="30%"><strong>Mata Pelajaran</strong></td>
                                    <td>${mapel}</td>
                                </tr>
                                <tr>
                                    <td><strong>Kelas</strong></td>
                                    <td>${kelas}</td>
                                </tr>
                                <tr>
                                    <td><strong>Tutor</strong></td>
                                    <td>${tutor}</td>
                                </tr>
                                <tr>
                                    <td><strong>Deadline</strong></td>
                                    <td>${deadline}</td>
                                </tr>
                                <tr>
                                    <td><strong>Status</strong></td>
                                    <td><span class="badge bg-warning">Belum Dikumpulkan</span></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6><i class="bi bi-clock"></i> Timeline</h6>
                                    <div class="timeline mt-3">
                                        <div class="d-flex mb-2">
                                            <div class="timeline-badge bg-success"></div>
                                            <div class="ms-3">
                                                <small class="d-block">Tugas Dibuat</small>
                                                <small class="text-muted">${new Date().toLocaleDateString('id-ID')}</small>
                                            </div>
                                        </div>
                                        <div class="d-flex">
                                            <div class="timeline-badge bg-warning"></div>
                                            <div class="ms-3">
                                                <small class="d-block">Deadline</small>
                                                <small class="text-muted">${deadline}</small>
                                            </div>
                                        </div>
                                    </div>
                                    <hr>
                                    <h6><i class="bi bi-file-earmark"></i> File Terkait</h6>
                                    <div class="mt-2">
                                        <button class="btn btn-sm btn-outline-primary w-100 mb-2">
                                            <i class="bi bi-download"></i> Download Soal
                                        </button>
                                        <button class="btn btn-sm btn-outline-success w-100" disabled>
                                            <i class="bi bi-upload"></i> Upload Jawaban
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                // Tampilkan modal
                const modal = new bootstrap.Modal(document.getElementById('detailTugasModal'));
                modal.show();
            });
        });

        // Inisialisasi filter
        filterTugas();
    </script>

    <style>
        .timeline-badge {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-top: 5px;
        }

        .progress {
            border-radius: 10px;
        }

        .progress-bar {
            border-radius: 10px;
        }

        .card {
            transition: transform 0.2s;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
    </style>
</body>

</html>