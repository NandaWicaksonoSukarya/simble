<?php
session_start();
// echo "<pre>";
// print_r($_SESSION);
// echo "</pre>";

require "../config.php"; // harus menghasilkan $conn (mysqli)

// helper escape
function h($s)
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}



$id_siswa = mysqli_real_escape_string($conn, $_SESSION['id_siswa']);

// filter: mapel dan periode (month/year)
$filter_mapel = isset($_GET['mapel']) ? mysqli_real_escape_string($conn, $_GET['mapel']) : '';
$filter_month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
$filter_year  = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

// boundary
if ($filter_month < 1 || $filter_month > 12) $filter_month = (int)date('n');
if ($filter_year < 1970 || $filter_year > 2100) $filter_year = (int)date('Y');

// helper for month name (Indo)
function nama_bulan($m)
{
    $nama = ["", "Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
    return $nama[(int)$m];
}

/**
 * 1) Ambil daftar mapel dari tabel tugas (distinct) untuk opsi filter & kartu
 */
$mapelRes = mysqli_query($conn, "
    SELECT id_mapel, nama_mapel 
    FROM mapel 
    ORDER BY nama_mapel ASC
");

$mapelList = [];
while ($r = mysqli_fetch_assoc($mapelRes)) $mapelList[] = $r['id_mapel'];

/**
 * 2) Summary: rata-rata, max, min, total tugas dinilai
 *    filter by id_siswa + selected month/year + (optional) mapel
 */
$whereSummary = "WHERE n.id_siswa = '" . $id_siswa . "'
                 AND MONTH(n.uploaded_at) = '" . mysqli_real_escape_string($conn, $filter_month) . "'
                 AND YEAR(n.uploaded_at) = '" . mysqli_real_escape_string($conn, $filter_year) . "'";

if ($filter_mapel) {
    $whereSummary .= " AND t.id_mapel = '" . mysqli_real_escape_string($conn, $filter_mapel) . "'";
}

$qSummary = "
    SELECT
      COUNT(n.nilai) AS total_tugas,
      AVG(n.nilai) AS rata2,
      MAX(n.nilai) AS tertinggi,
      MIN(n.nilai) AS terendah
    FROM nilai n
    LEFT JOIN tugas t ON t.id_tugas = n.id_tugas
    $whereSummary
";
$resSum = mysqli_query($conn, $qSummary);
$sum = mysqli_fetch_assoc($resSum);
$total_tugas = (int)$sum['total_tugas'];
$rata2 = $sum['rata2'] !== null ? round($sum['rata2'], 0) : 0;
$tert = $sum['tertinggi'] !== null ? (int)$sum['tertinggi'] : 0;
$terendah = $sum['terendah'] !== null ? (int)$sum['terendah'] : 0;

/**
 * 3) Card per mapel: rata-rata per mapel (batas: hanya mapel yang ada)
 */
$mapelStats = [];
foreach ($mapelList as $mp) {
    $mpEsc = mysqli_real_escape_string($conn, $mp);
    $q = "SELECT COUNT(n.nilai) AS cnt, AVG(n.nilai) AS avgv
          FROM nilai n
          LEFT JOIN tugas t ON t.id_tugas = n.id_tugas
          WHERE n.id_siswa = '$id_siswa' AND t.id_mapel = '$mpEsc'
          AND MONTH(n.uploaded_at) = '" . mysqli_real_escape_string($conn, $filter_month) . "'
          AND YEAR(n.uploaded_at) = '" . mysqli_real_escape_string($conn, $filter_year) . "'";
    $r = mysqli_fetch_assoc(mysqli_query($conn, $q));
    $mapelStats[$mp] = [
        'count' => (int)$r['cnt'],
        'avg' => $r['avgv'] !== null ? round($r['avgv'], 0) : null
    ];
}

/**
 * 4) Daftar nilai per mapel: ambil tugas->nilai untuk siswa
 *    Jika filter_mapel dipilih, tampilkan hanya mapel itu; else tampilkan untuk semua mapel (di layout, kita menampilkan beberapa kartu UI)
 *
 *    Kita akan siapkan array $nilaiPerMapel[mapel] = array of rows
 */
$nilaiPerMapel = [];
$mapelToQuery = $filter_mapel ? [$filter_mapel] : $mapelList;
foreach ($mapelToQuery as $mp) {
    $mpEsc = mysqli_real_escape_string($conn, $mp);
    // ambil tugas yang relevan JOIN nilai (nilai mungkin null jika belum dikoreksi; tapi tabel nilai menyimpan submit + nilai)
    $q = "SELECT 
        t.id_tugas AS tugas_id, 
        t.judul, 
        t.id_kelas, 
        t.deadline, 
        t.created_at, 
        t.id_tutor,
        n.id AS submit_id, 
        n.file_path, 
        n.uploaded_at, 
        n.nilai
      FROM tugas t
      LEFT JOIN nilai n ON n.id_tugas = t.id_tugas AND n.id_siswa = '$id_siswa'
      WHERE t.id_mapel = '$mpEsc'
      ORDER BY t.created_at DESC, t.deadline DESC
      LIMIT 50";

    $res = mysqli_query($conn, $q);
    $rows = [];
    while ($rw = mysqli_fetch_assoc($res)) $rows[] = $rw;
    $nilaiPerMapel[$mp] = $rows;
}

/**
 * 5) Data chart: ambil nilai terbaru (limit 12) untuk chart perkembangan.
 *    Chart akan menampilkan dataset per mapel (up to 3 mapel, tapi kita tampilkan untuk semua mapelList dynamically)
 */
$chartData = []; // mapel => [labels[], data[]]
foreach ($mapelList as $mp) {
    $mpEsc = mysqli_real_escape_string($conn, $mp);
    // ambil last 12 nilai untuk siswa pada mapel ini (urut by uploaded_at asc so chart flows left->right)
    $q = "SELECT n.nilai, DATE_FORMAT(n.uploaded_at, '%d %b') AS label
          FROM nilai n
          LEFT JOIN tugas t ON t.id_tugas = n.id_tugas
          WHERE n.id_siswa = '$id_siswa' AND t.id_mapel = '$mpEsc'
          ORDER BY n.uploaded_at DESC
          LIMIT 12";
    $res = mysqli_query($conn, $q);
    $labels = [];
    $data = [];
    $temp = [];
    while ($r = mysqli_fetch_assoc($res)) {
        // we will reverse later to chronological order
        $temp[] = $r;
    }
    $temp = array_reverse($temp);
    foreach ($temp as $r) {
        $labels[] = $r['label'];
        $data[] = $r['nilai'] !== null ? (int)$r['nilai'] : null;
    }
    if (!empty($data)) {
        $chartData[$mp] = ['labels' => $labels, 'data' => $data];
    }
}

/**
 * 6) Additional stats used in header summary: If no data for selected month, try last 3 months aggregate as fallback (optional)
 *    (not necessary but we keep simple: we already have summary above)
 */

/* end PHP / start HTML */
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nilai - Sistem Informasi Bimbel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar (tetap seperti UI) -->
            <div class="col-md-2 sidebar p-0">
                <div class="text-center text-white py-4">
                    <i class="bi bi-book-fill" style="font-size: 2.5rem;"></i>
                    <h5 class="mt-2">Bimbel System</h5>
                    <small>Portal Siswa</small>
                </div>
                <nav class="nav flex-column">
                    <a class="nav-link" href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                    <a class="nav-link" href="jadwal.php"><i class="bi bi-calendar3"></i> Jadwal Kelas</a>
                    <a class="nav-link" href="materi.php"><i class="bi bi-journal-text"></i> Materi</a>
                    <a class="nav-link" href="tugas.php"><i class="bi bi-clipboard-check"></i> Tugas</a>
                    <a class="nav-link active" href="nilai.php"><i class="bi bi-bar-chart"></i> Nilai</a>
                    <a class="nav-link" href="pembayaran.php"><i class="bi bi-cash-coin"></i> Pembayaran</a>
                    <a class="nav-link" href="profil.php"><i class="bi bi-person"></i> Profil</a>
                    <a class="nav-link" href="../index.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 content-wrapper p-4">
                <div class="page-header">
                    <h2>Nilai & Prestasi</h2>
                    <p class="text-muted">Lihat nilai tugas dan ujian Anda</p>
                </div>

                <!-- Filters row: month/year and mapel -->



                <!-- Summary Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h2 class="text-primary"><?= h($rata2) ?></h2>
                                <p class="text-muted mb-0">Rata-rata Nilai (<?= h(nama_bulan($filter_month) . ' ' . $filter_year) ?>)</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h2 class="text-success"><?= h($tert) ?></h2>
                                <p class="text-muted mb-0">Nilai Tertinggi</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h2 class="text-warning"><?= h($terendah) ?></h2>
                                <p class="text-muted mb-0">Nilai Terendah</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h2 class="text-info"><?= h($total_tugas) ?></h2>
                                <p class="text-muted mb-0">Total Tugas Dinilai</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Nilai Per Mata Pelajaran (Cards) -->
                <div class="row mb-4">
                    <?php foreach ($mapelList as $mp):
                        $stats = $mapelStats[$mp] ?? ['count' => 0, 'avg' => null];

                        // ambil rows
                        $rows = $nilaiPerMapel[$mp] ?? [];

                        // Skip mapel yang tidak punya tugas sama sekali
                        if (empty($rows)) continue;
                    ?>

                        <div class="col-md-6 mb-3">
                            <div class="card">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0"><i class="bi bi-calculator text-primary"></i> <?= h($mp) ?></h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span>Rata-rata Nilai</span>
                                        <h4 class="text-primary mb-0"><?= $stats['avg'] !== null ? h($stats['avg']) : '-' ?></h4>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Tugas</th>
                                                    <th>Tanggal</th>
                                                    <th>Nilai</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $rows = $nilaiPerMapel[$mp] ?? [];
                                                if (!empty($rows)):
                                                    foreach ($rows as $rw):
                                                        $tgl = !empty($rw['created_at'])
                                                            ? date('j M Y', strtotime($rw['created_at']))
                                                            : '-';
                                                        $nilaiVal = ($rw['nilai'] !== null && $rw['nilai'] !== '') ? (int)$rw['nilai'] : '-';
                                                        $judul = $rw['judul'] ?? '-';
                                                ?>
                                                        <tr>
                                                            <td><?= h($judul) ?></td>
                                                            <td><?= h($tgl) ?></td>
                                                            <td>
                                                                <?php if ($nilaiVal === '-'): ?>
                                                                    <span class="badge bg-secondary">Belum Dinilai</span>
                                                                <?php else: ?>
                                                                    <span class="badge bg-success"><?= h($nilaiVal) ?></span>
                                                                <?php endif; ?>
                                                            </td>
                                                        </tr>
                                                    <?php
                                                    endforeach;
                                                else:
                                                    ?>
                                                    <tr>
                                                        <td colspan="3" class="text-center text-muted">Tidak ada data untuk mata pelajaran ini.</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Grafik Perkembangan Nilai -->


                <!-- ChartJS dan script -->
                <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
                <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                <script>
                    (function() {
                        const ctx = document.getElementById('nilaiChart').getContext('2d');

                        // Build datasets from PHP $chartData
                        const raw = <?= json_encode($chartData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
                        // raw format: { "Matematika": {labels: [...], data: [...]}, ... }
                        const datasets = [];
                        const allLabelsSet = new Set();

                        for (const mp in raw) {
                            raw[mp].labels.forEach(l => allLabelsSet.add(l));
                        }
                        // Use sorted labels (maintain insertion from PHP, but we need unified x-axis)
                        const allLabels = Array.from(allLabelsSet);

                        // Color palette (will cycle)
                        const colors = [
                            'rgb(13,110,253)', // blue
                            'rgb(25,135,84)', // green
                            'rgb(255,193,7)', // yellow
                            'rgb(220,53,69)', // red
                            'rgb(13,202,240)', // cyan
                            'rgb(108,117,125)' // gray
                        ];

                        let ci = 0;
                        for (const mp in raw) {
                            const dataByLabel = {};
                            raw[mp].labels.forEach((lab, idx) => {
                                dataByLabel[lab] = raw[mp].data[idx];
                            });
                            // align to allLabels
                            const aligned = allLabels.map(l => (l in dataByLabel) ? dataByLabel[l] : null);
                            datasets.push({
                                label: mp,
                                data: aligned,
                                borderColor: colors[ci % colors.length],
                                tension: 0.4,
                                spanGaps: true,
                                fill: false
                            });
                            ci++;
                        }

                        // If no dataset, render empty chart with placeholder
                        if (datasets.length === 0) {
                            document.getElementById('nilaiChart').parentNode.innerHTML = '<p class="text-muted p-3">Belum ada data nilai untuk periode ini.</p>';
                            return;
                        }

                        new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: allLabels,
                                datasets: datasets
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: true,
                                scales: {
                                    y: {
                                        suggestedMin: 0,
                                        suggestedMax: 100
                                    }
                                },
                                plugins: {
                                    legend: {
                                        position: 'top'
                                    }
                                }
                            }
                        });
                    })();
                </script>
</body>

</html>