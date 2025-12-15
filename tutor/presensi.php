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
   AMBIL ID TUTOR DARI SESSION
========================= */
$username = $_SESSION['username'] ?? '';
if ($username === '') {
    session_destroy();
    header("Location: ../index.php");
    exit;
}

// Ambil id_tutor dari tabel users berdasarkan username
$username_escaped = mysqli_real_escape_string($conn, $username);
$q_user = mysqli_query($conn, "
    SELECT u.id_tutor, t.*, m.nama_mapel
    FROM users u
    LEFT JOIN tutor t ON u.id_tutor = t.id_tutor
    LEFT JOIN mapel m ON t.id_mapel = m.id_mapel
    WHERE u.username = '$username_escaped' 
    AND u.role = 'tutor'
    LIMIT 1
");

if (!$q_user || mysqli_num_rows($q_user) === 0) {
    echo "Data tutor tidak ditemukan. Hubungi admin.";
    exit;
}

$user_data = mysqli_fetch_assoc($q_user);
$id_tutor = $user_data['id_tutor'];
$tutor_nama = $user_data['nama_tutor'];
$nama_mapel_tutor = $user_data['nama_mapel'];

// filter params (GET)
$tanggal   = $_GET['tanggal'] ?? date('Y-m-d');
$id_kelas  = $_GET['id_kelas'] ?? '';
$id_jadwal = $_GET['jadwal'] ?? '';
$view_mode = isset($_GET['view']) && $_GET['view'] == 'true';

// Ambil daftar jadwal milik tutor (dipakai untuk dropdown)
$q_jadwal = mysqli_query($conn, "
    SELECT j.id_jadwal, j.id_kelas, j.id_mapel, j.tanggal, j.jam_mulai, j.jam_selesai,
           k.nama_kelas, m.nama_mapel, r.nama_ruangan, j.status
    FROM jadwal j
    LEFT JOIN kelas k ON j.id_kelas = k.id_kelas
    LEFT JOIN mapel m ON j.id_mapel = m.id_mapel
    LEFT JOIN ruangan r ON j.id_ruangan = r.id_ruangan
    WHERE j.id_tutor = " . intval($id_tutor) . "
    ORDER BY j.tanggal DESC, j.jam_mulai
");

// jika id_jadwal dipilih, ambil detail jadwal
$jadwal_selected = null;
if (!empty($id_jadwal)) {
    $id_jadwal_int = intval($id_jadwal);
    $resJ = mysqli_query($conn, "
        SELECT j.*, k.nama_kelas, m.nama_mapel, r.nama_ruangan
        FROM jadwal j
        LEFT JOIN kelas k ON j.id_kelas = k.id_kelas
        LEFT JOIN mapel m ON j.id_mapel = m.id_mapel
        LEFT JOIN ruangan r ON j.id_ruangan = r.id_ruangan
        WHERE j.id_jadwal = $id_jadwal_int
        AND j.id_tutor = " . intval($id_tutor) . "
        LIMIT 1
    ");
    if ($jad = mysqli_fetch_assoc($resJ)) {
        $jadwal_selected = $jad;
        // override filters from jadwal
        $id_kelas = $jad['id_kelas'];
        $tanggal = $jad['tanggal'];
        $nama_mapel = $jad['nama_mapel'];
        $id_mapel = $jad['id_mapel'];
    }
}

// Ambil daftar siswa jika kelas dipilih
$siswa = [];
if (!empty($id_kelas)) {
    $id_kelas_int = intval($id_kelas);
    $q_siswa = mysqli_query($conn, "
        SELECT * FROM siswa 
        WHERE id_kelas = $id_kelas_int 
        AND status_aktif = 'Aktif' 
        ORDER BY nama ASC
    ");
    while ($s = mysqli_fetch_assoc($q_siswa)) $siswa[] = $s;
}

// ====== Proses simpan presensi siswa (POST) ======
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_presensi'])) {
    $tanggal_post = mysqli_real_escape_string($conn, $_POST['tanggal'] ?? date('Y-m-d'));
    $id_kelas_post = intval($_POST['id_kelas'] ?? 0);
    $id_mapel_post = intval($_POST['id_mapel'] ?? 0);
    $id_jadwal_post = intval($_POST['id_jadwal'] ?? 0);
    $waktu_presensi = date('H:i:s');

    // struktur input: presensi[<id_siswa>][status] and presensi[<id_siswa>][keterangan]
    if (!empty($_POST['presensi']) && is_array($_POST['presensi'])) {
        foreach ($_POST['presensi'] as $siswa_db_id => $vals) {
            $siswa_db_id_int = intval($siswa_db_id);
            $status = mysqli_real_escape_string($conn, $vals['status'] ?? '');
            $keterangan = mysqli_real_escape_string($conn, $vals['keterangan'] ?? '');

            // Cek apakah sudah ada presensi untuk siswa di jadwal ini
            $cek = mysqli_query($conn, "
                SELECT id_presensi FROM presensi
                WHERE id_siswa = '$siswa_db_id_int'
                  AND tanggal = '$tanggal_post'
                  AND id_kelas = '$id_kelas_post'
                  AND id_jadwal = '$id_jadwal_post'
                LIMIT 1
            ");
            
            if ($cek && mysqli_num_rows($cek) > 0) {
                $row = mysqli_fetch_assoc($cek);
                $pid = (int)$row['id_presensi'];
                mysqli_query($conn, "
                    UPDATE presensi SET
                        status = '$status',
                        keterangan = '$keterangan',
                        waktu = '$waktu_presensi',
                        created_at = NOW()
                    WHERE id_presensi = $pid
                ");
            } else {
                mysqli_query($conn, "
                    INSERT INTO presensi (id_siswa, id_kelas, id_jadwal, tanggal, waktu, status, keterangan, created_at)
                    VALUES ($siswa_db_id_int, $id_kelas_post, $id_jadwal_post, 
                            '$tanggal_post', '$waktu_presensi', '$status', '$keterangan', NOW())
                ");
            }
        }
        
        // Update status jadwal menjadi "Selesai" jika sudah diisi presensi
        if ($id_jadwal_post > 0) {
            mysqli_query($conn, "
                UPDATE jadwal SET status = 'Selesai' 
                WHERE id_jadwal = $id_jadwal_post
            ");
        }
    }

    $_SESSION['flash_presensi'] = "Presensi berhasil disimpan.";
    // redirect kembali ke halaman dengan filter sama
    $url = "presensi.php?tanggal=" . urlencode($tanggal_post);
    if (!empty($id_kelas_post)) $url .= "&id_kelas=" . urlencode($id_kelas_post);
    if (!empty($id_jadwal_post)) $url .= "&jadwal=" . urlencode($id_jadwal_post);
    header("Location: $url");
    exit;
}

// ===== Ambil existing presensi (prefill) =====
$existingPresensi = [];
if (!empty($id_kelas) && !empty($tanggal) && !empty($id_jadwal)) {
    $qP = mysqli_query($conn, "
        SELECT * FROM presensi
        WHERE tanggal = '" . mysqli_real_escape_string($conn, $tanggal) . "'
          AND id_kelas = " . intval($id_kelas) . "
          AND id_jadwal = " . intval($id_jadwal) . "
    ");
    while ($p = mysqli_fetch_assoc($qP)) {
        $existingPresensi[$p['id_siswa']] = $p;
    }
}

// ===== Proses presensi tutor =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_presensi_tutor'])) {
    $id_jadwal_tutor = intval($_POST['id_jadwal'] ?? 0);
    $status_tutor = mysqli_real_escape_string($conn, $_POST['status_tutor'] ?? 'Hadir');
    $catatan_tutor = mysqli_real_escape_string($conn, $_POST['catatan_tutor'] ?? '');
    $waktu_mulai = mysqli_real_escape_string($conn, $_POST['waktu_mulai'] ?? '');
    $waktu_selesai = mysqli_real_escape_string($conn, $_POST['waktu_selesai'] ?? '');
    
    // Hitung durasi
    $durasi_jam = 0;
    if ($waktu_mulai && $waktu_selesai) {
        $start = strtotime($waktu_mulai);
        $end = strtotime($waktu_selesai);
        if ($start && $end && $end > $start) {
            $durasi_jam = round(($end - $start) / 3600, 2);
        }
    }
    
    // Simpan presensi tutor
    mysqli_query($conn, "
        INSERT INTO presensi_tutor (id_tutor, id_jadwal, status, waktu_mulai, waktu_selesai, durasi_jam, catatan, created_at)
        VALUES ($id_tutor, $id_jadwal_tutor, '$status_tutor', 
                " . ($waktu_mulai ? "'$waktu_mulai'" : "NULL") . ",
                " . ($waktu_selesai ? "'$waktu_selesai'" : "NULL") . ",
                $durasi_jam, '$catatan_tutor', NOW())
    ");
    
    $_SESSION['flash_presensi'] = "Presensi tutor berhasil disimpan.";
    header("Location: presensi.php?jadwal=" . $id_jadwal_tutor);
    exit;
}

// ===== Cek apakah tutor sudah mengisi presensi untuk jadwal ini =====
$presensi_tutor = null;
if (!empty($id_jadwal)) {
    $q_pt = mysqli_query($conn, "
        SELECT * FROM presensi_tutor 
        WHERE id_tutor = $id_tutor 
        AND id_jadwal = " . intval($id_jadwal) . "
        ORDER BY created_at DESC LIMIT 1
    ");
    if ($q_pt && mysqli_num_rows($q_pt) > 0) {
        $presensi_tutor = mysqli_fetch_assoc($q_pt);
    }
}

// flash
$flash = $_SESSION['flash_presensi'] ?? null;
unset($_SESSION['flash_presensi']);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Presensi - Portal Tutor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .btn-group-presensi .btn {
            padding: 0.25rem 0.75rem;
            font-size: 0.875rem;
        }
        .presensi-card {
            border-left: 4px solid #0d6efd;
        }
        .table-presensi tbody tr:hover {
            background-color: #f8f9fa;
        }
        .status-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- sidebar -->
        <div class="col-md-2 sidebar p-0">
            <div class="text-center text-white py-4">
                <i class="bi bi-book-fill" style="font-size:2.5rem;"></i>
                <h5 class="mt-2">Bimbel System</h5>
                <small>Portal Tutor</small>
            </div>
            <nav class="nav flex-column">
                <a class="nav-link" href="dashboard_tutor.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                <a class="nav-link" href="jadwal.php"><i class="bi bi-calendar3"></i> Jadwal Mengajar</a>
                <a class="nav-link active" href="presensi.php"><i class="bi bi-check2-square"></i> Presensi</a>
                <a class="nav-link" href="materi.php"><i class="bi bi-journal-text"></i> Materi</a>
                <a class="nav-link" href="tugas.php"><i class="bi bi-clipboard-check"></i> Tugas & Penilaian</a>
                <a class="nav-link" href="siswa.php"><i class="bi bi-people"></i> Data Siswa</a>
                <a class="nav-link" href="profil.php"><i class="bi bi-person"></i> Profil</a>
                <a class="nav-link" href="../logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
            </nav>
        </div>

        <!-- main -->
        <div class="col-md-10 content-wrapper p-4">
            <div class="page-header mb-4">
                <h2><i class="bi bi-check2-square me-2"></i>Presensi</h2>
                <p class="text-muted">Kelola presensi siswa dan kehadiran tutor</p>
                <p class="text-muted"><strong>Tutor:</strong> <?= h($tutor_nama) ?> | <strong>Mapel:</strong> <?= h($nama_mapel_tutor) ?></p>
            </div>

            <?php if ($flash): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= h($flash) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Filter Section -->
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-filter me-2"></i>Filter Presensi</h5>
                    <form id="filterForm" method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Pilih Jadwal</label>
                            <select class="form-select" name="jadwal" onchange="this.form.submit()">
                                <option value="">-- Pilih Jadwal --</option>
                                <?php
                                mysqli_data_seek($q_jadwal, 0);
                                while ($jr = mysqli_fetch_assoc($q_jadwal)):
                                    $sel = ($id_jadwal == $jr['id_jadwal']) ? 'selected' : '';
                                    $date_label = date('d/m/Y', strtotime($jr['tanggal']));
                                    $time_label = substr($jr['jam_mulai'], 0, 5) . '-' . substr($jr['jam_selesai'], 0, 5);
                                ?>
                                    <option value="<?= h($jr['id_jadwal']) ?>" <?= $sel ?>>
                                        <?= h($jr['nama_kelas'] . ' - ' . $jr['nama_mapel'] . ' (' . $date_label . ' ' . $time_label . ')') ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tanggal</label>
                            <input type="date" name="tanggal" class="form-control" value="<?= h($tanggal) ?>" onchange="this.form.submit()">
                        </div>
                        <div class="col-md-2 align-self-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-search"></i> Tampilkan
                            </button>
                        </div>
                        <div class="col-md-2 align-self-end">
                            <a href="presensi.php" class="btn btn-outline-secondary w-100">
                                <i class="bi bi-arrow-clockwise"></i> Reset
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <?php if (!empty($jadwal_selected)): ?>
                <!-- Info Jadwal -->
                <div class="card mb-4 presensi-card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <h5><?= h($jadwal_selected['nama_mapel']) ?> - <?= h($jadwal_selected['nama_kelas']) ?></h5>
                                <div class="row mt-2">
                                    <div class="col-md-3">
                                        <small class="text-muted">Tanggal:</small>
                                        <p class="mb-0"><strong><?= date('d F Y', strtotime($jadwal_selected['tanggal'])) ?></strong></p>
                                    </div>
                                    <div class="col-md-3">
                                        <small class="text-muted">Waktu:</small>
                                        <p class="mb-0"><strong><?= substr($jadwal_selected['jam_mulai'], 0, 5) ?> - <?= substr($jadwal_selected['jam_selesai'], 0, 5) ?></strong></p>
                                    </div>
                                    <div class="col-md-3">
                                        <small class="text-muted">Ruangan:</small>
                                        <p class="mb-0"><strong><?= h($jadwal_selected['nama_ruangan'] ?? '-') ?></strong></p>
                                    </div>
                                    <div class="col-md-3">
                                        <small class="text-muted">Status:</small>
                                        <p class="mb-0">
                                            <?php 
                                            $status_class = 'bg-secondary';
                                            if ($jadwal_selected['status'] == 'Berlangsung') $status_class = 'bg-primary';
                                            if ($jadwal_selected['status'] == 'Selesai') $status_class = 'bg-success';
                                            ?>
                                            <span class="badge <?= $status_class ?>"><?= h($jadwal_selected['status'] ?? 'Belum Dimulai') ?></span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 text-end">
                                <?php if ($view_mode): ?>
                                    <span class="badge bg-info status-badge">Mode View</span>
                                <?php elseif ($presensi_tutor): ?>
                                    <span class="badge bg-success status-badge">Presensi Tutor Sudah Diisi</span>
                                <?php else: ?>
                                    <span class="badge bg-warning status-badge">Presensi Tutor Belum Diisi</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Presensi Tutor Form (jika belum diisi dan bukan view mode) -->
                <?php if (!$presensi_tutor && !$view_mode): ?>
                    <div class="card mb-4">
                        <div class="card-header bg-white">
                            <h6 class="mb-0"><i class="bi bi-person-check me-2"></i>Presensi Tutor</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" class="row g-3">
                                <input type="hidden" name="simpan_presensi_tutor" value="1">
                                <input type="hidden" name="id_jadwal" value="<?= h($id_jadwal) ?>">
                                
                                <div class="col-md-3">
                                    <label class="form-label">Status Kehadiran</label>
                                    <select class="form-select" name="status_tutor" required>
                                        <option value="Hadir">Hadir</option>
                                        <option value="Izin">Izin</option>
                                        <option value="Sakit">Sakit</option>
                                        <option value="Alpha">Alpha</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-3">
                                    <label class="form-label">Waktu Mulai</label>
                                    <input type="time" class="form-control" name="waktu_mulai" value="<?= date('H:i') ?>">
                                </div>
                                
                                <div class="col-md-3">
                                    <label class="form-label">Waktu Selesai</label>
                                    <input type="time" class="form-control" name="waktu_selesai">
                                </div>
                                
                                <div class="col-md-3">
                                    <label class="form-label">Catatan</label>
                                    <input type="text" class="form-control" name="catatan_tutor" placeholder="Opsional">
                                </div>
                                
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-check2-circle"></i> Simpan Presensi Tutor
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Presensi Siswa Form -->
                <?php if (!empty($siswa)): ?>
                    <form method="POST">
                        <input type="hidden" name="simpan_presensi" value="1">
                        <input type="hidden" name="tanggal" value="<?= h($tanggal) ?>">
                        <input type="hidden" name="id_kelas" value="<?= h($id_kelas) ?>">
                        <input type="hidden" name="id_mapel" value="<?= h($id_mapel ?? '') ?>">
                        <input type="hidden" name="id_jadwal" value="<?= h($id_jadwal) ?>">

                        <div class="card">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="bi bi-people me-2"></i>Presensi Siswa 
                                    <small class="text-muted">(<?= count($siswa) ?> siswa)</small>
                                </h5>
                                <?php if (!$view_mode): ?>
                                    <button type="submit" class="btn btn-success">
                                        <i class="bi bi-save"></i> Simpan Presensi Siswa
                                    </button>
                                <?php else: ?>
                                    <span class="badge bg-info">Mode View Only</span>
                                <?php endif; ?>
                            </div>

                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover table-presensi">
                                        <thead class="table-light">
                                            <tr>
                                                <th width="5%">No</th>
                                                <th width="10%">ID Siswa</th>
                                                <th>Nama Siswa</th>
                                                <th width="25%">Status Kehadiran</th>
                                                <th width="25%">Keterangan</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $no=1; foreach ($siswa as $s): 
                                                $dbid = (int)$s['id_siswa'];
                                                $preset = $existingPresensi[$s['id_siswa']] ?? null;
                                                $preset_status = $preset['status'] ?? 'Hadir';
                                                $preset_ket = $preset['keterangan'] ?? '';
                                            ?>
                                                <tr>
                                                    <td><?= $no++ ?></td>
                                                    <td><?= h($s['id_siswa']) ?></td>
                                                    <td><?= h($s['nama']) ?></td>
                                                    <td>
                                                        <div class="btn-group btn-group-presensi" role="group">
                                                            <input type="radio" class="btn-check" name="presensi[<?= $dbid ?>][status]" 
                                                                   id="hadir<?= $dbid ?>" value="Hadir" 
                                                                   <?= ($preset_status === 'Hadir') ? 'checked' : '' ?>
                                                                   <?= $view_mode ? 'disabled' : '' ?>>
                                                            <label class="btn btn-outline-success btn-sm" for="hadir<?= $dbid ?>">H</label>

                                                            <input type="radio" class="btn-check" name="presensi[<?= $dbid ?>][status]" 
                                                                   id="izin<?= $dbid ?>" value="Izin" 
                                                                   <?= ($preset_status === 'Izin') ? 'checked' : '' ?>
                                                                   <?= $view_mode ? 'disabled' : '' ?>>
                                                            <label class="btn btn-outline-warning btn-sm" for="izin<?= $dbid ?>">I</label>

                                                            <input type="radio" class="btn-check" name="presensi[<?= $dbid ?>][status]" 
                                                                   id="sakit<?= $dbid ?>" value="Sakit" 
                                                                   <?= ($preset_status === 'Sakit') ? 'checked' : '' ?>
                                                                   <?= $view_mode ? 'disabled' : '' ?>>
                                                            <label class="btn btn-outline-info btn-sm" for="sakit<?= $dbid ?>">S</label>

                                                            <input type="radio" class="btn-check" name="presensi[<?= $dbid ?>][status]" 
                                                                   id="alpha<?= $dbid ?>" value="Alpha" 
                                                                   <?= ($preset_status === 'Alpha') ? 'checked' : '' ?>
                                                                   <?= $view_mode ? 'disabled' : '' ?>>
                                                            <label class="btn btn-outline-danger btn-sm" for="alpha<?= $dbid ?>">A</label>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control form-control-sm" 
                                                               name="presensi[<?= $dbid ?>][keterangan]" 
                                                               value="<?= h($preset_ket) ?>" 
                                                               placeholder="Keterangan"
                                                               <?= $view_mode ? 'readonly' : '' ?>>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </form>

                <?php elseif (!empty($jadwal_selected)): ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i> Tidak ada siswa aktif di kelas ini.
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <!-- Instruksi awal -->
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-calendar-check" style="font-size: 4rem; color: #6c757d;"></i>
                        <h4 class="mt-3">Pilih Jadwal</h4>
                        <p class="text-muted">Silakan pilih jadwal mengajar dari dropdown di atas untuk mengisi presensi.</p>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Auto submit filter ketika dropdown berubah
    document.querySelectorAll('select[name="jadwal"], input[name="tanggal"]').forEach(function(el) {
        el.addEventListener('change', function() {
            document.getElementById('filterForm').submit();
        });
    });
    
    // Set waktu selesai default (1 jam dari sekarang)
    document.addEventListener('DOMContentLoaded', function() {
        const waktuMulai = document.querySelector('input[name="waktu_mulai"]');
        const waktuSelesai = document.querySelector('input[name="waktu_selesai"]');
        
        if (waktuMulai && waktuSelesai && !waktuSelesai.value) {
            const now = new Date();
            now.setHours(now.getHours() + 1);
            waktuSelesai.value = now.toTimeString().slice(0, 5);
        }
    });
</script>
</body>
</html>