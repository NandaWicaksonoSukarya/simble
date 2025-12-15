<?php
session_start();
require "../config.php";

// Cek login siswa
if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'siswa') {
    header("Location: ../index.php");
    exit;
}

$id_siswa = $_SESSION['id_user'] ?? $_SESSION['id_siswa'] ?? null;

if (!$id_siswa) {
    header("Location: ../index.php");
    exit;
}

// Helper function untuk sanitasi output
function h($string)
{
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

// Ambil data siswa
$querySiswa = "SELECT * FROM siswa WHERE id_siswa = '$id_siswa' AND status_aktif = 'aktif'";
$resultSiswa = mysqli_query($conn, $querySiswa);
$student = mysqli_fetch_assoc($resultSiswa);

if (!$student) {
    session_destroy();
    header("Location: ../index.php");
    exit;
}

// Ambil nama kelas
$nama_kelas = '-';
if (!empty($student['id_kelas'])) {
    $queryKelas = "SELECT nama_kelas FROM kelas WHERE id_kelas = '" . $student['id_kelas'] . "'";
    $resultKelas = mysqli_query($conn, $queryKelas);
    if ($rowKelas = mysqli_fetch_assoc($resultKelas)) {
        $nama_kelas = $rowKelas['nama_kelas'];
    }
}

// Hitung kehadiran persentase
$attendancePercent = '0%';
$queryKehadiran = "SELECT 
    SUM(CASE WHEN status = 'Hadir' THEN 1 ELSE 0 END) as hadir,
    COUNT(*) as total
    FROM presensi WHERE id_siswa = '$id_siswa'";
$resultKehadiran = mysqli_query($conn, $queryKehadiran);
if ($rowKehadiran = mysqli_fetch_assoc($resultKehadiran)) {
    $hadir = (int)$rowKehadiran['hadir'];
    $total = (int)$rowKehadiran['total'];
    if ($total > 0) {
        $attendancePercent = round(($hadir / $total) * 100) . '%';
    }
}

$avgGrade = 0;

$queryNilai = "
    SELECT AVG(nilai) AS avg_nilai 
    FROM penilaian_tugas 
    WHERE id_siswa = '$id_siswa' 
    AND nilai IS NOT NULL
";

$resultNilai = mysqli_query($conn, $queryNilai);

if ($resultNilai && mysqli_num_rows($resultNilai) > 0) {
    $rowNilai = mysqli_fetch_assoc($resultNilai);
    if ($rowNilai['avg_nilai'] !== null) {
        $avgGrade = round($rowNilai['avg_nilai'], 1);
    }
}


// Hitung jumlah kelas minggu ini
$classesThisWeek = 0;
$queryClassesWeek = "SELECT COUNT(*) as total FROM jadwal j
    JOIN kelas k ON j.id_kelas = k.id_kelas
    WHERE k.id_kelas = '" . ($student['id_kelas'] ?? '') . "'
    AND j.tanggal BETWEEN DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY) 
    AND DATE_ADD(CURDATE(), INTERVAL (6 - WEEKDAY(CURDATE())) DAY)";
$resultClassesWeek = mysqli_query($conn, $queryClassesWeek);
if ($rowClassesWeek = mysqli_fetch_assoc($resultClassesWeek)) {
    $classesThisWeek = (int)$rowClassesWeek['total'];
}

// Ambil tugas aktif (belum deadline atau deadline masih valid)
$activeTasks = [];
$queryTasks = "SELECT t.*, m.nama_mapel 
    FROM tugas t
    LEFT JOIN mapel m ON t.id_mapel = m.id_mapel
    WHERE t.id_kelas = '" . ($student['id_kelas'] ?? '') . "'
    AND (t.deadline IS NULL OR t.deadline >= CURDATE())
    AND (t.status IS NULL OR t.status = 'aktif')
    ORDER BY t.deadline ASC
    LIMIT 5";
$resultTasks = mysqli_query($conn, $queryTasks);
while ($rowTask = mysqli_fetch_assoc($resultTasks)) {
    $activeTasks[] = $rowTask;
}

