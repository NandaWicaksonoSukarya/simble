<?php
require "../config.php";
session_start();

if (!isset($_SESSION['login']) || $_SESSION['role'] !== "admin") {
    header("Location: ../login.php");
    exit;
}

// ========================
// DATA STATISTIK
// ========================
$totalTutor = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) total FROM tutor")
)['total'];

$tutorAktif = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) total FROM tutor WHERE status='Aktif'")
)['total'];

$tutorCuti = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) total FROM tutor WHERE status='Cuti'")
)['total'];

$mengajarHariIni = mysqli_fetch_assoc(
    mysqli_query($conn, "
        SELECT COUNT(DISTINCT id_tutor) total
        FROM jadwal
        WHERE tanggal = CURDATE()
    ")
)['total'] ?? 0;


// ========================
// DATA TUTOR (CARD)
// ========================
$q = mysqli_query($conn, "
    SELECT 
        t.id_tutor,
        t.nama_tutor,
        t.email,
        t.telepon,
        t.status,
        m.nama_mapel,

        COUNT(DISTINCT k.id_kelas) AS kelas_minggu,
        COUNT(DISTINCT s.id_siswa) AS jumlah_siswa

    FROM tutor t
    LEFT JOIN mapel m ON t.id_mapel = m.id_mapel
    LEFT JOIN jadwal k ON t.id_tutor = k.id_tutor
    LEFT JOIN siswa s ON k.id_kelas = s.id_kelas

    GROUP BY t.id_tutor
");

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Tutor - Sistem Informasi Bimbel</title>
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
                    <i class="bi bi-person-badge" style="font-size: 2.5rem;"></i>
                    <h5 class="mt-2">Bimbel System</h5>
                    <small>Admin Panel</small>
                </div>

                <nav class="nav flex-column">
                    <a class="nav-link" href="dashboardadmin.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                    <a class="nav-link" href="siswa.php"><i class="bi bi-people"></i> Data Siswa</a>
                    <a class="nav-link active" href="tutor.php"><i class="bi bi-person-badge"></i> Data Tutor</a>
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
                        <h2>Data Tutor</h2>
                        <p class="text-muted">Kelola data tutor bimbel</p>
                    </div>

                    <a href="tambahtutor.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Tambah Tutor Baru
                    </a>
                </div>

                <div class="d-flex gap-3 mt-4">

                    <!-- Total Tutor -->
                    <div class="p-4 rounded shadow-sm bg-white flex-fill">
                        <p class="text-secondary m-0">Total Tutor</p>
                        <h2 class="fw-bold mt-2"><?= $totalTutor ?></h2>
                    </div>

                    <!-- Tutor Aktif -->
                    <div class="p-4 rounded shadow-sm bg-white flex-fill">
                        <p class="text-secondary m-0">Tutor Aktif</p>
                        <h2 class="fw-bold mt-2"><?= $tutorAktif ?></h2>
                    </div>

                    <!-- Mengajar Hari Ini -->
                    <div class="p-4 rounded shadow-sm bg-white flex-fill">
                        <p class="text-secondary m-0">Mengajar Hari Ini</p>
                        <h2 class="fw-bold mt-2"><?= $mengajarHariIni ?></h2>
                    </div>

                    <!-- Cuti / Izin -->
                    <div class="p-4 rounded shadow-sm bg-white flex-fill">
                        <p class="text-secondary m-0">Cuti/Izin</p>
                        <h2 class="fw-bold mt-2"><?= $tutorCuti ?></h2>
                    </div>

                </div> <br>

                <!-- CARD GRID -->
                <div class="row">

                    <?php while ($row = mysqli_fetch_assoc($q)) : ?>

                        <?php
                        // Inisial Nama
                        $namaParts = explode(" ", $row['nama_tutor']);
                        $inisial = strtoupper($namaParts[0][0] . ($namaParts[1][0] ?? ''));

                        // Warna Lingkaran
                        $colors = ["#0d6efd", "#198754", "#dc3545", "#ffc107", "#20c997", "#0dcaf0", "#6f42c1"];
                        $color = $colors[array_rand($colors)];
                        ?>

                        <div class="col-md-4 mb-4">
                            <div class="card shadow-sm" style="border-radius: 12px;">

                                <!-- HEADER -->
                                <div class="card-body text-center">

                                    <!-- Foto Lingkaran -->
                                    <div style="
                    margin: 0 auto; 
                    width: 70px; 
                    height: 70px; 
                    border-radius: 50%; 
                    background: <?= $color ?>; 
                    display:flex;
                    align-items:center;
                    justify-content:center;
                    color: white;
                    font-weight: 600;
                    font-size: 24px;">
                                        <?= $inisial ?>
                                    </div>

                                    <h5 class="mt-3 mb-0"><?= $row['nama_tutor'] ?></h5>
                                    <small class="text-muted"><?= $row['nama_mapel'] ?></small>

                                    <!-- STATUS -->
                                    <div class="mt-2">
                                        <?php if ($row['status'] == "Aktif") : ?>
                                            <span class="badge bg-success">Aktif</span>
                                        <?php elseif ($row['status'] == "Cuti") : ?>
                                            <span class="badge bg-warning">Cuti</span>
                                        <?php else : ?>
                                            <span class="badge bg-secondary"><?= $row['status'] ?></span>
                                        <?php endif; ?>
                                    </div>

                                    <hr>

                                    <!-- INFORMASI -->
                                    <div class="text-start small">
                                        <p class="mb-1">
                                            <i class="bi bi-envelope"></i> <?= $row['email'] ?>
                                        </p>

                                        <p class="mb-1">
                                            <i class="bi bi-telephone"></i> <?= $row['telepon'] ?>
                                        </p>

                                        <p class="mb-1">
                                            <i class="bi bi-people"></i> <?= $row['jumlah_siswa'] ?> Siswa
                                        </p>

                                        <p class="mb-1">
                                            <i class="bi bi-calendar3"></i> <?= $row['kelas_minggu'] ?> Kelas/Minggu
                                        </p>
                                    </div>

                                    <hr>

                                    <!-- TOMBOL -->
                                    <div class="d-flex justify-content-center gap-2">

                                        <!-- DETAIL -->
                                        <a href="tutor-detail.php?id_tutor=<?= $row['id_tutor'] ?>"
                                            class="btn btn-sm btn-info">
                                            <i class="bi bi-eye"></i> Detail
                                        </a>


                                        <!-- EDIT -->
                                        <a href="tutor-edit.php?id_tutor=<?= $row['id_tutor'] ?>" class="btn btn-sm btn-warning">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>

                                        <!-- JADWAL -->
                                        <a href="tutor-jadwal.php?id_tutor=<?= $row['id_tutor'] ?>" class="btn btn-sm btn-primary">
                                            <i class="bi bi-clock"></i> Jadwal
                                        </a>

                                    </div>

                                </div>
                            </div>
                        </div>

                    <?php endwhile; ?>

                </div>


            </div>
        </div>
    </div>

    <!-- DETAIL MODAL -->
    <div class="modal fade" id="detailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">Detail Tutor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body" id="modalContent">
                    <!-- otomatis diisi JS -->
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
                        <h6>Informasi Tutor</h6>
                        <table class="table table-sm">
                            <tr><td><strong>ID Tutor</strong></td><td>${data.id}</td></tr>
                            <tr><td><strong>Nama Lengkap</strong></td><td>${data.nama}</td></tr>
                            <tr><td><strong>Email</strong></td><td>${data.email}</td></tr>
                            <tr><td><strong>No Telepon</strong></td><td>${data.telepon}</td></tr>
                            <tr><td><strong>Mata Pelajaran</strong></td><td>${data.mapel}</td></tr>
                            <tr><td><strong>Pendidikan</strong></td><td>${data.pendidikan}</td></tr>
                        </table>
                    </div>

                    <div class="col-md-6">
                        <h6>Status & Info Lain</h6>
                        <table class="table table-sm">
                            <tr><td><strong>Status</strong></td><td>${data.status}</td></tr>
                        </table>
                    </div>
                </div>
            `;

                document.getElementById("modalContent").innerHTML = html;
            });
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>