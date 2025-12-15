<?php
session_start();
require "../config.php";

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

function formatRupiah($angka)
{
    return 'Rp ' . number_format($angka, 0, ',', '.');
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Laporan - Sistem Informasi Bimbel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .dashboard-card {
            border-radius: 15px;
            transition: all 0.3s;
            height: 100%;
            border: none;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            cursor: pointer;
        }

        .dashboard-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
        }

        .card-icon {
            font-size: 3.5rem;
            opacity: 0.9;
        }

        .stat-card {
            border-radius: 10px;
            border-left: 5px solid;
        }

        .quick-stat {
            font-size: 2rem;
            font-weight: bold;
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
                    <a class="nav-link" href="mapel.php"><i class="bi bi-book"></i> Mata Pelajaran</a>
                    <a class="nav-link active" href="laporan.php"><i class="bi bi-file-earmark-text"></i> Laporan</a>
                    <a class="nav-link" href="../logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 content-wrapper p-4">
                <!-- Header -->
                <div class="page-header mb-4">
                    <h1><i class="bi bi-file-earmark-text"></i> Dashboard Laporan</h1>
                    <p class="text-muted">Pilih jenis laporan yang ingin dilihat dan dikelola</p>
                </div>

                <!-- Quick Stats -->
                <div class="row mb-5">
                    <div class="col-md-3">
                        <?php
                        $qTotalSiswa = mysqli_query($conn, "SELECT COUNT(*) as total FROM siswa WHERE status_aktif = 'Aktif'");
                        $totalSiswa = mysqli_fetch_assoc($qTotalSiswa)['total'] ?? 0;
                        ?>
                        <div class="card stat-card border-left-primary">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="text-muted">Siswa Aktif</h6>
                                        <div class="quick-stat text-primary"><?= $totalSiswa ?></div>
                                    </div>
                                    <i class="bi bi-people text-primary" style="font-size: 2rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <?php
                        $qTotalTutor = mysqli_query($conn, "SELECT COUNT(*) as total FROM tutor WHERE status = 'Aktif'");
                        $totalTutor = mysqli_fetch_assoc($qTotalTutor)['total'] ?? 0;
                        ?>
                        <div class="card stat-card border-left-success">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="text-muted">Tutor Aktif</h6>
                                        <div class="quick-stat text-success"><?= $totalTutor ?></div>
                                    </div>
                                    <i class="bi bi-person-badge text-success" style="font-size: 2rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <?php
                        $qTotalKelas = mysqli_query($conn, "SELECT COUNT(*) as total FROM kelas WHERE status = 'Aktif'");
                        $totalKelas = mysqli_fetch_assoc($qTotalKelas)['total'] ?? 0;
                        ?>
                        <div class="card stat-card border-left-warning">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="text-muted">Kelas Aktif</h6>
                                        <div class="quick-stat text-warning"><?= $totalKelas ?></div>
                                    </div>
                                    <i class="bi bi-calendar3 text-warning" style="font-size: 2rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <?php
                        $qTotalTunggakan = mysqli_query($conn, "SELECT SUM(nominal) as total FROM pembayaran WHERE status = 'Belum Lunas' AND tgl_bayar <= CURDATE()");
                        $totalTunggakan = mysqli_fetch_assoc($qTotalTunggakan)['total'] ?? 0;
                        ?>
                        <div class="card stat-card border-left-danger">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="text-muted">Total Tunggakan</h6>
                                        <div class="quick-stat text-danger"><?= formatRupiah($totalTunggakan) ?></div>
                                    </div>
                                    <i class="bi bi-exclamation-triangle text-danger" style="font-size: 2rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Laporan Cards -->
                <div class="row">
                    <!-- Keuangan -->
                    <div class="col-md-4 mb-4">
                        <a href="laporan_keuangan.php" class="text-decoration-none">
                            <div class="card dashboard-card border-primary">
                                <div class="card-body text-center p-5">
                                    <i class="bi bi-cash-coin card-icon text-primary mb-3"></i>
                                    <h4 class="card-title">Laporan Keuangan</h4>
                                    <p class="text-muted mb-4">Analisis pendapatan, pembayaran, tunggakan, dan performa keuangan</p>
                                    <div class="d-flex justify-content-center gap-2">
                                        <span class="badge bg-primary">Pendapatan</span>
                                        <span class="badge bg-success">Transaksi</span>
                                        <span class="badge bg-warning">Tunggakan</span>
                                    </div>
                                    <div class="mt-3">
                                        <small><i class="bi bi-arrow-right"></i> Klik untuk melihat detail</small>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>

                    <!-- Akademik -->
                    <div class="col-md-4 mb-4">
                        <a href="laporan_akademik.php" class="text-decoration-none">
                            <div class="card dashboard-card border-success">
                                <div class="card-body text-center p-5">
                                    <i class="bi bi-journal-check card-icon text-success mb-3"></i>
                                    <h4 class="card-title">Laporan Akademik</h4>
                                    <p class="text-muted mb-4">Monitoring nilai tugas, rata-rata nilai, dan pengumpulan tugas</p>
                                    <div class="d-flex justify-content-center gap-2">
                                        <span class="badge bg-success">Nilai</span>
                                        <span class="badge bg-info">Tugas</span>
                                        <span class="badge bg-warning">Progress</span>
                                    </div>
                                    <div class="mt-3">
                                        <small><i class="bi bi-arrow-right"></i> Klik untuk melihat detail</small>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>

                    <!-- Kehadiran -->
                    <div class="col-md-4 mb-4">
                        <a href="laporan_kehadiran.php" class="text-decoration-none">
                            <div class="card dashboard-card border-warning">
                                <div class="card-body text-center p-5">
                                    <i class="bi bi-calendar-check card-icon text-warning mb-3"></i>
                                    <h4 class="card-title">Laporan Kehadiran</h4>
                                    <p class="text-muted mb-4">Presensi siswa & tutor, statistik kehadiran, dan ketidakhadiran</p>
                                    <div class="d-flex justify-content-center gap-2">
                                        <span class="badge bg-warning">Hadir</span>
                                        <span class="badge bg-info">Izin</span>
                                        <span class="badge bg-danger">Alpha</span>
                                    </div>
                                    <div class="mt-3">
                                        <small><i class="bi bi-arrow-right"></i> Klik untuk melihat detail</small>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>

                    <!-- Kelas & Jadwal -->
                    <div class="col-md-4 mb-4">
                        <a href="laporan_kelas.php" class="text-decoration-none">
                            <div class="card dashboard-card border-info">
                                <div class="card-body text-center p-5">
                                    <i class="bi bi-people card-icon text-info mb-3"></i>
                                    <h4 class="card-title">Laporan Kelas & Jadwal</h4>
                                    <p class="text-muted mb-4">Statistik kelas, jadwal mengajar, dan distribusi siswa</p>
                                    <div class="d-flex justify-content-center gap-2">
                                        <span class="badge bg-info">Kelas</span>
                                        <span class="badge bg-primary">Jadwal</span>
                                        <span class="badge bg-success">Distribusi</span>
                                    </div>
                                    <div class="mt-3">
                                        <small><i class="bi bi-arrow-right"></i> Klik untuk melihat detail</small>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>

                    <!-- Siswa -->
                    <div class="col-md-4 mb-4">
                        <a href="laporan_kelas.php?tab=siswa" class="text-decoration-none">
                            <div class="card dashboard-card" style="border-color: #6f42c1 !important;">
                                <div class="card-body text-center p-5">
                                    <i class="bi bi-person card-icon" style="color: #6f42c1; margin-bottom: 1rem;"></i>
                                    <h4 class="card-title">Laporan Siswa</h4>
                                    <p class="text-muted mb-4">Statistik siswa, distribusi per kelas, dan monitoring tunggakan</p>
                                    <div class="d-flex justify-content-center gap-2">
                                        <span class="badge" style="background-color: #6f42c1;">Siswa</span>
                                        <span class="badge bg-warning">Tunggakan</span>
                                        <span class="badge bg-success">Status</span>
                                    </div>
                                    <div class="mt-3">
                                        <small><i class="bi bi-arrow-right"></i> Klik untuk melihat detail</small>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>

                    <!-- Tutor -->
                    <div class="col-md-4 mb-4">
                        <a href="laporan_kelas.php?tab=tutor" class="text-decoration-none">
                            <div class="card dashboard-card border-danger">
                                <div class="card-body text-center p-5">
                                    <i class="bi bi-person-badge card-icon text-danger mb-3"></i>
                                    <h4 class="card-title">Laporan Tutor</h4>
                                    <p class="text-muted mb-4">Statistik tutor, jam mengajar, dan performa mengajar</p>
                                    <div class="d-flex justify-content-center gap-2">
                                        <span class="badge bg-danger">Tutor</span>
                                        <span class="badge bg-info">Jam</span>
                                        <span class="badge bg-success">Aktif</span>
                                    </div>
                                    <div class="mt-3">
                                        <small><i class="bi bi-arrow-right"></i> Klik untuk melihat detail</small>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card mt-5">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="bi bi-lightning-charge"></i> Akses Cepat</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 text-center">
                                <a href="laporan_keuangan.php?tahun=<?= date('Y') ?>&bulan=<?= date('m') ?>" class="btn btn-outline-primary btn-lg w-100 mb-2">
                                    <i class="bi bi-cash-coin"></i> Bulan Ini
                                </a>
                                <small class="text-muted">Laporan Keuangan</small>
                            </div>
                            <div class="col-md-3 text-center">
                                <a href="laporan_kehadiran.php?bulan=<?= date('m') ?>&tahun=<?= date('Y') ?>" class="btn btn-outline-warning btn-lg w-100 mb-2">
                                    <i class="bi bi-calendar-check"></i> Presensi
                                </a>
                                <small class="text-muted">Kehadiran Bulan Ini</small>
                            </div>
                            <div class="col-md-3 text-center">
                                <a href="laporan_kelas.php" class="btn btn-outline-info btn-lg w-100 mb-2">
                                    <i class="bi bi-calendar3"></i> Jadwal
                                </a>
                                <small class="text-muted">Jadwal Minggu Ini</small>
                            </div>
                            <div class="col-md-3 text-center">
                                <a href="laporan_keuangan.php?status_pembayaran=Belum%20Lunas" class="btn btn-outline-danger btn-lg w-100 mb-2">
                                    <i class="bi bi-exclamation-triangle"></i> Tunggakan
                                </a>
                                <small class="text-muted">Siswa Menunggak</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Animasi hover cards
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.dashboard-card');
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-10px)';
                });
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        });
    </script>
</body>

</html>