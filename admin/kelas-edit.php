<?php
require "../config.php";
session_start();

// cek session admin
if (!isset($_SESSION['login']) || $_SESSION['role'] !== "admin") {
  header("Location: ../index.php");
  exit;
}

// helper
function h($s)
{
  return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

// wajib ada id di URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
  echo "<script>alert('ID tidak ditemukan di URL.'); window.location='kelas.php';</script>";
  exit;
}

$id_jadwal = (int)$_GET['id'];

// aktifkan reporting agar error mysqli lebih informatif saat develop
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// ambil daftar mapel, kelas, tutor, ruangan
$mapelList = [];
$mapQ = mysqli_query($conn, "SELECT id_mapel, nama_mapel FROM mapel ORDER BY nama_mapel ASC");
while ($r = mysqli_fetch_assoc($mapQ)) $mapelList[] = $r;

$kelasList = [];
$kelasQ = mysqli_query($conn, "SELECT id_kelas, nama_kelas FROM kelas ORDER BY nama_kelas ASC");
while ($r = mysqli_fetch_assoc($kelasQ)) $kelasList[] = $r;

$tutorList = [];
$tutorQ = mysqli_query($conn, "SELECT id_tutor, nama_tutor FROM tutor ORDER BY nama_tutor ASC");
while ($r = mysqli_fetch_assoc($tutorQ)) $tutorList[] = $r;

$ruanganList = [];
$ruanganQ = mysqli_query($conn, "SELECT id_ruangan, nama_ruangan FROM ruangan ORDER BY nama_ruangan ASC");
while ($r = mysqli_fetch_assoc($ruanganQ)) $ruanganList[] = $r;

/* ambil data jadwal berdasarkan id_jadwal */
$stmt = mysqli_prepare($conn, "SELECT id_jadwal, id_mapel, id_kelas, id_tutor, tanggal, jam_mulai, jam_selesai, id_ruangan, status FROM jadwal WHERE id_jadwal = ?");
if (!$stmt) {
  die("Prepare failed: " . mysqli_error($conn));
}
mysqli_stmt_bind_param($stmt, "i", $id_jadwal);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$data) {
  echo "<script>alert('Data dengan ID = " . $id_jadwal . " tidak ditemukan.'); window.location='kelas.php';</script>";
  exit;
}

