<?php
session_start();
include "../config.php";

if (!isset($_SESSION['id_siswa'])) {
    die("Error: Session siswa tidak ditemukan. Silakan login ulang.");
}

$id_siswa = intval($_SESSION['id_siswa']);

// ====== Proteksi siswa ======
if (!isset($_SESSION['login']) || $_SESSION['role'] !== "siswa") {
    header("Location: ../index.php");
    exit;
}

// Ambil data siswa dan kelasnya dengan join ke tabel kelas untuk mendapatkan nama_kelas
$querySiswa = mysqli_query($conn, "
    SELECT s.*, k.nama_kelas 
    FROM siswa s 
    LEFT JOIN kelas k ON s.id_kelas = k.id_kelas 
    WHERE s.id_siswa = '$id_siswa'
");
$siswa = mysqli_fetch_assoc($querySiswa);

if (!$siswa) {
    die("Error: Data siswa tidak ditemukan.");
}

$id_kelas = $siswa['id_kelas'];

if (empty($id_kelas)) {
    die("Error: Siswa belum memiliki kelas. Hubungi admin untuk mengatur kelas Anda.");
}

/**
 * Pilihan bulan / tahun kalender
 */
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
$year  = isset($_GET['year'])  ? (int)$_GET['year']  : (int)date('Y');

if ($month < 1 || $month > 12) $month = date('n');
if ($year < 1970 || $year > 2100) $year = date('Y');

$startOfMonth = "$year-$month-01";
$endOfMonth   = date("Y-m-t", strtotime($startOfMonth));

/**
 * Ambil jadwal kelas siswa versi kalender
 */
$qEvents = mysqli_query($conn, "
    SELECT 
        j.*, 
        t.nama_tutor, 
        m.nama_mapel,
        r.nama_ruangan,
        k.nama_kelas
    FROM jadwal j
    LEFT JOIN tutor t ON j.id_tutor = t.id_tutor
    LEFT JOIN mapel m ON j.id_mapel = m.id_mapel
    LEFT JOIN ruangan r ON j.id_ruangan = r.id_ruangan
    LEFT JOIN kelas k ON j.id_kelas = k.id_kelas
    WHERE j.id_kelas = '$id_kelas'
      AND j.tanggal BETWEEN '$startOfMonth' AND '$endOfMonth'
    ORDER BY j.tanggal, j.jam_mulai
");

$events = [];
while ($r = mysqli_fetch_assoc($qEvents)) {
    $events[$r['tanggal']][] = $r;
}

/**
 * List view (tampilan daftar jadwal minggu ini)
 */
// Tentukan awal dan akhir minggu ini
$today = date('Y-m-d');
$startOfWeek = date('Y-m-d', strtotime('monday this week', strtotime($today)));
$endOfWeek = date('Y-m-d', strtotime('sunday this week', strtotime($today)));

$qJadwalMingguIni = mysqli_query($conn, "
    SELECT 
        j.*, 
        m.nama_mapel,
        t.nama_tutor,
        r.nama_ruangan
    FROM jadwal j
    LEFT JOIN mapel m ON j.id_mapel = m.id_mapel
    LEFT JOIN tutor t ON j.id_tutor = t.id_tutor
    LEFT JOIN ruangan r ON j.id_ruangan = r.id_ruangan
    WHERE j.id_kelas = '$id_kelas'
      AND j.tanggal BETWEEN '$startOfWeek' AND '$endOfWeek'
    ORDER BY j.tanggal, j.jam_mulai
");

/**
 * Jadwal semua hari (untuk tabel)
 */
$qAllJadwal = mysqli_query($conn, "
    SELECT 
        j.*, 
        m.nama_mapel,
        t.nama_tutor,
        r.nama_ruangan,
        DAYNAME(j.tanggal) as nama_hari
    FROM jadwal j
    LEFT JOIN mapel m ON j.id_mapel = m.id_mapel
    LEFT JOIN tutor t ON j.id_tutor = t.id_tutor
    LEFT JOIN ruangan r ON j.id_ruangan = r.id_ruangan
    WHERE j.id_kelas = '$id_kelas'
    ORDER BY j.tanggal, j.jam_mulai
");

/**
 * Helper fungsi
 */
function nama_bulan($m)
{
    $n = [
        "",
        "Januari",
        "Februari",
        "Maret",
        "April",
        "Mei",
        "Juni",
        "Juli",
        "Agustus",
        "September",
        "Oktober",
        "November",
        "Desember"
    ];
    return $n[(int)$m];
}

function nama_hari_indonesia($hari_inggris)
{
    $hari = [
        'Monday' => 'Senin',
        'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu',
        'Thursday' => 'Kamis',
        'Friday' => 'Jumat',
        'Saturday' => 'Sabtu',
        'Sunday' => 'Minggu'
    ];
    return $hari[$hari_inggris] ?? $hari_inggris;
}

function get_badge_status($status)
{
    $badge = [
        "aktif" => "success",
        "selesai" => "primary",
        "batal" => "danger",
        "libur" => "warning"
    ];
    return $badge[strtolower($status)] ?? "secondary";
}

function h($s)
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal Kelas - Sistem Informasi Bimbel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .calendar-table th,
        .calendar-table td {
            vertical-align: top;
            height: 120px;
        }

        .calendar-day {
            padding: .5rem;
            min-height: 120px;
        }

        .calendar-event {
            font-size: 0.85rem;
            margin-top: 4px;
            padding: 4px;
            border-radius: 4px;
            cursor: pointer;
        }

        .cal-day-number {
            display: block;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .toggle-month {
            width: auto;
            display: inline-block;
        }
        
        .hari-hari {
            font-weight: bold;
        }
        
        .today {
            background-color: #e7f3ff !important;
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
                    <small>Portal Siswa</small>
                </div>
                <nav class="nav flex-column">
                    <a class="nav-link" href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                    <a class="nav-link active" href="jadwal.php"><i class="bi bi-calendar3"></i> Jadwal Kelas</a>
                    <a class="nav-link" href="materi.php"><i class="bi bi-journal-text"></i> Materi</a>
                    <a class="nav-link" href="tugas.php"><i class="bi bi-clipboard-check"></i> Tugas</a>
                    <a class="nav-link" href="nilai.php"><i class="bi bi-bar-chart"></i> Nilai</a>
                    <a class="nav-link" href="pembayaran.php"><i class="bi bi-cash-coin"></i> Pembayaran</a>
                    <a class="nav-link" href="profil.php"><i class="bi bi-person"></i> Profil</a>
                    <a class="nav-link" href="../logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 content-wrapper p-4">
                <div class="page-header d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h2>Jadwal Kelas</h2>
                        <p class="text-muted">Jadwal pembelajaran <?= h($siswa['nama'] ?? 'Siswa') ?></p>
                    </div>
                    <div>
                        <button class="btn btn-outline-primary" id="toggleKalBtn" onclick="toggleKalender()">
                            <i class="bi bi-calendar3"></i> Tampilkan Kalender
                        </button>
                    </div>
                </div>

                <!-- Informasi Siswa -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="row">
                            
                                    <div class="col-md-9">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <small class="text-muted">Kelas</small>
                                                <p class="mb-1"><strong><?= h($siswa['nama_kelas'] ?? 'Belum ada kelas') ?></strong></p>
                                            </div>
                                            <div class="col-md-4">
                                                <small class="text-muted">Sekolah</small>
                                                <p class="mb-1"><strong><?= h($siswa['sekolah'] ?? 'Tidak ditentukan') ?></strong></p>
                                            </div>
                                            <div class="col-md-4">
                                                <small class="text-muted">Status Aktif</small>
                                                <p class="mb-1">
                                                    <span class="badge <?= ($siswa['status_aktif'] == 'aktif') ? 'bg-success' : 'bg-danger' ?>">
                                                        <?= h($siswa['status_aktif'] ?? 'tidak aktif') ?>
                                                    </span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Calendar View -->
                <div id="kalender-wrapper" style="display:none;">
                    <div class="card mb-4">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-0">Kalender <?= nama_bulan($month) . " " . $year ?></h5>
                            </div>
                            <div class="d-flex align-items-center">
                                <form id="monthForm" method="GET" class="d-flex align-items-center">
                                    <select id="month" name="month" class="form-select toggle-month me-2">
                                        <?php for ($m = 1; $m <= 12; $m++): ?>
                                            <option value="<?= $m ?>" <?= ($m == $month ? 'selected' : '') ?>><?= nama_bulan($m) ?></option>
                                        <?php endfor; ?>
                                    </select>
                                    <select id="year" name="year" class="form-select toggle-month me-2">
                                        <?php for ($y = date('Y') - 1; $y <= date('Y') + 1; $y++): ?>
                                            <option value="<?= $y ?>" <?= ($y == $year ? 'selected' : '') ?>><?= $y ?></option>
                                        <?php endfor; ?>
                                    </select>
                                    <button type="submit" class="btn btn-primary btn-sm">Lihat</button>
                                </form>

                                <div class="ms-2">
                                    <a href="?month=<?= ($month == 1 ? 12 : $month - 1) ?>&year=<?= ($month == 1 ? $year - 1 : $year) ?>" class="btn btn-sm btn-outline-secondary">‹</a>
                                    <a href="?month=<?= date('n') ?>&year=<?= date('Y') ?>" class="btn btn-sm btn-outline-secondary mx-1">Hari Ini</a>
                                    <a href="?month=<?= ($month == 12 ? 1 : $month + 1) ?>&year=<?= ($month == 12 ? $year + 1 : $year) ?>" class="btn btn-sm btn-outline-secondary">›</a>
                                </div>
                            </div>
                        </div>

                        <div class="card-body">
                            <div class="table-responsive">
                                <?php
                                // Generate calendar grid for selected month
                                $firstDayOfMonth = strtotime("$year-$month-01");
                                $startWeekDay = (int)date('N', $firstDayOfMonth); // 1 (Mon) - 7 (Sun)
                                $daysInMonth = (int)date('t', $firstDayOfMonth);

                                $cells = [];
                                // leading empty cells if month doesn't start on Monday
                                for ($i = 1; $i < $startWeekDay; $i++) $cells[] = null;
                                // fill days
                                for ($d = 1; $d <= $daysInMonth; $d++) {
                                    $cells[] = $d;
                                }
                                // append trailing nulls to complete the last week
                                while (count($cells) % 7 !== 0) $cells[] = null;

                                echo '<table class="table table-bordered calendar-table">';
                                echo '<thead class="table-light"><tr>
                                        <th class="text-center">Senin</th>
                                        <th class="text-center">Selasa</th>
                                        <th class="text-center">Rabu</th>
                                        <th class="text-center">Kamis</th>
                                        <th class="text-center">Jumat</th>
                                        <th class="text-center">Sabtu</th>
                                        <th class="text-center">Minggu</th>
                                      </tr></thead><tbody>';

                                $cellIndex = 0;
                                foreach (array_chunk($cells, 7) as $week) {
                                    echo '<tr>';
                                    foreach ($week as $cell) {
                                        if ($cell === null) {
                                            echo '<td class="calendar-day"></td>';
                                        } else {
                                            $dateStr = sprintf("%04d-%02d-%02d", $year, $month, $cell);
                                            $todayClass = ($dateStr == date('Y-m-d')) ? 'today' : '';
                                            echo '<td class="calendar-day ' . $todayClass . '">';
                                            echo '<span class="cal-day-number">' . $cell . '</span>';

                                            // tampilkan events (jika ada) untuk tanggal ini
                                            if (isset($events[$dateStr])) {
                                                foreach ($events[$dateStr] as $ev) {
                                                    // pilih warna berdasarkan mapel
                                                    $mapelLower = strtolower($ev['nama_mapel'] ?? '');
                                                    if (strpos($mapelLower, 'matematika') !== false) $cls = "bg-primary";
                                                    elseif (strpos($mapelLower, 'fisika') !== false) $cls = "bg-success";
                                                    elseif (strpos($mapelLower, 'kimia') !== false) $cls = "bg-warning";
                                                    elseif (strpos($mapelLower, 'biologi') !== false) $cls = "bg-danger";
                                                    elseif (strpos($mapelLower, 'bahasa inggris') !== false) $cls = "bg-info";
                                                    else $cls = "bg-secondary";
                                                    
                                                    $cls .= " text-white";
                                                    
                                                    // tampilkan waktu - mapel
                                                    $short = h(substr($ev['jam_mulai'], 0, 5) . ' - ' . $ev['nama_mapel']);
                                                    echo '<div class="calendar-event ' . $cls . '" 
                                                          onclick="showEventDetail(' . htmlspecialchars(json_encode($ev), ENT_QUOTES, 'UTF-8') . ')">
                                                            ' . $short . '
                                                          </div>';
                                                }
                                            }

                                            echo '</td>';
                                        }
                                        $cellIndex++;
                                    }
                                    echo '</tr>';
                                }

                                echo '</tbody></table>';
                                ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Jadwal Minggu Ini -->
                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Jadwal Minggu Ini (<?= date('d M', strtotime($startOfWeek)) ?> - <?= date('d M Y', strtotime($endOfWeek)) ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (mysqli_num_rows($qJadwalMingguIni) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Hari</th>
                                            <th>Tanggal</th>
                                            <th>Waktu</th>
                                            <th>Mata Pelajaran</th>
                                            <th>Tutor</th>
                                            <th>Ruangan</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($row = mysqli_fetch_assoc($qJadwalMingguIni)): ?>
                                            <?php
                                            $hari = nama_hari_indonesia(date('l', strtotime($row['tanggal'])));
                                            $isToday = ($row['tanggal'] == date('Y-m-d')) ? 'table-primary' : '';
                                            ?>
                                            <tr class="<?= $isToday ?>">
                                                <td class="hari-hari"><?= $hari ?></td>
                                                <td><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                                                <td><?= substr($row['jam_mulai'], 0, 5) ?> - <?= substr($row['jam_selesai'], 0, 5) ?></td>
                                                <td><?= h($row['nama_mapel'] ?? 'Tidak ditentukan') ?></td>
                                                <td><?= h($row['nama_tutor'] ?? 'Tidak ditentukan') ?></td>
                                                <td><?= h($row['nama_ruangan'] ?? 'Tidak ditentukan') ?></td>
                                                <td>
                                                    <span class="badge bg-<?= get_badge_status($row['status']) ?>">
                                                        <?= h($row['status'] ?? 'tidak diketahui') ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> Tidak ada jadwal untuk minggu ini.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Semua Jadwal -->
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Semua Jadwal</h5>
                    </div>
                    <div class="card-body">
                        <?php if (mysqli_num_rows($qAllJadwal) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Hari</th>
                                            <th>Tanggal</th>
                                            <th>Waktu</th>
                                            <th>Mata Pelajaran</th>
                                            <th>Tutor</th>
                                            <th>Ruangan</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($row = mysqli_fetch_assoc($qAllJadwal)): ?>
                                            <?php
                                            $hari = nama_hari_indonesia($row['nama_hari']);
                                            $isToday = ($row['tanggal'] == date('Y-m-d')) ? 'table-primary' : '';
                                            ?>
                                            <tr class="<?= $isToday ?>">
                                                <td class="hari-hari"><?= $hari ?></td>
                                                <td><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                                                <td><?= substr($row['jam_mulai'], 0, 5) ?> - <?= substr($row['jam_selesai'], 0, 5) ?></td>
                                                <td><?= h($row['nama_mapel'] ?? 'Tidak ditentukan') ?></td>
                                                <td><?= h($row['nama_tutor'] ?? 'Tidak ditentukan') ?></td>
                                                <td><?= h($row['nama_ruangan'] ?? 'Tidak ditentukan') ?></td>
                                                <td>
                                                    <span class="badge bg-<?= get_badge_status($row['status']) ?>">
                                                        <?= h($row['status'] ?? 'tidak diketahui') ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> Tidak ada jadwal yang tersedia.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Modal Detail Event -->
    <div class="modal fade" id="eventModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="eventModalTitle">Detail Jadwal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="eventModalBody">
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
        let kalenderVisible = false;
        
        function toggleKalender() {
            const wrapper = document.getElementById('kalender-wrapper');
            const button = document.getElementById('toggleKalBtn');
            
            if (kalenderVisible) {
                wrapper.style.display = 'none';
                button.innerHTML = '<i class="bi bi-calendar3"></i> Tampilkan Kalender';
                button.className = 'btn btn-outline-primary';
            } else {
                wrapper.style.display = 'block';
                button.innerHTML = '<i class="bi bi-list"></i> Sembunyikan Kalender';
                button.className = 'btn btn-primary';
            }
            kalenderVisible = !kalenderVisible;
        }
        
        function showEventDetail(eventData) {
            document.getElementById('eventModalTitle').textContent = 'Detail Jadwal - ' + eventData.nama_mapel;
            
            const modalBody = document.getElementById('eventModalBody');
            modalBody.innerHTML = `
                <div class="mb-3">
                    <h6>${eventData.nama_mapel}</h6>
                    <hr>
                </div>
                <table class="table table-sm">
                    <tr>
                        <td width="40%"><strong>Tanggal</strong></td>
                        <td>${formatTanggal(eventData.tanggal)}</td>
                    </tr>
                    <tr>
                        <td><strong>Waktu</strong></td>
                        <td>${eventData.jam_mulai.substring(0,5)} - ${eventData.jam_selesai.substring(0,5)}</td>
                    </tr>
                    <tr>
                        <td><strong>Tutor</strong></td>
                        <td>${eventData.nama_tutor || 'Tidak ditentukan'}</td>
                    </tr>
                    <tr>
                        <td><strong>Ruangan</strong></td>
                        <td>${eventData.nama_ruangan || 'Tidak ditentukan'}</td>
                    </tr>
                    <tr>
                        <td><strong>Kelas</strong></td>
                        <td>${eventData.nama_kelas || 'Tidak ditentukan'}</td>
                    </tr>
                    <tr>
                        <td><strong>Status</strong></td>
                        <td>
                            <span class="badge ${getStatusBadgeClass(eventData.status)}">
                                ${eventData.status || 'tidak diketahui'}
                            </span>
                        </td>
                    </tr>
                </table>
                <div class="alert alert-info mt-3">
                    <i class="bi bi-info-circle"></i> 
                    Pastikan Anda datang tepat waktu dan membawa perlengkapan belajar yang diperlukan.
                </div>
            `;
            
            const modal = new bootstrap.Modal(document.getElementById('eventModal'));
            modal.show();
        }
        
        function formatTanggal(tanggal) {
            const date = new Date(tanggal);
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            return date.toLocaleDateString('id-ID', options);
        }
        
        function getStatusBadgeClass(status) {
            const statusMap = {
                'aktif': 'bg-success',
                'selesai': 'bg-primary',
                'batal': 'bg-danger',
                'libur': 'bg-warning'
            };
            return statusMap[status] || 'bg-secondary';
        }
    </script>
</body>
</html>