<?php
session_start();
require "../config.php"; // pastikan file conn mysqli berada di path ini dan memberikan $conn (mysqli)


function h($s)
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

// ---------- konfigurasi ----------
$tagihan_per_bulan = 500000; // FIX sesuai instruksi

// fungsi simple untuk label bulan dalam Bahasa Indonesia
function bulan_label_ind($date_str)
{
    $bulan_id = [
        'January' => 'Januari',
        'February' => 'Februari',
        'March' => 'Maret',
        'April' => 'April',
        'May' => 'Mei',
        'June' => 'Juni',
        'July' => 'Juli',
        'August' => 'Agustus',
        'September' => 'September',
        'October' => 'Oktober',
        'November' => 'November',
        'December' => 'Desember'
    ];
    $ts = strtotime($date_str);
    $eng = date('F', $ts);
    $tahun = date('Y', $ts);
    return (isset($bulan_id[$eng]) ? $bulan_id[$eng] : $eng) . " " . $tahun;
}

// ---------- data siswa ----------
$id_siswa = (int) $_SESSION['id_siswa'];
$siswa_nama = '';
$r = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nama FROM siswa WHERE id_siswa = $id_siswa LIMIT 1"));
if ($r) $siswa_nama = $r['nama'];

// ---------- periode saat ini ----------
$bulan_kode = date('Y-m');        // "2024-12"
$bulan_label = bulan_label_ind(date('Y-m-01'));

// ---------- total bayar bulan ini (prepared) ----------
$stmt = $conn->prepare("SELECT COALESCE(SUM(jumlah),0) AS total FROM pembayaran WHERE id_siswa = ? AND DATE_FORMAT(tanggal, '%Y-%m') = ?");
$stmt->bind_param('is', $id_siswa, $bulan_kode);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$total_bayar_bulan_ini = (int)$row['total'];
$stmt->close();

$sisa_tagihan = $tagihan_per_bulan - $total_bayar_bulan_ini;
if ($sisa_tagihan < 0) $sisa_tagihan = 0;
$status_bulan_ini = ($total_bayar_bulan_ini >= $tagihan_per_bulan) ? "Lunas" : "Belum Lunas";

// ---------- riwayat pembayaran siswa ----------
$riwayat_q = $conn->prepare("SELECT p.id_siswa, p.bulan, p.jumlah, p.tanggal, p.status FROM pembayaran p WHERE p.id_siswa = ? ORDER BY p.tanggal DESC");
$riwayat_q->bind_param('i', $id_siswa);
$riwayat_q->execute();
$riwayat_res = $riwayat_q->get_result();

