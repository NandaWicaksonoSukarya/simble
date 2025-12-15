<?php
include "../config.php";
session_start();

// Cek apakah user sudah login sebagai tutor
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'tutor') {
    header("Location: ../login.php");
    exit();
}

$tutor = $_SESSION['username'];

// Ambil ID materi dari URL
$id_materi = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Ambil data materi berdasarkan ID
$query_materi = mysqli_query($conn, "SELECT * FROM materi WHERE id_materi = '$id_materi' AND tutor = '$tutor'");
$materi = mysqli_fetch_assoc($query_materi);

// Jika materi tidak ditemukan atau bukan milik tutor
if (!$materi) {
    echo "<script>alert('Materi tidak ditemukan atau Anda tidak memiliki akses!'); window.location.href='materi.php';</script>";
    exit();
}

// Ambil data untuk dropdown
$mapel = mysqli_query($conn, "SELECT * FROM mapel ORDER BY id_mapel ASC");
$kelas = mysqli_query($conn, "SELECT * FROM kelas ORDER BY id_kelas ASC");

// Proses form edit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $judul = mysqli_real_escape_string($conn, $_POST['judul']);
    $id_mapel = intval($_POST['id_mapel']);
    $id_kelas = intval($_POST['id_kelas']);

    // Handle file upload jika ada file baru
    if (!empty($_FILES['file']['name'])) {
        $file_name = $_FILES['file']['name'];
        $file_tmp = $_FILES['file']['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        // Direktori penyimpanan
        $upload_dir = "../materi/";

        // Generate nama file unik
        $new_file_name = uniqid() . '_' . date('YmdHis') . '.' . $file_ext;
        $file_path = $upload_dir . $new_file_name;

        // Validasi ekstensi file
        $allowed_ext = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'txt', 'jpg', 'jpeg', 'png'];
        if (in_array($file_ext, $allowed_ext)) {
            // Upload file baru
            if (move_uploaded_file($file_tmp, $file_path)) {
                // Hapus file lama jika ada
                $old_file = "../materi/" . $materi['file_path'];
                if (file_exists($old_file) && $materi['file_path'] != '') {
                    unlink($old_file);
                }

                $file_path_db = $new_file_name;
            } else {
                echo "<script>alert('Gagal mengupload file!');</script>";
                $file_path_db = $materi['file_path'];
            }
        } else {
            echo "<script>alert('Ekstensi file tidak diizinkan!');</script>";
            $file_path_db = $materi['file_path'];
        }
    } else {
        // Jika tidak ada file baru, gunakan file lama
        $file_path_db = $materi['file_path'];
    }

    // Update data materi
    $update_query = "UPDATE materi SET 
                    judul = '$judul',
                    id_mapel = '$id_mapel',
                    id_kelas = '$id_kelas',
                    file_path = '$file_path_db'
                    WHERE id_materi = '$id_materi' AND tutor = '$tutor'";

    if (mysqli_query($conn, $update_query)) {
        echo "<script>
                alert('Materi berhasil diperbarui!');
                window.location.href='materi.php';
              </script>";
    } else {
        echo "<script>alert('Gagal memperbarui materi: " . mysqli_error($conn) . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Materi - Sistem Informasi Bimbel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .file-info {
            background-color: #f8f9fa;
            border: 1px dashed #dee2e6;
            border-radius: 5px;
            padding: 10px;
            margin-top: 5px;
        }

        .file-info a {
            text-decoration: none;
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
                    <a class="nav-link active" href="materi.php"><i class="bi bi-journal-text"></i> Materi</a>
                    <a class="nav-link" href="tugas.php"><i class="bi bi-clipboard-check"></i> Tugas & Penilaian</a>
                    <a class="nav-link" href="siswa.php"><i class="bi bi-people"></i> Data Siswa</a>
                    <a class="nav-link" href="profil.php"><i class="bi bi-person"></i> Profil</a>
                    <a class="nav-link" href="../logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 content-wrapper">
                <div class="page-header d-flex justify-content-between align-items-center">
                    <div>
                        <h2>Edit Materi Pembelajaran</h2>
                        <p class="text-muted">Edit materi pembelajaran Anda</p>
                    </div>
                    <a href="materi.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Kembali ke Daftar Materi
                    </a>
                </div>

                <!-- Edit Form -->
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Edit Materi: <?= htmlspecialchars($materi['judul']) ?></h5>
                    </div>
                    <div class="card-body">
                        <form action="" method="POST" enctype="multipart/form-data">
                            <!-- JUDUL -->
                            <div class="mb-3">
                                <label class="form-label">Judul Materi</label>
                                <input type="text" name="judul" class="form-control"
                                    value="<?= htmlspecialchars($materi['judul']) ?>" required>
                            </div>

                            <!-- DROPDOWN MAPEL -->
                            <div class="mb-3">
                                <label class="form-label">Mapel</label>
                                <select name="id_mapel" class="form-select" required>
                                    <option value="">-- Pilih Mapel --</option>
                                    <?php
                                    // Reset pointer hasil query
                                    mysqli_data_seek($mapel, 0);
                                    while ($m = mysqli_fetch_assoc($mapel)) :
                                    ?>
                                        <option value="<?= $m['id_mapel']; ?>"
                                            <?= ($m['id_mapel'] == $materi['id_mapel']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($m['nama_mapel']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <!-- DROPDOWN KELAS -->
                            <div class="mb-3">
                                <label class="form-label">Kelas</label>
                                <select name="id_kelas" class="form-select" required>
                                    <option value="">-- Pilih Kelas --</option>
                                    <?php
                                    // Reset pointer hasil query
                                    mysqli_data_seek($kelas, 0);
                                    while ($k = mysqli_fetch_assoc($kelas)) :
                                    ?>
                                        <option value="<?= $k['id_kelas']; ?>"
                                            <?= ($k['id_kelas'] == $materi['id_kelas']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($k['nama_kelas']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <!-- FILE -->
                            <div class="mb-3">
                                <label class="form-label">File Materi</label>
                                <input type="file" name="file" class="form-control">
                                <small class="text-muted">Biarkan kosong jika tidak ingin mengubah file</small>

                                <!-- Info file saat ini -->
                                <?php if (!empty($materi['file_path'])) : ?>
                                    <div class="file-info mt-2">
                                        <strong>File saat ini:</strong><br>
                                        <a href="../materi/<?= $materi['file_path'] ?>" target="_blank" class="text-primary">
                                            <i class="bi bi-file-earmark"></i> Lihat File
                                        </a>
                                        <span class="text-muted ms-2">
                                            (Terakhir diupdate: <?= date('d M Y H:i', strtotime($materi['updated_at'] ?? $materi['created_at'])) ?>)
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- INFO TAMBAHAN -->
                            <div class="mb-4">
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle"></i>
                                    <strong>Informasi:</strong><br>
                                    • File yang diizinkan: PDF, DOC, DOCX, PPT, PPTX, TXT, JPG, JPEG, PNG<br>
                                    • Maksimal ukuran file: 10MB<br>
                                    • Materi dibuat pada: <?= date('d M Y H:i', strtotime($materi['created_at'])) ?>
                                </div>
                            </div>

                            <!-- TOMBOL -->
                            <div class="d-flex justify-content-between">
                                <a href="materi.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-x-circle"></i> Batal
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Simpan Perubahan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validasi ukuran file (maksimal 10MB)
        document.querySelector('input[name="file"]').addEventListener('change', function(e) {
            if (this.files[0]) {
                const fileSize = this.files[0].size / 1024 / 1024; // dalam MB
                if (fileSize > 10) {
                    alert('Ukuran file terlalu besar! Maksimal 10MB.');
                    this.value = '';
                }
            }
        });
    </script>
</body>

</html>