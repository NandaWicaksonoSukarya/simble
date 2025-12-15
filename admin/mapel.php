<?php
session_start();
require "../config.php";

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

function h($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

/* =====================================================
   MASTER DATA (untuk dropdown filter)
===================================================== */
$jenjangList = [
    'SD' => 'SD',
    'SMP' => 'SMP', 
];

$kurikulumList = [
    'Kurikulum 2013' => 'Kurikulum 2013',
    'Kurikulum Merdeka' => 'Kurikulum Merdeka',
    'KTSP' => 'KTSP'
];

/* =====================================================
   HAPUS / NONAKTIFKAN MAPEL
===================================================== */
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    mysqli_query($conn, "UPDATE mapel SET status = 'Nonaktif' WHERE id_mapel = $id");
    
    $_SESSION['flash'][] = [
        'type' => 'success',
        'text' => 'Mapel berhasil dinonaktifkan'
    ];
    
    header("Location: mapel.php");
    exit;
}

if (isset($_GET['aktifkan'])) {
    $id = (int)$_GET['aktifkan'];
    mysqli_query($conn, "UPDATE mapel SET status = 'Aktif' WHERE id_mapel = $id");
    
    $_SESSION['flash'][] = [
        'type' => 'success',
        'text' => 'Mapel berhasil diaktifkan'
    ];
    
    header("Location: mapel.php");
    exit;
}

