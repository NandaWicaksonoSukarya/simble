<?php
session_start();
require "../config.php";

if (!isset($_SESSION['login']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

// ====== STAT CARDS ======
$total_siswa = $conn->query("SELECT COUNT(*) AS total FROM siswa")->fetch_assoc()['total'];
$total_tutor = $conn->query("SELECT COUNT(*) AS total FROM tutor")->fetch_assoc()['total'];
$total_kelas = $conn->query("SELECT COUNT(*) AS total FROM kelas WHERE status='aktif'")->fetch_assoc()['total'];
$total_pendapatan = $conn->query("SELECT SUM(nominal) AS total FROM pembayaran WHERE MONTH(tgl_bayar) = MONTH(CURDATE()) AND YEAR(tgl_bayar) = YEAR(CURDATE()) AND status = 'lunas'
")->fetch_assoc()['total'] ?? 0;
$total_pendapatan_fmt = "Rp " . number_format($total_pendapatan, 0, ',', '.');

// ====== CHART DATA PENDAFTARAN SISWA 6 BULAN ======
$student_chart_labels = [];
$student_chart_data = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i month"));
    $label = date('M Y', strtotime("-$i month"));
    $total = $conn->query("SELECT COUNT(*) AS total FROM siswa WHERE DATE_FORMAT(tgl_daftar,'%Y-%m')='$month'")->fetch_assoc()['total'];
    $student_chart_labels[] = $label;
    $student_chart_data[] = $total;
}

// ====== CHART DATA STATUS PEMBAYARAN ======
$total_lunas = $conn->query("SELECT COUNT(*) AS total FROM pembayaran WHERE status='lunas'")->fetch_assoc()['total'];
$total_pending = $conn->query("SELECT COUNT(*) AS total FROM pembayaran WHERE status='pending'")->fetch_assoc()['total'];

// // ====== KELAS HARI INI ======
// $today = date('l');
// $day_map = ['Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu', 'Sunday' => 'Minggu'];
// $hari_ini = $day_map[$today];

$kelas_hari_ini = $conn->query("
    SELECT 
        k.nama_kelas,
        m.nama_mapel,
        t.nama_tutor,
        j.jam_mulai,
        j.jam_selesai,
        j.status
    FROM jadwal j
    JOIN kelas k ON j.id_kelas = k.id_kelas
    JOIN tutor t ON j.id_tutor = t.id_tutor
    JOIN mapel m ON j.id_mapel = m.id_mapel
    WHERE j.tanggal = CURDATE()
");


// ====== AKTIVITAS TERBARU ======
$aktivitas_siswa = $conn->query("SELECT nama, tgl_daftar FROM siswa ORDER BY tgl_daftar DESC LIMIT 5");
$aktivitas_pembayaran = $conn->query("SELECT s.nama, p.tgl_bayar, p.status FROM pembayaran p JOIN siswa s ON p.id_siswa=s.id_siswa ORDER BY p.tgl_bayar DESC LIMIT 5");
$aktivitas_materi = $conn->query("SELECT k.nama_kelas, m.judul, m.tgl_upload FROM materi m JOIN kelas k ON m.id_kelas=k.id_kelas ORDER BY m.tgl_upload DESC LIMIT 5");
$aktivitas_tutor = $conn->query("SELECT nama_tutor, created_at FROM tutor ORDER BY created_at DESC LIMIT 5");
$aktivitas_presensi = $conn->query("SELECT s.nama, pr.tanggal, pr.status FROM presensi pr JOIN siswa s ON pr.id_siswa=s.id_siswa ORDER BY pr.tanggal DESC LIMIT 5");
$badgeClass = [
    'Hadir' => 'bg-success',
    'Izin'  => 'bg-info',
    'Sakit' => 'bg-warning',
    'Alpha' => 'bg-danger'
];
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Sistem Informasi Bimbel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>
    <div class="container-fluid">
        <div class="row">

            <!-- SIDEBAR -->
            <div class="col-md-2 sidebar p-0">
                <div class="text-center text-white py-4">
                    <i class="bi bi-book-fill" style="font-size: 2.5rem;"></i>
                    <h5 class="mt-2">Bimbel System</h5>
                    <small>Admin Panel</small>
                </div>
                <nav class="nav flex-column">
                    <a class="nav-link active" href="dashboardadmin.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                    <a class="nav-link" href="siswa.php"><i class="bi bi-people"></i> Data Siswa</a>
                    <a class="nav-link" href="tutor.php"><i class="bi bi-person-badge"></i> Data Tutor</a>
                    <a class="nav-link" href="kelas.php"><i class="bi bi-calendar3"></i> Jadwal Kelas</a>
                    <a class="nav-link" href="pembayaran.php"><i class="bi bi-cash-coin"></i> Pembayaran</a>
                    <a class="nav-link" href="mapel.php"><i class="bi bi-journal-text"></i> Mata Pelajaran</a>
                    <a class="nav-link" href="laporan.php"><i class="bi bi-file-earmark-text"></i> Laporan</a>
                    <a class="nav-link" href="../logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
                </nav>
            </div>

            <!-- MAIN CONTENT -->
            <div class="col-md-10 content-wrapper">
                <div class="page-header">
                    <h2>Dashboard Admin</h2>
                    <p class="text-muted">Selamat datang, Admin! Berikut ringkasan sistem bimbel Anda.</p>
                </div>

                <!-- STAT CARDS -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card stat-card bg-primary text-white">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h3 class="mb-0"><?= $total_siswa ?></h3>
                                    <p class="mb-0">Total Siswa</p>
                                </div>
                                <i class="bi bi-people icon"></i>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card stat-card bg-success text-white">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h3 class="mb-0"><?= $total_tutor ?></h3>
                                    <p class="mb-0">Total Tutor</p>
                                </div>
                                <i class="bi bi-person-badge icon"></i>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card stat-card bg-warning text-white">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h3 class="mb-0"><?= $total_kelas ?></h3>
                                    <p class="mb-0">Kelas Aktif</p>
                                </div>
                                <i class="bi bi-calendar3 icon"></i>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card stat-card bg-info text-white">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h3 class="mb-0"><?= $total_pendapatan_fmt ?></h3>
                                    <p class="mb-0">Pendapatan Bulan Ini</p>
                                </div>
                                <i class="bi bi-cash-coin icon"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CHARTS -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">Grafik Pendaftaran Siswa (6 Bulan Terakhir)</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="studentChart" height="80"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">Status Pembayaran</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="paymentChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- KELAS HARI INI -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">Kelas Hari Ini</h5>
                            </div>
                            <div class="card-body">
                                <div class="list-group list-group-flush">
                                    <?php while ($row = $kelas_hari_ini->fetch_assoc()): ?>
                                        <div class="list-group-item d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-1"><?= $row['nama_mapel'] ?> - <?= $row['nama_kelas'] ?></h6>
                                                <small class="text-muted"><?= substr($row['jam_mulai'], 0, 5) ?> - <?= substr($row['jam_selesai'], 0, 5) ?> | Tutor: <?= $row['nama_tutor'] ?></small>
                                            </div>
                                            <span class="badge <?= $row['status'] == 'aktif' ? 'bg-success' : 'bg-secondary' ?>"><?= $row['status'] == 'aktif' ? 'Berlangsung' : 'Selesai' ?></span>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- AKTIVITAS TERBARU -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">Aktivitas Terbaru</h5>
                            </div>
                            <div class="card-body">
                                <div class="list-group list-group-flush">
                                    <!-- Siswa baru -->
                                    <?php while ($row = $aktivitas_siswa->fetch_assoc()): ?>
                                        <div class="list-group-item d-flex align-items-center">
                                            <i class="bi bi-person-plus-fill text-primary me-3"></i>
                                            <div>
                                                <p class="mb-0">Siswa baru terdaftar: <?= $row['nama'] ?></p>
                                                <small class="text-muted"><?= date('d M Y', strtotime($row['tgl_daftar'])) ?></small>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>

                                    <!-- Pembayaran terbaru -->
                                    <?php while ($row = $aktivitas_pembayaran->fetch_assoc()): ?>
                                        <div class="list-group-item d-flex align-items-center">
                                            <i class="bi bi-cash text-success me-3"></i>
                                            <div>
                                                <p class="mb-0">Pembayaran dari <?= $row['nama'] ?> (<?= $row['status'] ?>)</p>
                                                <small class="text-muted"><?= date('d M Y', strtotime($row['tgl_bayar'])) ?></small>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>

                                    <!-- Materi terbaru -->
                                    <?php while ($row = $aktivitas_materi->fetch_assoc()): ?>
                                        <div class="list-group-item d-flex align-items-center">
                                            <i class="bi bi-file-earmark-text text-info me-3"></i>
                                            <div>
                                                <p class="mb-0">Materi baru diupload: <?= $row['judul'] ?> (<?= $row['nama_kelas'] ?>)</p>
                                                <small class="text-muted"><?= date('d M Y', strtotime($row['tgl_upload'])) ?></small>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>

                                    <!-- Presensi terbaru -->
                                    <?php while ($row = $aktivitas_presensi->fetch_assoc()): ?>
                                        <div class="list-group-item d-flex align-items-center">
                                            <i class="bi bi-check-circle text-warning me-3"></i>
                                            <div>
                                                <p class="mb-0">
                                                    Presensi: <?= $row['nama'] ?>
                                                    <span class="badge <?= $badgeClass[$row['status']] ?? 'bg-secondary' ?>">
                                                        <?= $row['status'] ?>
                                                    </span>
                                                </p>
                                                <small class="text-muted">
                                                    <?= date('d M Y', strtotime($row['tanggal'])) ?>
                                                </small>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>

                                    <!-- Tutor baru -->
                                    <?php while ($row = $aktivitas_tutor->fetch_assoc()): ?>
                                        <div class="list-group-item d-flex align-items-center">
                                            <i class="bi bi-person-badge-fill text-success me-3"></i>
                                            <div>
                                                <p class="mb-0">Tutor baru ditambahkan: <?= $row['nama_tutor'] ?></p>
                                                <small class="text-muted">
                                                    <?= date('d M Y', strtotime($row['created_at'])) ?>
                                                </small>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Chart Pendaftaran Siswa
        const ctxStudent = document.getElementById('studentChart').getContext('2d');
        new Chart(ctxStudent, {
            type: 'line',
            data: {
                labels: <?= json_encode($student_chart_labels) ?>,
                datasets: [{
                    label: 'Jumlah Siswa',
                    data: <?= json_encode($student_chart_data) ?>,
                    borderColor: 'rgba(54,162,235,1)',
                    backgroundColor: 'rgba(54,162,235,0.2)',
                    tension: 0.3
                }]
            },
            options: {
                responsive: true
            }
        });

        // Chart Status Pembayaran
        const ctxPayment = document.getElementById('paymentChart').getContext('2d');
        new Chart(ctxPayment, {
            type: 'doughnut',
            data: {
                labels: ['Lunas', 'Pending'],
                datasets: [{
                    data: [<?= $total_lunas ?>, <?= $total_pending ?>],
                    backgroundColor: ['#198754', '#ffc107']
                }]
            },
            options: {
                responsive: true
            }
        });
    </script>

</body>

</html>