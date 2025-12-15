<?php
session_start();
require "../config.php";

function h($str)
{
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}


/* =========================
   PROTEKSI LOGIN TUTOR
========================= */
if (!isset($_SESSION['login']) || $_SESSION['role'] !== "tutor") {
    header("Location: ../index.php");
    exit;
}

/* =========================
   AMBIL USERNAME LOGIN
========================= */
$username = $_SESSION['username'] ?? '';
if ($username === '') {
    session_destroy();
    header("Location: ../index.php");
    exit;
}

$username = mysqli_real_escape_string($conn, $username);

/* =========================
   AMBIL DATA TUTOR
   (users → tutor → mapel)
========================= */
$q = mysqli_query($conn, "
    SELECT 
        t.*,
        t.nama_tutor,
        m.nama_mapel,
        u.username
    FROM users u
    INNER JOIN tutor t ON u.id_tutor = t.id_tutor
    LEFT JOIN mapel m ON t.id_mapel = m.id_mapel
    WHERE u.username = '$username'
    LIMIT 1
");

if (!$q || mysqli_num_rows($q) === 0) {
    echo "Data tutor tidak ditemukan. Hubungi admin.";
    exit;
}

$tutor = mysqli_fetch_assoc($q);

/* =========================
   VARIABEL UTAMA
========================= */
$id_tutor   = (int)$tutor['id_tutor'];
$tutor_nama = $tutor['nama_tutor'];


/**
 * Bulan kalender (GET ?month=2024-12)
 */
$month_param = isset($_GET['month']) ? preg_replace('/[^0-9\-]/', '', $_GET['month']) : date('Y-m');
$month_ts = strtotime($month_param . '-01');

if (!$month_ts) $month_ts = time();

$year  = date('Y', $month_ts);
$month = date('m', $month_ts);

/**
 * Tentukan minggu ini (Senin–Minggu)
 */
$today    = date('Y-m-d');
$monday   = date('Y-m-d', strtotime('monday this week'));
$sunday   = date('Y-m-d', strtotime('sunday this week'));

/**
 * Statistik minggu ini
 */
$q_week = mysqli_query($conn, "
    SELECT 
        COUNT(*) AS total_kelas,
        SUM(TIME_TO_SEC(TIMEDIFF(jam_selesai, jam_mulai))/3600) AS total_jam
    FROM jadwal
    WHERE id_tutor = '$id_tutor'
    AND tanggal BETWEEN '$monday' AND '$sunday'
");

$week_data  = mysqli_fetch_assoc($q_week);
$kelas_minggu = (int)($week_data['total_kelas'] ?? 0);
$jam_minggu   = $week_data['total_jam'] ? round($week_data['total_jam'], 2) : 0;

/**
 * Statistik hari ini
 */
$q_today = mysqli_query($conn, "
    SELECT COUNT(*) AS c
    FROM jadwal
    WHERE id_tutor = '$id_tutor'
    AND tanggal = '$today'
");

$kelas_hari = (int)mysqli_fetch_assoc($q_today)['c'];

/**
 * Total siswa yang diajar tutor
 */
$q_siswa = mysqli_query($conn, "
    SELECT COUNT(*) AS total_siswa
    FROM siswa s
    INNER JOIN kelas k ON s.id_kelas = k.id_kelas
    WHERE k.id_tutor = '$id_tutor' 
    AND s.status_aktif = 'Aktif'
");
$total_siswa = (int)mysqli_fetch_assoc($q_siswa)['total_siswa'];

/**
 * Jadwal untuk halaman utama tutor
 */
$qList = $conn->query("
    SELECT j.*, k.nama_kelas, m.nama_mapel, r.nama_ruangan
    FROM jadwal j
    JOIN kelas k ON j.id_kelas = k.id_kelas
    JOIN mapel m ON j.id_mapel = m.id_mapel
    LEFT JOIN ruangan r ON j.id_ruangan = r.id_ruangan
    WHERE j.id_tutor = '$id_tutor'
    ORDER BY j.tanggal, j.jam_mulai
    LIMIT 10
");

/**
 * Ambil jadwal untuk bulan ini (kalender)
 */
$startOfMonth = "$year-$month-01";
$endOfMonth   = date("Y-m-t", strtotime($startOfMonth));

$qMonth = $conn->query("
    SELECT j.*, k.nama_kelas, m.nama_mapel
    FROM jadwal j
    JOIN kelas k ON j.id_kelas = k.id_kelas
    JOIN mapel m ON j.id_mapel = m.id_mapel
    WHERE j.id_tutor = '$id_tutor'
    AND j.tanggal BETWEEN '$startOfMonth' AND '$endOfMonth'
    ORDER BY j.tanggal, j.jam_mulai
");

$events_by_day = [];
while ($r = $qMonth->fetch_assoc()) {
    $events_by_day[$r['tanggal']][] = $r;
}

function fmt_time($time)
{
    return date("H:i", strtotime($time));
}

/**
 * Jadwal minggu ini (table list)
 */
$q_week_list = $conn->query("
    SELECT j.*, k.nama_kelas, m.nama_mapel, r.nama_ruangan
    FROM jadwal j
    JOIN kelas k ON j.id_kelas = k.id_kelas
    JOIN mapel m ON j.id_mapel = m.id_mapel
    LEFT JOIN ruangan r ON j.id_ruangan = r.id_ruangan
    WHERE j.id_tutor = '$id_tutor'
    AND j.tanggal BETWEEN '$monday' AND '$sunday'
    ORDER BY j.tanggal, j.jam_mulai
");

$month_label = date("F Y", strtotime("$year-$month-01"));

/**
 * Generate kalender
 */
function generate_month_grid($year, $month)
{
    $first = strtotime("$year-$month-01");
    $firstWeekday = (int)date('N', $first); // 1-7 (Mon–Sun)
    $days = (int)date('t', $first);

    $grid = [];
    $week = array_fill(0, 7, null);

    $day = 1;

    // week 1
    for ($i = $firstWeekday - 1; $i < 7; $i++) {
        $week[$i] = $day++;
    }
    $grid[] = $week;

    // next weeks
    while ($day <= $days) {
        $week = array_fill(0, 7, null);
        for ($i = 0; $i < 7 && $day <= $days; $i++) {
            $week[$i] = $day++;
        }
        $grid[] = $week;
    }

    return $grid;
}

$month_grid = generate_month_grid($year, $month);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal Mengajar - Sistem Informasi Bimbel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .calendar-day {
            vertical-align: top;
            height: 120px;
            width: 14%;
        }

        .calendar-event {
            margin-top: 6px;
            padding: 4px 6px;
            border-radius: 6px;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .calendar-event:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .calendar-day strong {
            display: block;
            margin-bottom: 6px;
        }
        
        .table-responsive {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .stat-card {
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card .icon {
            font-size: 2rem;
            opacity: 0.8;
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
                    <a class="nav-link active" href="jadwal.php"><i class="bi bi-calendar3"></i> Jadwal Mengajar</a>
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
                <div class="page-header mb-4">
                    <h2><i class="bi bi-calendar3 me-2"></i>Jadwal Mengajar</h2>
                    <p class="text-muted">Kelola jadwal mengajar Anda, <?= h($tutor_nama) ?></p>
                </div>

                <!-- Stats -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card stat-card bg-primary text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h3 class="mb-0"><?= h($kelas_minggu) ?></h3>
                                    <p class="mb-0">Kelas Minggu Ini</p>
                                </div>
                                <i class="bi bi-calendar3 icon"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card bg-success text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h3 class="mb-0"><?= h($kelas_hari) ?></h3>
                                    <p class="mb-0">Kelas Hari Ini</p>
                                </div>
                                <i class="bi bi-calendar-check icon"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card bg-warning text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h3 class="mb-0"><?= h($total_siswa) ?></h3>
                                    <p class="mb-0">Total Siswa</p>
                                </div>
                                <i class="bi bi-people icon"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card bg-info text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h3 class="mb-0"><?= h($jam_minggu) ?></h3>
                                    <p class="mb-0">Jam/Minggu</p>
                                </div>
                                <i class="bi bi-clock icon"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Calendar View -->
                <div class="card mb-4">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-calendar-month me-2"></i>Kalender <?= h($month_label) ?></h5>
                        <div>
                            <?php
                            // prev and next month links
                            $prev = date('Y-m', strtotime("-1 month", $month_ts));
                            $next = date('Y-m', strtotime("+1 month", $month_ts));
                            ?>
                            <a class="btn btn-sm btn-outline-primary me-2" href="?month=<?= h($prev) ?>">
                                <i class="bi bi-chevron-left"></i> Bulan Sebelumnya
                            </a>
                            <a class="btn btn-sm btn-outline-primary" href="?month=<?= h($next) ?>">
                                Bulan Berikutnya <i class="bi bi-chevron-right"></i>
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-center">Senin</th>
                                        <th class="text-center">Selasa</th>
                                        <th class="text-center">Rabu</th>
                                        <th class="text-center">Kamis</th>
                                        <th class="text-center">Jumat</th>
                                        <th class="text-center">Sabtu</th>
                                        <th class="text-center">Minggu</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($month_grid as $week): ?>
                                        <tr>
                                            <?php foreach ($week as $d): ?>
                                                <?php if ($d === null): ?>
                                                    <td class="calendar-day bg-light"></td>
                                                <?php else:
                                                    $date_str = sprintf('%04d-%02d-%02d', $year, $month, $d);
                                                    $events = isset($events_by_day[$date_str]) ? $events_by_day[$date_str] : [];
                                                    $is_today = ($date_str == $today) ? 'bg-info text-white' : '';
                                                ?>
                                                    <td class="calendar-day <?= $is_today ?>">
                                                        <strong><?= h($d) ?></strong>
                                                        <?php if ($date_str == $today): ?>
                                                            <small class="badge bg-danger">Hari Ini</small>
                                                        <?php endif; ?>
                                                        <?php foreach ($events as $ev):
                                                            // choose badge color by mapel
                                                            $cls = 'bg-primary text-white';
                                                            $m = strtolower($ev['nama_mapel']);
                                                            if (strpos($m, 'fisika') !== false) $cls = 'bg-success text-white';
                                                            elseif (strpos($m, 'kimia') !== false) $cls = 'bg-warning text-dark';
                                                            elseif (strpos($m, 'biologi') !== false) $cls = 'bg-danger text-white';
                                                            elseif (strpos($m, 'matematika') !== false) $cls = 'bg-info text-white';
                                                            elseif (strpos($m, 'bahasa') !== false) $cls = 'bg-secondary text-white';
                                                        ?>
                                                            <div class="calendar-event <?= h($cls) ?>" 
                                                                 title="<?= h($ev['nama_kelas']) ?> - <?= h($ev['nama_mapel']) ?>\n<?= fmt_time($ev['jam_mulai']) ?>-<?= fmt_time($ev['jam_selesai']) ?>"
                                                                 data-bs-toggle="tooltip" data-bs-placement="top">
                                                                <small><?= h(substr($ev['nama_mapel'], 0, 15)) ?>...</small><br>
                                                                <small><?= fmt_time($ev['jam_mulai']) ?>-<?= fmt_time($ev['jam_selesai']) ?></small>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </td>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Jadwal Minggu Ini -->
                <div class="card">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-calendar-week me-2"></i>Jadwal Mengajar Minggu Ini</h5>
                        <div class="text-muted">
                            <?= h(date('d M Y', strtotime($monday))) ?> - <?= h(date('d M Y', strtotime($sunday))) ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Hari</th>
                                        <th>Tanggal</th>
                                        <th>Waktu</th>
                                        <th>Kelas</th>
                                        <th>Mata Pelajaran</th>
                                        <th>Ruangan</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $has_any = false;
                                    while ($w = $q_week_list->fetch_assoc()):
                                        $has_any = true;
                                        $hari_label = date('l', strtotime($w['tanggal']));
                                        $mapDay = [
                                            'Monday' => 'Senin',
                                            'Tuesday' => 'Selasa',
                                            'Wednesday' => 'Rabu',
                                            'Thursday' => 'Kamis',
                                            'Friday' => 'Jumat',
                                            'Saturday' => 'Sabtu',
                                            'Sunday' => 'Minggu'
                                        ];
                                        $hari_id = isset($mapDay[$hari_label]) ? $mapDay[$hari_label] : $hari_label;
                                        
                                        // Tentukan status berdasarkan waktu
                                        $jam_sekarang = date('H:i');
                                        $tanggal_sekarang = date('Y-m-d');
                                        $status = '';
                                        $badge_class = 'bg-secondary';
                                        
                                        if ($w['tanggal'] < $tanggal_sekarang) {
                                            $status = 'Selesai';
                                            $badge_class = 'bg-success';
                                        } elseif ($w['tanggal'] > $tanggal_sekarang) {
                                            $status = 'Akan Datang';
                                            $badge_class = 'bg-warning';
                                        } else {
                                            if ($w['jam_mulai'] > $jam_sekarang) {
                                                $status = 'Akan Datang';
                                                $badge_class = 'bg-warning';
                                            } elseif ($w['jam_mulai'] <= $jam_sekarang && $w['jam_selesai'] >= $jam_sekarang) {
                                                $status = 'Berlangsung';
                                                $badge_class = 'bg-primary';
                                            } else {
                                                $status = 'Selesai';
                                                $badge_class = 'bg-success';
                                            }
                                        }
                                    ?>
                                        <tr class="<?= ($status === 'Berlangsung') ? 'table-primary' : '' ?>">
                                            <td><?= h($hari_id) ?></td>
                                            <td><?= date('d-m-Y', strtotime($w['tanggal'])) ?></td>
                                            <td><?= h(fmt_time($w['jam_mulai'])) ?> - <?= h(fmt_time($w['jam_selesai'])) ?></td>
                                            <td><?= h($w['nama_kelas']) ?></td>
                                            <td><?= h($w['nama_mapel']) ?></td>
                                            <td><?= h($w['nama_ruangan'] ?? '-') ?></td>
                                            <td>
                                                <span class="badge <?= $badge_class ?>"><?= h($status) ?></span>
                                            </td>
                                            <td>
                                                <?php if ($status === 'Berlangsung' || ($w['tanggal'] == $today && $w['jam_mulai'] <= $jam_sekarang && $w['jam_selesai'] >= $jam_sekarang)): ?>
                                                    <a href="presensi.php?jadwal=<?= h($w['id_jadwal']) ?>" class="btn btn-sm btn-success">
                                                        <i class="bi bi-check2-square"></i> Presensi
                                                    </a>
                                                <?php elseif ($status === 'Akan Datang'): ?>
                                                    <button class="btn btn-sm btn-outline-warning" disabled>
                                                        <i class="bi bi-clock"></i> Menunggu
                                                    </button>
                                                <?php else: ?>
                                                    <a href="presensi.php?jadwal=<?= h($w['id_jadwal']) ?>&view=true" class="btn btn-sm btn-outline-info">
                                                        <i class="bi bi-eye"></i> Lihat
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>

                                    <?php if (!$has_any): ?>
                                        <tr>
                                            <td colspan="8" class="text-center text-muted py-4">
                                                <i class="bi bi-calendar-x" style="font-size: 2rem;"></i>
                                                <p class="mt-2">Tidak ada jadwal untuk minggu ini.</p>
                                            </td>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
    </script>
</body>

</html>