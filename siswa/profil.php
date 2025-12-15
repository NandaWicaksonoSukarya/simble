<?php
ini_set('session.cookie_path', '/');
session_start();

require "../config.php";

if (!isset($_SESSION['login']) || $_SESSION['role'] !== "siswa") {
    header("Location: ../index.php");
    exit;
}


// helper escape
function h($s)
{
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}


$siswa_primary_id = (int) $_SESSION['id_siswa'];


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Profile update
    if (isset($_POST['action']) && $_POST['action'] === 'update_profile') {
        $nama = mysqli_real_escape_string($conn, $_POST['nama'] ?? '');
        $email = mysqli_real_escape_string($conn, $_POST['email'] ?? '');
        $telepon = mysqli_real_escape_string($conn, $_POST['telepon'] ?? '');
        $tmp_lahir = mysqli_real_escape_string($conn, $_POST['tmp_lahir'] ?? '');
        $tgl_lahir = mysqli_real_escape_string($conn, $_POST['tgl_lahir'] ?? '');
        $alamat = mysqli_real_escape_string($conn, $_POST['alamat'] ?? '');
        $sekolah = mysqli_real_escape_string($conn, $_POST['sekolah'] ?? '');
        // update
        
        $qup = "UPDATE siswa SET 
                    nama = '$nama',
                    email = '$email',
                    telepon = '$telepon',
                    tmp_lahir = '$tmp_lahir',
                    tgl_lahir = " . ($tgl_lahir ? "'$tgl_lahir'" : "NULL") . ",
                    alamat = '$alamat',
                    sekolah = '$sekolah'
                WHERE id_siswa = ('$siswa_primary_id')";
        mysqli_query($conn, $qup);
        $flash = "Profil berhasil diperbarui.";
    }

    // Change password (template) - actual implementation depends on where password disimpan
    if (isset($_POST['action']) && $_POST['action'] === 'change_password') {
        $old = $_POST['old_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        if ($new !== $confirm) {
            $flash = "Konfirmasi password tidak cocok.";
        } else {
            // NOTE: kamu harus menyesuaikan bagian ini jika password disimpan di tabel user.
            // Sebagai placeholder, kita set flash sukses tapi tidak mengubah apa-apa.
            $flash = "Fungsi ubah password diproses (implementasikan sesuai struktur user/password).";
        }
    }
}