/* =====================================================
   DATA MAPEL (untuk tabel)
===================================================== */
$qMapel = mysqli_query($conn, "
    SELECT 
        m.*,
        COUNT(k.id_kelas) as jumlah_kelas
    FROM mapel m
    LEFT JOIN kelas k ON m.id_mapel = k.id_mapel AND k.status = 'Aktif'
    GROUP BY m.id_mapel
    ORDER BY m.nama_mapel ASC
");

$mapelData = [];
while ($row = mysqli_fetch_assoc($qMapel)) {
    $mapelData[] = $row;
}

/* =====================================================
   STATISTIK RINGAN
===================================================== */
$statistik = [];
if (!empty($mapelData)) {
    $totalKelas = 0;
    $totalMateri = 0;
    $totalTugas = 0;
    
    foreach ($mapelData as $mapel) {
        $totalKelas += $mapel['jumlah_kelas'];
        
        // Hitung materi per mapel
        $qMateri = mysqli_query($conn, "
            SELECT COUNT(*) as count FROM materi m
            JOIN kelas k ON m.id_kelas = k.id_kelas
            WHERE k.id_mapel = " . $mapel['id_mapel']
        );
        $materiCount = mysqli_fetch_assoc($qMateri)['count'];
        $totalMateri += $materiCount;
        
        // Hitung tugas per mapel
        $qTugas = mysqli_query($conn, "
            SELECT COUNT(*) as count FROM tugas t
            WHERE t.id_mapel = " . $mapel['id_mapel']
        );
        $tugasCount = mysqli_fetch_assoc($qTugas)['count'];
        $totalTugas += $tugasCount;
    }
    
    $statistik = [
        'total_mapel' => count($mapelData),
        'total_kelas' => $totalKelas,
        'total_materi' => $totalMateri,
        'total_tugas' => $totalTugas
    ];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Mapel - Sistem Informasi Bimbel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .card-statistik {
            border-radius: 10px;
            transition: transform 0.2s;
        }
        
        .card-statistik:hover {
            transform: translateY(-5px);
        }
        
        .mapel-table tr {
            transition: background-color 0.2s;
        }
        
        .mapel-table tr:hover {
            background-color: rgba(0, 0, 0, 0.02);
        }
        
        .status-badge {
            font-size: 0.75rem;
            padding: 3px 10px;
            border-radius: 20px;
        }
        
        .action-buttons .btn {
            padding: 2px 8px;
            font-size: 0.875rem;
        }
        
        .filter-card {
            background-color: #f8f9fa;
            border-radius: 10px;
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
                    <a class="nav-link" href="siswa.php"><i class="bi bi-people"></i> Data Siswa</a>
                    <a class="nav-link" href="tutor.php"><i class="bi bi-person-badge"></i> Data Tutor</a>
                    <a class="nav-link" href="kelas.php"><i class="bi bi-calendar3"></i> Jadwal Kelas</a>
                    <a class="nav-link" href="pembayaran.php"><i class="bi bi-cash-coin"></i> Pembayaran</a>
                    <a class="nav-link active" href="mapel.php"><i class="bi bi-book"></i> Mata Pelajaran</a>
                    <a class="nav-link" href="laporan.php"><i class="bi bi-file-earmark-text"></i> Laporan</a>
                    <a class="nav-link" href="../logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 content-wrapper p-4">
                <div class="page-header d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2>Data Mata Pelajaran</h2>
                        <p class="text-muted">Kelola mata pelajaran yang tersedia di sistem</p>
                    </div>
                    <a href="mapel_tambah.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Tambah Mapel
                    </a>
                </div>

                <!-- Flash messages -->
                <?php if (!empty($_SESSION['flash'])): ?>
                    <div class="mb-3">
                        <?php foreach ($_SESSION['flash'] as $f): ?>
                            <div class="alert alert-<?= h($f['type']) ?> alert-dismissible fade show">
                                <?= h($f['text']) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endforeach; ?>
                        <?php $_SESSION['flash'] = []; ?>
                    </div>
                <?php endif; ?>

                <!-- Statistik Ringan -->
                <?php if (!empty($statistik)): ?>
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card card-statistik border-primary">
                            <div class="card-body text-center">
                                <h1 class="text-primary"><?= $statistik['total_mapel'] ?></h1>
                                <p class="text-muted mb-0">Total Mapel</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card card-statistik border-success">
                            <div class="card-body text-center">
                                <h1 class="text-success"><?= $statistik['total_kelas'] ?></h1>
                                <p class="text-muted mb-0">Digunakan di Kelas</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card card-statistik border-info">
                            <div class="card-body text-center">
                                <h1 class="text-info"><?= $statistik['total_materi'] ?></h1>
                                <p class="text-muted mb-0">Total Materi</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card card-statistik border-warning">
                            <div class="card-body text-center">
                                <h1 class="text-warning"><?= $statistik['total_tugas'] ?></h1>
                                <p class="text-muted mb-0">Total Tugas</p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Filter -->
                <div class="card filter-card mb-4">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Filter Data</h5>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Jenjang</label>
                                <select class="form-select" id="filterJenjang">
                                    <option value="">Semua Jenjang</option>
                                    <?php foreach ($jenjangList as $key => $value): ?>
                                        <option value="<?= $key ?>"><?= $value ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Kurikulum</label>
                                <select class="form-select" id="filterKurikulum">
                                    <option value="">Semua Kurikulum</option>
                                    <?php foreach ($kurikulumList as $key => $value): ?>
                                        <option value="<?= $key ?>"><?= $value ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Status</label>
                                <select class="form-select" id="filterStatus">
                                    <option value="">Semua Status</option>
                                    <option value="Aktif">Aktif</option>
                                    <option value="Nonaktif">Nonaktif</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Tabel Daftar Mapel -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover mapel-table">
                                <thead class="table-light">
                                    <tr>
                                        <th>No</th>
                                        <th>Nama Mapel</th>
                                        <th>Jenjang</th>
                                        <th>Kurikulum</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="mapelTableBody">
                                    <?php if (empty($mapelData)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4">
                                            <i class="bi bi-book display-1 text-muted"></i>
                                            <h5 class="mt-3">Belum ada data mapel</h5>
                                            <p class="text-muted">Tambahkan mapel pertama Anda</p>
                                            <a href="mapel_tambah.php" class="btn btn-primary">
                                                <i class="bi bi-plus-circle"></i> Tambah Mapel
                                            </a>
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                        <?php foreach ($mapelData as $index => $row): ?>
                                        <tr class="mapel-row"
                                            data-jenjang="<?= h($row['jenjang']) ?>"
                                            data-kurikulum="<?= h($row['kurikulum']) ?>"
                                            data-status="<?= h($row['status']) ?>">
                                            <td><?= $index + 1 ?></td>
                                            <td>
                                                <strong><?= h($row['nama_mapel']) ?></strong>
                                                <?php if ($row['kode_mapel']): ?>
                                                    <br>
                                                    <small class="text-muted"><?= h($row['kode_mapel']) ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= $row['jenjang'] ? h($row['jenjang']) : '<span class="text-muted">-</span>' ?></td>
                                            <td><?= $row['kurikulum'] ? h($row['kurikulum']) : '<span class="text-muted">-</span>' ?></td>
                                            <td>
                                                <span class="badge <?= $row['status'] == 'Aktif' ? 'bg-success' : 'bg-secondary' ?> status-badge">
                                                    <?= h($row['status']) ?>
                                                </span>
                                                <?php if ($row['jumlah_kelas'] > 0): ?>
                                                    <br>
                                                    <small class="text-muted">Digunakan di <?= $row['jumlah_kelas'] ?> kelas</small>
                                                <?php endif; ?>
                                            </td>
                                            <td class="action-buttons">
                                                <div class="d-flex gap-2">
                                                    <a href="mapel_detail.php?id=<?= $row['id_mapel'] ?>" 
                                                       class="btn btn-sm btn-info" title="Detail">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <a href="mapel_edit.php?id=<?= $row['id_mapel'] ?>" 
                                                       class="btn btn-sm btn-warning" title="Edit">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <?php if ($row['status'] == 'Aktif'): ?>
                                                        <a href="?hapus=<?= $row['id_mapel'] ?>" 
                                                           class="btn btn-sm btn-danger" 
                                                           title="Nonaktifkan"
                                                           onclick="return confirm('Nonaktifkan mapel <?= h($row['nama_mapel']) ?>?')">
                                                            <i class="bi bi-x-circle"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <a href="?aktifkan=<?= $row['id_mapel'] ?>" 
                                                           class="btn btn-sm btn-success" 
                                                           title="Aktifkan"
                                                           onclick="return confirm('Aktifkan mapel <?= h($row['nama_mapel']) ?>?')">
                                                            <i class="bi bi-check-circle"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Filter Tabel Mapel
        document.addEventListener('DOMContentLoaded', function() {
            const filterJenjang = document.getElementById('filterJenjang');
            const filterKurikulum = document.getElementById('filterKurikulum');
            const filterStatus = document.getElementById('filterStatus');
            
            if (filterJenjang) {
                filterJenjang.addEventListener('change', filterTable);
            }
            if (filterKurikulum) {
                filterKurikulum.addEventListener('change', filterTable);
            }
            if (filterStatus) {
                filterStatus.addEventListener('change', filterTable);
            }
        });
        
        function filterTable() {
            const filterJenjang = document.getElementById('filterJenjang').value;
            const filterKurikulum = document.getElementById('filterKurikulum').value;
            const filterStatus = document.getElementById('filterStatus').value;
            
            document.querySelectorAll('.mapel-row').forEach(row => {
                const jenjang = row.getAttribute('data-jenjang');
                const kurikulum = row.getAttribute('data-kurikulum');
                const status = row.getAttribute('data-status');
                
                const show =
                    (filterJenjang === '' || filterJenjang === jenjang) &&
                    (filterKurikulum === '' || filterKurikulum === kurikulum) &&
                    (filterStatus === '' || filterStatus === status);
                
                row.style.display = show ? '' : 'none';
            });
        }
    </script>
</body>
</html>