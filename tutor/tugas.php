<?php
session_start();
// echo "ID TUTOR SESSION = ";
// var_dump($_SESSION['id_tutor']);


require "../config.php"; // pastikan file koneksi mysqli ada dan $conn tersedia

// proteksi route: hanya tutor yang boleh akses
if (!isset($_SESSION['login']) || ($_SESSION['role'] !== 'tutor' && $_SESSION['role'] !== 'admin')) {
    header("Location: ../index.php");
    exit;
}

// ambil nama tutor dari session (asumsi)
$tutor_name = $_SESSION['nama'] ?? '';

// ambil semua tugas yang dibuat tutor (jika admin -> tampil semua)
if ($_SESSION['role'] === 'admin') {
    $q_tugas = mysqli_query($conn, "SELECT * FROM tugas ORDER BY created_at DESC");
} else {
    // menggunakan kolom `tutor` pada tabel tugas
    $safe_tutor = mysqli_real_escape_string($conn, $tutor_name);
    $q_tugas = mysqli_query($conn, "SELECT * FROM tugas WHERE id_tutor='" . intval($_SESSION['id_tutor']) . "'
 ORDER BY created_at DESC");
}

// tentukan tugas yang dipilih untuk tab Penilaian
$selected_tugas_id = isset($_GET['id_tugas']) ? intval($_GET['id_tugas']) : null;

// Ambil ID tugas pertama tanpa memakan row
if (!$selected_tugas_id) {
    $q_first = mysqli_query($conn, "SELECT id_tugas FROM tugas WHERE id_tutor='" . intval($_SESSION['id_tutor']) . "' ORDER BY created_at DESC LIMIT 1");
    $first = mysqli_fetch_assoc($q_first);
    if ($first) {
        $selected_tugas_id = $first['id_tugas'];
    }
}


// ambil data tugas terpilih untuk menampilkan judul di tab Penilaian
$selected_tugas = null;
if ($selected_tugas_id) {
    $t = mysqli_query($conn, "SELECT * FROM tugas WHERE id_tugas = " . intval($selected_tugas_id));
    $selected_tugas = mysqli_fetch_assoc($t);
}

// ambil daftar submission / nilai untuk tugas terpilih
$submissions = [];

if ($selected_tugas_id) {
    $st = mysqli_query($conn, "
        SELECT 
            pt.id_penilaian,
            pt.nilai,
            pt.file_jawaban,
            pt.tgl_kumpul,
            s.nama AS siswa_nama
        FROM penilaian_tugas pt
        JOIN siswa s ON pt.id_siswa = s.id_siswa
        WHERE pt.id_tugas = " . intval($selected_tugas_id) . "
        ORDER BY pt.tgl_kumpul DESC
    ");

    while ($r = mysqli_fetch_assoc($st)) {
        $submissions[] = $r;
    }
}


$mapel = mysqli_query($conn, "SELECT id_mapel, nama_mapel FROM mapel");
$kelas = mysqli_query($conn, "SELECT id_kelas, nama_kelas FROM kelas");

// helper format tanggal
function format_date($dt)
{
    if (!$dt) return '-';
    return date('j M Y', strtotime($dt));
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tugas & Penilaian - Sistem Informasi Bimbel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar (tetap sama) -->
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
                    <a class="nav-link active" href="tugas.php"><i class="bi bi-clipboard-check"></i> Tugas & Penilaian</a>
                    <a class="nav-link" href="siswa.php"><i class="bi bi-people"></i> Data Siswa</a>
                    <a class="nav-link" href="profil.php"><i class="bi bi-person"></i> Profil</a>
                    <a class="nav-link" href="../logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
                </nav>
            </div>

            <!-- Main Content (UI tetap persis) -->
            <div class="col-md-10 content-wrapper">
                <div class="page-header d-flex justify-content-between align-items-center">
                    <div>
                        <h2>Tugas & Penilaian</h2>
                        <p class="text-muted">Kelola tugas dan nilai siswa</p>
                    </div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#buatTugasModal">
                        <i class="bi bi-plus-circle"></i> Buat Tugas Baru
                    </button>
                </div>

                <!-- Tabs -->
                <ul class="nav nav-tabs mb-4">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#tugas">Daftar Tugas</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#penilaian">Penilaian</a>
                    </li>
                </ul>

                <div class="tab-content">
                    <!-- Tab Tugas -->
                    <div class="tab-pane fade show active" id="tugas">
                        <?php
                        // jika tidak ada tugas, tampilkan info
                        if (mysqli_num_rows($q_tugas) == 0) { ?>
                            <div class="alert alert-info">Belum ada tugas. Buat tugas baru menggunakan tombol "Buat Tugas Baru".</div>
                            <?php } else {
                            echo "Jumlah tugas: " . mysqli_num_rows($q_tugas);
                            while ($tugas = mysqli_fetch_assoc($q_tugas)) {
                                // hitung jumlah terkumpul dan total siswa (jika ada tabel siswa per kelas, asumsi sederhana: hitung nilai entries)
                                $tid = intval($tugas['id_tugas']);
                                $q_count = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM nilai WHERE id_tugas=$tid");
                                $cnt = mysqli_fetch_assoc($q_count)['cnt'] ?? 0;
                                // Asumsi total siswa per kelas: jika ada tabel kelas atau perhitungan lain, harus disesuaikan.
                                // Untuk sekarang tampilkan "X terkumpul" berdasarkan count.
                                // status badge
                                $status_badge = '<span class="badge bg-secondary">Tidak Aktif</span>';
                                $status_label = $tugas['status'] ?? '';
                                if (strtolower($status_label) === 'aktif') $status_badge = '<span class="badge bg-warning">Aktif</span>';
                                if (strtolower($status_label) === 'selesai') $status_badge = '<span class="badge bg-success">Selesai</span>';
                            ?>
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <div class="d-flex align-items-center mb-2">
                                                    <?= $status_badge ?>
                                                </div>
                                                <h5><?= htmlspecialchars($tugas['judul']) ?></h5>
                                                <p class="text-muted mb-2">Kelas <?= htmlspecialchars($tugas['id_kelas']) ?></p>
                                                <p class="mb-2"><?= nl2br(htmlspecialchars($tugas['deskripsi'])) ?></p>
                                                <div class="d-flex align-items-center text-muted small">
                                                    <i class="bi bi-calendar me-1"></i> Deadline: <?= $tugas['deadline'] ? date('j M Y, H:i', strtotime($tugas['deadline'])) : '-' ?>
                                                    <span class="mx-2">|</span>
                                                    <i class="bi bi-people me-1"></i> <?= intval($cnt) ?>/<?= htmlspecialchars($tugas['id_kelas']) ? 'siswa' : 'siswa' ?> mengumpulkan
                                                </div>
                                            </div>
                                            <div>
                                                <a href="tugas_detail.php?id=<?= $tugas['id_tugas'] ?>" class="btn btn-sm btn-info mb-2">
                                                    <i class="bi bi-eye"></i> Detail
                                                </a>
                                                <a href="tugas_edit.php?id=<?= $tugas['id_tugas'] ?>" class="btn btn-sm btn-warning mb-2">
                                                    <i class="bi bi-pencil"></i> Edit
                                                </a>
                                                <a href="tugas_nilai.php?tugas_id=<?= $tugas['id_tugas'] ?>" class="btn btn-sm btn-primary">
                                                    <i class="bi bi-star"></i> Nilai
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                        <?php }
                        } ?>
                    </div>

                    <!-- Tab Penilaian -->
                    <div class="tab-pane fade" id="penilaian">
                        <div class="card">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">Penilaian: <?= $selected_tugas ? htmlspecialchars($selected_tugas['judul']) : '-' ?></h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <form method="post" action="simpan_nilai.php">
                                        <input type="hidden" name="tugas_id" value="<?= intval($selected_tugas_id) ?>">
                                        <table class="table table-hover">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Nama Siswa</th>
                                                    <th>Tanggal Kumpul</th>
                                                    <th>File</th>
                                                    <th>Nilai</th>
                                                    <th>Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (count($submissions) === 0) { ?>
                                                    <tr>
                                                        <td colspan="5" class="text-center text-muted">Belum ada pengumpulan untuk tugas ini.</td>
                                                    </tr>
                                                    <?php } else {
                                                    foreach ($submissions as $s) {
                                                        $file_btn = $s['file_path'] ? '<a href="' . htmlspecialchars($s['file_path']) . '" class="btn btn-sm btn-outline-primary" download><i class="bi bi-download"></i></a>' : '-';
                                                    ?>
                                                        <tr>
                                                            <td><?= htmlspecialchars($s['siswa_nama']) ?></td>
                                                            <td><?= format_date($s['uploaded_at']) ?></td>
                                                            <td><?= $file_btn ?></td>
                                                            <td>
                                                                <input type="number" name="nilai[<?= intval($s['id']) ?>]" class="form-control form-control-sm" style="width: 80px;" value="<?= htmlspecialchars($s['nilai']) ?>">
                                                            </td>
                                                            <td>
                                                                <button type="submit" name="save_id" value="<?= intval($s['id']) ?>" class="btn btn-sm btn-success">Simpan</button>
                                                            </td>
                                                        </tr>
                                                <?php }
                                                } ?>
                                            </tbody>
                                        </table>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>


    <!-- Modal Buat Tugas -->
    <div class="modal fade" id="buatTugasModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">Buat Tugas Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <form action="buat_tugas.php" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">

                        <!-- JUDUL -->
                        <div class="mb-3">
                            <label class="form-label">Judul Tugas</label>
                            <input type="text" name="judul" class="form-control" required>
                        </div>

                        <!-- DROPDOWN MAPEL -->
                        <div class="mb-3">
                            <label class="form-label">Mapel</label>
                            <select name="id_mapel" class="form-select" required>
                                <option value="">-- Pilih Mapel --</option>
                                <?php while ($m = mysqli_fetch_assoc($mapel)) : ?>
                                    <option value="<?= $m['id_mapel']; ?>"><?= $m['nama_mapel']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <!-- DROPDOWN KELAS -->
                        <div class="mb-3">
                            <label class="form-label">Kelas</label>
                            <select name="id_kelas" class="form-select" required>
                                <option value="">-- Pilih Kelas --</option>
                                <?php while ($k = mysqli_fetch_assoc($kelas)) : ?>
                                    <option value="<?= $k['id_kelas']; ?>"><?= $k['nama_kelas']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <!-- DESKRIPSI -->
                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea name="deskripsi" class="form-control" rows="3"></textarea>
                        </div>

                        <!-- DEADLINE -->
                        <div class="mb-3">
                            <label class="form-label">Deadline</label>
                            <input type="datetime-local" name="deadline" class="form-control">
                        </div>

                        <!-- FILE ATTACH -->
                        <div class="mb-3">
                            <label class="form-label">Lampiran (opsional)</label>
                            <input type="file" name="lampiran" class="form-control">
                        </div>

                        <!-- ID TUTOR -->
                        <input type="hidden" name="id_tutor" value="<?= $_SESSION['id_tutor']; ?>">

                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Tugas</button>
                    </div>
                </form>

            </div>
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>