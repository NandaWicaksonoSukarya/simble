<?php
session_start();
require "../config.php";

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

function h($str)
{
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

function getMonthName($month)
{
    $months = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
    return $months[$month] ?? '';
}

/* =====================================================
   VARIABEL FILTER
===================================================== */
$tahun = $_GET['tahun'] ?? date('Y');
$bulan = $_GET['bulan'] ?? date('m');
$id_kelas = $_GET['id_kelas'] ?? '';
$id_mapel = $_GET['id_mapel'] ?? '';

/* =====================================================
   DATA DROPDOWN
===================================================== */
$tahunList = [];
for ($i = 2020; $i <= date('Y') + 1; $i++) {
    $tahunList[] = $i;
}

$bulanList = [];
for ($i = 1; $i <= 12; $i++) {
    $bulanList[$i] = getMonthName($i);
}

// Data kelas untuk filter
$qKelas = mysqli_query($conn, "
    SELECT k.id_kelas, k.nama_kelas, m.nama_mapel
    FROM kelas k
    JOIN mapel m ON k.id_mapel = m.id_mapel
    WHERE k.status = 'Aktif'
    ORDER BY k.nama_kelas
");

$kelasList = [];
while ($row = mysqli_fetch_assoc($qKelas)) {
    $kelasList[$row['id_kelas']] = $row['nama_kelas'] . ' - ' . $row['nama_mapel'];
}

// Data mapel untuk filter
$qMapel = mysqli_query($conn, "
    SELECT id_mapel, nama_mapel 
    FROM mapel 
    WHERE status = 'Aktif'
    ORDER BY nama_mapel
");

$mapelList = [];
while ($row = mysqli_fetch_assoc($qMapel)) {
    $mapelList[$row['id_mapel']] = $row['nama_mapel'];
}

/* =====================================================
   QUERY DATA AKADEMIK
===================================================== */
// Laporan Nilai
$whereNilai = "1=1";
if ($id_kelas) {
    $whereNilai .= " AND t.id_kelas = '$id_kelas'";
}
if ($id_mapel) {
    $whereNilai .= " AND t.id_mapel = '$id_mapel'";
}

// Cek apakah tabel penilaian_tugas dan tugas ada
$tableTugasExists = mysqli_query($conn, "SHOW TABLES LIKE 'tugas'");
$tablePenilaianExists = mysqli_query($conn, "SHOW TABLES LIKE 'penilaian_tugas'");

$qLaporanNilai = null;
$qRataNilaiSiswa = null;
$qLaporanTugas = null;
$statistikAkademik = [
    'total_tugas' => 0,
    'rata_nilai' => 0,
    'siswa_tuntas' => 0,
    'persentase_kumpul' => 0
];

if (mysqli_num_rows($tableTugasExists) > 0 && mysqli_num_rows($tablePenilaianExists) > 0) {
    // Total Tugas
    $qTotalTugas = mysqli_query($conn, "
        SELECT COUNT(*) as total 
        FROM tugas 
        WHERE YEAR(created_at) = '$tahun'
    ");
    $statistikAkademik['total_tugas'] = mysqli_fetch_assoc($qTotalTugas)['total'] ?? 0;

    // Rata-rata Nilai
    $qRataNilai = mysqli_query($conn, "
        SELECT AVG(nilai) as rata_rata 
        FROM penilaian_tugas 
        WHERE nilai IS NOT NULL
    ");
    $statistikAkademik['rata_nilai'] = mysqli_fetch_assoc($qRataNilai)['rata_rata'] ?? 0;

    // Siswa Tuntas
    $qSiswaTuntas = mysqli_query($conn, "
        SELECT COUNT(DISTINCT id_siswa) as total 
        FROM penilaian_tugas 
        WHERE nilai >= 75
    ");
    $statistikAkademik['siswa_tuntas'] = mysqli_fetch_assoc($qSiswaTuntas)['total'] ?? 0;

    // Persentase Kumpul
    $qPersentaseKumpul = mysqli_query($conn, "
        SELECT 
            ROUND((COUNT(*) * 100.0 / 
            (SELECT COUNT(*) FROM penilaian_tugas)), 2) as persentase
        FROM penilaian_tugas
        WHERE nilai IS NOT NULL
    ");
    $statistikAkademik['persentase_kumpul'] = mysqli_fetch_assoc($qPersentaseKumpul)['persentase'] ?? 0;

    // Laporan Nilai
    $qLaporanNilai = mysqli_query($conn, "
        SELECT 
            s.nama,
            k.nama_kelas,
            m.nama_mapel,
            t.judul as judul_tugas,
            p.nilai,
            p.uploaded_at,
            DATE_FORMAT(t.deadline, '%d %b %Y') as deadline_format
        FROM penilaian_tugas p
        JOIN siswa s ON p.id_siswa = s.id_siswa
        JOIN tugas t ON p.id_tugas = t.id_tugas
        JOIN kelas k ON t.id_kelas = k.id_kelas
        JOIN mapel m ON t.id_mapel = m.id_mapel
        WHERE $whereNilai
        AND p.nilai IS NOT NULL
        ORDER BY p.uploaded_at DESC
        LIMIT 50
    ");

    // Rata-rata Nilai per Siswa
    $qRataNilaiSiswa = mysqli_query($conn, "
        SELECT 
            s.id_siswa,
            s.nama,
            k.id_kelas,
            k.nama_kelas,
            m.id_mapel,
            m.nama_mapel,
            COUNT(p.id) as jumlah_tugas,
            AVG(p.nilai) as rata_nilai,
            MIN(p.nilai) as nilai_terendah,
            MAX(p.nilai) as nilai_tertinggi
        FROM penilaian_tugas p
        JOIN siswa s ON p.id_siswa = s.id_siswa
        JOIN tugas t ON p.id_tugas = t.id_tugas
        JOIN kelas k ON t.id_kelas = k.id_kelas
        JOIN mapel m ON t.id_mapel = m.id_mapel
        WHERE p.nilai IS NOT NULL
        GROUP BY 
            s.id_siswa,
            s.nama,
            k.id_kelas,
            k.nama_kelas,
            m.id_mapel,
            m.nama_mapel
        HAVING COUNT(p.id) > 0
        ORDER BY rata_nilai DESC
        LIMIT 20
    ");

    // Cek apakah tabel kelas_siswa ada
    $tableKelasSiswaExists = mysqli_query($conn, "SHOW TABLES LIKE 'kelas_siswa'");

    if (mysqli_num_rows($tableKelasSiswaExists) > 0) {
        $qLaporanTugas = mysqli_query($conn, "
            SELECT 
                t.judul,
                m.nama_mapel,
                k.nama_kelas,
                tu.nama_tutor,
                DATE_FORMAT(t.deadline, '%d %b %Y %H:%i') as deadline,
                t.status,
                COUNT(DISTINCT p.id_siswa) as jumlah_pengumpulan,
                COUNT(DISTINCT ks.id_siswa) as total_siswa,
                ROUND((COUNT(DISTINCT p.id_siswa) * 100.0 / COUNT(DISTINCT ks.id_siswa)), 2) as persentase_pengumpulan
            FROM tugas t
            JOIN mapel m ON t.id_mapel = m.id_mapel
            JOIN kelas k ON t.id_kelas = k.id_kelas
            JOIN tutor tu ON t.id_tutor = tu.id_tutor
            LEFT JOIN kelas_siswa ks ON k.id_kelas = ks.id_kelas
            LEFT JOIN penilaian_tugas p ON t.id_tugas = p.id_tugas
            WHERE $whereNilai
            GROUP BY t.id_tugas
            ORDER BY t.created_at DESC
            LIMIT 30
        ");
    } else {
        $qLaporanTugas = mysqli_query($conn, "
            SELECT 
                t.judul,
                m.nama_mapel,
                k.nama_kelas,
                tu.nama_tutor,
                DATE_FORMAT(t.deadline, '%d %b %Y %H:%i') as deadline,
                t.status,
                COUNT(DISTINCT p.id_siswa) as jumlah_pengumpulan,
                0 as total_siswa,
                0 as persentase_pengumpulan
            FROM tugas t
            JOIN mapel m ON t.id_mapel = m.id_mapel
            JOIN kelas k ON t.id_kelas = k.id_kelas
            JOIN tutor tu ON t.id_tutor = tu.id_tutor
            LEFT JOIN penilaian_tugas p ON t.id_tugas = p.id_tugas
            WHERE $whereNilai
            GROUP BY t.id_tugas
            ORDER BY t.created_at DESC
            LIMIT 30
        ");
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Akademik - Sistem Informasi Bimbel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .report-card {
            border-radius: 10px;
            transition: transform 0.2s;
            margin-bottom: 20px;
            border: 1px solid #dee2e6;
        }

        .report-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .report-card .card-header {
            border-bottom: 2px solid;
            font-weight: 600;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
        }

        .filter-section {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .table-sm th {
            font-weight: 600;
            background-color: #f8f9fa;
        }

        .badge-status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
        }

        .progress-thin {
            height: 8px;
            margin-top: 5px;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            .report-card {
                break-inside: avoid;
            }

            .table {
                font-size: 12px;
            }
        }
        
        .back-btn {
            margin-right: 10px;
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar p-0 no-print">
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
                    <a class="nav-link" href="mapel.php"><i class="bi bi-book"></i> Data Mapel</a>
                    <a class="nav-link active" href="laporan.php"><i class="bi bi-file-earmark-text"></i> Laporan</a>
                    <a class="nav-link" href="../logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 content-wrapper p-4">
                <!-- Header -->
                <div class="page-header d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2><i class="bi bi-journal-check"></i> Laporan Akademik</h2>
                        <p class="text-muted">Monitoring dan analisis data akademik bimbel</p>
                    </div>
                    <div class="no-print">
                        <a href="laporan.php" class="btn btn-outline-secondary back-btn">
                            <i class="bi bi-arrow-left"></i> Kembali
                        </a>
                        <button onclick="window.print()" class="btn btn-outline-primary">
                            <i class="bi bi-printer"></i> Cetak Laporan
                        </button>
                    </div>
                </div>

                <!-- Filter Section -->
                <div class="filter-section no-print">
                    <h5><i class="bi bi-funnel"></i> Filter Laporan Akademik</h5>
                    <form method="GET" class="row g-3 mt-2">
                        <div class="col-md-3">
                            <label class="form-label">Tahun</label>
                            <select name="tahun" class="form-select">
                                <?php foreach ($tahunList as $thn): ?>
                                    <option value="<?= $thn ?>" <?= $tahun == $thn ? 'selected' : '' ?>>
                                        <?= $thn ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Bulan</label>
                            <select name="bulan" class="form-select">
                                <?php foreach ($bulanList as $key => $nama): ?>
                                    <option value="<?= sprintf('%02d', $key) ?>" <?= $bulan == sprintf('%02d', $key) ? 'selected' : '' ?>>
                                        <?= $nama ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Kelas</label>
                            <select name="id_kelas" class="form-select">
                                <option value="">Semua Kelas</option>
                                <?php foreach ($kelasList as $id => $nama): ?>
                                    <option value="<?= $id ?>" <?= $id_kelas == $id ? 'selected' : '' ?>>
                                        <?= h($nama) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Mata Pelajaran</label>
                            <select name="id_mapel" class="form-select">
                                <option value="">Semua Mapel</option>
                                <?php foreach ($mapelList as $id => $nama): ?>
                                    <option value="<?= $id ?>" <?= $id_mapel == $id ? 'selected' : '' ?>>
                                        <?= h($nama) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-filter"></i> Terapkan Filter
                            </button>
                            <a href="laporan_akademik.php" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-clockwise"></i> Reset
                            </a>
                        </div>
                    </form>
                </div>

                <?php if (mysqli_num_rows($tableTugasExists) > 0 && mysqli_num_rows($tablePenilaianExists) > 0): ?>
                    <!-- Statistik Akademik -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card report-card border-primary">
                                <div class="card-body text-center">
                                    <div class="stat-number text-primary"><?= $statistikAkademik['total_tugas'] ?></div>
                                    <p class="text-muted mb-0">Total Tugas <?= $tahun ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card report-card border-success">
                                <div class="card-body text-center">
                                    <div class="stat-number text-success"><?= number_format($statistikAkademik['rata_nilai'], 2) ?></div>
                                    <p class="text-muted mb-0">Rata-rata Nilai</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card report-card border-warning">
                                <div class="card-body text-center">
                                    <div class="stat-number text-warning"><?= $statistikAkademik['siswa_tuntas'] ?></div>
                                    <p class="text-muted mb-0">Siswa Tuntas</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card report-card border-info">
                                <div class="card-body text-center">
                                    <div class="stat-number text-info"><?= number_format($statistikAkademik['persentase_kumpul'], 1) ?>%</div>
                                    <p class="text-muted mb-0">Nilai Tersubmit</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Laporan Nilai -->
                    <div class="card report-card mb-4">
                        <div class="card-header border-primary">
                            <h5 class="mb-0"><i class="bi bi-journal-check"></i> Laporan Nilai Tugas</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($qLaporanNilai && mysqli_num_rows($qLaporanNilai) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Nama Siswa</th>
                                                <th>Kelas</th>
                                                <th>Mapel</th>
                                                <th>Tugas</th>
                                                <th>Nilai</th>
                                                <th>Deadline</th>
                                                <th>Dikumpulkan</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($row = mysqli_fetch_assoc($qLaporanNilai)): ?>
                                                <tr>
                                                    <td><?= h($row['nama']) ?></td>
                                                    <td><?= h($row['nama_kelas']) ?></td>
                                                    <td><?= h($row['nama_mapel']) ?></td>
                                                    <td><?= h($row['judul_tugas']) ?></td>
                                                    <td>
                                                        <span class="badge <?= $row['nilai'] >= 75 ? 'bg-success' : 'bg-warning' ?>">
                                                            <?= $row['nilai'] ?>
                                                        </span>
                                                    </td>
                                                    <td><?= h($row['deadline_format']) ?></td>
                                                    <td><?= date('d M Y', strtotime($row['uploaded_at'])) ?></td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="no-data">
                                    <i class="bi bi-journal-x display-4"></i>
                                    <p class="mt-3">Belum ada data nilai tugas</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Rata-rata Nilai per Siswa -->
                    <div class="card report-card mb-4">
                        <div class="card-header border-success">
                            <h5 class="mb-0"><i class="bi bi-bar-chart"></i> Rata-rata Nilai per Siswa</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($qRataNilaiSiswa && mysqli_num_rows($qRataNilaiSiswa) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Nama Siswa</th>
                                                <th>Kelas</th>
                                                <th>Mapel</th>
                                                <th>Jumlah Tugas</th>
                                                <th>Rata-rata</th>
                                                <th>Terendah</th>
                                                <th>Tertinggi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($row = mysqli_fetch_assoc($qRataNilaiSiswa)): ?>
                                                <tr>
                                                    <td><?= h($row['nama']) ?></td>
                                                    <td><?= h($row['nama_kelas']) ?></td>
                                                    <td><?= h($row['nama_mapel']) ?></td>
                                                    <td><?= $row['jumlah_tugas'] ?></td>
                                                    <td>
                                                        <span class="badge <?= $row['rata_nilai'] >= 75 ? 'bg-success' : ($row['rata_nilai'] >= 60 ? 'bg-warning' : 'bg-danger') ?>">
                                                            <?= number_format($row['rata_nilai'], 2) ?>
                                                        </span>
                                                    </td>
                                                    <td><?= $row['nilai_terendah'] ?></td>
                                                    <td><?= $row['nilai_tertinggi'] ?></td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="no-data">
                                    <i class="bi bi-graph-up display-4"></i>
                                    <p class="mt-3">Belum ada data rata-rata nilai</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Laporan Tugas -->
                    <div class="card report-card">
                        <div class="card-header border-warning">
                            <h5 class="mb-0"><i class="bi bi-list-task"></i> Laporan Pengumpulan Tugas</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($qLaporanTugas && mysqli_num_rows($qLaporanTugas) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Judul Tugas</th>
                                                <th>Mapel</th>
                                                <th>Kelas</th>
                                                <th>Tutor</th>
                                                <th>Deadline</th>
                                                <th>Status</th>
                                                <th>Pengumpulan</th>
                                                <th>Persentase</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($row = mysqli_fetch_assoc($qLaporanTugas)): ?>
                                                <tr>
                                                    <td><?= h($row['judul']) ?></td>
                                                    <td><?= h($row['nama_mapel']) ?></td>
                                                    <td><?= h($row['nama_kelas']) ?></td>
                                                    <td><?= h($row['nama_tutor']) ?></td>
                                                    <td><?= h($row['deadline']) ?></td>
                                                    <td>
                                                        <span class="badge-status <?= $row['status'] == 'Selesai' ? 'bg-success' : 'bg-warning' ?>">
                                                            <?= h($row['status']) ?>
                                                        </span>
                                                    </td>
                                                    <td><?= $row['jumlah_pengumpulan'] ?> / <?= $row['total_siswa'] ?></td>
                                                    <td>
                                                        <?php if ($row['total_siswa'] > 0): ?>
                                                            <div class="d-flex align-items-center">
                                                                <div class="progress progress-thin w-100">
                                                                    <div class="progress-bar <?= $row['persentase_pengumpulan'] >= 80 ? 'bg-success' : ($row['persentase_pengumpulan'] >= 60 ? 'bg-warning' : 'bg-danger') ?>"
                                                                        role="progressbar"
                                                                        style="width: <?= $row['persentase_pengumpulan'] ?>%">
                                                                    </div>
                                                                </div>
                                                                <span class="ms-2"><?= number_format($row['persentase_pengumpulan'], 1) ?>%</span>
                                                            </div>
                                                        <?php else: ?>
                                                            <span class="text-muted">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                            </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="no-data">
                                    <i class="bi bi-list-task display-4"></i>
                                    <p class="mt-3">Belum ada data tugas</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <h5><i class="bi bi-info-circle"></i> Informasi</h5>
                        <p>Tabel tugas atau penilaian_tugas belum tersedia. Fitur laporan akademik akan tersedia setelah tabel dibuat.</p>
                        <p class="mb-0">
                            <a href="laporan.php" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-arrow-left"></i> Kembali ke Dashboard Laporan
                            </a>
                        </p>
                    </div>
                <?php endif; ?>

                <!-- Footer Informasi -->
                <div class="card mt-4 no-print">
                    <div class="card-body text-center">
                        <p class="text-muted mb-0">
                            <i class="bi bi-info-circle"></i>
                            Laporan Akademik dihasilkan pada <?= date('d F Y H:i:s') ?> |
                            Periode: <?= getMonthName((int)$bulan) ?> <?= $tahun ?>
                            <?php if ($id_kelas): ?> | Kelas: <?= h($kelasList[$id_kelas] ?? '') ?><?php endif; ?>
                            <?php if ($id_mapel): ?> | Mapel: <?= h($mapelList[$id_mapel] ?? '') ?><?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>