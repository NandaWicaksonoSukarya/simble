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
   VARIABEL FILTER
===================================================== */
$tahun = $_GET['tahun'] ?? date('Y');
$bulan = $_GET['bulan'] ?? date('m');
$id_kelas = $_GET['id_kelas'] ?? '';
$status_pembayaran = $_GET['status_pembayaran'] ?? '';

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
   QUERY DATA KEUANGAN
===================================================== */
// Pendapatan per bulan
$qPendapatanBulanan = mysqli_query($conn, "
    SELECT 
        DATE_FORMAT(p.tgl_bayar, '%Y-%m') AS periode,
        SUM(p.nominal) AS total_pendapatan,
        COUNT(DISTINCT p.id_siswa) AS jumlah_siswa
    FROM pembayaran p
    WHERE p.status = 'Lunas'
    AND YEAR(p.tgl_bayar) = '$tahun'
    GROUP BY DATE_FORMAT(p.tgl_bayar, '%Y-%m')
    ORDER BY periode DESC
");

// Pembayaran siswa dengan filter
$wherePembayaran = "1=1";
if ($id_kelas) {
    $wherePembayaran .= " AND p.id_kelas = '$id_kelas'";
}
if ($status_pembayaran) {
    $wherePembayaran .= " AND p.status = '$status_pembayaran'";
}

$qPembayaranSiswa = mysqli_query($conn, "
    SELECT 
        s.nama,
        k.nama_kelas,
        m.nama_mapel,
        DATE_FORMAT(p.tgl_bayar, '%M %Y') as bulan_tagihan,
        p.nominal,
        p.status,
        DATE_FORMAT(p.tgl_bayar, '%d %b %Y') as tgl_bayar_format
    FROM pembayaran p
    JOIN siswa s ON p.id_siswa = s.id_siswa
    LEFT JOIN kelas k ON p.id_kelas = k.id_kelas
    LEFT JOIN mapel m ON k.id_mapel = m.id_mapel
    WHERE $wherePembayaran
    ORDER BY p.tgl_bayar DESC
    LIMIT 50
");

// Tunggakan siswa
$qTunggakan = mysqli_query($conn, "
    SELECT 
        s.nama,
        k.nama_kelas,
        m.nama_mapel,
        COUNT(p.id_pembayaran) as jumlah_tunggakan,
        SUM(p.nominal) as total_tunggakan,
        MIN(p.tgl_bayar) as tanggal_tertua
    FROM pembayaran p
    JOIN siswa s ON p.id_siswa = s.id_siswa
    LEFT JOIN kelas k ON p.id_kelas = k.id_kelas
    LEFT JOIN mapel m ON k.id_mapel = m.id_mapel
    WHERE p.status = 'Belum Lunas'
    AND p.tgl_bayar <= CURDATE()
    GROUP BY s.id_siswa, k.id_kelas
    ORDER BY total_tunggakan DESC
    LIMIT 20
");

// Rekap pemasukan per kelas/mapel
$qRekapKelas = mysqli_query($conn, "
    SELECT 
        m.nama_mapel,
        k.nama_kelas,
        COUNT(DISTINCT p.id_siswa) as jumlah_siswa,
        COUNT(p.id_pembayaran) as jumlah_transaksi,
        SUM(CASE WHEN p.status = 'Lunas' THEN p.nominal ELSE 0 END) as total_pemasukan,
        SUM(CASE WHEN p.status = 'Belum Lunas' THEN p.nominal ELSE 0 END) as total_tunggakan
    FROM pembayaran p
    LEFT JOIN kelas k ON p.id_kelas = k.id_kelas
    LEFT JOIN mapel m ON k.id_mapel = m.id_mapel
    WHERE YEAR(p.tgl_bayar) = '$tahun'
    GROUP BY k.id_kelas, m.id_mapel
    ORDER BY total_pemasukan DESC
");

/* =====================================================
   STATISTIK KEUANGAN
===================================================== */
// Total Pendapatan Tahun Ini
$qTotalPendapatan = mysqli_query($conn, "
    SELECT SUM(nominal) as total 
    FROM pembayaran 
    WHERE status = 'Lunas'
    AND YEAR(tgl_bayar) = '$tahun'
");
$totalPendapatan = mysqli_fetch_assoc($qTotalPendapatan)['total'] ?? 0;

// Transaksi Bulan Ini
$qTransaksiBulan = mysqli_query($conn, "
    SELECT COUNT(*) as total 
    FROM pembayaran 
    WHERE status = 'Lunas'
    AND MONTH(tgl_bayar) = '$bulan'
    AND YEAR(tgl_bayar) = '$tahun'
");
$transaksiBulan = mysqli_fetch_assoc($qTransaksiBulan)['total'] ?? 0;

// Total Tunggakan
$qTunggakanTotal = mysqli_query($conn, "
    SELECT SUM(nominal) as total 
    FROM pembayaran 
    WHERE status = 'Belum Lunas'
    AND tgl_bayar <= CURDATE()
");
$tunggakanTotal = mysqli_fetch_assoc($qTunggakanTotal)['total'] ?? 0;

// Siswa Menunggak
$qSiswaTunggak = mysqli_query($conn, "
    SELECT COUNT(DISTINCT id_siswa) as total 
    FROM pembayaran 
    WHERE status = 'Belum Lunas'
    AND tgl_bayar <= CURDATE()
");
$siswaTunggak = mysqli_fetch_assoc($qSiswaTunggak)['total'] ?? 0;


?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Keuangan - Sistem Informasi Bimbel</title>
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
                        <h2><i class="bi bi-cash-coin"></i> Laporan Keuangan</h2>
                        <p class="text-muted">Monitoring dan analisis data keuangan bimbel</p>
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
                    <h5><i class="bi bi-funnel"></i> Filter Laporan Keuangan</h5>
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
                            <label class="form-label">Status Pembayaran</label>
                            <select name="status_pembayaran" class="form-select">
                                <option value="">Semua Status</option>
                                <option value="Lunas" <?= $status_pembayaran == 'Lunas' ? 'selected' : '' ?>>Lunas</option>
                                <option value="Belum Lunas" <?= $status_pembayaran == 'Belum Lunas' ? 'selected' : '' ?>>Belum Lunas</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-filter"></i> Terapkan Filter
                            </button>
                            <a href="laporan_keuangan.php" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-clockwise"></i> Reset
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Statistik Keuangan -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card report-card border-primary">
                            <div class="card-body text-center">
                                <div class="stat-number text-primary"><?= formatRupiah($totalPendapatan) ?></div>
                                <p class="text-muted mb-0">Total Pendapatan <?= $tahun ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card report-card border-success">
                            <div class="card-body text-center">
                                <div class="stat-number text-success"><?= $transaksiBulan ?></div>
                                <p class="text-muted mb-0">Transaksi <?= getMonthName((int)$bulan) ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card report-card border-warning">
                            <div class="card-body text-center">
                                <div class="stat-number text-warning"><?= formatRupiah($tunggakanTotal) ?></div>
                                <p class="text-muted mb-0">Total Tunggakan</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card report-card border-info">
                            <div class="card-body text-center">
                                <div class="stat-number text-info"><?= $siswaTunggak ?></div>
                                <p class="text-muted mb-0">Siswa Menunggak</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pendapatan Per Bulan -->
                <div class="card report-card mb-4">
                    <div class="card-header border-primary">
                        <h5 class="mb-0"><i class="bi bi-graph-up"></i> Pendapatan Per Bulan (<?= $tahun ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (mysqli_num_rows($qPendapatanBulanan) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Bulan</th>
                                            <th>Jumlah Transaksi</th>
                                            <th>Total Pendapatan</th>
                                           
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($row = mysqli_fetch_assoc($qPendapatanBulanan)): ?>
                                            <tr>
                                                <td><?= date('F Y', strtotime($row['periode'] . '-01')) ?></td>
                                                <td><?= $row['jumlah_siswa'] ?></td>
                                                <td><?= formatRupiah($row['total_pendapatan']) ?></td>
                                                
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="no-data">
                                <i class="bi bi-bar-chart display-4"></i>
                                <p class="mt-3">Tidak ada data pendapatan untuk tahun <?= $tahun ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Pembayaran Siswa -->
                <div class="card report-card mb-4">
                    <div class="card-header border-success">
                        <h5 class="mb-0"><i class="bi bi-cash-stack"></i> Pembayaran Siswa</h5>
                    </div>
                    <div class="card-body">
                        <?php if (mysqli_num_rows($qPembayaranSiswa) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Nama Siswa</th>
                                            <th>Kelas</th>
                                            <th>Bulan Tagihan</th>
                                            <th>Jumlah Bayar</th>
                                            <th>Status</th>
                                            <th>Tanggal Bayar</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($row = mysqli_fetch_assoc($qPembayaranSiswa)): ?>
                                            <tr>
                                                <td><?= h($row['nama']) ?></td>
                                                <td><?= h($row['nama_kelas'] ?? '-') ?></td>
                                                <td><?= h($row['bulan_tagihan']) ?></td>
                                                <td><?= formatRupiah($row['nominal']) ?></td>
                                                <td>
                                                    <span class="badge-status <?= $row['status'] == 'Lunas' ? 'bg-success' : 'bg-warning' ?>">
                                                        <?= h($row['status']) ?>
                                                    </span>
                                                </td>
                                                <td><?= h($row['tgl_bayar_format']) ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="no-data">
                                <i class="bi bi-cash-coin display-4"></i>
                                <p class="mt-3">Tidak ada data pembayaran siswa</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Tunggakan Siswa -->
                <div class="card report-card mb-4">
                    <div class="card-header border-warning">
                        <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Tunggakan Siswa</h5>
                    </div>
                    <div class="card-body">
                        <?php if (mysqli_num_rows($qTunggakan) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Nama Siswa</th>
                                            <th>Kelas</th>
                                            <th>Jumlah Tunggakan</th>
                                            <th>Total Tunggakan</th>
                                            <th>Tanggal Tertua</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($row = mysqli_fetch_assoc($qTunggakan)): ?>
                                            <tr>
                                                <td><?= h($row['nama']) ?></td>
                                                <td><?= h($row['nama_kelas'] ?? '-') ?></td>
                                                <td><?= $row['jumlah_tunggakan'] ?> transaksi</td>
                                                <td class="text-danger"><?= formatRupiah($row['total_tunggakan']) ?></td>
                                                <td><?= date('M Y', strtotime($row['tanggal_tertua'])) ?></td>
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

                <!-- Rekap per Kelas/Mapel -->
                <div class="card report-card">
                    <div class="card-header border-info">
                        <h5 class="mb-0"><i class="bi bi-diagram-3"></i> Rekap Pemasukan per Kelas/Mapel (<?= $tahun ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (mysqli_num_rows($qRekapKelas) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Mata Pelajaran</th>
                                            <th>Kelas</th>
                                            <th>Jumlah Siswa</th>
                                            <th>Jumlah Transaksi</th>
                                            <th>Total Pemasukan</th>
                                            <th>Total Tunggakan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($row = mysqli_fetch_assoc($qRekapKelas)): ?>
                                            <tr>
                                                <td><?= h($row['nama_mapel'] ?? '-') ?></td>
                                                <td><?= h($row['nama_kelas'] ?? '-') ?></td>
                                                <td><?= $row['jumlah_siswa'] ?></td>
                                                <td><?= $row['jumlah_transaksi'] ?></td>
                                                <td class="text-success"><?= formatRupiah($row['total_pemasukan']) ?></td>
                                                <td class="text-warning"><?= formatRupiah($row['total_tunggakan']) ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="no-data">
                                <i class="bi bi-diagram-3 display-4"></i>
                                <p class="mt-3">Tidak ada data pemasukan per kelas</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Footer Informasi -->
                <div class="card mt-4 no-print">
                    <div class="card-body text-center">
                        <p class="text-muted mb-0">
                            <i class="bi bi-info-circle"></i>
                            Laporan Keuangan dihasilkan pada <?= date('d F Y H:i:s') ?> |
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