<?php
require "../config.php";
session_start();

// Cek login admin
if (!isset($_SESSION['login']) || $_SESSION['role'] !== "admin") {
    header("Location: ../index.php");
    exit;
}

// Ambil ID siswa dari URL
$id_siswa = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_siswa == 0) {
    header("Location: siswa.php");
    exit;
}

// Ambil data siswa untuk konfirmasi
$query_siswa = mysqli_query($conn, "
    SELECT s.*, k.nama_kelas 
    FROM siswa s
    LEFT JOIN kelas k ON s.id_kelas = k.id_kelas
    WHERE s.id_siswa = $id_siswa
");

$siswa = mysqli_fetch_assoc($query_siswa);

if (!$siswa) {
    echo "<script>
            alert('Data siswa tidak ditemukan!');
            window.location.href='siswa.php';
          </script>";
    exit;
}

// Proses penghapusan jika konfirmasi diterima
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['confirm_delete']) && $_POST['confirm_delete'] == 'yes') {

        // Mulai transaksi untuk menjaga konsistensi data
        mysqli_begin_transaction($conn);

        try {
            // 1. Hapus data nilai siswa
            $delete_nilai = "DELETE FROM penilaian_tugas WHERE id_siswa = $id_siswa";
            mysqli_query($conn, $delete_nilai);

            // 2. Hapus data presensi siswa
            $delete_presensi = "DELETE FROM presensi WHERE id_siswa = $id_siswa";
            mysqli_query($conn, $delete_presensi);

            // 3. Hapus data tugas upload siswa
            $delete_tugas = "DELETE FROM penilaian_tugas WHERE id_siswa = $id_siswa";
            mysqli_query($conn, $delete_tugas);

            // 4. Hapus data pembayaran siswa
            $delete_pembayaran = "DELETE FROM pembayaran WHERE id_siswa = $id_siswa";
            mysqli_query($conn, $delete_pembayaran);



            // 6. Hapus data siswa dari tabel utama
            $delete_siswa = "DELETE FROM siswa WHERE id_siswa = $id_siswa";
            mysqli_query($conn, $delete_siswa);

            // 7. Hapus akun login siswa jika ada di tabel user
            $delete_user = "DELETE FROM users WHERE username = '" . mysqli_real_escape_string($conn, $siswa['email']) . "' 
                           AND role = 'siswa'";
            mysqli_query($conn, $delete_user);



            // Commit transaksi
            mysqli_commit($conn);

            echo "<script>
                    alert('Data siswa berhasil dihapus!');
                    window.location.href='siswa.php';
                  </script>";
        } catch (Exception $e) {
            // Rollback jika ada error
            mysqli_rollback($conn);
            echo "<script>
                    alert('Gagal menghapus data siswa: " . addslashes($e->getMessage()) . "');
                    window.location.href='siswa.php';
                  </script>";
        }
    } else {
        // Jika tidak konfirmasi, kembali ke siswa.php
        header("Location: siswa.php");
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hapus Siswa - Sistem Informasi Bimbel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .confirmation-box {
            max-width: 800px;
            margin: 0 auto;
            border-left: 4px solid #dc3545;
        }

        .warning-icon {
            font-size: 4rem;
            color: #dc3545;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .consequences-list {
            list-style-type: none;
            padding-left: 0;
        }

        .consequences-list li {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }

        .consequences-list li:last-child {
            border-bottom: none;
        }

        .consequences-list i {
            width: 25px;
        }

        .data-summary {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
        }

        .avatar-placeholder {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
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
                        <h2>Hapus Data Siswa</h2>
                        <p class="text-muted">Konfirmasi penghapusan data siswa</p>
                    </div>
                    <a href="siswa.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Kembali
                    </a>
                </div>

                <!-- Confirmation Card -->
                <div class="card confirmation-box">
                    <div class="card-header bg-white text-center">
                        <i class="bi bi-exclamation-triangle warning-icon"></i>
                        <h4 class="mt-3 text-danger">Konfirmasi Penghapusan</h4>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <p class="lead">Anda yakin ingin menghapus data siswa ini?</p>
                            <h4 class="text-danger">"<?= htmlspecialchars($siswa['nama']) ?>"</h4>
                        </div>

                        <!-- Informasi Siswa -->
                        <div class="row mb-4">
                            <div class="col-md-3 text-center">
                                <div class="avatar-placeholder mb-3 mx-auto">
                                    <i class="bi bi-person"></i>
                                </div>
                                <h5>ID: <?= $siswa['id_siswa'] ?></h5>
                                <span class="badge <?= $siswa['status'] == 'Aktif' ? 'bg-success' : 'bg-danger' ?>">
                                    <?= $siswa['status'] ?>
                                </span>
                            </div>
                            <div class="col-md-9">
                                <div class="data-summary">
                                    <div class="info-grid">
                                        <div>
                                            <strong>Nama Lengkap:</strong><br>
                                            <?= htmlspecialchars($siswa['nama']) ?>
                                        </div>
                                        <div>
                                            <strong>Email:</strong><br>
                                            <?= htmlspecialchars($siswa['email']) ?>
                                        </div>
                                        <div>
                                            <strong>Kelas:</strong><br>
                                            <?= htmlspecialchars($siswa['nama_kelas']) ?>
                                        </div>
                                        <div>
                                            <strong>Telepon:</strong><br>
                                            <?= htmlspecialchars($siswa['telepon']) ?>
                                        </div>
                                        <div>
                                            <strong>Orang Tua:</strong><br>
                                            <?= htmlspecialchars($siswa['ortu']) ?>
                                        </div>
                                        <div>
                                            <strong>Tanggal Daftar:</strong><br>
                                            <?= $siswa['tgl_lahir'] ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Data yang akan dihapus -->
                        <div class="alert alert-warning">
                            <h6><i class="bi bi-exclamation-octagon"></i> Data yang akan dihapus:</h6>
                            <?php
                            // Hitung data terkait
                            $data_counts = [];

                            // Hitung nilai
                            $count_nilai = mysqli_fetch_assoc(mysqli_query(
                                $conn,
                                "SELECT COUNT(*) as total FROM penilaian_tugas WHERE id_siswa = $id_siswa"
                            ))['total'];
                            $data_counts['nilai'] = $count_nilai;

                            // Hitung presensi
                            $count_presensi = mysqli_fetch_assoc(mysqli_query(
                                $conn,
                                "SELECT COUNT(*) as total FROM presensi WHERE id_siswa = $id_siswa"
                            ))['total'];
                            $data_counts['presensi'] = $count_presensi;

                            // Hitung tugas
                            $count_tugas = mysqli_fetch_assoc(mysqli_query(
                                $conn,
                                "SELECT COUNT(*) as total FROM penilaian_tugas WHERE id_siswa = $id_siswa"
                            ))['total'];
                            $data_counts['tugas'] = $count_tugas;

                            // Hitung pembayaran
                            $count_pembayaran = mysqli_fetch_assoc(mysqli_query(
                                $conn,
                                "SELECT COUNT(*) as total FROM pembayaran WHERE id_siswa = $id_siswa"
                            ))['total'];
                            $data_counts['pembayaran'] = $count_pembayaran;
                            ?>

                            <div class="row mt-2">
                                <div class="col-md-6">
                                    <div class="summary-item">
                                        <span>Data Nilai:</span>
                                        <span class="badge bg-danger"><?= $data_counts['nilai'] ?> records</span>
                                    </div>
                                    <div class="summary-item">
                                        <span>Data Presensi:</span>
                                        <span class="badge bg-danger"><?= $data_counts['presensi'] ?> records</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="summary-item">
                                        <span>Data Tugas:</span>
                                        <span class="badge bg-danger"><?= $data_counts['tugas'] ?> records</span>
                                    </div>
                                    <div class="summary-item">
                                        <span>Data Pembayaran:</span>
                                        <span class="badge bg-danger"><?= $data_counts['pembayaran'] ?> records</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Dampak Penghapusan -->
                        <div class="alert alert-danger">
                            <h6><i class="bi bi-x-octagon"></i> Dampak Penghapusan:</h6>
                            <ul class="consequences-list">
                                <li><i class="bi bi-x-circle text-danger"></i> Data siswa akan dihapus PERMANEN dari database</li>
                                <li><i class="bi bi-x-circle text-danger"></i> Semua data terkait (nilai, presensi, tugas) akan hilang</li>
                                <li><i class="bi bi-x-circle text-danger"></i> Riwayat pembayaran akan dihapus</li>
                                <li><i class="bi bi-x-circle text-danger"></i> Akun login siswa akan dinonaktifkan</li>
                                <li><i class="bi bi-x-circle text-danger"></i> TIDAK DAPAT DIKEMBALIKAN</li>
                            </ul>
                        </div>

                        <!-- Form Konfirmasi -->
                        <form method="POST" action="" onsubmit="return confirmFinal()">
                            <input type="hidden" name="confirm_delete" value="yes">

                            <div class="mb-4">
                                <label for="verification" class="form-label">
                                    <strong>Verifikasi:</strong> Ketik "<span class="text-danger">HAPUS SISWA</span>" untuk konfirmasi
                                </label>
                                <input type="text" id="verification" class="form-control"
                                    placeholder="Ketik HAPUS SISWA disini" required
                                    onkeyup="checkVerification()">
                                <div class="form-text">Ini untuk memastikan Anda tidak salah klik.</div>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="siswa.php" class="btn btn-secondary me-md-2">
                                    <i class="bi bi-x-circle"></i> Batalkan
                                </a>
                                <button type="submit" id="deleteBtn" class="btn btn-danger" disabled>
                                    <i class="bi bi-trash"></i> Hapus Permanen
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Alternatif -->
                <div class="card mt-4">
                    <div class="card-header bg-white">
                        <h6><i class="bi bi-lightbulb"></i> Alternatif yang Disarankan</h6>
                    </div>
                    <div class="card-body">
                        <p>Sebelum menghapus, pertimbangkan alternatif berikut:</p>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="alert alert-info">
                                    <h6><i class="bi bi-person-x"></i> Nonaktifkan Saja</h6>
                                    <p>Ubah status siswa menjadi "Tidak Aktif" tanpa menghapus data.</p>
                                    <a href="edit-siswa.php?id=<?= $id_siswa ?>" class="btn btn-sm btn-outline-info">
                                        <i class="bi bi-pencil"></i> Edit Status
                                    </a>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="alert alert-success">
                                    <h6><i class="bi bi-archive"></i> Arsipkan Data</h6>
                                    <p>Backup data siswa sebelum menghapus untuk keperluan arsip.</p>
                                    <button class="btn btn-sm btn-outline-success" onclick="alert('Fitur backup sedang dikembangkan')">
                                        <i class="bi bi-download"></i> Backup Data
                                    </button>
                                </div>
                            </div>
                        </div>
                        <p class="mb-0">
                            <a href="edit-siswa.php?id=<?= $id_siswa ?>" class="btn btn-sm btn-outline-warning me-2">
                                <i class="bi bi-pencil"></i> Edit Data
                            </a>
                            <a href="#" class="btn btn-sm btn-outline-info"
                                onclick="showDetailModal(); return false;">
                                <i class="bi bi-eye"></i> Lihat Detail Lengkap
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Detail -->
    <div class="modal fade" id="detailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Lengkap Siswa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?php
                    $detail_html = "
                    <div class='row'>
                        <div class='col-md-6'>
                            <h6>Informasi Pribadi</h6>
                            <table class='table table-sm'>
                                <tr><td><strong>ID Siswa</strong></td><td>{$siswa['id_siswa']}</td></tr>
                                <tr><td><strong>Nama Lengkap</strong></td><td>{$siswa['nama']}</td></tr>
                                <tr><td><strong>TTL</strong></td><td>{$siswa['ttl']}</td></tr>
                                <tr><td><strong>Jenis Kelamin</strong></td><td>{$siswa['jk']}</td></tr>
                                <tr><td><strong>Alamat</strong></td><td>{$siswa['alamat']}</td></tr>
                            </table>
                        </div>
                        <div class='col-md-6'>
                            <h6>Kontak</h6>
                            <table class='table table-sm'>
                                <tr><td><strong>Email</strong></td><td>{$siswa['email']}</td></tr>
                                <tr><td><strong>No. Telepon</strong></td><td>{$siswa['telepon']}</td></tr>
                                <tr><td><strong>Orang Tua</strong></td><td>{$siswa['ortu']}</td></tr>
                                <tr><td><strong>No. Telepon Ortu</strong></td><td>{$siswa['telepon_ortu']}</td></tr>
                            </table>
                        </div>
                    </div>
                    <hr>
                    <h6>Informasi Akademik</h6>
                    <table class='table table-sm'>
                        <tr><td><strong>Kelas</strong></td><td>{$siswa['kelas']}</td></tr>
                        <tr><td><strong>Tanggal Daftar</strong></td><td>{$siswa['tgl_daftar']}</td></tr>
                        <tr><td><strong>Status</strong></td><td>{$siswa['status']}</td></tr>
                    </table>
                    ";
                    echo $detail_html;
                    ?>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validasi input verifikasi
        function checkVerification() {
            const verificationInput = document.getElementById('verification');
            const deleteBtn = document.getElementById('deleteBtn');

            if (verificationInput.value.toUpperCase() === 'HAPUS SISWA') {
                deleteBtn.disabled = false;
                verificationInput.classList.remove('is-invalid');
                verificationInput.classList.add('is-valid');
            } else {
                deleteBtn.disabled = true;
                verificationInput.classList.remove('is-valid');
                verificationInput.classList.add('is-invalid');
            }
        }

        // Konfirmasi final sebelum submit
        function confirmFinal() {
            const verificationInput = document.getElementById('verification');

            if (verificationInput.value.toUpperCase() !== 'HAPUS SISWA') {
                alert('Harap ketik "HAPUS SISWA" untuk verifikasi!');
                return false;
            }

            const totalRecords = <?= array_sum($data_counts) ?>;

            return confirm(`⚠️ PERINGATAN AKHIR!\n\nAnda akan menghapus data siswa PERMANEN.\nTotal ${totalRecords} records data terkait akan dihapus.\n\nTekan OK untuk melanjutkan atau Cancel untuk membatalkan.`);
        }

        // Tampilkan modal detail
        function showDetailModal() {
            const detailModal = new bootstrap.Modal(document.getElementById('detailModal'));
            detailModal.show();
        }

        // Fokus ke input verifikasi saat halaman dimuat
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('verification').focus();
        });
    </script>
</body>

</html>