/* proses update kalau form disubmit */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  // ambil input dan sanitasi sederhana (prepared akan menghindari SQL injection)
  $id_mapel     = isset($_POST['id_mapel']) ? (int)$_POST['id_mapel'] : 0;
  $id_kelas     = isset($_POST['id_kelas']) ? (int)$_POST['id_kelas'] : 0;
  $id_tutor     = isset($_POST['id_tutor']) ? (int)$_POST['id_tutor'] : 0;
  $tanggal      = trim($_POST['tanggal'] ?? '');
  $jam_mulai    = trim($_POST['jam_mulai'] ?? '');
  $jam_selesai  = trim($_POST['jam_selesai'] ?? '');
  $id_ruangan   = isset($_POST['id_ruangan']) ? (int)$_POST['id_ruangan'] : 0;
  $status       = trim($_POST['status'] ?? '');

  // validasi minimum
  $errors = [];
  if ($id_mapel <= 0) $errors[] = "Mata pelajaran belum dipilih.";
  if ($id_kelas <= 0) $errors[] = "Kelas belum dipilih.";
  if ($id_tutor <= 0) $errors[] = "Tutor belum dipilih.";
  if ($tanggal === '') $errors[] = "Tanggal harus diisi.";
  if ($jam_mulai === '' || $jam_selesai === '') $errors[] = "Jam mulai/selesai harus diisi.";
  if ($id_ruangan <= 0) $errors[] = "Ruangan belum dipilih.";

  if (!empty($errors)) {
    $err_html = implode("\\n", $errors);
    echo "<script>alert('Form tidak valid:\\n{$err_html}');</script>";
  } else {
    // siapkan update (pastikan nama kolom benar)
    $upd = mysqli_prepare($conn, "
            UPDATE jadwal SET
                id_mapel = ?,
                id_kelas = ?,
                id_tutor = ?,
                tanggal = ?,
                jam_mulai = ?,
                jam_selesai = ?,
                id_ruangan = ?,
                status = ?
            WHERE id_jadwal = ?
        ");

    if (!$upd) {
      // tampilkan pesan error SQL agar mudah debug
      die("Prepare update failed: " . mysqli_error($conn));
    }

    // tipe: 7 int, 2 string, 1 int -> total 9 params -> "iiissssii"
    $bind = mysqli_stmt_bind_param(
      $upd,
      "iiisssisi",
      $id_mapel,
      $id_kelas,
      $id_tutor,
      $tanggal,
      $jam_mulai,
      $jam_selesai,
      $id_ruangan,
      $status,
      $id_jadwal
    );

    if ($bind === false) {
      die("Bind param failed: " . mysqli_error($conn));
    }

    $exec = mysqli_stmt_execute($upd);
    if ($exec) {
      mysqli_stmt_close($upd);
      echo "<script>alert('Jadwal berhasil diperbarui.'); window.location='kelas.php';</script>";
      exit;
    } else {
      $sqlerr = mysqli_error($conn);
      mysqli_stmt_close($upd);
      die("Gagal update: " . $sqlerr);
    }
  }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Edit Jadwal - Bimbel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
  <style>
    .sidebar {
      background: linear-gradient(180deg, #0d6efd 0%, #0a58ca 100%);
    }

    .sidebar .nav-link {
      color: rgba(255, 255, 255, 0.9);
      padding: 12px 20px;
      border-left: 3px solid transparent;
    }

    .sidebar .nav-link:hover {
      background-color: rgba(255, 255, 255, 0.1);
      border-left-color: #fff;
    }

    .sidebar .nav-link.active {
      background-color: rgba(255, 255, 255, 0.15);
      border-left-color: #fff;
      font-weight: 500;
    }

    .content-wrapper {
      background-color: #f8f9fa;
      min-height: 100vh;
    }
  </style>
</head>

<body>
  <div class="container-fluid">
    <div class="row">
      <!-- SIDEBAR -->
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
          <a class="nav-link active" href="kelas.php"><i class="bi bi-calendar3"></i> Jadwal Kelas</a>
          <a class="nav-link" href="pembayaran.php"><i class="bi bi-cash-coin"></i> Pembayaran</a>
          <a class="nav-link" href="mapel.php"><i class="bi bi-journal-text"></i> Mata Pelajaran</a>
          <a class="nav-link" href="laporan.php"><i class="bi bi-file-earmark-text"></i> Laporan</a>
          <a class="nav-link" href="../logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </nav>
      </div>

      <!-- MAIN -->
      <div class="col-md-10 content-wrapper p-4">
        <div class="page-header d-flex justify-content-between align-items-center mb-3">
          <div>
            <h2><i class="bi bi-pencil-square me-2"></i>Edit Jadwal</h2>
            <p class="text-muted">Ubah informasi jadwal kelas</p>
          </div>
          <a href="kelas.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-1"></i>Kembali
          </a>
        </div>

        <div class="card shadow-sm">
          <div class="card-body">
            <form method="POST">
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label">Mata Pelajaran <span class="text-danger">*</span></label>
                  <select name="id_mapel" class="form-select" required>
                    <option value="">-- Pilih Mata Pelajaran --</option>
                    <?php foreach ($mapelList as $m): ?>
                      <option value="<?= h($m['id_mapel']) ?>"
                        <?= ($data['id_mapel'] == $m['id_mapel']) ? 'selected' : '' ?>>
                        <?= h($m['nama_mapel']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div class="col-md-6 mb-3">
                  <label class="form-label">Kelas <span class="text-danger">*</span></label>
                  <select name="id_kelas" class="form-select" required>
                    <option value="">-- Pilih Kelas --</option>
                    <?php foreach ($kelasList as $k): ?>
                      <option value="<?= h($k['id_kelas']) ?>"
                        <?= ($data['id_kelas'] == $k['id_kelas']) ? 'selected' : '' ?>>
                        <?= h($k['nama_kelas']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div class="col-md-6 mb-3">
                  <label class="form-label">Tutor <span class="text-danger">*</span></label>
                  <select name="id_tutor" class="form-select" required>
                    <option value="">-- Pilih Tutor --</option>
                    <?php foreach ($tutorList as $t): ?>
                      <option value="<?= h($t['id_tutor']) ?>"
                        <?= ($data['id_tutor'] == $t['id_tutor']) ? 'selected' : '' ?>>
                        <?= h($t['nama_tutor']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div class="col-md-6 mb-3">
                  <label class="form-label">Ruangan <span class="text-danger">*</span></label>
                  <select name="id_ruangan" class="form-select" required>
                    <option value="">-- Pilih Ruangan --</option>
                    <?php foreach ($ruanganList as $r): ?>
                      <option value="<?= h($r['id_ruangan']) ?>"
                        <?= ($data['id_ruangan'] == $r['id_ruangan']) ? 'selected' : '' ?>>
                        <?= h($r['nama_ruangan']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div class="col-md-4 mb-3">
                  <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                  <input type="date" name="tanggal" class="form-control" value="<?= h($data['tanggal']) ?>" required>
                </div>

                <div class="col-md-4 mb-3">
                  <label class="form-label">Jam Mulai <span class="text-danger">*</span></label>
                  <input type="time" name="jam_mulai" class="form-control" value="<?= h($data['jam_mulai']) ?>" required>
                </div>

                <div class="col-md-4 mb-3">
                  <label class="form-label">Jam Selesai <span class="text-danger">*</span></label>
                  <input type="time" name="jam_selesai" class="form-control" value="<?= h($data['jam_selesai']) ?>" required>
                </div>

                <div class="col-md-6 mb-3">
                  <label class="form-label">Status <span class="text-danger">*</span></label>
                  <select name="status" class="form-select" required>
                    <option value="Aktif" <?= ($data['status'] == "Aktif") ? 'selected' : '' ?>>Aktif</option>
                    <option value="Berjalan" <?= ($data['status'] == "Berjalan") ? 'selected' : '' ?>>Berjalan</option>
                    <option value="Selesai" <?= ($data['status'] == "Selesai") ? 'selected' : '' ?>>Selesai</option>
                    <option value="Ditunda" <?= ($data['status'] == "Ditunda") ? 'selected' : '' ?>>Ditunda</option>
                  </select>
                </div>
              </div>

              <div class="d-flex justify-content-between mt-4">
                <button type="button" class="btn btn-outline-secondary" onclick="window.history.back()">
                  <i class="bi bi-x-circle me-1"></i>Batal
                </button>
                <button class="btn btn-primary" type="submit">
                  <i class="bi bi-check-circle me-1"></i>Update Jadwal
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- Info Jadwal -->
        <div class="card mt-4">
          <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Informasi Jadwal</h5>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-3">
                <small class="text-muted">ID Jadwal:</small>
                <p><strong>#<?= h($data['id_jadwal']) ?></strong></p>
              </div>
              <div class="col-md-3">
                <small class="text-muted">Terakhir Diupdate:</small>
                <p><strong><?= date('d-m-Y H:i') ?></strong></p>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Validasi form client-side
    document.querySelector('form').addEventListener('submit', function(e) {
      const tanggal = document.querySelector('input[name="tanggal"]').value;
      const jamMulai = document.querySelector('input[name="jam_mulai"]').value;
      const jamSelesai = document.querySelector('input[name="jam_selesai"]').value;

      if (tanggal && jamMulai && jamSelesai) {
        const selectedDate = new Date(tanggal);
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        if (selectedDate < today) {
          if (!confirm('Tanggal yang dipilih sudah lewat. Apakah Anda yakin?')) {
            e.preventDefault();
            return false;
          }
        }

        if (jamSelesai <= jamMulai) {
          alert('Jam selesai harus lebih besar dari jam mulai!');
          e.preventDefault();
          return false;
        }
      }
    });

    // Set minimum date ke hari ini untuk input tanggal
    document.addEventListener('DOMContentLoaded', function() {
      const today = new Date().toISOString().split('T')[0];
      document.querySelector('input[name="tanggal"]').min = today;
    });
  </script>
</body>

</html>