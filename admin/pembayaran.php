<?php
require "../config.php";
session_start();

// Cek login admin
if (!isset($_SESSION['login']) || $_SESSION['role'] !== "admin") {
    header("Location: ../index.php");
    exit;
}

// HANDLE AJAX LIST
// HANDLE AJAX LIST
if (isset($_GET['aksi']) && $_GET['aksi'] == "list") {

    $cari    = $_GET['cari'] ?? '';
    $status  = $_GET['status'] ?? '';
    $tanggal = $_GET['tanggal'] ?? '';

    $where = [];

    if ($cari != '') {
        $where[] = "(siswa.nama LIKE '%$cari%' OR siswa.id_siswa LIKE '%$cari%')";
    }

    if ($status != '') {
        $where[] = "pembayaran.status = '$status'";
    }

    if ($tanggal != '') {
        $where[] = "DATE(pembayaran.tgl_bayar) = '$tanggal'";
    }

    $whereSQL = count($where) ? "WHERE " . implode(" AND ", $where) : "";

    $q = mysqli_query($conn, "
        SELECT pembayaran.id_pembayaran,
               siswa.id_siswa,
               siswa.nama,
               DATE(pembayaran.tgl_bayar) AS tgl_bayar,
               pembayaran.nominal,
               pembayaran.status
        FROM pembayaran
        JOIN siswa ON siswa.id_siswa = pembayaran.id_siswa
        $whereSQL
        ORDER BY pembayaran.id_pembayaran DESC
    ");

    $data = [];
    while ($d = mysqli_fetch_assoc($q)) {
        $data[] = $d;
    }

    echo json_encode($data);
    exit;
}



// HANDLE INSERT
if (isset($_GET['aksi']) && $_GET['aksi'] == "insert") {

    $id_siswa  = $_POST['id_siswa'];
    $tgl_bayar = $_POST['bulan']; // dari input date
    $nominal   = str_replace(['.', ','], '', $_POST['nominal']);

    mysqli_query($conn, "
    INSERT INTO pembayaran (id_siswa, tgl_bayar, nominal, status)
    VALUES ('$id_siswa', '$tgl_bayar', '$nominal', 'Lunas')
");



    echo json_encode(["success" => true]);
    exit;
}


// HANDLE DELETE
if (isset($_GET['aksi']) && $_GET['aksi'] == "delete") {
    $id = $_GET['id_pembayaran'];
    mysqli_query($conn, "DELETE FROM pembayaran WHERE id_pembayaran='$id'");
    echo json_encode(["success" => true]);
    exit;
}


// DATA SISWA UNTUK SELECT
$siswa = mysqli_query($conn, "SELECT id_siswa, nama FROM siswa ORDER BY nama ASC");

// ================================
// STATISTIK PEMBAYARAN
// ================================

// Total pendapatan
$qTotal = mysqli_query($conn, "SELECT SUM(nominal) AS total FROM pembayaran WHERE status='Lunas'");
$rTotal = mysqli_fetch_assoc($qTotal);
$totalPendapatan = $rTotal['total'] ?? 0;

// Jumlah pembayaran lunas
$qLunas = mysqli_query($conn, "SELECT COUNT(*) AS jml FROM pembayaran WHERE status='Lunas'");
$jumlahLunas = mysqli_fetch_assoc($qLunas)['jml'] ?? 0;

// Jumlah belum lunas
$qBelum = mysqli_query($conn, "SELECT COUNT(*) AS jml FROM pembayaran WHERE status='Belum Lunas'");
$jumlahBelum = mysqli_fetch_assoc($qBelum)['jml'] ?? 0;

// Jumlah terlambat
$qTelat = mysqli_query($conn, "SELECT COUNT(*) AS jml FROM pembayaran WHERE status='Terlambat'");
$jumlahTerlambat = mysqli_fetch_assoc($qTelat)['jml'] ?? 0;

?>


<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran - Sistem Informasi Bimbel</title>
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
                    <a class="nav-link" href="siswa.php"><i class="bi bi-people"></i> Data Siswa</a>
                    <a class="nav-link" href="tutor.php"><i class="bi bi-person-badge"></i> Data Tutor</a>
                    <a class="nav-link" href="kelas.php"><i class="bi bi-calendar3"></i> Jadwal Kelas</a>
                    <a class="nav-link active" href="pembayaran.php"><i class="bi bi-cash-coin"></i> Pembayaran</a>
                    <a class="nav-link" href="mapel.php"><i class="bi bi-journal-text"></i> Mata Pelajaran</a>
                    <a class="nav-link" href="laporan.php"><i class="bi bi-file-earmark-text"></i> Laporan</a>
                    <a class="nav-link" href="../logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
                </nav>
            </div>

            <!-- MAIN CONTENT -->
            <div class="col-md-10 content-wrapper">

                <div class="page-header">
                    <h2>Manajemen Pembayaran</h2>
                    <p class="text-muted">Kelola tagihan dan pembayaran siswa</p>
                </div>

                <!-- (UI STATISTIK TETAP – TIDAK DIUBAH) -->
                <div class="row mb-4">
                    <!-- Total Pendapatan -->
                    <div class="col-md-3">
                        <div class="card text-white" style="background:#1F7A3D;">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <h4>Rp <?= number_format($totalPendapatan, 0, ',', '.'); ?></h4>
                                    <p class="mb-0">Total Pendapatan</p>
                                </div>
                                <i class="fas fa-money-bill-wave fa-2x"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Pembayaran Lunas -->
                    <div class="col-md-3">
                        <div class="card text-white" style="background:#0d6efd;">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <h4><?= $jumlahLunas ?></h4>
                                    <p class="mb-0">Pembayaran Lunas</p>
                                </div>
                                <i class="fas fa-check-circle fa-2x"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Belum Lunas -->
                    <div class="col-md-3">
                        <div class="card text-white" style="background:#FFC107;">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <h4><?= $jumlahBelum ?></h4>
                                    <p class="mb-0">Belum Lunas</p>
                                </div>
                                <i class="fas fa-clock fa-2x"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Terlambat -->
                    <div class="col-md-3">
                        <div class="card text-white" style="background:#DC3545;">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <h4><?= $jumlahTerlambat ?></h4>
                                    <p class="mb-0">Terlambat</p>
                                </div>
                                <i class="fas fa-exclamation-triangle fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- FILTER + BUTTON ADD -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row">

                            <div class="col-md-3">
                                <input type="text" id="search" class="form-control" placeholder="Cari nama atau ID siswa..." onkeyup="loadPembayaran()">
                            </div>

                            <div class="col-md-2">
                                <select class="form-select" id="filterStatus">
                                    <option value="">Semua Status</option>
                                    <option value="Lunas">Lunas</option>
                                    <option value="Belum Lunas">Belum Lunas</option>
                                    <option value="Terlambat">Terlambat</option>
                                </select>
                            </div>

                            <div class="col-md-2">
                                <input type="date" id="filterTanggal" class="form-control">
                            </div>

                            <div class="col-md-2">
                                <button class="btn btn-primary w-100" onclick="loadPembayaran()">Filter</button>
                            </div>


                            <div class="col-md-3">
                                <button class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#addPembayaranModal">
                                    <i class="bi bi-plus-circle"></i> Input Pembayaran
                                </button>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- TABLE DATA PEMBAYARAN -->
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Daftar Tagihan & Pembayaran</h5>
                    </div>

                    <div class="card-body">
                        <div class="table-responsive">

                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID Siswa</th>
                                        <th>Nama Siswa</th>
                                        <th>Tanggal Bayar</th>
                                        <th>Nominal</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>

                                <!-- HANYA SATU TBODY -->
                                <tbody id="dataPembayaran"></tbody>

                            </table>

                        </div>
                    </div>

                </div>

            </div>
        </div>
    </div>

    <!-- MODAL INPUT PEMBAYARAN -->
    <div class="modal fade" id="addPembayaranModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">Input Pembayaran</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <form id="formPembayaran">
                        <div class="mb-3">
                            <label class="form-label">Siswa</label>
                            <select name="id_siswa" id="id_siswa" class="form-control">
                                <?php while ($s = mysqli_fetch_assoc($siswa)) { ?>
                                    <option value="<?= $s['id_siswa'] ?>"><?= $s['nama'] ?></option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Periode (Bulan)</label>
                            <input type="date" class="form-control" name="bulan" id="bulan" required>

                        </div>

                        <div class="mb-3">
                            <label class="form-label">Jumlah Pembayaran</label>
                            <input type="text" id="jumlah" name="nominal" class="form-control" required>
                        </div>
                    </form>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary" onclick="simpanPembayaran(event)">Simpan</button>
                </div>

            </div>
        </div>
    </div>

    <!-- JAVASCRIPT -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        function loadPembayaran() {

            let cari = document.querySelector("#search").value;
            let status = document.querySelector("#filterStatus").value;
            let tanggal = document.querySelector("#filterTanggal").value;

            let url = "pembayaran.php?aksi=list" +
                "&cari=" + encodeURIComponent(cari) +
                "&status=" + encodeURIComponent(status) +
                "&tanggal=" + encodeURIComponent(tanggal);

            fetch(url)
                .then(res => res.json())
                .then(data => {

                    let tbody = document.querySelector("#dataPembayaran");
                    tbody.innerHTML = "";

                    if (data.length === 0) {
                        tbody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center text-muted">
                            Data tidak ditemukan
                        </td>
                    </tr>`;
                        return;
                    }

                    data.forEach(p => {
                        tbody.innerHTML += `
                <tr>
                    <td>${p.id_siswa}</td>
                    <td>${p.nama}</td>
                    <td>${p.tgl_bayar}</td>
                    <td>Rp ${new Intl.NumberFormat('id-ID').format(p.nominal)}</td>
                    <td>${p.status}</td>
                    <td>
                        <button class="btn btn-danger btn-sm"
                            onclick="hapusPembayaran(${p.id_pembayaran})">
                            Hapus
                        </button>
                    </td>
                </tr>`;
                    });
                })
                .catch(err => {
                    console.error(err);
                    alert("Gagal memuat data");
                });
        }

        loadPembayaran();
    </script>


</body>

</html>