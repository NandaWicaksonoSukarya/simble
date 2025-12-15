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

function formatRupiah($angka)
{
    return 'Rp ' . number_format($angka, 0, ',', '.');
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
   VARIABEL FILTER & TAB
===================================================== */
$tahun = $_GET['tahun'] ?? date('Y');
$bulan = $_GET['bulan'] ?? date('m');
$id_kelas = $_GET['id_kelas'] ?? '';
$tab = $_GET['tab'] ?? 'class'; // class, student, tutor

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
   TAB 1: LAPORAN KELAS & JADWAL
===================================================== */
if ($tab === 'class') {
    // Statistik Kelas
    $qStatistikKelas = mysqli_query($conn, "
        SELECT 
            COUNT(*) as total_kelas,
            COUNT(CASE WHEN status = 'Aktif' THEN 1 END) as kelas_aktif,
            COUNT(CASE WHEN status = 'Nonaktif' THEN 1 END) as kelas_nonaktif,
            AVG(jumlah_siswa) as rata_siswa_per_kelas
        FROM (
            SELECT 
                k.*,
                COUNT(ks.id_siswa) as jumlah_siswa
            FROM kelas k
            LEFT JOIN kelas_siswa ks ON k.id_kelas = ks.id_kelas
            GROUP BY k.id_kelas
        ) as kelas_detail
    ");

    $statistikKelas = mysqli_fetch_assoc($qStatistikKelas) ?? ['total_kelas' => 0, 'kelas_aktif' => 0, 'kelas_nonaktif' => 0, 'rata_siswa_per_kelas' => 0];

    // Jadwal minggu ini
    $startWeek = date('Y-m-d', strtotime('monday this week'));
    $endWeek = date('Y-m-d', strtotime('sunday this week'));

    $qJadwalMinggu = mysqli_query($conn, "
        SELECT 
            j.tanggal,
            DATE_FORMAT(j.tanggal, '%W') AS hari,
            k.nama_kelas,
            m.nama_mapel,
            t.nama_tutor,
            j.jam_mulai,
            j.jam_selesai,
            r.nama_ruangan
        FROM jadwal j
        JOIN kelas k ON j.id_kelas = k.id_kelas
        JOIN mapel m ON j.id_mapel = m.id_mapel
        JOIN tutor t ON j.id_tutor = t.id_tutor
        LEFT JOIN ruangan r ON j.id_ruangan = r.id_ruangan
        WHERE j.tanggal BETWEEN '$startWeek' AND '$endWeek'
          AND k.status = 'Aktif'
        ORDER BY j.tanggal, j.jam_mulai
    ");

    // Kelas dengan siswa terbanyak
    $qKelasTerbanyak = mysqli_query($conn, "
        SELECT 
            k.id_kelas,
            k.nama_kelas,
            m.nama_mapel,
            t.nama_tutor,
            COUNT(ks.id_siswa) AS jumlah_siswa
        FROM kelas k
        JOIN mapel m ON k.id_mapel = m.id_mapel
        JOIN tutor t ON k.id_tutor = t.id_tutor
        LEFT JOIN kelas_siswa ks ON k.id_kelas = ks.id_kelas
        WHERE k.status = 'Aktif'
        GROUP BY 
            k.id_kelas,
            k.nama_kelas,
            m.nama_mapel,
            t.nama_tutor
        ORDER BY jumlah_siswa DESC
        LIMIT 10
    ");
}

/* =====================================================
   TAB 2: LAPORAN SISWA
===================================================== */
if ($tab === 'student') {
    // Statistik Siswa
    $qStatistikSiswa = mysqli_query($conn, "
        SELECT 
            COUNT(*) AS total_siswa,
            COUNT(CASE WHEN status_aktif = 'Aktif' THEN 1 END) AS siswa_aktif,
            COUNT(CASE WHEN status_aktif = 'Nonaktif' THEN 1 END) AS siswa_nonaktif,
            COUNT(CASE WHEN status_aktif = 'Alumni' THEN 1 END) AS siswa_alumni,
            COUNT(
                CASE 
                    WHEN MONTH(tgl_daftar) = MONTH(CURDATE())
                     AND YEAR(tgl_daftar) = YEAR(CURDATE())
                    THEN id_siswa
                END
            ) AS siswa_baru_bulan_ini
        FROM siswa
    ");

    $statistikSiswa = mysqli_fetch_assoc($qStatistikSiswa) ?? ['total_siswa' => 0, 'siswa_aktif' => 0, 'siswa_nonaktif' => 0, 'siswa_alumni' => 0, 'siswa_baru_bulan_ini' => 0];

    // Siswa per kelas
    $qSiswaPerKelas = mysqli_query($conn, "
        SELECT 
            k.nama_kelas,
            m.nama_mapel,
            COUNT(ks.id_siswa) as jumlah_siswa,
            GROUP_CONCAT(s.nama ORDER BY s.nama SEPARATOR ', ') as daftar_siswa
        FROM kelas k
        JOIN mapel m ON k.id_mapel = m.id_mapel
        LEFT JOIN kelas_siswa ks ON k.id_kelas = ks.id_kelas
        LEFT JOIN siswa s ON ks.id_siswa = s.id_siswa
        WHERE k.status = 'Aktif'
        GROUP BY k.id_kelas
        ORDER BY jumlah_siswa DESC
    ");

    // Siswa dengan tunggakan
    $qSiswaTunggakan = mysqli_query($conn, "
        SELECT 
            s.nama,
            s.email,
            s.telepon,
            COUNT(p.id_pembayaran) as jumlah_tunggakan,
            SUM(p.nominal) as total_tunggakan,
            GROUP_CONCAT(DISTINCT CONCAT(k.nama_kelas, ' (', m.nama_mapel, ')') SEPARATOR '; ') as kelas_tunggakan
        FROM siswa s
        JOIN pembayaran p ON s.id_siswa = p.id_siswa
        LEFT JOIN kelas k ON p.id_kelas = k.id_kelas
        LEFT JOIN mapel m ON k.id_mapel = m.id_mapel
        WHERE p.status = 'Belum Lunas'
        AND p.tgl_bayar <= CURDATE()
        GROUP BY s.id_siswa
        HAVING total_tunggakan > 0
        ORDER BY total_tunggakan DESC
        LIMIT 15
    ");
}

/* =====================================================
   TAB 3: LAPORAN TUTOR
===================================================== */
if ($tab === 'tutor') {
    // Statistik Tutor
    $qStatistikTutor = mysqli_query($conn, "
        SELECT 
            COUNT(*) as total_tutor,
            COUNT(CASE WHEN status = 'Aktif' THEN 1 END) as tutor_aktif,
            COUNT(CASE WHEN status = 'Nonaktif' THEN 1 END) as tutor_nonaktif,
            AVG(pengalaman) as rata_pengalaman
        FROM tutor
    ");

    $statistikTutor = mysqli_fetch_assoc($qStatistikTutor) ?? ['total_tutor' => 0, 'tutor_aktif' => 0, 'tutor_nonaktif' => 0, 'rata_pengalaman' => 0];

    // Tutor aktif dengan detail
    $qTutorAktif = mysqli_query($conn, "
        SELECT 
            t.id_tutor,
            t.nama_tutor,
            t.email,
            t.telepon,
            t.pendidikan,
            t.pengalaman,
            m.nama_mapel AS spesialisasi,
            COUNT(DISTINCT k.id_kelas) AS jumlah_kelas,
            COUNT(DISTINCT j.id_jadwal) AS jumlah_jadwal
        FROM tutor t
        LEFT JOIN mapel m ON t.id_mapel = m.id_mapel
        LEFT JOIN kelas k ON t.id_tutor = k.id_tutor
        LEFT JOIN jadwal j ON t.id_tutor = j.id_tutor
        WHERE t.status = 'Aktif'
        GROUP BY
            t.id_tutor,
            t.nama_tutor,
            t.email,
            t.telepon,
            t.pendidikan,
            t.pengalaman,
            m.nama_mapel
        ORDER BY jumlah_kelas DESC
    ");

    // Jam mengajar per tutor
    $qJamMengajar = mysqli_query($conn, "
        SELECT 
            t.nama_tutor,
            COUNT(DISTINCT j.id_jadwal) as jumlah_sesi,
            SUM(TIME_TO_SEC(TIMEDIFF(j.jam_selesai, j.jam_mulai))) / 3600 as total_jam_mengajar,
            AVG(TIME_TO_SEC(TIMEDIFF(j.jam_selesai, j.jam_mulai))) / 3600 as rata_jam_per_sesi
        FROM tutor t
        JOIN jadwal j ON t.id_tutor = j.id_tutor
        WHERE MONTH(j.tanggal) = '$bulan' AND YEAR(j.tanggal) = '$tahun'
        GROUP BY t.id_tutor
        HAVING jumlah_sesi > 0
        ORDER BY total_jam_mengajar DESC
    ");

    // Total jam mengajar
    $qTotalJam = mysqli_query($conn, "
        SELECT SUM(TIME_TO_SEC(TIMEDIFF(jam_selesai, jam_mulai))) / 3600 as total_jam
        FROM jadwal
        WHERE MONTH(tanggal) = '$bulan'
        AND YEAR(tanggal) = '$tahun'
    ");
    $totalJam = mysqli_fetch_assoc($qTotalJam)['total_jam'] ?? 0;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Kelas & Personil - Sistem Informasi Bimbel</title>
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

        .nav-tabs .nav-link {
            font-weight: 500;
            cursor: pointer;
        }

        .nav-tabs .nav-link.active {
            border-color: #0d6efd;
            background-color: #e7f1ff;
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

        .tab-content {
            padding-top: 20px;
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
                        <h2><i class="bi bi-people"></i> Laporan Kelas & Personil</h2>
                        <p class="text-muted">Monitoring data kelas, siswa, dan tutor</p>
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
                    <h5><i class="bi bi-funnel"></i> Filter Laporan</h5>
                    <form method="GET" class="row g-3 mt-2">
                        <input type="hidden" name="tab" value="<?= $tab ?>">
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
                            <label class="form-label">&nbsp;</label>
                            <div>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-filter"></i> Terapkan Filter
                                </button>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <a href="laporan_kelas.php?tab=<?= $tab ?>" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-clockwise"></i> Reset
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Tabs Navigasi -->
                <ul class="nav nav-tabs mb-4 no-print" id="reportTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <a class="nav-link <?= $tab === 'class' ? 'active' : '' ?>"
                            href="laporan_kelas.php?tab=class&tahun=<?= $tahun ?>&bulan=<?= $bulan ?>&id_kelas=<?= $id_kelas ?>"
                            role="tab">
                            <i class="bi bi-people"></i> Kelas & Jadwal
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link <?= $tab === 'student' ? 'active' : '' ?>"
                            href="laporan_kelas.php?tab=student&tahun=<?= $tahun ?>&bulan=<?= $bulan ?>&id_kelas=<?= $id_kelas ?>"
                            role="tab">
                            <i class="bi bi-person"></i> Siswa
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link <?= $tab === 'tutor' ? 'active' : '' ?>"
                            href="laporan_kelas.php?tab=tutor&tahun=<?= $tahun ?>&bulan=<?= $bulan ?>&id_kelas=<?= $id_kelas ?>"
                            role="tab">
                            <i class="bi bi-person-badge"></i> Tutor
                        </a>
                    </li>
                </ul>

                <div class="tab-content">
                    <!-- TAB 1: KELAS & JADWAL -->
                    <?php if ($tab === 'class'): ?>
                        <!-- Statistik Kelas -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card report-card border-primary">
                                    <div class="card-body text-center">
                                        <div class="stat-number text-primary"><?= $statistikKelas['total_kelas'] ?></div>
                                        <p class="text-muted mb-0">Total Kelas</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card report-card border-success">
                                    <div class="card-body text-center">
                                        <div class="stat-number text-success"><?= $statistikKelas['kelas_aktif'] ?></div>
                                        <p class="text-muted mb-0">Kelas Aktif</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card report-card border-warning">
                                    <div class="card-body text-center">
                                        <div class="stat-number text-warning"><?= number_format($statistikKelas['rata_siswa_per_kelas'] ?? 0, 1) ?></div>
                                        <p class="text-muted mb-0">Rata Siswa/Kelas</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card report-card border-info">
                                    <div class="card-body text-center">
                                        <?php
                                        $qJadwalMingguCount = mysqli_query($conn, "
                                            SELECT COUNT(*) as total 
                                            FROM jadwal 
                                            WHERE tanggal BETWEEN '$startWeek' AND '$endWeek'
                                        ");
                                        $jadwalMingguCount = mysqli_fetch_assoc($qJadwalMingguCount)['total'] ?? 0;
                                        ?>
                                        <div class="stat-number text-info"><?= $jadwalMingguCount ?></div>
                                        <p class="text-muted mb-0">Jadwal Minggu Ini</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Jadwal Minggu Ini -->
                        <div class="card report-card mb-4">
                            <div class="card-header border-primary">
                                <h5 class="mb-0"><i class="bi bi-calendar-week"></i> Jadwal Minggu Ini (<?= date('d M', strtotime($startWeek)) ?> - <?= date('d M', strtotime($endWeek)) ?>)</h5>
                            </div>
                            <div class="card-body">
                                <?php if (mysqli_num_rows($qJadwalMinggu) > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Hari/Tanggal</th>
                                                    <th>Kelas</th>
                                                    <th>Mapel</th>
                                                    <th>Tutor</th>
                                                    <th>Waktu</th>
                                                    <th>Ruangan</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while ($row = mysqli_fetch_assoc($qJadwalMinggu)): ?>
                                                    <tr>
                                                        <td>
                                                            <?= date('D', strtotime($row['tanggal'])) ?><br>
                                                            <small><?= date('d M', strtotime($row['tanggal'])) ?></small>
                                                        </td>
                                                        <td><?= h($row['nama_kelas']) ?></td>
                                                        <td><?= h($row['nama_mapel']) ?></td>
                                                        <td><?= h($row['nama_tutor']) ?></td>
                                                        <td><?= date('H:i', strtotime($row['jam_mulai'])) ?> - <?= date('H:i', strtotime($row['jam_selesai'])) ?></td>
                                                        <td><?= h($row['nama_ruangan'] ?? '-') ?></td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="no-data">
                                        <i class="bi bi-calendar-x display-4"></i>
                                        <p class="mt-3">Tidak ada jadwal untuk minggu ini</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Kelas dengan Siswa Terbanyak -->
                        <div class="card report-card">
                            <div class="card-header border-success">
                                <h5 class="mb-0"><i class="bi bi-trophy"></i> Kelas dengan Siswa Terbanyak</h5>
                            </div>
                            <div class="card-body">
                                <?php if (mysqli_num_rows($qKelasTerbanyak) > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Kelas</th>
                                                    <th>Mapel</th>
                                                    <th>Tutor</th>
                                                    <th>Jumlah Siswa</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while ($row = mysqli_fetch_assoc($qKelasTerbanyak)): ?>
                                                    <tr>
                                                        <td><?= h($row['nama_kelas']) ?></td>
                                                        <td><?= h($row['nama_mapel']) ?></td>
                                                        <td><?= h($row['nama_tutor']) ?></td>
                                                        <td>
                                                            <span class="badge bg-info"><?= $row['jumlah_siswa'] ?> siswa</span>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="no-data">
                                        <i class="bi bi-people display-4"></i>
                                        <p class="mt-3">Belum ada data kelas</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- TAB 2: LAPORAN SISWA -->
                    <?php if ($tab === 'student'): ?>
                        <!-- Statistik Siswa -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card report-card border-primary">
                                    <div class="card-body text-center">
                                        <div class="stat-number text-primary"><?= $statistikSiswa['total_siswa'] ?></div>
                                        <p class="text-muted mb-0">Total Siswa</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card report-card border-success">
                                    <div class="card-body text-center">
                                        <div class="stat-number text-success"><?= $statistikSiswa['siswa_aktif'] ?></div>
                                        <p class="text-muted mb-0">Siswa Aktif</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card report-card border-warning">
                                    <div class="card-body text-center">
                                        <div class="stat-number text-warning"><?= $statistikSiswa['siswa_baru_bulan_ini'] ?></div>
                                        <p class="text-muted mb-0">Siswa Baru Bulan Ini</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card report-card border-info">
                                    <div class="card-body text-center">
                                        <div class="stat-number text-info"><?= $statistikSiswa['siswa_alumni'] ?></div>
                                        <p class="text-muted mb-0">Siswa Alumni</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Siswa per Kelas -->
                        <div class="card report-card mb-4">
                            <div class="card-header border-primary">
                                <h5 class="mb-0"><i class="bi bi-diagram-3"></i> Distribusi Siswa per Kelas</h5>
                            </div>
                            <div class="card-body">
                                <?php if (mysqli_num_rows($qSiswaPerKelas) > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Kelas</th>
                                                    <th>Mapel</th>
                                                    <th>Jumlah Siswa</th>
                                                    <th>Daftar Siswa</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while ($row = mysqli_fetch_assoc($qSiswaPerKelas)): ?>
                                                    <tr>
                                                        <td><?= h($row['nama_kelas']) ?></td>
                                                        <td><?= h($row['nama_mapel']) ?></td>
                                                        <td>
                                                            <span class="badge bg-info"><?= $row['jumlah_siswa'] ?> siswa</span>
                                                        </td>
                                                        <td>
                                                            <small class="text-muted"><?= h($row['daftar_siswa']) ?></small>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="no-data">
                                        <i class="bi bi-people display-4"></i>
                                        <p class="mt-3">Belum ada data siswa per kelas</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Siswa dengan Tunggakan -->
                        <div class="card report-card">
                            <div class="card-header border-warning">
                                <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Siswa dengan Tunggakan</h5>
                            </div>
                            <div class="card-body">
                                <?php if (mysqli_num_rows($qSiswaTunggakan) > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Nama Siswa</th>
                                                    <th>Kontak</th>
                                                    <th>Jumlah Tunggakan</th>
                                                    <th>Total Tunggakan</th>
                                                    <th>Kelas dengan Tunggakan</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while ($row = mysqli_fetch_assoc($qSiswaTunggakan)): ?>
                                                    <tr>
                                                        <td><strong><?= h($row['nama']) ?></strong></td>
                                                        <td>
                                                            <small>
                                                                <?= h($row['email']) ?><br>
                                                                <?= h($row['telepon']) ?>
                                                            </small>
                                                        </td>
                                                        <td><?= $row['jumlah_tunggakan'] ?> transaksi</td>
                                                        <td class="text-danger"><strong><?= formatRupiah($row['total_tunggakan']) ?></strong></td>
                                                        <td><small><?= h($row['kelas_tunggakan']) ?></small></td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="no-data">
                                        <i class="bi bi-check-circle display-4 text-success"></i>
                                        <p class="mt-3">Tidak ada siswa yang menunggak</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- TAB 3: LAPORAN TUTOR -->
                    <?php if ($tab === 'tutor'): ?>
                        <!-- Statistik Tutor -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card report-card border-primary">
                                    <div class="card-body text-center">
                                        <div class="stat-number text-primary"><?= $statistikTutor['total_tutor'] ?></div>
                                        <p class="text-muted mb-0">Total Tutor</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card report-card border-success">
                                    <div class="card-body text-center">
                                        <div class="stat-number text-success"><?= $statistikTutor['tutor_aktif'] ?></div>
                                        <p class="text-muted mb-0">Tutor Aktif</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card report-card border-warning">
                                    <div class="card-body text-center">
                                        <div class="stat-number text-warning"><?= number_format($statistikTutor['rata_pengalaman'] ?? 0, 1) ?></div>
                                        <p class="text-muted mb-0">Rata Pengalaman (tahun)</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card report-card border-info">
                                    <div class="card-body text-center">
                                        <div class="stat-number text-info"><?= number_format($totalJam, 1) ?></div>
                                        <p class="text-muted mb-0">Total Jam Mengajar</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tutor Aktif -->
                        <div class="card report-card mb-4">
                            <div class="card-header border-primary">
                                <h5 class="mb-0"><i class="bi bi-person-badge"></i> Tutor Aktif</h5>
                            </div>
                            <div class="card-body">
                                <?php if (mysqli_num_rows($qTutorAktif) > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Nama Tutor</th>
                                                    <th>Kontak</th>
                                                    <th>Pengalaman</th>
                                                    <th>Spesialisasi</th>
                                                    <th>Jumlah Kelas</th>
                                                    <th>Jumlah Jadwal</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while ($row = mysqli_fetch_assoc($qTutorAktif)): ?>
                                                    <tr>
                                                        <td><strong><?= h($row['nama_tutor']) ?></strong></td>
                                                        <td>
                                                            <small>
                                                                <?= h($row['email']) ?><br>
                                                                <?= h($row['telepon']) ?>
                                                            </small>
                                                        </td>
                                                        <td><?= $row['pengalaman'] ?> tahun</td>
                                                        <td><?= h($row['spesialisasi'] ?? '-') ?></td>
                                                        <td>
                                                            <span class="badge bg-info"><?= $row['jumlah_kelas'] ?></span>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-warning"><?= $row['jumlah_jadwal'] ?></span>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="no-data">
                                        <i class="bi bi-person-badge display-4"></i>
                                        <p class="mt-3">Belum ada data tutor</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Jam Mengajar per Tutor -->
                        <div class="card report-card">
                            <div class="card-header border-success">
                                <h5 class="mb-0"><i class="bi bi-clock-history"></i> Jam Mengajar per Tutor (<?= getMonthName((int)$bulan) ?> <?= $tahun ?>)</h5>
                            </div>
                            <div class="card-body">
                                <?php if (mysqli_num_rows($qJamMengajar) > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Nama Tutor</th>
                                                    <th>Jumlah Sesi</th>
                                                    <th>Total Jam</th>
                                                    <th>Rata per Sesi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while ($row = mysqli_fetch_assoc($qJamMengajar)): ?>
                                                    <tr>
                                                        <td><?= h($row['nama_tutor']) ?></td>
                                                        <td><?= $row['jumlah_sesi'] ?> sesi</td>
                                                        <td class="text-success">
                                                            <strong><?= number_format($row['total_jam_mengajar'], 1) ?> jam</strong>
                                                        </td>
                                                        <td><?= number_format($row['rata_jam_per_sesi'], 1) ?> jam/sesi</td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="no-data">
                                        <i class="bi bi-clock-history display-4"></i>
                                        <p class="mt-3">Belum ada data jam mengajar</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Footer Informasi -->
                <div class="card mt-4 no-print">
                    <div class="card-body text-center">
                        <p class="text-muted mb-0">
                            <i class="bi bi-info-circle"></i>
                            Laporan dihasilkan pada <?= date('d F Y H:i:s') ?> |
                            Periode: <?= getMonthName((int)$bulan) ?> <?= $tahun ?>
                            <?php if ($id_kelas): ?> | Kelas: <?= h($kelasList[$id_kelas] ?? '') ?><?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Simpan tab aktif di localStorage
        document.addEventListener('DOMContentLoaded', function() {
            // Highlight tab aktif berdasarkan URL parameter
            const urlParams = new URLSearchParams(window.location.search);
            const activeTab = urlParams.get('tab') || 'class';

            // Update tab styling
            document.querySelectorAll('.nav-tabs .nav-link').forEach(tab => {
                tab.classList.remove('active');
                if (tab.getAttribute('href').includes(`tab=${activeTab}`)) {
                    tab.classList.add('active');
                }
            });
        });
    </script>
</body>

</html>