// ---------- build periode dropdown (12 bulan terakhir) ----------
$periods = [];
for ($i = 0; $i < 12; $i++) {
    $ts = strtotime("-$i months");
    $code = date('Y-m', $ts);      // "2024-12"
    $label = bulan_label_ind(date('Y-m-01', $ts));
    $periods[] = ['code' => $code, 'label' => $label];
}
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
                    <small>Portal Siswa</small>
                </div>
                <nav class="nav flex-column">
                    <a class="nav-link" href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                    <a class="nav-link" href="jadwal.php"><i class="bi bi-calendar3"></i> Jadwal Kelas</a>
                    <a class="nav-link" href="materi.php"><i class="bi bi-journal-text"></i> Materi</a>
                    <a class="nav-link" href="tugas.php"><i class="bi bi-clipboard-check"></i> Tugas</a>
                    <a class="nav-link" href="nilai.php"><i class="bi bi-bar-chart"></i> Nilai</a>
                    <a class="nav-link active" href="pembayaran.php"><i class="bi bi-cash-coin"></i> Pembayaran</a>
                    <a class="nav-link" href="profil.php"><i class="bi bi-person"></i> Profil</a>
                    <a class="nav-link" href="../logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 content-wrapper">
                <div class="page-header">
                    <h2>Status Pembayaran</h2>
                    <p class="text-muted">Lihat tagihan dan riwayat pembayaran Anda</p>
                </div>

                <!-- Status Pembayaran Bulan Ini -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h5>Tagihan Bulan <?= h($bulan_label) ?></h5>
                                <div class="row mt-3">
                                    <div class="col-md-4">
                                        <p class="text-muted mb-1">Total Tagihan</p>
                                        <h4>Rp <?= number_format($tagihan_per_bulan, 0, ',', '.') ?></h4>
                                    </div>
                                    <div class="col-md-4">
                                        <p class="text-muted mb-1">Sudah Dibayar</p>
                                        <h4 class="text-success">Rp <?= number_format($total_bayar_bulan_ini, 0, ',', '.') ?></h4>
                                    </div>
                                    <div class="col-md-4">
                                        <p class="text-muted mb-1">Sisa Tagihan</p>
                                        <h4 class="<?= $sisa_tagihan == 0 ? 'text-success' : 'text-danger' ?>">Rp <?= number_format($sisa_tagihan, 0, ',', '.') ?></h4>
                                    </div>
                                </div>
                                <p class="text-muted mt-3 mb-0">
                                    <i class="bi bi-calendar-check"></i> Jatuh Tempo: 2 <?= date('F Y') ?>
                                </p>
                                <p class="text-muted mt-1 mb-0">
                                    <small>Nama: <?= h($siswa_nama) ?></small>
                                </p>
                            </div>
                            <div class="col-md-4 text-center">
                                <?php if ($status_bulan_ini == "Lunas"): ?>
                                    <span class="badge bg-success p-3" style="font-size: 1.2rem;">
                                        <i class="bi bi-check-circle"></i> LUNAS
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-danger p-3" style="font-size: 1.2rem;">
                                        <i class="bi bi-exclamation-circle"></i> Belum Lunas
                                    </span>
                                <?php endif; ?>

                                <button class="btn btn-primary mt-3 w-100" onclick="window.print()">
                                    <i class="bi bi-printer"></i> Cetak Invoice
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Informasi Rekening -->
                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Informasi Pembayaran</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6><i class="bi bi-bank text-primary"></i> Transfer Bank</h6>
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>Bank</strong></td>
                                        <td>BCA</td>
                                    </tr>
                                    <tr>
                                        <td><strong>No. Rekening</strong></td>
                                        <td>1234567890</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Atas Nama</strong></td>
                                        <td>Bimbel XYZ</td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6><i class="bi bi-wallet2 text-success"></i> E-Wallet</h6>
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>GoPay</strong></td>
                                        <td>081234567890</td>
                                    </tr>
                                    <tr>
                                        <td><strong>OVO</strong></td>
                                        <td>081234567890</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Dana</strong></td>
                                        <td>081234567890</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        <div class="alert alert-info mt-3">
                            <i class="bi bi-info-circle"></i> Setelah melakukan pembayaran, silakan upload bukti transfer melalui tombol di bawah atau hubungi admin.
                        </div>
                        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#uploadBuktiModal">
                            <i class="bi bi-upload"></i> Upload Bukti Transfer
                        </button>
                    </div>
                </div>

                <!-- Riwayat Pembayaran -->
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Riwayat Pembayaran</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Periode</th>
                                        <th>Tanggal Bayar</th>
                                        <th>Jumlah</th>
                                        <th>Metode</th>
                                        <th>Status</th>
                                        <th>Invoice</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($r = $riwayat_res->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= h(bulan_label_ind($r['bulan'] . '-01')) ?></td>
                                            <td><?= date('d M Y', strtotime($r['tanggal'])) ?></td>
                                            <td>Rp <?= number_format($r['jumlah'], 0, ',', '.') ?></td>
                                            <td>-</td> <!-- metode tidak tersedia di skema, biarkan '-' -->
                                            <td>
                                                <?php
                                                $st = $r['status'];
                                                if (strtolower($st) === 'lunas') echo '<span class="badge bg-success">Lunas</span>';
                                                else echo '<span class="badge bg-warning">' . h($st) . '</span>';
                                                ?>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-primary" onclick="alert('Fitur cetak invoice belum diimplementasikan');">
                                                    <i class="bi bi-printer"></i> Cetak
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                    <?php if ($riwayat_res->num_rows === 0): ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted">Belum ada riwayat pembayaran.</td>
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

    <!-- Upload Bukti Modal -->
    <div class="modal fade" id="uploadBuktiModal" tabindex="-1">
        <div class="modal-dialog">
            <form class="modal-content" method="POST" action="upload_bukti.php" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title">Upload Bukti Transfer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id_siswa" value="<?= h($id_siswa) ?>">
                    <div class="mb-3">
                        <label class="form-label">Periode Pembayaran</label>
                        <select class="form-select" name="bulan" required>
                            <?php foreach ($periods as $p): ?>
                                <option value="<?= h($p['code']) ?>"><?= h($p['label']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Jumlah Dibayar</label>
                        <input type="number" name="jumlah" class="form-control" value="<?= $tagihan_per_bulan ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Metode Pembayaran</label>
                        <select class="form-select" name="metode" required>
                            <option value="Transfer Bank BCA">Transfer Bank BCA</option>
                            <option value="Transfer Bank Mandiri">Transfer Bank Mandiri</option>
                            <option value="GoPay">GoPay</option>
                            <option value="OVO">OVO</option>
                            <option value="Dana">Dana</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tanggal Transfer</label>
                        <input type="date" name="tanggal" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Upload Bukti Transfer</label>
                        <input type="file" name="bukti" class="form-control" accept="image/*" required>
                        <small class="text-muted">Format: JPG, PNG (Max 2MB)</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Catatan (Opsional)</label>
                        <textarea name="catatan" class="form-control" rows="2" placeholder="Tambahkan catatan jika diperlukan"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-upload"></i> Upload
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
<?php
// tutup statement
$riwayat_q->close();
$conn->close();
?>