<?php
session_start();
require "../config.php"; // harus menyediakan $conn (mysqli)

// proteksi route: hanya tutor/admin yang boleh akses
if (!isset($_SESSION['login']) || ($_SESSION['role'] !== 'tutor' && $_SESSION['role'] !== 'admin')) {
    header("Location: ../index.php");
    exit;
}

// optional: tutor name from session
$tutor_name = $_SESSION['nama'] ?? '';

// ====== STATISTIK ======
// total siswa aktif
$q_total = mysqli_query($conn, "SELECT COUNT(*) AS total FROM siswa WHERE status_aktif = 'aktif'");
$total_siswa = mysqli_fetch_assoc($q_total)['total'] ?? 0;

// jumlah per kelas (group by kelas) - hanya siswa aktif
$q_kelas = mysqli_query($conn, "
    SELECT 
        s.id_kelas,
        k.nama_kelas,
        COUNT(*) AS cnt
    FROM siswa s
    LEFT JOIN kelas k ON s.id_kelas = k.id_kelas
    WHERE s.status_aktif = 'aktif'
    GROUP BY s.id_kelas
");
$kelas_counts = [];
$kelas_options = [];
while ($r = mysqli_fetch_assoc($q_kelas)) {
    $kelas_counts[$r['id_kelas']] = $r['cnt'];
    $kelas_options[$r['id_kelas']] = $r['nama_kelas'];
}

// overall attendance percent: (hadir / total presensi) * 100
$q_att = mysqli_query($conn, "SELECT 
    SUM(CASE WHEN status = 'Hadir' THEN 1 ELSE 0 END) as hadir,
    COUNT(*) as total_presensi
    FROM presensi");
$att_row = mysqli_fetch_assoc($q_att);
$hadir = intval($att_row['hadir'] ?? 0);
$total_presensi = intval($att_row['total_presensi'] ?? 0);
$attendance_percent = ($total_presensi > 0) ? round(($hadir / $total_presensi) * 100) : 0;

// overall average nilai dari penilaian_tugas
$q_avgnilai = mysqli_query($conn, "SELECT AVG(nilai) AS avg_nilai FROM penilaian_tugas WHERE nilai IS NOT NULL");
$avgnilai_row = mysqli_fetch_assoc($q_avgnilai);
$avg_nilai_overall = $avgnilai_row['avg_nilai'] ? round($avgnilai_row['avg_nilai']) : 0;

// ====== FILTER ======
$search = isset($_GET['q']) ? mysqli_real_escape_string($conn, $_GET['q']) : '';
$filter_kelas = isset($_GET['kelas']) ? mysqli_real_escape_string($conn, $_GET['kelas']) : '';

// Query dasar dengan filter
$query_siswa = "
    SELECT 
        s.id_siswa,
        s.nama,
        k.nama_kelas AS kelas,
        s.email,
        s.telepon,
        s.id_kelas
    FROM siswa s
    LEFT JOIN kelas k ON s.id_kelas = k.id_kelas
    WHERE s.status_aktif = 'aktif'
";

// Tambah kondisi filter
$conditions = [];
if (!empty($search)) {
    $conditions[] = "(s.nama LIKE '%$search%' OR s.email LIKE '%$search%')";
}
if (!empty($filter_kelas)) {
    $conditions[] = "s.id_kelas = '$filter_kelas'";
}

if (!empty($conditions)) {
    $query_siswa .= " AND " . implode(" AND ", $conditions);
}

$query_siswa .= " ORDER BY s.nama ASC";

$q_siswa = mysqli_query($conn, $query_siswa);

// helper
function pct($num)
{
    return $num . '%';
}
function safe($v)
{
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Siswa - Sistem Informasi Bimbel</title>
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
                    <a class="nav-link" href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                    <a class="nav-link" href="jadwal.php"><i class="bi bi-calendar3"></i> Jadwal Mengajar</a>
                    <a class="nav-link" href="presensi.php"><i class="bi bi-check2-square"></i> Presensi</a>
                    <a class="nav-link" href="materi.php"><i class="bi bi-journal-text"></i> Materi</a>
                    <a class="nav-link" href="tugas.php"><i class="bi bi-clipboard-check"></i> Tugas & Penilaian</a>
                    <a class="nav-link active" href="siswa.php"><i class="bi bi-people"></i> Data Siswa</a>
                    <a class="nav-link" href="profil.php"><i class="bi bi-person"></i> Profil</a>
                    <a class="nav-link" href="../logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 content-wrapper">
                <div class="page-header">
                    <h2>Data Siswa</h2>
                    <p class="text-muted">Lihat data siswa yang Anda ajar</p>
                </div>

                <!-- Stats -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3 class="text-primary"><?= number_format($total_siswa) ?></h3>
                                <p class="text-muted mb-0">Total Siswa Aktif</p>
                            </div>
                        </div>
                    </div>

                    <!-- Tampilkan dua kelas pertama jika ada -->
                    <div class="col-md-4">
                        <div class="card text-center">
                            <div class="card-body">
                                <?php
                                $kelas_keys = array_keys($kelas_counts);
                                $k1_value = isset($kelas_keys[0]) ? $kelas_counts[$kelas_keys[0]] : 0;
                                $k1_label = isset($kelas_keys[0]) ? $kelas_options[$kelas_keys[0]] : 'Kelas 12';
                                ?>
                                <h3 class="text-success"><?= number_format($k1_value) ?></h3>
                                <p class="text-muted mb-0"><?= safe($k1_label) ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card text-center">
                            <div class="card-body">
                                <?php
                                $k2_value = isset($kelas_keys[1]) ? $kelas_counts[$kelas_keys[1]] : 0;
                                $k2_label = isset($kelas_keys[1]) ? $kelas_options[$kelas_keys[1]] : 'Kelas 11';
                                ?>
                                <h3 class="text-info"><?= number_format($k2_value) ?></h3>
                                <p class="text-muted mb-0"><?= safe($k2_label) ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filter -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row">
                            <form class="row g-2" method="get" action="siswa.php">
                                <div class="col-md-5">
                                    <input type="text" name="q" class="form-control" placeholder="Cari nama atau email siswa..." value="<?= isset($_GET['q']) ? safe($_GET['q']) : '' ?>">
                                </div>
                                <div class="col-md-4">
                                    <select class="form-select" name="kelas">
                                        <option value="">Semua Kelas</option>
                                        <?php
                                        foreach ($kelas_options as $id_kelas => $nama_kelas) {
                                            $sel = (isset($_GET['kelas']) && $_GET['kelas'] == $id_kelas) ? 'selected' : '';
                                            echo "<option value=\"" . safe($id_kelas) . "\" $sel>" . safe($nama_kelas) . "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <button class="btn btn-primary w-100">Filter</button>
                                    <?php if (!empty($search) || !empty($filter_kelas)): ?>
                                        <a href="siswa.php" class="btn btn-outline-secondary w-100 mt-2">Reset</a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID Siswa</th>
                                        <th>Nama</th>
                                        <th>Kelas</th>
                                        <th>Email</th>
                                        <th>Kehadiran</th>
                                        <th>Rata-rata Nilai</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (mysqli_num_rows($q_siswa) > 0): ?>
                                        <?php while ($row = mysqli_fetch_assoc($q_siswa)): ?>
                                            <tr>
                                                <td><?= $row['id_siswa'] ?></td>
                                                <td><?= safe($row['nama']) ?></td>
                                                <td><?= safe($row['kelas'] ?? 'Tidak Ada Kelas') ?></td>
                                                <td><?= safe($row['email']) ?></td>

                                                <!-- kehadiran -->
                                                <td>
                                                    <?php
                                                    $id = $row['id_siswa'];
                                                    $q_kehadiran = mysqli_query($conn, "
                                                        SELECT 
                                                            SUM(CASE WHEN status='Hadir' THEN 1 ELSE 0 END) AS hadir,
                                                            COUNT(*) AS total
                                                        FROM presensi 
                                                        WHERE id_siswa = '$id'
                                                    ");
                                                    $kh = mysqli_fetch_assoc($q_kehadiran);
                                                    $persen = ($kh['total'] > 0) ? round(($kh['hadir'] / $kh['total']) * 100) : 0;
                                                    echo $persen . '%';
                                                    ?>
                                                </td>

                                                <!-- rata-rata nilai -->
                                                <td>
                                                    <?php
                                                    $q_avg = mysqli_query($conn, "
                                                        SELECT AVG(nilai) AS avg_nilai 
                                                        FROM penilaian_tugas 
                                                        WHERE id_siswa = '$id'
                                                    ");
                                                    $avg_result = mysqli_fetch_assoc($q_avg);
                                                    $avg_nilai = $avg_result['avg_nilai'];
                                                    echo $avg_nilai ? round($avg_nilai, 1) : '0';
                                                    ?>
                                                </td>

                                                <td>
                                                    <button class="btn btn-sm btn-info"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#detailSiswaModal"
                                                        data-idsiswa="<?= $row['id_siswa'] ?>">
                                                        Detail
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center">Tidak ada data siswa ditemukan</td>
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

    <!-- Detail Modal -->
    <div class="modal fade" id="detailSiswaModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Siswa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="detailContent">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Informasi Pribadi</h6>
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>Nama</strong></td>
                                        <td id="det-nama">-</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Kelas</strong></td>
                                        <td id="det-kelas">-</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Email</strong></td>
                                        <td id="det-email">-</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Telepon</strong></td>
                                        <td id="det-telepon">-</td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6>Statistik</h6>
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>Kehadiran</strong></td>
                                        <td id="det-kehadiran">-</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Rata-rata Nilai</strong></td>
                                        <td id="det-avgnilai">-</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Tugas Terkumpul</strong></td>
                                        <td id="det-terkumpul">-</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var detailModal = document.getElementById('detailSiswaModal');
            detailModal.addEventListener('show.bs.modal', function(event) {
                var button = event.relatedTarget;
                var id_siswa = button.getAttribute('data-idsiswa');
                if (!id_siswa) return;
                
                // Reset data sebelumnya
                document.getElementById('det-nama').textContent = '-';
                document.getElementById('det-kelas').textContent = '-';
                document.getElementById('det-email').textContent = '-';
                document.getElementById('det-telepon').textContent = '-';
                document.getElementById('det-kehadiran').textContent = '-';
                document.getElementById('det-avgnilai').textContent = '-';
                document.getElementById('det-terkumpul').textContent = '-';
                
                // Panggil endpoint untuk detail siswa
                fetch('siswa_detail.php?id_siswa=' + encodeURIComponent(id_siswa))
                    .then(r => {
                        if (!r.ok) throw new Error('Network response was not ok');
                        return r.json();
                    })
                    .then(data => {
                        document.getElementById('det-nama').textContent = data.nama || '-';
                        document.getElementById('det-kelas').textContent = data.kelas || '-';
                        document.getElementById('det-email').textContent = data.email || '-';
                        document.getElementById('det-telepon').textContent = data.telepon || '-';
                        document.getElementById('det-kehadiran').textContent = (data.kehadiran !== undefined) ? (data.kehadiran + '%') : '-';
                        document.getElementById('det-avgnilai').textContent = (data.avg_nilai !== null && data.avg_nilai !== undefined) ? data.avg_nilai : '-';
                        document.getElementById('det-terkumpul').textContent = (data.terkumpul !== undefined) ? data.terkumpul : '-';
                    })
                    .catch(err => {
                        console.error('Error fetching student details:', err);
                        alert('Gagal mengambil data siswa. Silakan coba lagi.');
                    });
            });
        });
    </script>
</body>
</html>