// Hitung materi tersedia
$materiCount = 0;
$queryMateri = "SELECT COUNT(*) as total FROM materi 
    WHERE id_kelas = '" . ($student['id_kelas'] ?? '') . "'
    AND (status IS NULL OR status = 'aktif')";
$resultMateri = mysqli_query($conn, $queryMateri);
if ($rowMateri = mysqli_fetch_assoc($resultMateri)) {
    $materiCount = (int)$rowMateri['total'];
}

// Ambil status pembayaran terbaru
$paymentStatus = 'Belum Bayar';
$queryPayment = "SELECT status FROM pembayaran 
    WHERE id_siswa = '$id_siswa' 
    ORDER BY created_at DESC LIMIT 1";
$resultPayment = mysqli_query($conn, $queryPayment);
if ($rowPayment = mysqli_fetch_assoc($resultPayment)) {
    $paymentStatus = $rowPayment['status'] == 'lunas' ? 'Lunas' : 'Belum Lunas';
}

// Ambil jadwal hari ini
$jadwalToday = [];
$today = date('Y-m-d');
$queryJadwal = "SELECT 
    j.*, 
    m.nama_mapel as mapel,
    t.nama_tutor as tutor,
    r.nama_ruangan as ruangan
    FROM jadwal j
    LEFT JOIN mapel m ON j.id_mapel = m.id_mapel
    LEFT JOIN tutor t ON j.id_tutor = t.id_tutor
    LEFT JOIN ruangan r ON j.id_ruangan = r.id_ruangan
    WHERE j.id_kelas = '" . ($student['id_kelas'] ?? '') . "'
    AND j.tanggal = '$today'
    ORDER BY j.jam_mulai ASC";
$resultJadwal = mysqli_query($conn, $queryJadwal);
while ($rowJadwal = mysqli_fetch_assoc($resultJadwal)) {
    $jadwalToday[] = $rowJadwal;
}

// Ambil notifikasi (dari log_aktivitas yang relevan)
$notifications = [];
$queryNotif = "SELECT * FROM log_aktivitas 
    WHERE (user_id = '$id_siswa' OR user_role = 'siswa')
    ORDER BY created_at DESC 
    LIMIT 5";
$resultNotif = mysqli_query($conn, $queryNotif);
while ($rowNotif = mysqli_fetch_assoc($resultNotif)) {
    $notifications[] = [
        'title' => $rowNotif['aktivitas'],
        'message' => $rowNotif['detail'],
        'created_at' => $rowNotif['created_at']
    ];
}

// Ambil nilai terbaru
$latestScores = [];
$queryScores = "SELECT 
    pt.nilai,
    pt.created_at,
    t.judul as tugas_judul,
    m.nama_mapel as mapel
    FROM penilaian_tugas pt
    LEFT JOIN tugas t ON pt.id_tugas = t.id_tugas
    LEFT JOIN mapel m ON t.id_mapel = m.id_mapel
    WHERE pt.id_siswa = '$id_siswa'
    AND pt.nilai IS NOT NULL
    ORDER BY pt.created_at DESC
    LIMIT 5";
$resultScores = mysqli_query($conn, $queryScores);
while ($rowScore = mysqli_fetch_assoc($resultScores)) {
    $latestScores[] = [
        'nilai' => $rowScore['nilai'],
        'mapel' => $rowScore['mapel'],
        'jenis' => $rowScore['tugas_judul']
    ];
}

