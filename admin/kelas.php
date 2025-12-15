<?php
session_start();

require "../config.php";

// pastikan user login admin
if (!isset($_SESSION['login']) || $_SESSION['role'] !== "admin") {
    header("Location: ../index.php");
    exit;
}

/**
 * PROSES INSERT (Tambah Jadwal Baru)
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_jadwal'])) {

    $id_mapel       = mysqli_real_escape_string($conn, $_POST['id_mapel']);
    $id_kelas    = mysqli_real_escape_string($conn, $_POST['id_kelas']);
    $id_tutor    = mysqli_real_escape_string($conn, $_POST['id_tutor']);
    $hari        = mysqli_real_escape_string($conn, $_POST['hari']);
    $jam_mulai   = mysqli_real_escape_string($conn, $_POST['jam_mulai']);
    $jam_selesai = mysqli_real_escape_string($conn, $_POST['jam_selesai']);
    $ruangan     = mysqli_real_escape_string($conn, $_POST['ruangan']);
    $status      = mysqli_real_escape_string($conn, $_POST['status']);
    $tanggal     = mysqli_real_escape_string($conn, $_POST['tanggal']);
    $kelas       = mysqli_real_escape_string($conn, $_POST['kelas']);
    $tutor       = mysqli_real_escape_string($conn, $_POST['tutor']);
    $id_siswa    = mysqli_real_escape_string($conn, $_POST['id_siswa']);

    if (!$tanggal) {
        $_SESSION['error'] = "Tanggal wajib diisi.";
        header("Location: kelas.php");
        exit;
    }

    $sql = " INSERT INTO jadwal (id_mapel, id_kelas, id_tutor, tanggal, jam_mulai, jam_selesai, ruangan, status)
VALUES
('$id_mapel', '$id_kelas', '$id_tutor', '$tanggal', '$jam_mulai', '$jam_selesai', '$ruangan', '$status')
";


    mysqli_query($conn, $sql);
    header("Location: kelas.php?added=1");
    exit;
}

/**
 * Ambil jadwal untuk admin list
 */
