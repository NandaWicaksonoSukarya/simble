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
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember'
    ];
    return $months[$month] ?? '';
}

/* =====================================================
   VARIABEL FILTER
===================================================== */
$tahun = $_GET['tahun'] ?? date('Y');
$bulan = $_GET['bulan'] ?? date('m');
$id_kelas = $_GET['id_kelas'] ?? '';

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

/* =====================================================
   QUERY DATA KEHADIRAN
===================================================== */
// Cek apakah tabel presensi ada
$tablePresensiExists = mysqli_query($conn, "SHOW TABLES LIKE 'presensi'");
$tableJadwalExists = mysqli_query($conn, "SHOW TABLES LIKE 'jadwal'");
$tablePresensiTutorExists = mysqli_query($conn, "SHOW TABLES LIKE 'presensi_tutor'");

$qKehadiranSiswa = null;
$qKehadiranTutor = null;
$statistikKehadiran = [
    'total_kehadiran' => 0,
    'rata_hadir' => 0,
    'total_alpha' => 0,
    'tutor_hadir' => 0
];

if (mysqli_num_rows($tablePresensiExists) > 0) {
    // Total Kehadiran
    $qTotalKehadiran = mysqli_query($conn, "
        SELECT COUNT(*) as total 
        FROM presensi 
        WHERE MONTH(tanggal) = '$bulan'
        AND YEAR(tanggal) = '$tahun'
    ");
    $statistikKehadiran['total_kehadiran'] = mysqli_fetch_assoc($qTotalKehadiran)['total'] ?? 0;

    // Rata-rata Kehadiran
    $qRataHadir = mysqli_query($conn, "
        SELECT ROUND(AVG(
            CASE WHEN status = 'Hadir' THEN 100
                 WHEN status = 'Izin' THEN 50
                 ELSE 0 END
        ), 2) as rata_hadir
        FROM presensi 
        WHERE MONTH(tanggal) = '$bulan'
        AND YEAR(tanggal) = '$tahun'
    ");
    $statistikKehadiran['rata_hadir'] = mysqli_fetch_assoc($qRataHadir)['rata_hadir'] ?? 0;

    // Total Alpha
    $qTotalAlpha = mysqli_query($conn, "
        SELECT COUNT(*) as total 
        FROM presensi 
        WHERE status = 'Alpha'
        AND MONTH(tanggal) = '$bulan'
        AND YEAR(tanggal) = '$tahun'
    ");
    $statistikKehadiran['total_alpha'] = mysqli_fetch_assoc($qTotalAlpha)['total'] ?? 0;

    // Kehadiran Siswa dengan filter
    $whereSiswa = "MONTH(pr.tanggal) = '$bulan' AND YEAR(pr.tanggal) = '$tahun'";
    if ($id_kelas) {
        $whereSiswa .= " AND k.id_kelas = '$id_kelas'";
    }

    $qKehadiranSiswa = mysqli_query($conn, "
        SELECT 
            s.nama,
            k.nama_kelas,
            COUNT(DISTINCT DATE(pr.tanggal)) as total_pertemuan,
            SUM(CASE WHEN pr.status = 'Hadir' THEN 1 ELSE 0 END) as jumlah_hadir,
            SUM(CASE WHEN pr.status = 'Izin' THEN 1 ELSE 0 END) as jumlah_izin,
            SUM(CASE WHEN pr.status = 'Alpha' THEN 1 ELSE 0 END) as jumlah_alpha,
            ROUND((SUM(CASE WHEN pr.status = 'Hadir' THEN 1 ELSE 0 END) * 100.0 / COUNT(DISTINCT DATE(pr.tanggal))), 2) as persentase_hadir
        FROM presensi pr
        JOIN siswa s ON pr.id_siswa = s.id_siswa
        JOIN kelas k ON pr.id_kelas = k.id_kelas
        WHERE $whereSiswa
        GROUP BY s.id_siswa, k.id_kelas
        HAVING total_pertemuan > 0
        ORDER BY persentase_hadir DESC
        LIMIT 30
    ");
}

if (mysqli_num_rows($tableJadwalExists) > 0 && mysqli_num_rows($tablePresensiTutorExists) > 0) {
    // Tutor Hadir
    $qTutorHadir = mysqli_query($conn, "
        SELECT COUNT(DISTINCT id_tutor) as total 
        FROM jadwal j
        JOIN presensi_tutor pt ON j.id_jadwal = pt.id_jadwal
        WHERE MONTH(j.tanggal) = '$bulan'
        AND YEAR(j.tanggal) = '$tahun'
        AND pt.status = 'Hadir'
    ");
    $statistikKehadiran['tutor_hadir'] = mysqli_fetch_assoc($qTutorHadir)['total'] ?? 0;

    // Kehadiran Tutor
    $whereTutor = "MONTH(j.tanggal) = '$bulan' AND YEAR(j.tanggal) = '$tahun'";
    if ($id_kelas) {
        $whereTutor .= " AND k.id_kelas = '$id_kelas'";
    }

    $qKehadiranTutor = mysqli_query($conn, "
        SELECT 
            t.id_tutor,
            t.nama_tutor,
            k.id_kelas,
            k.nama_kelas,
            m.id_mapel,
            m.nama_mapel,
            COUNT(DISTINCT j.id_jadwal) AS total_jadwal,
            COUNT(DISTINCT pr.id_jadwal) AS total_hadir,
            ROUND(
                (COUNT(DISTINCT pr.id_jadwal) * 100.0 / COUNT(DISTINCT j.id_jadwal)),
                2
            ) AS persentase_hadir
        FROM jadwal j
        JOIN tutor t ON j.id_tutor = t.id_tutor
        JOIN kelas k ON j.id_kelas = k.id_kelas
        JOIN mapel m ON j.id_mapel = m.id_mapel
        LEFT JOIN presensi_tutor pr 
            ON pr.id_jadwal = j.id_jadwal
            AND pr.id_tutor = j.id_tutor
            AND pr.status = 'Hadir'
        WHERE $whereTutor
        GROUP BY
            t.id_tutor,
            t.nama_tutor,
            k.id_kelas,
            k.nama_kelas,
            m.id_mapel,
            m.nama_mapel
        HAVING total_jadwal > 0
        ORDER BY persentase_hadir DESC
    ");
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Kehadiran - Sistem Informasi Bimbel</title>
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
                        <h2><i class="bi bi-calendar-check"></i> Laporan Kehadiran</h2>
                        <p class="text-muted">Monitoring dan analisis data kehadiran siswa & tutor</p>
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
                    <h5><i class="bi bi-funnel"></i> Filter Laporan Kehadiran</h5>
                    <form method="GET" class="row g-3 mt-2">
                        <div class="col-md-4">
                            <label class="form-label">Tahun</label>
                            <select name="tahun" class="form-select">
                                <?php foreach ($tahunList as $thn): ?>
                                    <option value="<?= $thn ?>" <?= $tahun == $thn ? 'selected' : '' ?>>
                                        <?= $thn ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Bulan</label>
                            <select name="bulan" class="form-select">
                                <?php foreach ($bulanList as $key => $nama): ?>
                                    <option value="<?= sprintf('%02d', $key) ?>" <?= $bulan == sprintf('%02d', $key) ? 'selected' : '' ?>>
                                        <?= $nama ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
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
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-filter"></i> Terapkan Filter
                            </button>
                            <a href="laporan_kehadiran.php" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-clockwise"></i> Reset
                            </a>
                        </div>
                    </form>
                </div>

                <?php if (mysqli_num_rows($tablePresensiExists) > 0): ?>
                    <!-- Statistik Kehadiran -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card report-card border-primary">
                                <div class="card-body text-center">
                                    <div class="stat-number text-primary"><?= $statistikKehadiran['total_kehadiran'] ?></div>
                                    <p class="text-muted mb-0">Presensi <?= getMonthName((int)$bulan) ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card report-card border-success">
                                <div class="card-body text-center">
                                    <div class="stat-number text-success"><?= number_format($statistikKehadiran['rata_hadir'], 1) ?>%</div>
                                    <p class="text-muted mb-0">Rata-rata Kehadiran</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card report-card border-warning">
                                <div class="card-body text-center">
                                    <div class="stat-number text-warning"><?= $statistikKehadiran['total_alpha'] ?></div>
                                    <p class="text-muted mb-0">Total Alpha</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card report-card border-info">
                                <div class="card-body text-center">
                                    <div class="stat-number text-info"><?= $statistikKehadiran['tutor_hadir'] ?></div>
                                    <p class="text-muted mb-0">Tutor Hadir</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Kehadiran Siswa -->
                    <div class="card report-card mb-4">
                        <div class="card-header border-primary">
                            <h5 class="mb-0"><i class="bi bi-person-check"></i> Kehadiran Siswa (<?= getMonthName((int)$bulan) ?> <?= $tahun ?>)</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($qKehadiranSiswa && mysqli_num_rows($qKehadiranSiswa) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Nama Siswa</th>
                                                <th>Kelas</th>
                                                <th>Total Pertemuan</th>
                                                <th>Hadir</th>
                                                <th>Izin</th>
                                                <th>Alpha</th>
                                                <th>Persentase Hadir</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($row = mysqli_fetch_assoc($qKehadiranSiswa)): ?>
                                                <tr>
                                                    <td><?= h($row['nama']) ?></td>
                                                    <td><?= h($row['nama_kelas']) ?></td>
                                                    <td><?= $row['total_pertemuan'] ?></td>
                                                    <td class="text-success"><?= $row['jumlah_hadir'] ?></td>
                                                    <td class="text-warning"><?= $row['jumlah_izin'] ?></td>
                                                    <td class="text-danger"><?= $row['jumlah_alpha'] ?></td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="progress progress-thin w-100">
                                                                <div class="progress-bar <?= $row['persentase_hadir'] >= 80 ? 'bg-success' : ($row['persentase_hadir'] >= 60 ? 'bg-warning' : 'bg-danger') ?>"
                                                                    role="progressbar"
                                                                    style="width: <?= $row['persentase_hadir'] ?>%">
                                                                </div>
                                                            </div>
                                                            <span class="ms-2"><?= number_format($row['persentase_hadir'], 1) ?>%</span>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="no-data">
                                    <i class="bi bi-person-x display-4"></i>
                                    <p class="mt-3">Belum ada data kehadiran siswa untuk periode ini</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Kehadiran Tutor -->
                    <?php if (mysqli_num_rows($tableJadwalExists) > 0 && mysqli_num_rows($tablePresensiTutorExists) > 0): ?>
                        <div class="card report-card">
                            <div class="card-header border-success">
                                <h5 class="mb-0"><i class="bi bi-person-badge"></i> Kehadiran Tutor (<?= getMonthName((int)$bulan) ?> <?= $tahun ?>)</h5>
                            </div>
                            <div class="card-body">
                                <?php if ($qKehadiranTutor && mysqli_num_rows($qKehadiranTutor) > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Nama Tutor</th>
                                                    <th>Kelas</th>
                                                    <th>Mapel</th>
                                                    <th>Total Jadwal</th>
                                                    <th>Total Hadir</th>
                                                    <th>Persentase Hadir</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while ($row = mysqli_fetch_assoc($qKehadiranTutor)): ?>
                                                    <tr>
                                                        <td><?= h($row['nama_tutor']) ?></td>
                                                        <td><?= h($row['nama_kelas']) ?></td>
                                                        <td><?= h($row['nama_mapel']) ?></td>
                                                        <td><?= $row['total_jadwal'] ?></td>
                                                        <td class="text-success"><?= $row['total_hadir'] ?></td>
                                                        <td>
                                                            <span class="badge <?= $row['persentase_hadir'] >= 90 ? 'bg-success' : ($row['persentase_hadir'] >= 70 ? 'bg-warning' : 'bg-danger') ?>">
                                                                <?= number_format($row['persentase_hadir'], 1) ?>%
                                                            </span>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="no-data">
                                        <i class="bi bi-person-badge-x display-4"></i>
                                        <p class="mt-3">Belum ada data kehadiran tutor untuk periode ini</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="alert alert-info">
                        <h5><i class="bi bi-info-circle"></i> Informasi</h5>
                        <p>Tabel presensi belum tersedia. Fitur laporan kehadiran akan tersedia setelah tabel dibuat.</p>
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
                            Laporan Kehadiran dihasilkan pada <?= date('d F Y H:i:s') ?> |
                            Periode: <?= getMonthName((int)$bulan) ?> <?= $tahun ?>
                            <?php if ($id_kelas): ?> | Kelas: <?= h($kelasList[$id_kelas] ?? '') ?><?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>