// ambil data siswa
$res = mysqli_query($conn, "
    SELECT s.*, k.nama_kelas 
    FROM siswa s
    LEFT JOIN kelas k ON s.kelas = k.id_kelas
    WHERE s.id_siswa = $siswa_primary_id
    LIMIT 1
");
$siswa = mysqli_fetch_assoc($res);
if (!$siswa) {
    echo "Data siswa tidak ditemukan.";
    exit;
}
$id_siswa_code = $siswa['nib']; // kode seperti SIS-2024-001
$kelas_siswa = $siswa['nama_kelas'];

// Statistik 1: Kehadiran (% bulan berjalan)
$bulan_kode = date('Y-m'); // contoh 2024-12
// total presensi di bulan ini untuk siswa (semua status)
$qtot = mysqli_query($conn, "SELECT COUNT(*) AS c FROM presensi WHERE id_siswa = '" . mysqli_real_escape_string($conn, $id_siswa_code) . "' AND DATE_FORMAT(tanggal,'%Y-%m') = '$bulan_kode'");
$totRow = mysqli_fetch_assoc($qtot);
$total_presensi_bulan = (int)$totRow['c'];
// hadir count
$qhadir = mysqli_query($conn, "SELECT COUNT(*) AS c FROM presensi WHERE id_siswa = '" . mysqli_real_escape_string($conn, $id_siswa_code) . "' AND DATE_FORMAT(tanggal,'%Y-%m') = '$bulan_kode' AND status = 'Hadir'");
$hadirRow = mysqli_fetch_assoc($qhadir);
$hadir_count = (int)$hadirRow['c'];
$kehadiran_persen = $total_presensi_bulan ? round(($hadir_count / $total_presensi_bulan) * 100) : 0;

// Statistik 2: Rata-rata nilai
$qavg = mysqli_query($conn, "SELECT AVG(nilai) AS avg_nilai, COUNT(*) AS cnt FROM nilai WHERE id_siswa = '" . mysqli_real_escape_string($conn, $id_siswa_code) . "' AND nilai IS NOT NULL");
$avgRow = mysqli_fetch_assoc($qavg);
$rata_nilai = $avgRow && $avgRow['avg_nilai'] !== null ? round($avgRow['avg_nilai']) : 0;
$total_nilai_count = (int)$avgRow['cnt'];

// Statistik 3: Tugas selesai / total tugas (sesuai kelas siswa)
$total_tugas = 0;
$sel_tugas = mysqli_query($conn, "SELECT COUNT(*) AS c FROM tugas WHERE id_kelas = '" . mysqli_real_escape_string($conn, $kelas_siswa) . "'");
if ($sel_tugas) {
    $total_tugas = (int)mysqli_fetch_assoc($sel_tugas)['c'];
}
// tugas selesai: hitung di tabel nilai sebagai tugas yang punya nilai untuk siswa ini (asumsi 1 nilai per tugas)
$tugas_selesai = 0;
$sel_done = mysqli_query($conn, "SELECT COUNT(DISTINCT id_tugas) AS c FROM nilai WHERE id_siswa = '" . mysqli_real_escape_string($conn, $id_siswa_code) . "' AND nilai IS NOT NULL");
if ($sel_done) {
    $tugas_selesai = (int)mysqli_fetch_assoc($sel_done)['c'];
}

// Statistik 4: Status Pembayaran bulan ini
// tagihan default (boleh diubah)
$tagihan_per_bulan = 500000;
$qpay = mysqli_query($conn, "SELECT COALESCE(SUM(jumlah),0) AS total FROM pembayaran WHERE id_siswa = $siswa_primary_id AND DATE_FORMAT(tanggal,'%Y-%m') = '$bulan_kode'");
$payRow = mysqli_fetch_assoc($qpay);
$total_bayar_bulan_ini = $payRow ? (int)$payRow['total'] : 0;
$sisa_tagihan = max(0, $tagihan_per_bulan - $total_bayar_bulan_ini);
$status_pembayaran = $total_bayar_bulan_ini >= $tagihan_per_bulan ? 'Lunas' : 'Belum Lunas';

// Riwayat pembayaran (limit last 50)
$riwayat = mysqli_query($conn, "SELECT * FROM pembayaran WHERE id_siswa = $siswa_primary_id ORDER BY tanggal DESC LIMIT 200");

// prepare foto url (jika kosong gunakan ui-avatars)
$foto_url = $siswa['foto'] ? $siswa['foto'] : "https://ui-avatars.com/api/?name=" . urlencode($siswa['nama']) . "&size=150&background=0D6EFD&color=fff";

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - Sistem Informasi Bimbel</title>
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
                    <small>Portal Siswa</small>
                </div>
                <nav class="nav flex-column">
                    <a class="nav-link" href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                    <a class="nav-link" href="jadwal.php"><i class="bi bi-calendar3"></i> Jadwal Kelas</a>
                    <a class="nav-link" href="materi.php"><i class="bi bi-journal-text"></i> Materi</a>
                    <a class="nav-link" href="tugas.php"><i class="bi bi-clipboard-check"></i> Tugas</a>
                    <a class="nav-link" href="nilai.php"><i class="bi bi-bar-chart"></i> Nilai</a>
                    <a class="nav-link" href="pembayaran.php"><i class="bi bi-cash-coin"></i> Pembayaran</a>
                    <a class="nav-link active" href="profil.php"><i class="bi bi-person"></i> Profil</a>
                    <a class="nav-link" href="../logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 content-wrapper">
                <div class="page-header">
                    <h2>Profil Saya</h2>
                    <p class="text-muted">Kelola informasi profil Anda</p>
                </div>

                <?php
                // Flash message handler
                $flash = $_SESSION['flash'] ?? null;
                unset($_SESSION['flash']);
                ?>

                <?php if ($flash): ?>
                    <div class="alert alert-success"><?= h($flash) ?></div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-4">
                        <div class="card text-center">
                            <div class="card-body">
                                <img src="<?= h($foto_url) ?>" class="rounded-circle mb-3" alt="Profile" style="width:150px;height:150px;object-fit:cover;">
                                <h4><?= h($siswa['nama']) ?></h4>
                                <p class="text-muted"><?= h($siswa['nib']) ?></p>
                                <p class="text-muted"><?= h($siswa['nama_kelas']) ?></p>
                                <!-- Ganti foto: simple form (upload handling not implemented fully) -->
                                <form method="post" enctype="multipart/form-data" class="d-inline">
                                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#changePhotoModal">
                                        <i class="bi bi-camera"></i> Ganti Foto
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div class="card mt-3">
                            <div class="card-body">
                                <h6>Statistik Saya</h6>
                                <hr>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Kehadiran</span>
                                    <strong class="text-success"><?= h($kehadiran_persen) ?>%</strong>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Rata-rata Nilai</span>
                                    <strong class="text-primary"><?= h($rata_nilai) ?></strong>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Tugas Selesai</span>
                                    <strong><?= h($tugas_selesai) ?>/<?= h($total_tugas) ?></strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Status Pembayaran</span>
                                    <strong class="<?= $status_pembayaran === 'Lunas' ? 'text-success' : 'text-danger' ?>"><?= h($status_pembayaran) ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-8">
                        <!-- Informasi Pribadi -->
                        <div class="card">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">Informasi Pribadi</h5>
                            </div>
                            <div class="card-body">
                                <form method="post">
                                    <input type="hidden" name="action" value="update_profile">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Nama Lengkap</label>
                                            <input type="text" name="nama" class="form-control" value="<?= h($siswa['nama']) ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">NIB</label>
                                            <input type="text" class="form-control" value="<?= h($siswa['nib']) ?>" readonly>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Email</label>
                                            <input type="email" name="email" class="form-control" value="<?= h($siswa['email']) ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">No. Telepon</label>
                                            <input type="tel" name="telepon" class="form-control" value="<?= h($siswa['telepon']) ?>">
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Tempat Lahir</label>
                                            <input type="text" name="tmp_lahir" class="form-control" value="<?= h($siswa['tmp_lahir']) ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Tanggal Lahir</label>
                                            <input type="date" name="tgl_lahir" class="form-control" value="<?= h($siswa['tgl_lahir']) ?>">
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Alamat</label>
                                        <textarea name="alamat" class="form-control" rows="3"><?= h($siswa['alamat']) ?></textarea>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Asal Sekolah</label>
                                            <input type="text" name="sekolah" class="form-control" value="<?= h($siswa['sekolah']) ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Kelas</label>
                                            <input type="text" class="form-control" value="<?= h($siswa['nama_kelas']) ?>" readonly>
                                        </div>
                                    </div>
                                    <hr>
                                    <h6>Informasi Orang Tua / Wali</h6>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Nama Orang Tua</label>
                                            <input type="text" name="ortu" class="form-control" value="<?= h($siswa['ortu']) ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">No. Telepon Orang Tua</label>
                                            <input type="tel" name="ortu_telp" class="form-control" value="<?= h($siswa['ortu_telp']) ?>">
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-save"></i> Simpan Perubahan
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Ubah Password -->
                        <div class="card mt-3">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">Ubah Password</h5>
                            </div>
                            <div class="card-body">
                                <form method="post">
                                    <input type="hidden" name="action" value="change_password">
                                    <div class="mb-3">
                                        <label class="form-label">Password Lama</label>
                                        <input type="password" name="old_password" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Password Baru</label>
                                        <input type="password" name="new_password" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Konfirmasi Password Baru</label>
                                        <input type="password" name="confirm_password" class="form-control" required>
                                    </div>
                                    <button type="submit" class="btn btn-warning">
                                        <i class="bi bi-key"></i> Ubah Password
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Change Photo (simple) -->
                <div class="modal fade" id="changePhotoModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form method="post" action="upload_foto.php" enctype="multipart/form-data">
                                <div class="modal-header">
                                    <h5 class="modal-title">Ganti Foto Profil</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label class="form-label">Pilih Foto (JPG/PNG, max 2MB)</label>
                                        <input type="file" name="foto" class="form-control" accept="image/*" required>
                                    </div>
                                    <p class="small text-muted">Upload akan menyimpan nama file ke kolom <code>foto</code> di tabel <code>siswa</code>. Implementasikan upload handling di <code>upload_foto.php</code>.</p>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                    <button type="submit" class="btn btn-primary">Upload</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>