// Jika tidak ada nilai, buat placeholder untuk UI
if (empty($latestScores)) {
    $latestScores = [
        ['mapel' => 'Matematika', 'jenis' => 'Belum ada nilai', 'nilai' => '-'],
        ['mapel' => 'Fisika', 'jenis' => 'Belum ada nilai', 'nilai' => '-']
    ];
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Siswa - Sistem Informasi Bimbel</title>
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
                    <a class="nav-link active" href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                    <a class="nav-link" href="jadwal.php"><i class="bi bi-calendar3"></i> Jadwal Kelas</a>
                    <a class="nav-link" href="materi.php"><i class="bi bi-journal-text"></i> Materi</a>
                    <a class="nav-link" href="tugas.php"><i class="bi bi-clipboard-check"></i> Tugas</a>
                    <a class="nav-link" href="nilai.php"><i class="bi bi-bar-chart"></i> Nilai</a>
                    <a class="nav-link" href="pembayaran.php"><i class="bi bi-cash-coin"></i> Pembayaran</a>
                    <a class="nav-link" href="profil.php"><i class="bi bi-person"></i> Profil</a>
                    <a class="nav-link" href="../logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 content-wrapper">
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2>Dashboard Siswa</h2>
                        <p class="text-muted">Selamat datang, <?= h($student['nama']) ?>!</p>
                    </div>
                    <div>
                        <span class="badge bg-success">Siswa Aktif</span>
                    </div>
                </div>

                <!-- Profile Card -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-2 text-center">
                                <img src="https://ui-avatars.com/api/?name=<?= urlencode($student['nama']) ?>&size=100&background=0D6EFD&color=fff" class="rounded-circle" alt="Profile">
                            </div>
                            <div class="col-md-7">
                                <h4><?= h($student['nama']) ?></h4>
                                <p class="mb-1"><i class="bi bi-card-text"></i> NIB: <?= h($student['nib'] ?? '-') ?></p>
                                <p class="mb-1"><i class="bi bi-book"></i> Kelas: <?= h($nama_kelas) ?></p>
                                <p class="mb-0"><i class="bi bi-envelope"></i> <?= h($student['email'] ?? '-') ?></p>
                            </div>
                            <div class="col-md-3 text-end">
                                <div class="mb-2">
                                    <small class="text-muted">Kehadiran</small>
                                    <h4 class="text-success"><?= h($attendancePercent) ?></h4>
                                </div>
                                <div>
                                    <small class="text-muted">Rata-rata Nilai</small>
                                    <h4 class="text-primary"><?= is_numeric($avgGrade) ? h($avgGrade) : '-' ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3 class="text-primary"><?= h($classesThisWeek) ?></h3>
                                <p class="text-muted mb-0">Kelas Minggu Ini</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3 class="text-warning"><?= h(count($activeTasks)) ?></h3>
                                <p class="text-muted mb-0">Tugas Aktif</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3 class="text-success"><?= h($materiCount) ?></h3>
                                <p class="text-muted mb-0">Materi Tersedia</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3 class="text-info"><?= h($paymentStatus) ?></h3>
                                <p class="text-muted mb-0">Status Pembayaran</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Jadwal Hari Ini -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">Jadwal Kelas Hari Ini</h5>
                            </div>
                            <div class="card-body">
                                <div class="list-group list-group-flush">
                                    <?php if (!empty($jadwalToday)): ?>
                                        <?php foreach ($jadwalToday as $j):
                                            $statusBadge = 'bg-warning';
                                            if (isset($j['status']) && in_array($j['status'], ['Berjalan', 'Aktif', 'Selesai'])) $statusBadge = ($j['status'] == 'Selesai') ? 'bg-secondary' : 'bg-success';
                                        ?>
                                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-1"><?= h($j['mapel']) ?></h6>
                                                    <small class="text-muted">
                                                        <i class="bi bi-clock"></i> <?= h(substr($j['jam_mulai'] ?? '', 0, 5)) ?> - <?= h(substr($j['jam_selesai'] ?? '', 0, 5)) ?> |
                                                        <i class="bi bi-person"></i> <?= h($j['tutor'] ?? '-') ?> |
                                                        <i class="bi bi-geo-alt"></i> <?= h($j['ruangan'] ?? '-') ?>
                                                    </small>
                                                </div>
                                                <span class="badge <?= $statusBadge ?>"><?= h($j['status'] ?? 'Jadwal') ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="list-group-item">
                                            <div class="text-muted">Tidak ada jadwal untuk hari ini.</div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Notifikasi -->
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">Notifikasi</h5>
                            </div>
                            <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                                <div class="list-group list-group-flush">
                                    <?php if (!empty($notifications)): ?>
                                        <?php foreach ($notifications as $n): ?>
                                            <div class="list-group-item px-0">
                                                <div class="d-flex align-items-start">
                                                    <i class="bi bi-bell-fill text-warning me-2"></i>
                                                    <div>
                                                        <p class="mb-1 small"><strong><?= h($n['title']) ?></strong></p>
                                                        <p class="mb-0 small text-muted"><?= h($n['message']) ?></p>
                                                        <small class="text-muted"><?= h(date('j M Y, H:i', strtotime($n['created_at'] ?? date('Y-m-d H:i')))) ?></small>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="list-group-item px-0">
                                            <div class="text-muted small">Belum ada notifikasi.</div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tugas & Nilai -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Tugas Aktif</h5>
                                <a href="tugas.php" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
                            </div>
                            <div class="card-body">
                                <div class="list-group list-group-flush">
                                    <?php if (!empty($activeTasks)): ?>
                                        <?php foreach ($activeTasks as $t):
                                            $deadline = $t['deadline'];
                                            $diff = $deadline ? (strtotime($deadline) - time()) : null;
                                            $badge = 'bg-success';
                                            if ($diff !== null && $diff < 86400) $badge = 'bg-danger'; // <1 day
                                            elseif ($diff !== null && $diff < 3 * 86400) $badge = 'bg-warning';
                                        ?>
                                            <div class="list-group-item px-0">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <h6 class="mb-1"><?= h($t['judul']) ?></h6>
                                                        <small class="text-muted"><?= h($t['nama_mapel'] ?? '-') ?></small>
                                                    </div>
                                                    <span class="badge <?= $badge ?>"><?= $deadline ? ((strtotime($deadline) - time() < 86400) ? 'Besok' : ((strtotime($deadline) - time() < 3 * 86400) ? '3 Hari' : 'Menunggu')) : 'Menunggu' ?></span>
                                                </div>
                                                <div class="progress mt-2" style="height: 5px;">
                                                    <?php
                                                    // approximate progress based on time to deadline (visual only)
                                                    $progress = 0;
                                                    if ($deadline) {
                                                        $totalSpan = max(1, strtotime($deadline) - strtotime($t['created_at'] ?? date('Y-m-d H:i:s')));
                                                        $past = max(0, time() - strtotime($t['created_at'] ?? date('Y-m-d H:i:s')));
                                                        $progress = min(100, round(($past / $totalSpan) * 100));
                                                    }
                                                    ?>
                                                    <div class="progress-bar <?= $badge ?>" style="width: <?= $progress ?>%"></div>
                                                </div>
                                                <small class="text-muted">Deadline: <?= $deadline ? h(date('j M Y', strtotime($deadline))) : '-' ?></small>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="list-group-item px-0">
                                            <div class="text-muted">Tidak ada tugas aktif.</div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Nilai Terbaru</h5>
                                <a href="nilai.php" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Mata Pelajaran</th>
                                                <th>Tugas</th>
                                                <th>Nilai</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($latestScores)): ?>
                                                <?php foreach ($latestScores as $ls): ?>
                                                    <tr>
                                                        <td><?= h($ls['mapel']) ?></td>
                                                        <td><?= h($ls['jenis'] ?? '-') ?></td>
                                                        <td><span class="badge <?= ((int)$ls['nilai'] >= 80) ? 'bg-success' : 'bg-warning' ?>"><?= h($ls['nilai']) ?></span></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <!-- fallback: show empty or placeholder rows similar to original UI -->
                                                <tr>
                                                    <td>Matematika</td>
                                                    <td>Quiz Bab 4</td>
                                                    <td><span class="badge bg-success">-</span></td>
                                                </tr>
                                                <tr>
                                                    <td>Fisika</td>
                                                    <td>Tugas Gelombang</td>
                                                    <td><span class="badge bg-success">-</span></td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>