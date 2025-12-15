<?php
include "../config.php";
session_start();

// Proteksi login tutor
if (!isset($_SESSION['login']) || $_SESSION['role'] !== "tutor") {
    header("Location: ../index.php");
    exit;
}

// Ambil id_tutor dari session atau username
$id_tutor = $_SESSION['id_tutor'] ?? null;
if (!$id_tutor && isset($_SESSION['username'])) {
    $username = mysqli_real_escape_string($conn, $_SESSION['username']);
    $q_user = mysqli_query($conn, "SELECT id_tutor FROM users WHERE username = '$username' AND role = 'tutor' LIMIT 1");
    if ($q_user && mysqli_num_rows($q_user) > 0) {
        $user_data = mysqli_fetch_assoc($q_user);
        $id_tutor = $user_data['id_tutor'];
    }
}

if (!$id_tutor) {
    session_destroy();
    header("Location: ../index.php");
    exit;
}

// ambil data materi berdasarkan id_tutor
$q = mysqli_query($conn, "
    SELECT 
        m.*, 
        k.nama_kelas,
        mp.nama_mapel,
        t.nama_tutor
    FROM materi m
    LEFT JOIN kelas k ON m.id_kelas = k.id_kelas
    LEFT JOIN mapel mp ON m.id_mapel = mp.id_mapel
    LEFT JOIN tutor t ON m.id_tutor = t.id_tutor
    WHERE m.id_tutor = '$id_tutor'
    ORDER BY m.created_at DESC
");

// ambil data mapel dan kelas yang diajar oleh tutor ini
$mapel = mysqli_query($conn, "
    SELECT DISTINCT m.* 
    FROM mapel m
    INNER JOIN jadwal j ON m.id_mapel = j.id_mapel
    WHERE j.id_tutor = '$id_tutor'
    ORDER BY m.nama_mapel ASC
");

$kelas = mysqli_query($conn, "
    SELECT DISTINCT k.* 
    FROM kelas k
    INNER JOIN jadwal j ON k.id_kelas = j.id_kelas
    WHERE j.id_tutor = '$id_tutor'
    ORDER BY k.nama_kelas ASC
");

// Fungsi helper untuk escape output
function h($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Materi - Sistem Informasi Bimbel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .material-card {
            transition: transform 0.2s;
        }
        .material-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .file-icon {
            font-size: 3rem;
            color: #6c757d;
        }
        .file-badge {
            font-size: 0.75rem;
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
                    <a class="nav-link" href="dashboard_tutor.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
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
            <div class="col-md-10 content-wrapper p-4">
                <div class="page-header d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2><i class="bi bi-journal-text me-2"></i>Materi Pembelajaran</h2>
                        <p class="text-muted">Kelola materi pembelajaran untuk kelas yang Anda ajar</p>
                    </div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadMateriModal">
                        <i class="bi bi-upload"></i> Upload Materi Baru
                    </button>
                </div>

                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        Materi berhasil diupload!
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php elseif (isset($_GET['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        Gagal upload materi. Silakan coba lagi.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Materi List -->
                <?php if (mysqli_num_rows($q) > 0): ?>
                    <div class="row">
                        <?php while ($row = mysqli_fetch_assoc($q)): 
                            // Tentukan icon berdasarkan ekstensi file
                            $file_ext = strtolower(pathinfo($row['file'], PATHINFO_EXTENSION));
                            $file_icon = 'bi-file-earmark';
                            if (in_array($file_ext, ['pdf'])) $file_icon = 'bi-file-earmark-pdf';
                            elseif (in_array($file_ext, ['doc', 'docx'])) $file_icon = 'bi-file-earmark-word';
                            elseif (in_array($file_ext, ['ppt', 'pptx'])) $file_icon = 'bi-file-earmark-ppt';
                            elseif (in_array($file_ext, ['xls', 'xlsx'])) $file_icon = 'bi-file-earmark-excel';
                            elseif (in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif'])) $file_icon = 'bi-file-earmark-image';
                            
                            $file_path = '../uploads/materi/' . $row['file'];
                            $file_exists = file_exists($file_path);
                        ?>
                            <div class="col-md-4 mb-4">
                                <div class="card material-card h-100">
                                    <div class="card-body">
                                        <div class="text-center mb-3">
                                            <i class="bi <?= h($file_icon) ?> file-icon"></i>
                                        </div>
                                        <h5 class="card-title"><?= h($row['judul']) ?></h5>
                                        <p class="card-text text-muted small">
                                            <?= h($row['deskripsi'] ? substr($row['deskripsi'], 0, 100) . '...' : 'Tidak ada deskripsi') ?>
                                        </p>
                                        <div class="mb-3">
                                            <span class="badge bg-primary file-badge"><?= h($row['nama_kelas']) ?></span>
                                            <span class="badge bg-secondary file-badge"><?= h($row['nama_mapel']) ?></span>
                                            <?php if ($row['status'] == 'Aktif'): ?>
                                                <span class="badge bg-success file-badge">Aktif</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning file-badge"><?= h($row['status']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="card-text">
                                            <small class="text-muted">
                                                <i class="bi bi-calendar"></i> <?= date('d M Y', strtotime($row['tgl_upload'] ?: $row['created_at'])) ?>
                                            </small>
                                        </p>
                                    </div>
                                    <div class="card-footer bg-white d-flex justify-content-between">
                                        <a href="<?= $file_exists ? $file_path : '#' ?>" 
                                           class="btn btn-sm btn-outline-primary <?= !$file_exists ? 'disabled' : '' ?>" 
                                           target="_blank">
                                            <i class="bi bi-download"></i> Download
                                        </a>
                                        <div>
                                            <a href="edit_materi.php?id=<?= h($row['id_materi']) ?>" 
                                               class="btn btn-sm btn-outline-warning">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="hapus_materi.php?id=<?= h($row['id_materi']) ?>" 
                                               class="btn btn-sm btn-outline-danger"
                                               onclick="return confirm('Yakin ingin menghapus materi ini?');">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="bi bi-journal-x" style="font-size: 4rem; color: #6c757d;"></i>
                            <h4 class="mt-3">Belum Ada Materi</h4>
                            <p class="text-muted">Anda belum mengupload materi apapun.</p>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadMateriModal">
                                <i class="bi bi-upload"></i> Upload Materi Pertama
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Upload Modal -->
    <div class="modal fade" id="uploadMateriModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-upload me-2"></i>Upload Materi Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <form action="upload_materi.php" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="id_tutor" value="<?= h($id_tutor) ?>">
                        
                        <!-- JUDUL -->
                        <div class="mb-3">
                            <label class="form-label">Judul Materi *</label>
                            <input type="text" name="judul" class="form-control" required 
                                   placeholder="Contoh: Materi Trigonometri Kelas 10">
                        </div>

                        <!-- DESKRIPSI -->
                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea name="deskripsi" class="form-control" rows="3" 
                                      placeholder="Deskripsi singkat tentang materi ini"></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <!-- DROPDOWN MAPEL -->
                                <div class="mb-3">
                                    <label class="form-label">Mata Pelajaran *</label>
                                    <select name="id_mapel" class="form-select" required>
                                        <option value="">-- Pilih Mapel --</option>
                                        <?php 
                                        mysqli_data_seek($mapel, 0);
                                        while ($m = mysqli_fetch_assoc($mapel)): 
                                        ?>
                                            <option value="<?= h($m['id_mapel']) ?>">
                                                <?= h($m['nama_mapel']) ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <!-- DROPDOWN KELAS -->
                                <div class="mb-3">
                                    <label class="form-label">Kelas *</label>
                                    <select name="id_kelas" class="form-select" required>
                                        <option value="">-- Pilih Kelas --</option>
                                        <?php 
                                        mysqli_data_seek($kelas, 0);
                                        while ($k = mysqli_fetch_assoc($kelas)): 
                                        ?>
                                            <option value="<?= h($k['id_kelas']) ?>">
                                                <?= h($k['nama_kelas']) ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- STATUS -->
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="Aktif" selected>Aktif</option>
                                <option value="Nonaktif">Nonaktif</option>
                                <option value="Draft">Draft</option>
                            </select>
                        </div>

                        <!-- FILE -->
                        <div class="mb-3">
                            <label class="form-label">File Materi *</label>
                            <input type="file" name="file" class="form-control" required 
                                   accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.jpg,.jpeg,.png,.txt">
                            <div class="form-text">
                                Format yang didukung: PDF, Word, PowerPoint, Excel, Image, Text (Maks: 10MB)
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-upload"></i> Upload Materi
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Preview nama file yang dipilih
        document.querySelector('input[name="file"]').addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name;
            if (fileName) {
                const fileSize = (e.target.files[0].size / 1024 / 1024).toFixed(2); // MB
                const fileExt = fileName.split('.').pop().toLowerCase();
                
                const allowedExt = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'txt'];
                
                if (!allowedExt.includes(fileExt)) {
                    alert('Format file tidak didukung!');
                    e.target.value = '';
                    return;
                }
                
                if (fileSize > 10) {
                    alert('Ukuran file terlalu besar (maks 10MB)!');
                    e.target.value = '';
                    return;
                }
                
                console.log(`File: ${fileName} (${fileSize} MB)`);
            }
        });
        
        // Reset form modal ketika ditutup
        document.getElementById('uploadMateriModal').addEventListener('hidden.bs.modal', function () {
            this.querySelector('form').reset();
        });
    </script>
</body>
</html>