<?php
session_start();
// echo "<pre>";
// print_r($_SESSION);
// echo "</pre>";
// exit;

require "../config.php";

// proteksi login tutor
if (!isset($_SESSION['login']) || $_SESSION['role'] !== "tutor") {
    var_dump($_SESSION);
    exit;

    header("Location: ../index.php");
    exit;
}

$username = $_SESSION['username'] ?? "";
if ($username === "") {
    echo "Session username tutor tidak tersedia. Silakan logout lalu login ulang.";
    exit;
}

$username = mysqli_real_escape_string($conn, $username);

// ambil data tutor berdasarkan username dari tabel users
$q = mysqli_query($conn, "
    SELECT t.*, m.nama_mapel, u.username
    FROM tutor t
    INNER JOIN users u ON t.id_tutor = u.id_tutor
    LEFT JOIN mapel m ON t.id_mapel = m.id_mapel
    WHERE u.username = '$username'
    LIMIT 1
");

if (!$q || mysqli_num_rows($q) === 0) {
    die("Data tutor tidak ditemukan. Hubungi admin.");
}

$tutor = mysqli_fetch_assoc($q);
$tutor_id = $tutor['id_tutor'];
$tutor_nama = $tutor['nama_tutor'];

/* helper */
function h($s)
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

/* hitung jumlah siswa dari kelas yang diampu tutor ini */
$qJumlahSiswa = mysqli_query($conn, "
    SELECT COUNT(*) AS total_siswa
    FROM siswa s
    INNER JOIN kelas k ON s.id_kelas = k.id_kelas
    WHERE k.id_tutor = '$tutor_id' AND s.status_aktif = 'Aktif'
");
$jumlah_siswa = ($qJumlahSiswa) ? (int)mysqli_fetch_assoc($qJumlahSiswa)['total_siswa'] : 0;

/* hitung jumlah kelas dalam seminggu ke depan */
$startOfWeek = date('Y-m-d');
$endOfWeek = date('Y-m-d', strtotime('+6 days'));
$qKelasMinggu = mysqli_query($conn, "
    SELECT COUNT(*) AS total_kelas
    FROM jadwal
    WHERE id_tutor = '$tutor_id' 
      AND tanggal BETWEEN '$startOfWeek' AND '$endOfWeek'
");
$kelas_minggu = ($qKelasMinggu) ? (int)mysqli_fetch_assoc($qKelasMinggu)['total_kelas'] : 0;

/* jumlah materi upload oleh tutor */
$qMat = mysqli_query($conn, "SELECT COUNT(*) AS c FROM materi WHERE id_tutor = '$tutor_id'");
$mat_count = ($qMat ? (int)mysqli_fetch_assoc($qMat)['c'] : 0);

/* jumlah tugas yang dibuat oleh tutor yang status = aktif */
$qTugas = mysqli_query($conn, "
    SELECT COUNT(*) AS c 
    FROM tugas 
    WHERE id_tutor = '$tutor_id' 
      AND (status IS NULL OR status = 'Aktif')
");
$tugas_count = ($qTugas ? (int)mysqli_fetch_assoc($qTugas)['c'] : 0);

/* jumlah penilaian yang belum dinilai */
$qPending = mysqli_query($conn, "
    SELECT COUNT(*) AS c 
    FROM penilaian_tugas pt
    INNER JOIN tugas t ON pt.id_tugas = t.id_tugas
    WHERE t.id_tutor = '$tutor_id' 
      AND (pt.nilai IS NULL OR pt.nilai = '')
");
$pending_count = ($qPending ? (int)mysqli_fetch_assoc($qPending)['c'] : 0);

/* jadwal hari ini untuk tutor */
$hari_ini = date('Y-m-d');
$qJadwalToday = mysqli_query($conn, "
    SELECT j.*, k.nama_kelas, m.nama_mapel, r.nama_ruangan
    FROM jadwal j
    LEFT JOIN kelas k ON j.id_kelas = k.id_kelas
    LEFT JOIN mapel m ON j.id_mapel = m.id_mapel
    LEFT JOIN ruangan r ON j.id_ruangan = r.id_ruangan
    WHERE j.id_tutor = '$tutor_id' 
      AND j.tanggal = '$hari_ini'
    ORDER BY j.jam_mulai ASC
    LIMIT 10
");

/* ambil beberapa jadwal mendatang */
$qJadwalWeek = mysqli_query($conn, "
    SELECT j.*, k.nama_kelas, m.nama_mapel, r.nama_ruangan
    FROM jadwal j
    LEFT JOIN kelas k ON j.id_kelas = k.id_kelas
    LEFT JOIN mapel m ON j.id_mapel = m.id_mapel
    LEFT JOIN ruangan r ON j.id_ruangan = r.id_ruangan
    WHERE j.id_tutor = '$tutor_id' 
      AND j.tanggal >= '$hari_ini'
    ORDER BY j.tanggal ASC, j.jam_mulai ASC
    LIMIT 10
");

/* statistik rata-rata nilai per kelas */
// Hitung rata-rata nilai per kelas yang diampu tutor
$qStatKelas = mysqli_query($conn, "
    SELECT 
        k.nama_kelas,
        AVG(pt.nilai) AS rata_nilai
    FROM kelas k
    LEFT JOIN penilaian_tugas pt ON k.id_kelas = (
        SELECT id_kelas FROM tugas WHERE id_tugas = pt.id_tugas
    )
    WHERE k.id_tutor = '$tutor_id' AND pt.nilai IS NOT NULL
    GROUP BY k.id_kelas
    LIMIT 3
");

// Siapkan array untuk statistik kelas
$stat_kelas = [];
$avg_kelas_12 = 0;
$avg_kelas_11 = 0;
$avg_kelas_10 = 0;

if ($qStatKelas && mysqli_num_rows($qStatKelas) > 0) {
    $counter = 1;
    while ($row = mysqli_fetch_assoc($qStatKelas)) {
        $stat_kelas[] = $row;
        if ($counter == 1) $avg_kelas_12 = round($row['rata_nilai'] ?? 0);
        if ($counter == 2) $avg_kelas_11 = round($row['rata_nilai'] ?? 0);
        if ($counter == 3) $avg_kelas_10 = round($row['rata_nilai'] ?? 0);
        $counter++;
    }
}

/* kehadiran tutor bulan ini */
$bulan_ini = date('Y-m');
$qKehadiran = mysqli_query($conn, "
    SELECT 
        COUNT(*) AS total_hadir,
        (SELECT COUNT(*) FROM jadwal 
         WHERE id_tutor = '$tutor_id' 
         AND DATE_FORMAT(tanggal, '%Y-%m') = '$bulan_ini') AS total_jadwal
    FROM presensi_tutor pt
    INNER JOIN jadwal j ON pt.id_jadwal = j.id_jadwal
    WHERE j.id_tutor = '$tutor_id' 
      AND DATE_FORMAT(pt.created_at, '%Y-%m') = '$bulan_ini'
      AND pt.status = 'Hadir'
");

$kehadiran_pct = 0;
if ($qKehadiran && mysqli_num_rows($qKehadiran) > 0) {
    $data = mysqli_fetch_assoc($qKehadiran);
    $total_hadir = (int)$data['total_hadir'];
    $total_jadwal = (int)$data['total_jadwal'];
    
    if ($total_jadwal > 0) {
        $kehadiran_pct = round(($total_hadir / $total_jadwal) * 100);
    }
}

/* ambil 5 tugas teratas yang perlu dinilai */
$qTugasList = mysqli_query($conn, "
    SELECT 
        t.*,
        k.nama_kelas,
        m.nama_mapel,
        (SELECT COUNT(*) FROM penilaian_tugas WHERE id_tugas = t.id_tugas) AS terkumpul,
        (SELECT COUNT(*) FROM penilaian_tugas 
         WHERE id_tugas = t.id_tugas AND (nilai IS NULL OR nilai = '')) AS belum_dinilai
    FROM tugas t
    LEFT JOIN kelas k ON t.id_kelas = k.id_kelas
    LEFT JOIN mapel m ON t.id_mapel = m.id_mapel
    WHERE t.id_tutor = '$tutor_id'
    ORDER BY t.deadline ASC
    LIMIT 5
");

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Tutor - Sistem Informasi Bimbel</title>
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
                    <small>Portal Tutor</small>
                </div>
                <nav class="nav flex-column">
                    <a class="nav-link active" href="dashboard_tutor.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                    <a class="nav-link" href="jadwal.php"><i class="bi bi-calendar3"></i> Jadwal Mengajar</a>
                    <a class="nav-link" href="presensi.php"><i class="bi bi-check2-square"></i> Presensi</a>
                    <a class="nav-link" href="materi.php"><i class="bi bi-journal-text"></i> Materi</a>
                    <a class="nav-link" href="tugas.php"><i class="bi bi-clipboard-check"></i> Tugas & Penilaian</a>
                    <a class="nav-link" href="siswa.php"><i class="bi bi-people"></i> Data Siswa</a>
                    <a class="nav-link" href="profil.php"><i class="bi bi-person"></i> Profil</a>
                    <a class="nav-link" href="../logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 content-wrapper">
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2>Dashboard Tutor</h2>
                        <p class="text-muted">Selamat datang, <?= h($tutor_nama) ?>!</p>
                    </div>
                    <div>
                        <span class="badge bg-success"><?= h($tutor['status'] ?: 'Aktif') ?></span>
                    </div>
                </div>

                <!-- Profile Card -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-2 text-center">
                                <?php
                                $foto = !empty($tutor['foto']) ? '../uploads/' . h($tutor['foto']) : 
                                        "https://ui-avatars.com/api/?name=" . urlencode($tutor_nama) . "&size=100&background=0D6EFD&color=fff";
                                ?>
                                <img src="<?= h($foto) ?>" class="rounded-circle" width="100" height="100" alt="Profile">
                            </div>
                            <div class="col-md-7">
                                <h4><?= h($tutor_nama) ?></h4>
                                <p class="mb-1"><i class="bi bi-book"></i> Mata Pelajaran: <?= h($tutor['nama_mapel']) ?></p>
                                <p class="mb-1"><i class="bi bi-envelope"></i> <?= h($tutor['email']) ?></p>
                                <p class="mb-0"><i class="bi bi-phone"></i> <?= h($tutor['telepon']) ?></p>
                            </div>
                            <div class="col-md-3 text-end">
                                <div class="mb-2">
                                    <small class="text-muted">Kehadiran Bulan Ini</small>
                                    <?php if ($kehadiran_pct >= 80): ?>
                                        <h4 class="text-success"><?= h($kehadiran_pct) ?>%</h4>
                                    <?php elseif ($kehadiran_pct >= 60): ?>
                                        <h4 class="text-warning"><?= h($kehadiran_pct) ?>%</h4>
                                    <?php else: ?>
                                        <h4 class="text-danger"><?= h($kehadiran_pct) ?>%</h4>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <small class="text-muted">Total Siswa</small>
                                    <h4 class="text-primary"><?= h($jumlah_siswa) ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card stat-card bg-primary text-white">
                            <div class="d-flex justify-content-between align-items-center p-3">
                                <div>
                                    <h3 class="mb-0"><?= h($kelas_minggu) ?></h3>
                                    <p class="mb-0">Kelas Minggu Ini</p>
                                </div>
                                <i class="bi bi-calendar3 icon" style="font-size:1.8rem;"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card bg-warning text-white">
                            <div class="d-flex justify-content-between align-items-center p-3">
                                <div>
                                    <h3 class="mb-0"><?= h($tugas_count) ?></h3>
                                    <p class="mb-0">Tugas Aktif</p>
                                </div>
                                <i class="bi bi-clipboard-check icon" style="font-size:1.8rem;"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card bg-info text-white">
                            <div class="d-flex justify-content-between align-items-center p-3">
                                <div>
                                    <h3 class="mb-0"><?= h($pending_count) ?></h3>
                                    <p class="mb-0">Perlu Dinilai</p>
                                </div>
                                <i class="bi bi-star icon" style="font-size:1.8rem;"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card bg-success text-white">
                            <div class="d-flex justify-content-between align-items-center p-3">
                                <div>
                                    <h3 class="mb-0"><?= h($mat_count) ?></h3>
                                    <p class="mb-0">Materi Diupload</p>
                                </div>
                                <i class="bi bi-journal-text icon" style="font-size:1.8rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Jadwal Hari Ini -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Jadwal Mengajar Hari Ini (<?= date('d-m-Y') ?>)</h5>
                                <a class="btn btn-sm btn-primary" href="presensi.php">Isi Presensi</a>
                            </div>
                            <div class="card-body">
                                <div class="list-group list-group-flush">
                                    <?php if ($qJadwalToday && mysqli_num_rows($qJadwalToday) > 0): ?>
                                        <?php while ($row = mysqli_fetch_assoc($qJadwalToday)): ?>
                                            <?php
                                            // Tentukan status berdasarkan waktu
                                            $jam_sekarang = date('H:i');
                                            $status = '';
                                            if ($row['jam_mulai'] > $jam_sekarang) {
                                                $status = 'Akan Datang';
                                                $badge_class = 'bg-warning';
                                            } elseif ($row['jam_mulai'] <= $jam_sekarang && $row['jam_selesai'] >= $jam_sekarang) {
                                                $status = 'Berlangsung';
                                                $badge_class = 'bg-success';
                                            } else {
                                                $status = 'Selesai';
                                                $badge_class = 'bg-secondary';
                                            }
                                            ?>
                                            <div class="list-group-item">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <h6 class="mb-1"><?= h($row['nama_mapel']) ?> - <?= h($row['nama_kelas']) ?></h6>
                                                        <small class="text-muted">
                                                            <i class="bi bi-clock"></i> <?= substr($row['jam_mulai'], 0, 5) ?> - <?= substr($row['jam_selesai'], 0, 5) ?> |
                                                            <i class="bi bi-geo-alt"></i> <?= h($row['nama_ruangan'] ?: 'Ruang') ?>
                                                        </small>
                                                        <p class="mb-0 mt-2 small">Tanggal: <?= date('d-m-Y', strtotime($row['tanggal'])) ?></p>
                                                    </div>
                                                    <div>
                                                        <span class="badge <?= $badge_class ?> mb-2"><?= $status ?></span>
                                                        <a class="btn btn-sm btn-outline-primary d-block" href="presensi.php?jadwal=<?= h($row['id_jadwal']) ?>">Isi Presensi</a>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <div class="list-group-item text-muted">Tidak ada jadwal hari ini.</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Jadwal Mendatang -->
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">Jadwal Mendatang</h5>
                            </div>
                            <div class="card-body">
                                <div class="list-group list-group-flush">
                                    <?php if ($qJadwalWeek && mysqli_num_rows($qJadwalWeek) > 0): ?>
                                        <?php while ($row = mysqli_fetch_assoc($qJadwalWeek)): ?>
                                            <div class="list-group-item px-0">
                                                <div class="d-flex">
                                                    <div class="flex-shrink-0 text-center me-3" style="width: 60px;">
                                                        <div class="bg-light rounded p-2">
                                                            <small class="text-muted d-block"><?= date('D', strtotime($row['tanggal'])) ?></small>
                                                            <strong class="d-block"><?= date('d', strtotime($row['tanggal'])) ?></strong>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <h6 class="mb-1 small"><?= h($row['nama_mapel']) ?></h6>
                                                        <p class="mb-1 small text-muted">
                                                            <?= substr($row['jam_mulai'], 0, 5) ?> - <?= substr($row['jam_selesai'], 0, 5) ?>
                                                        </p>
                                                        <p class="mb-0 small text-muted"><?= h($row['nama_kelas']) ?></p>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <div class="list-group-item text-muted">Tidak ada jadwal mendatang.</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tugas & Penilaian -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Tugas Perlu Dinilai</h5>
                                <a href="tugas.php" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
                            </div>
                            <div class="card-body">
                                <div class="list-group list-group-flush">
                                    <?php if ($qTugasList && mysqli_num_rows($qTugasList) > 0): ?>
                                        <?php while ($t = mysqli_fetch_assoc($qTugasList)): ?>
                                            <div class="list-group-item px-0">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <h6 class="mb-1"><?= h($t['judul']) ?></h6>
                                                        <small class="text-muted"><?= h($t['nama_kelas']) ?> - <?= h($t['nama_mapel']) ?></small>
                                                        <div class="mt-2">
                                                            <span class="badge bg-info">Deadline: <?= date('d-m-Y', strtotime($t['deadline'])) ?></span>
                                                            <span class="badge bg-warning"><?= h($t['terkumpul']) ?> terkumpul</span>
                                                            <?php if ($t['belum_dinilai'] > 0): ?>
                                                                <span class="badge bg-danger"><?= h($t['belum_dinilai']) ?> belum dinilai</span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <a class="btn btn-sm btn-primary" href="nilai_input.php?tugas_id=<?= h($t['id_tugas']) ?>">Nilai</a>
                                                </div>
                                            </div>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <div class="list-group-item text-muted">Tidak ada tugas yang perlu dinilai.</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">Statistik Kelas</h5>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($stat_kelas)): ?>
                                    <?php foreach ($stat_kelas as $index => $kelas): ?>
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between mb-1">
                                                <span><?= h($kelas['nama_kelas']) ?> - Rata-rata Nilai</span>
                                                <strong><?= round($kelas['rata_nilai']) ?></strong>
                                            </div>
                                            <div class="progress">
                                                <div class="progress-bar 
                                                    <?= $kelas['rata_nilai'] >= 80 ? 'bg-success' : 
                                                       ($kelas['rata_nilai'] >= 70 ? 'bg-warning' : 'bg-danger') ?>" 
                                                    style="width: <?= min(100, $kelas['rata_nilai']) ?>%">
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-muted text-center py-3">Belum ada data nilai</div>
                                <?php endif; ?>
                                <hr>
                                <div class="row text-center">
                                    <div class="col-4">
                                        <?php if ($kehadiran_pct >= 80): ?>
                                            <h4 class="text-success"><?= h($kehadiran_pct) ?>%</h4>
                                        <?php elseif ($kehadiran_pct >= 60): ?>
                                            <h4 class="text-warning"><?= h($kehadiran_pct) ?>%</h4>
                                        <?php else: ?>
                                            <h4 class="text-danger"><?= h($kehadiran_pct) ?>%</h4>
                                        <?php endif; ?>
                                        <small class="text-muted">Kehadiran</small>
                                    </div>
                                    <div class="col-4">
                                        <h4 class="text-primary"><?= h($mat_count) ?></h4>
                                        <small class="text-muted">Materi Diupload</small>
                                    </div>
                                    <div class="col-4">
                                        <h4 class="text-info"><?= h($jumlah_siswa) ?></h4>
                                        <small class="text-muted">Total Siswa</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div> <!-- content-wrapper -->
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>