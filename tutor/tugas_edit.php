<?php
session_start();

require "../config.php";

// Proteksi route: hanya tutor yang boleh akses
if (!isset($_SESSION['login']) || ($_SESSION['role'] !== 'tutor' && $_SESSION['role'] !== 'admin')) {
    header("Location: ../index.php");
    exit;
}

// Ambil ID tugas dari URL
$id_tugas = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_tugas == 0) {
    header("Location: tugas.php");
    exit;
}

// Ambil data tugas berdasarkan ID
if ($_SESSION['role'] === 'admin') {
    $query = mysqli_query($conn, "
        SELECT t.*, m.nama_mapel, k.nama_kelas
        FROM tugas t
        LEFT JOIN mapel m ON t.id_mapel = m.id_mapel
        LEFT JOIN kelas k ON t.id_kelas = k.id_kelas
        WHERE t.id_tugas = $id_tugas
    ");
} else {
    $id_tutor = intval($_SESSION['id_tutor']);
    $query = mysqli_query($conn, "
        SELECT t.*, m.nama_mapel, k.nama_kelas
        FROM tugas t
        LEFT JOIN mapel m ON t.id_mapel = m.id_mapel
        LEFT JOIN kelas k ON t.id_kelas = k.id_kelas
        WHERE t.id_tugas = $id_tugas AND t.id_tutor = $id_tutor
    ");
}

$tugas = mysqli_fetch_assoc($query);

// Jika tugas tidak ditemukan
if (!$tugas) {
    echo "<script>
            alert('Tugas tidak ditemukan atau Anda tidak memiliki akses!');
            window.location.href='tugas.php';
          </script>";
    exit;
}

// Ambil data untuk dropdown
$mapel = mysqli_query($conn, "SELECT id_mapel, nama_mapel FROM mapel ORDER BY nama_mapel");
$kelas = mysqli_query($conn, "SELECT id_kelas, nama_kelas FROM kelas ORDER BY nama_kelas");

// Proses form edit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitasi input
    $judul = mysqli_real_escape_string($conn, $_POST['judul']);
    $id_mapel = intval($_POST['id_mapel']);
    $id_kelas = intval($_POST['id_kelas']);
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    $deadline = $_POST['deadline'];
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    // Format deadline jika ada
    if (!empty($deadline)) {
        $deadline = date('Y-m-d H:i:s', strtotime($deadline));
    } else {
        $deadline = null;
    }

    // // Handle file upload jika ada file baru
    // $lampiran = $tugas['lampiran']; // Default ke file lama

    if (!empty($_FILES['lampiran']['name'])) {
        $file_name = $_FILES['lampiran']['name'];
        $file_tmp = $_FILES['lampiran']['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        // Direktori penyimpanan
        $upload_dir = "../tugas/lampiran/";

        // Generate nama file unik
        $new_file_name = uniqid() . '_' . date('YmdHis') . '.' . $file_ext;
        $file_path = $upload_dir . $new_file_name;

        // Validasi ekstensi file
        $allowed_ext = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'txt', 'jpg', 'jpeg', 'png', 'zip', 'rar'];
        if (in_array($file_ext, $allowed_ext)) {
            // Upload file baru
            if (move_uploaded_file($file_tmp, $file_path)) {
                // Hapus file lama jika ada
                $old_file = "../tugas/lampiran/" . $tugas['lampiran'];
                if (file_exists($old_file) && !empty($tugas['lampiran'])) {
                    unlink($old_file);
                }

                $lampiran = $new_file_name;
            } else {
                echo "<script>alert('Gagal mengupload file lampiran!');</script>";
                $lampiran = $tugas['lampiran'];
            }
        } else {
            echo "<script>alert('Ekstensi file tidak diizinkan!');</script>";
            $lampiran = $tugas['lampiran'];
        }
    }

    // Update data tugas
    $update_query = "UPDATE tugas SET 
                    judul = '$judul',
                    id_mapel = '$id_mapel',
                    id_kelas = '$id_kelas',
                    deskripsi = '$deskripsi',
                    deadline = " . ($deadline ? "'$deadline'" : "NULL") . ",
                    status = '$status',
                    // lampiran = '$lampiran',
                    updated_at = NOW()
                    WHERE id_tugas = $id_tugas";

    if (mysqli_query($conn, $update_query)) {
        // Log aktivitas (opsional)
        $log_aktivitas = "INSERT INTO log_aktivitas (user, aksi, keterangan, waktu) 
                          VALUES ('" . mysqli_real_escape_string($conn, $_SESSION['username']) . "', 
                                  'EDIT TUGAS', 
                                  'Mengedit tugas: " . mysqli_real_escape_string($conn, $judul) . "', 
                                  NOW())";
        mysqli_query($conn, $log_aktivitas);

        echo "<script>
                alert('Tugas berhasil diperbarui!');
                window.location.href='tugas_detail.php?id=$id_tugas';
              </script>";
    } else {
        echo "<script>alert('Gagal memperbarui tugas: " . addslashes(mysqli_error($conn)) . "');</script>";
    }
}

// Format tanggal untuk input datetime-local
function format_for_datetime_local($datetime)
{
    if (!$datetime || $datetime == '0000-00-00 00:00:00') return '';
    return date('Y-m-d\TH:i', strtotime($datetime));
}

// Tentukan icon berdasarkan ekstensi file
function get_file_icon($filename)
{
    if (empty($filename)) return 'bi-file-earmark';

    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $icons = [
        'pdf' => 'bi-file-earmark-pdf',
        'doc' => 'bi-file-earmark-word',
        'docx' => 'bi-file-earmark-word',
        'ppt' => 'bi-file-earmark-ppt',
        'pptx' => 'bi-file-earmark-ppt',
        'txt' => 'bi-file-earmark-text',
        'jpg' => 'bi-file-earmark-image',
        'jpeg' => 'bi-file-earmark-image',
        'png' => 'bi-file-earmark-image',
        'zip' => 'bi-file-earmark-zip',
        'rar' => 'bi-file-earmark-zip'
    ];
    return isset($icons[$ext]) ? $icons[$ext] : 'bi-file-earmark';
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Tugas - Sistem Informasi Bimbel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .edit-card {
            border-left: 4px solid #0d6efd;
        }

        .file-info-box {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
            border: 1px dashed #dee2e6;
        }

        .status-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: 500;
        }

        .info-label {
            font-weight: 600;
            color: #6c757d;
            min-width: 120px;
            display: inline-block;
        }

        .preview-box {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
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

            <!-- Main Content -->
            <div class="col-md-10 content-wrapper">
                <div class="page-header d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2>Edit Tugas</h2>
                        <p class="text-muted">Perbarui informasi tugas</p>
                    </div>
                    <a href="tugas_detail.php?id=<?= $id_tugas ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Kembali ke Detail
                    </a>
                </div>

                <!-- Preview Informasi Tugas -->
                <div class="card preview-box mb-4">
                    <div class="card-body">
                        <h5><i class="bi bi-info-circle text-primary"></i> Informasi Tugas Saat Ini</h5>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <p><span class="info-label">ID Tugas:</span>
                                    <span class="badge bg-info">TGS<?= str_pad($tugas['id_tugas'], 4, '0', STR_PAD_LEFT) ?></span>
                                </p>

                                <p><span class="info-label">Status:</span>
                                    <?php
                                    $status_color = '';
                                    switch (strtolower($tugas['status'])) {
                                        case 'aktif':
                                            $status_color = 'bg-success';
                                            break;
                                        case 'selesai':
                                            $status_color = 'bg-primary';
                                            break;
                                        case 'nonaktif':
                                            $status_color = 'bg-secondary';
                                            break;
                                        default:
                                            $status_color = 'bg-secondary';
                                    }
                                    ?>
                                    <span class="badge <?= $status_color ?>"><?= ucfirst($tugas['status']) ?></span>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <p><span class="info-label">Dibuat:</span>
                                    <?= date('d M Y, H:i', strtotime($tugas['created_at'])) ?></p>



                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Edit -->
                <div class="card edit-card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Form Edit Tugas</h5>
                    </div>
                    <div class="card-body">
                        <form action="" method="POST" enctype="multipart/form-data">
                            <!-- JUDUL -->
                            <div class="mb-3">
                                <label class="form-label">Judul Tugas *</label>
                                <input type="text" name="judul" class="form-control"
                                    value="<?= htmlspecialchars($tugas['judul']) ?>" required>
                            </div>

                            <!-- DROPDOWN MAPEL -->
                            <div class="mb-3">
                                <label class="form-label">Mata Pelajaran *</label>
                                <select name="id_mapel" class="form-select" required>
                                    <option value="">-- Pilih Mapel --</option>
                                    <?php
                                    mysqli_data_seek($mapel, 0);
                                    while ($m = mysqli_fetch_assoc($mapel)):
                                    ?>
                                        <option value="<?= $m['id_mapel']; ?>"
                                            <?= ($m['id_mapel'] == $tugas['id_mapel']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($m['nama_mapel']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <!-- DROPDOWN KELAS -->
                            <div class="mb-3">
                                <label class="form-label">Kelas *</label>
                                <select name="id_kelas" class="form-select" required>
                                    <option value="">-- Pilih Kelas --</option>
                                    <?php
                                    mysqli_data_seek($kelas, 0);
                                    while ($k = mysqli_fetch_assoc($kelas)):
                                    ?>
                                        <option value="<?= $k['id_kelas']; ?>"
                                            <?= ($k['id_kelas'] == $tugas['id_kelas']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($k['nama_kelas']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <!-- DESKRIPSI -->
                            <div class="mb-3">
                                <label class="form-label">Deskripsi Tugas</label>
                                <textarea name="deskripsi" class="form-control" rows="4"><?= htmlspecialchars($tugas['deskripsi']) ?></textarea>
                                <div class="form-text">Jelaskan detail tugas, instruksi, dan ketentuan.</div>
                            </div>

                            <!-- DEADLINE -->
                            <div class="mb-3">
                                <label class="form-label">Deadline</label>
                                <input type="datetime-local" name="deadline" class="form-control"
                                    value="<?= format_for_datetime_local($tugas['deadline']) ?>">
                                <div class="form-text">Biarkan kosong jika tidak ada deadline.</div>
                            </div>

                            <!-- STATUS -->
                            <div class="mb-3">
                                <label class="form-label">Status *</label>
                                <select name="status" class="form-select" required>
                                    <option value="aktif" <?= ($tugas['status'] == 'aktif') ? 'selected' : '' ?>>Aktif</option>
                                    <option value="selesai" <?= ($tugas['status'] == 'selesai') ? 'selected' : '' ?>>Selesai</option>
                                    <option value="nonaktif" <?= ($tugas['status'] == 'nonaktif') ? 'selected' : '' ?>>Nonaktif</option>
                                    <option value="draft" <?= ($tugas['status'] == 'draft') ? 'selected' : '' ?>>Draft</option>
                                </select>
                                <div class="form-text">
                                    <strong>Aktif:</strong> Siswa dapat mengumpulkan<br>
                                    <strong>Selesai:</strong> Tugas selesai, siswa tidak dapat mengumpulkan<br>
                                    <strong>Nonaktif:</strong> Tugas tidak ditampilkan<br>
                                    <strong>Draft:</strong> Tugas masih dalam penyusunan
                                </div>
                            </div>

                            <!-- LAMPIRAN -->
                            <div class="mb-4">
                                <label class="form-label">Lampiran Tugas</label>
                                <input type="file" name="lampiran" class="form-control" id="fileInput">
                                <div class="form-text">Upload file pendukung (PDF, DOC, PPT, gambar, dll). Biarkan kosong jika tidak ingin mengubah.</div>

                                <?php if (!empty($tugas['lampiran'])): ?>
                                    <div class="file-info-box mt-3">
                                        <h6><i class="bi bi-paperclip"></i> Lampiran Saat Ini:</h6>
                                        <div class="d-flex align-items-center mt-2">
                                            <i class="bi <?= get_file_icon($tugas['lampiran']) ?> fs-4 text-primary me-3"></i>
                                            <div class="flex-grow-1">
                                                <p class="mb-1"><?= htmlspecialchars($tugas['lampiran']) ?></p>
                                                <?php
                                                $full_path = "../tugas/lampiran/" . $tugas['lampiran'];
                                                if (file_exists($full_path)) {
                                                    $size = filesize($full_path);
                                                    if ($size < 1024) {
                                                        $file_size = $size . ' B';
                                                    } elseif ($size < 1048576) {
                                                        $file_size = round($size / 1024, 2) . ' KB';
                                                    } else {
                                                        $file_size = round($size / 1048576, 2) . ' MB';
                                                    }
                                                    echo '<small class="text-muted">Ukuran: ' . $file_size . '</small>';
                                                }
                                                ?>
                                            </div>
                                            <a href="../tugas/lampiran/<?= htmlspecialchars($tugas['lampiran']) ?>"
                                                class="btn btn-sm btn-outline-primary me-2"
                                                download>
                                                <i class="bi bi-download"></i>
                                            </a>
                                            <a href="../tugas/lampiran/<?= htmlspecialchars($tugas['lampiran']) ?>"
                                                class="btn btn-sm btn-outline-info"
                                                target="_blank">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </div>
                                        <div class="form-check mt-2">
                                            <input class="form-check-input" type="checkbox" id="hapusLampiran" name="hapus_lampiran">
                                            <label class="form-check-label text-danger" for="hapusLampiran">
                                                <i class="bi bi-trash"></i> Hapus lampiran ini
                                            </label>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- INFORMASI -->
                            <div class="alert alert-info">
                                <h6><i class="bi bi-info-circle"></i> Informasi:</h6>
                                <ul class="mb-0">
                                    <li>Field dengan tanda * wajib diisi</li>
                                    <li>Pastikan deadline sudah sesuai jika diisi</li>
                                    <li>Status akan mempengaruhi akses siswa terhadap tugas</li>
                                    <li>File lampiran maksimal 10MB</li>
                                    <li>Tugas dibuat pada: <?= date('d M Y, H:i', strtotime($tugas['created_at'])) ?></li>
                                </ul>
                            </div>

                            <!-- TOMBOL AKSI -->
                            <div class="d-flex justify-content-between mt-4">
                                <div>
                                    <a href="tugas_detail.php?id=<?= $id_tugas ?>" class="btn btn-outline-secondary">
                                        <i class="bi bi-x-circle"></i> Batal
                                    </a>
                                    <a href="tugas.php" class="btn btn-outline-info ms-2">
                                        <i class="bi bi-list"></i> Daftar Tugas
                                    </a>
                                </div>
                                <div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-save"></i> Simpan Perubahan
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Statistik Cepat -->
                <?php
                // Ambil statistik tugas
                $stat_query = mysqli_query($conn, "
                    SELECT 
                        COUNT(DISTINCT tu.id_siswa) as total_kumpul,
                        AVG(n.nilai) as rata_nilai
                    FROM tugas_upload tu
                    LEFT JOIN nilai n ON tu.tugas_id = n.id_tugas AND tu.id_siswa = n.id_siswa
                    WHERE tu.tugas_id = $id_tugas
                ");
                $stat = mysqli_fetch_assoc($stat_query);
                ?>

                <?php if ($stat && ($stat['total_kumpul'] > 0 || $stat['rata_nilai'] > 0)): ?>
                    <div class="card mt-4">
                        <div class="card-header bg-white">
                            <h6 class="mb-0"><i class="bi bi-graph-up"></i> Statistik Tugas</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-primary bg-opacity-10 p-3 rounded me-3">
                                            <i class="bi bi-upload text-primary fs-4"></i>
                                        </div>
                                        <div>
                                            <h4 class="mb-0"><?= $stat['total_kumpul'] ?></h4>
                                            <p class="text-muted mb-0">Siswa Mengumpulkan</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-success bg-opacity-10 p-3 rounded me-3">
                                            <i class="bi bi-star text-success fs-4"></i>
                                        </div>
                                        <div>
                                            <h4 class="mb-0"><?= $stat['rata_nilai'] ? number_format($stat['rata_nilai'], 2) : '-' ?></h4>
                                            <p class="text-muted mb-0">Rata-rata Nilai</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="alert alert-warning mt-3">
                                <i class="bi bi-exclamation-triangle"></i>
                                <strong>Perhatian:</strong> Mengedit tugas setelah siswa mengumpulkan dapat mempengaruhi penilaian. Pastikan perubahan tidak merugikan siswa.
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validasi ukuran file
        document.getElementById('fileInput').addEventListener('change', function(e) {
            if (this.files[0]) {
                const fileSize = this.files[0].size / 1024 / 1024; // dalam MB
                if (fileSize > 10) {
                    alert('Ukuran file terlalu besar! Maksimal 10MB.');
                    this.value = '';
                }
            }
        });

        // Validasi deadline tidak boleh di masa lalu
        document.querySelector('input[name="deadline"]').addEventListener('change', function(e) {
            const deadline = new Date(this.value);
            const now = new Date();

            if (deadline < now) {
                if (!confirm('Deadline yang Anda pilih sudah lewat. Apakah Anda yakin?')) {
                    this.value = '';
                }
            }
        });

        // Toggle hapus lampiran
        document.getElementById('hapusLampiran').addEventListener('change', function(e) {
            const fileInput = document.getElementById('fileInput');
            if (this.checked) {
                fileInput.disabled = true;
                fileInput.value = '';
            } else {
                fileInput.disabled = false;
            }
        });

        // Preview deskripsi saat diketik
        document.querySelector('textarea[name="deskripsi"]').addEventListener('input', function(e) {
            const preview = document.getElementById('deskripsiPreview');
            if (preview) {
                preview.innerHTML = this.value.replace(/\n/g, '<br>');
            }
        });
    </script>
</body>

</html>