$qList = $conn->query("
    SELECT 
    jadwal.*,
    mapel.nama_mapel,
    kelas.nama_kelas,
    tutor.nama_tutor,
    r.nama_ruangan AS ruangan
FROM jadwal
JOIN mapel ON jadwal.id_mapel = mapel.id_mapel
JOIN kelas ON jadwal.id_kelas = kelas.id_kelas
JOIN tutor ON jadwal.id_tutor = tutor.id_tutor
LEFT JOIN ruangan r ON jadwal.id_ruangan = r.id_ruangan
ORDER BY tanggal, jam_mulai

");


/**
 * Kalender (bulan + tahun)
 */
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$year  = isset($_GET['year'])  ? (int)$_GET['year']  : date('Y');

$startOfMonth = "$year-$month-01";
$endOfMonth   = date("Y-m-t", strtotime($startOfMonth));

/**
 * Ambil event kalender
 */
$events = [];
$qEvents = $conn->query("
    SELECT 
        jadwal.*,
        kelas.nama_kelas,
        tutor.nama_tutor AS nama_tutor
    FROM jadwal
    JOIN kelas ON jadwal.id_kelas = kelas.id_kelas
    JOIN tutor ON jadwal.id_tutor = tutor.id_tutor
    WHERE jadwal.tanggal BETWEEN '$startOfMonth' AND '$endOfMonth'
    ORDER BY jadwal.tanggal, jadwal.jam_mulai
");

while ($r = $qEvents->fetch_assoc()) {
    $events[$r['tanggal']][] = $r;
}

function nama_bulan($m)
{
    $nama = [
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
    return $nama[(int)$m];
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
        /* tambahan kecil untuk kalender agar mirip layout awal */
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
                    <a class="nav-link active" href="kelas.php"><i class="bi bi-calendar3"></i> Jadwal Kelas</a>
                    <a class="nav-link" href="pembayaran.php"><i class="bi bi-cash-coin"></i> Pembayaran</a>
                    <a class="nav-link" href="mapel.php"><i class="bi bi-journal-text"></i> Mata Pelajaran</a>
                    <a class="nav-link" href="laporan.php"><i class="bi bi-file-earmark-text"></i> Laporan</a>
                    <a class="nav-link" href="../logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 content-wrapper p-4">
                <div class="page-header d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h2>Jadwal Kelas</h2>
                        <p class="text-muted">Kelola jadwal kelas dan les</p>
                    </div>
                    <div>
                        <a href="tambah-jadwal.php" class="btn btn-primary me-2">
                            <i class="bi bi-plus-circle"></i> Tambah Jadwal Baru
                        </a>


                        <button class="btn btn-outline-primary" id="toggleKalBtn" onclick="toggleKalender()">
                            <i class="bi bi-calendar3"></i> Tampilkan Kalender
                        </button>
                    </div>
                </div>

                <!-- Kalender: tersembunyi awalnya -->
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
                                        <?php for ($y = date('Y') - 3; $y <= date('Y') + 3; $y++): ?>
                                            <option value="<?= $y ?>" <?= ($y == $year ? 'selected' : '') ?>><?= $y ?></option>
                                        <?php endfor; ?>
                                    </select>
                                    <button type="submit" class="btn btn-primary btn-sm">Lihat</button>
                                </form>

                                <div class="ms-2">
                                    <button class="btn btn-sm btn-outline-secondary" onclick="prevMonth()">‹</button>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="nextMonth()">›</button>
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

                                // We'll display Mon-Sun columns. Need to know how many leading blanks.
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
                                            echo '<td class="calendar-day">';
                                            echo '<span class="cal-day-number">' . $cell . '</span>';

                                            // tampilkan events (jika ada) untuk tanggal ini
                                            if (isset($events[$dateStr])) {
                                                foreach ($events[$dateStr] as $ev) {
                                                    // pilih warna berdasarkan status atau mapel (sederhana)
                                                    $cls = "bg-primary text-white";
                                                    if ($ev['status'] == 'Ditunda' || $ev['status'] == 'Tidak Aktif') $cls = "bg-warning text-dark";
                                                    if ($ev['status'] == 'Selesai' || $ev['status'] == 'Berjalan') $cls = "bg-success text-white";
                                                    // tampilkan waktu - mapel (sesuai permintaan: detail muncul di modal saat klik)
                                                    $short = h(substr($ev['jam_mulai'], 0, 5) . ' - ' . $ev['id_mapel']);
                                                    echo '<div class="calendar-event ' . $cls . '" data-bs-toggle="modal" data-bs-target="#eventModal" data-event=\'' . json_encode($ev, JSON_HEX_APOS | JSON_HEX_QUOT) . '\'>' . $short . '</div>';
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

                <!-- List View -->
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Daftar Jadwal Kelas</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Hari</th>
                                        <th>Waktu</th>
                                        <th>Mata Pelajaran</th>
                                        <th>Kelas</th>
                                        <th>Tutor</th>
                                        <th>Ruangan</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = mysqli_fetch_assoc($qList)) : ?>
                                        <tr>
                                            <td><?= h($row['tanggal']) ?></td>
                                            <td><?= h(substr($row['jam_mulai'], 0, 5)) ?> - <?= h(substr($row['jam_selesai'], 0, 5)) ?></td>
                                            <td><?= h($row['nama_mapel']) ?></td>
                                            <td><?= h($row['nama_kelas']) ?></td>
                                            <td><?= h($row['nama_tutor']) ?></td>

                                            <td><?= h($row['ruangan'] ?? '-') ?></td>
                                            <td>
                                                <?php if ($row['status'] == "Aktif"): ?>
                                                    <span class="badge bg-success">Aktif</span>
                                                <?php elseif ($row['status'] == "Ditunda"): ?>
                                                    <span class="badge bg-warning">Ditunda</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary"><?= h($row['status']) ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-info view-detail" data-event='<?= json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT) ?>' data-bs-toggle="modal" data-bs-target="#eventModal"><i class="bi bi-eye"></i></button>

                                                <a href="kelas-edit.php?id=<?= $row['id_jadwal'] ?>" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>

                                                <a onclick="return confirm('Hapus jadwal ini?')" href="hapus-jadwal.php?id=<?= $row['id_kelas'] ?>" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div> <!-- content-wrapper -->
        </div>
    </div>

    <!-- Modal Event Detail -->
    <div class="modal fade" id="eventModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Jadwal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="eventDetailBody">
                    <!-- diisi via JS -->
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Toggle kalender
        function toggleKalender() {
            const w = document.getElementById('kalender-wrapper');
            const btn = document.getElementById('toggleKalBtn');
            if (w.style.display === 'none' || w.style.display === '') {
                w.style.display = 'block';
                btn.innerHTML = '<i class="bi bi-calendar3"></i> Sembunyikan Kalender';
            } else {
                w.style.display = 'none';
                btn.innerHTML = '<i class="bi bi-calendar3"></i> Tampilkan Kalender';
            }
        }

        // Prev / Next month: ubah value select lalu submit form
        function prevMonth() {
            const month = document.getElementById('month');
            const year = document.getElementById('year');
            let m = parseInt(month.value),
                y = parseInt(year.value);
            m--;
            if (m < 1) {
                m = 12;
                y--;
            }
            month.value = m;
            year.value = y;
            document.getElementById('monthForm').submit();
        }

        function nextMonth() {
            const month = document.getElementById('month');
            const year = document.getElementById('year');
            let m = parseInt(month.value),
                y = parseInt(year.value);
            m++;
            if (m > 12) {
                m = 1;
                y++;
            }
            month.value = m;
            year.value = y;
            document.getElementById('monthForm').submit();
        }

        // Saat klik event di calendar, isi modal detail
        document.addEventListener('DOMContentLoaded', function() {
            // elements with calendar-event
            document.querySelectorAll('.calendar-event').forEach(el => {
                el.addEventListener('click', function(e) {
                    const raw = this.getAttribute('data-event');
                    try {
                        const ev = JSON.parse(raw);
                        showEventModal(ev);
                    } catch (err) {
                        console.error(err);
                    }
                });
            });

            // tombol view detail di list view
            document.querySelectorAll('.view-detail').forEach(btn => {
                btn.addEventListener('click', function() {
                    const raw = this.getAttribute('data-event');
                    try {
                        const ev = JSON.parse(raw);
                        showEventModal(ev);
                    } catch (err) {
                        console.error(err);
                    }
                });
            });
        });

        function showEventModal(ev) {
            const body = document.getElementById('eventDetailBody');
            const html = `
                <div class="row">
                    <div class="col-md-6">
                        <h6>Informasi</h6>
                        <table class="table table-sm">
                            <tr><td><strong>Mapel</strong></td><td>${escapeHtml(ev.mapel)}</td></tr>
                            <tr><td><strong>Kelas</strong></td><td>${escapeHtml(ev.kelas)}</td></tr>
                            <tr><td><strong>Tutor</strong></td><td>${escapeHtml(ev.tutor)}</td></tr>
                            <tr><td><strong>Ruangan</strong></td><td>${escapeHtml(ev.ruangan)}</td></tr>
                            <tr><td><strong>Status</strong></td><td>${escapeHtml(ev.status)}</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6>Waktu & Tanggal</h6>
                        <table class="table table-sm">
                            <tr><td><strong>Tanggal</strong></td><td>${escapeHtml(ev.tanggal)}</td></tr>
                            <tr><td><strong>Jam</strong></td><td>${escapeHtml(ev.jam_mulai.substring(0,5))} - ${escapeHtml(ev.jam_selesai.substring(0,5))}</td></tr>
                        </table>
                    </div>
                </div>
            `;
            body.innerHTML = html;
        }

        function escapeHtml(unsafe) {
            if (unsafe === null || unsafe === undefined) return '';
            return String(unsafe)
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>