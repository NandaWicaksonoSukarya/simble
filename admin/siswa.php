<?php
require "../config.php";
session_start();

// Cek login admin
if (!isset($_SESSION['login']) || $_SESSION['role'] !== "admin") {
    header("Location: ../login.php");
    exit;
}

// ========================================
// FILTER & SEARCH (NAMA + STATUS)
// ========================================
$filter = [];

if (!empty($_GET['cari'])) {
    $cari = mysqli_real_escape_string($conn, $_GET['cari']);
    $filter[] = "siswa.nama LIKE '%$cari%'";
}

if (!empty($_GET['kelas'])) {
    $kelas = mysqli_real_escape_string($conn, $_GET['kelas']);
    $filter[] = "kelas.nama_kelas = '$kelas'";
}

if (!empty($_GET['status'])) {
    $status = mysqli_real_escape_string($conn, $_GET['status']);
    $filter[] = "siswa.status_bayar = '$status'";
}


$where = $filter ? "WHERE " . implode(" AND ", $filter) : "";


// ========================================
// PAGINATION
// ========================================
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

$countQuery = mysqli_query($conn, "
    SELECT siswa.id_siswa
    FROM siswa
    LEFT JOIN kelas ON siswa.id_kelas = kelas.id_kelas
    $where
");
$count = mysqli_num_rows($countQuery);
$pages = ceil($count / $limit);

$q = mysqli_query($conn, "
    SELECT siswa.*, kelas.nama_kelas 
    FROM siswa 
    LEFT JOIN kelas ON siswa.id_kelas = kelas.id_kelas
    $where
    ORDER BY siswa.id_siswa ASC
    LIMIT $start, $limit
");


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
                    <small>Admin Panel</small>
                </div>
                <nav class="nav flex-column">
                    <a class="nav-link" href="dashboardadmin.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                    <a class="nav-link active" href="siswa.php"><i class="bi bi-people"></i> Data Siswa</a>
                    <a class="nav-link" href="tutor.php"><i class="bi bi-person-badge"></i> Data Tutor</a>
                    <a class="nav-link" href="kelas.php"><i class="bi bi-calendar3"></i> Jadwal Kelas</a>
                    <a class="nav-link" href="pembayaran.php"><i class="bi bi-cash-coin"></i> Pembayaran</a>
                    <a class="nav-link" href="mapel.php"><i class="bi bi-journal-text"></i> Mata Pelajaran</a>
                    <a class="nav-link" href="laporan.php"><i class="bi bi-file-earmark-text"></i> Laporan</a>
                    <a class="nav-link" href="../logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 content-wrapper">

                <div class="page-header d-flex justify-content-between align-items-center">
                    <div>
                        <h2>Data Siswa</h2>
                        <p class="text-muted">Kelola data siswa bimbel</p>
                    </div>
                    <a href="../pendaftaran/form-pendaftaran.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Tambah Siswa Baru
                    </a>
                </div>

                <!-- FILTER -->
                <div class="card mb-3">
                    <div class="card-body">
                        <form method="GET" class="row g-2">
                            <div class="col-md-4">
                                <input type="text" name="cari" class="form-control" placeholder="Cari nama atau ID siswa..."
                                    value="<?= $_GET['cari'] ?? '' ?>">
                            </div>

                            <div class="col-md-3">
                                <select name="kelas" class="form-select">
                                    <option value="">Semua Kelas</option>

                                    <?php
                                    // Ambil data kelas dari tabel
                                    $qKelas = $conn->query("SELECT nama_kelas FROM kelas ORDER BY nama_kelas ASC");

                                    while ($row = $qKelas->fetch_assoc()):
                                        $nk = $row['nama_kelas'];
                                    ?>
                                        <option value="<?= htmlspecialchars($nk) ?>"
                                            <?= (@$_GET['kelas'] == $nk ? 'selected' : '') ?>>
                                            <?= htmlspecialchars($nk) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>


                            <div class="col-md-3">
                                <select name="status" class="form-select">
                                    <option value="">Semua Status</option>
                                    <option value="aktif" <?= (@$_GET['status'] == "aktif" ? 'selected' : '') ?>>Aktif</option>
                                    <option value="menunggu verifikasi" <?= (@$_GET['status'] == "menunggu verifikasi" ? 'selected' : '') ?>>Menunggu Verifikasi</option>
                                    <option value="ditolak" <?= (@$_GET['status'] == "ditolak" ? 'selected' : '') ?>>Ditolak</option>
                                </select>

                            </div>

                            <div class="col-md-2">
                                <button class="btn btn-primary w-100">Filter</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- TABLE -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">

                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID Siswa</th>
                                        <th>Nama Lengkap</th>
                                        <th>NIB</th>
                                        <th>Kelas</th>
                                        <th>Email</th>
                                        <th>No. Telepon</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php while ($row = mysqli_fetch_assoc($q)) : ?>
                                        <tr>
                                            <td><?= $row['id_siswa'] ?></td>
                                            <td><?= $row['nama'] ?></td>
                                            <td><?= $row['nib'] ?? '-' ?></td>
                                            <td><?= htmlspecialchars($row['nama_kelas'] ?? '-') ?></td>
                                            <td><?= $row['email'] ?></td>
                                            <td><?= $row['telepon'] ?></td>

                                            <td>
                                                <!-- Status Aktif -->
                                                <?php if ($row['status_aktif'] == 'aktif'): ?>
                                                    <span class="badge bg-success">Aktif</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Tidak Aktif</span>
                                                <?php endif; ?>

                                                <br>

                                                <!-- Status Bayar -->
                                                <?php if ($row['status_bayar'] == 'sudah bayar'): ?>
                                                    <span class="badge bg-primary">Sudah Bayar</span>
                                                <?php elseif ($row['status_bayar'] == 'menunggu verifikasi'): ?>
                                                    <span class="badge bg-warning text-dark">Menunggu Verifikasi</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Belum Bayar</span>
                                                <?php endif; ?>
                                            </td>

                                            <td>
                                                <!-- Detail -->
                                                <button class="btn btn-sm btn-info text-white viewDetail"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#detailModal"
                                                    data-data='<?= json_encode($row) ?>'>
                                                    <i class="bi bi-eye"></i>
                                                </button>

                                                <!-- Edit -->
                                                <a href="edit-siswa.php?id=<?= $row['id_siswa'] ?>" class="btn btn-sm btn-warning">
                                                    <i class="bi bi-pencil"></i>
                                                </a>

                                                <!-- AKTIFKAN SISWA (HANYA JIKA BELUM AKTIF) -->
                                                <?php if ($row['status_aktif'] !== 'aktif'): ?>
                                                    <a href="aktifkan-siswa.php?id=<?= $row['id_siswa'] ?>"
                                                        class="btn btn-sm btn-success"
                                                        title="Aktifkan Siswa">
                                                        <i class="bi bi-check-circle"></i>
                                                    </a>
                                                <?php endif; ?>

                                                <!-- Hapus -->
                                                <a onclick="return confirm('Hapus siswa ini?')"
                                                    href="hapus-siswa.php?id=<?= $row['id_siswa'] ?>"
                                                    class="btn btn-sm btn-danger">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </td>

                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>

                        </div>

                        <?php
                        $queryString = $_GET;
                        unset($queryString['page']);
                        $query = http_build_query($queryString);
                        ?>

                        <!-- PAGINATION -->
                        <nav>
                            <ul class="pagination mt-3">

                                <li class="page-item <?= ($page <= 1 ? 'disabled' : '') ?>">
                                    <a class="page-link" href="?page=<?= $page - 1 ?>">Previous</a>
                                </li>

                                <?php for ($i = 1; $i <= $pages; $i++): ?>
                                    <li class="page-item <?= ($page == $i ? 'active' : '') ?>">
                                        <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>

                                <li class="page-item <?= ($page >= $pages ? 'disabled' : '') ?>">
                                    <a class="page-link" href="?page=<?= $page + 1 ?>">Next</a>
                                </li>

                            </ul>
                        </nav>

                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- DETAIL MODAL -->
    <div class="modal fade" id="detailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Siswa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="modalContent"></div>

                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.querySelectorAll(".viewDetail").forEach(btn => {
            btn.addEventListener("click", function() {
                let data = JSON.parse(this.dataset.data);

                let html = `
    <div class="row">
        <div class="col-md-6">
            <h6>Informasi Pribadi</h6>
            <table class="table table-sm">
                <tr><td><strong>ID Siswa</strong></td><td>${data.id_siswa}</td></tr>
                <tr><td><strong>Nama Lengkap</strong></td><td>${data.nama}</td></tr>
                <tr><td><strong>TTL</strong></td><td>${data.tmp_lahir}, ${data.tgl_lahir}</td></tr>
                <tr><td><strong>Jenis Kelamin</strong></td><td>${data.jk}</td></tr>
                <tr><td><strong>Alamat</strong></td><td>${data.alamat}</td></tr>
            </table>
        </div>

        <div class="col-md-6">
            <h6>Kontak</h6>
            <table class="table table-sm">
                <tr><td><strong>Email</strong></td><td>${data.email}</td></tr>
                <tr><td><strong>No. Telepon</strong></td><td>${data.telepon}</td></tr>
                <tr><td><strong>Orang Tua</strong></td><td>${data.ortu}</td></tr>
                <tr><td><strong>No. Telepon Ortu</strong></td><td>${data.ortu_telp}</td></tr>
            </table>
        </div>
    </div>

    <hr>
    <h6>Informasi Akademik</h6>
    <table class="table table-sm">
        <tr><td><strong>NIB</strong></td><td>${data.nib ?? '-'}</td></tr>
        <tr><td><strong>Kelas</strong></td><td>${data.nama_kelas ?? '-'}</td></tr>
        <tr><td><strong>Status</strong></td><td>${data.status_aktif}</td></tr>
        <tr><td><strong>TTL</strong></td><td>${data.tmp_lahir}, ${data.tgl_lahir}</td></tr>

    </table>
`;


                document.getElementById("modalContent").innerHTML = html;
            });
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>