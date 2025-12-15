<?php
session_start();
require "../config.php";

// helper escape
function h($s)
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
$q = isset($_GET['q']) ? trim($_GET['q']) : '';

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// statistik
$resTotal = mysqli_query($conn, "SELECT COUNT(*) AS c FROM materi");
$totalMateri = (int)mysqli_fetch_assoc($resTotal)['c'];

$resPdf = mysqli_query($conn, "SELECT COUNT(*) AS c FROM materi WHERE LOWER(file) LIKE '%.pdf%'");
$pdfCount = (int)mysqli_fetch_assoc($resPdf)['c'];

$resPpt = mysqli_query($conn, "
    SELECT COUNT(*) AS c 
    FROM materi 
    WHERE LOWER(file) LIKE '%.ppt%' 
       OR LOWER(file) LIKE '%.pptx%'
");
$pptCount = (int)mysqli_fetch_assoc($resPpt)['c'];

$resNew = mysqli_query($conn, "
    SELECT COUNT(*) AS c 
    FROM materi 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 14 DAY)
");
$newCount = (int)mysqli_fetch_assoc($resNew)['c'];

// latest materi
$resLatest = mysqli_query($conn, "
    SELECT 
        m.*, 
        mp.nama_mapel, 
        k.nama_kelas,
        t.nama_tutor
    FROM materi m
    LEFT JOIN mapel mp ON m.id_mapel = mp.id_mapel
    LEFT JOIN kelas k ON m.id_kelas = k.id_kelas
    LEFT JOIN tutor t ON m.id_tutor = t.id_tutor
    ORDER BY m.created_at DESC 
    LIMIT 3
");
$latestList = [];
while ($r = mysqli_fetch_assoc($resLatest)) $latestList[] = $r;

// build WHERE
$whereParts = [];

if ($q !== '') {
    $qEsc = mysqli_real_escape_string($conn, $q);
    $whereParts[] = "(m.judul LIKE '%$qEsc%' 
                      OR m.deskripsi LIKE '%$qEsc%' 
                      OR t.nama_tutor LIKE '%$qEsc%'
                      OR mp.nama_mapel LIKE '%$qEsc%')";
}

$whereSql = $whereParts ? "WHERE " . implode(' AND ', $whereParts) : "";

// total filtered
$resCount = mysqli_query($conn, "
    SELECT COUNT(*) AS c 
    FROM materi m
    LEFT JOIN mapel mp ON m.id_mapel = mp.id_mapel
    LEFT JOIN tutor t ON m.id_tutor = t.id_tutor
    $whereSql
");
$totalFiltered = (int)mysqli_fetch_assoc($resCount)['c'];
$totalPages = max(1, ceil($totalFiltered / $perPage));

// list materi
$sql = "
    SELECT 
        m.*, 
        mp.nama_mapel, 
        k.nama_kelas,
        t.nama_tutor
    FROM materi m
    LEFT JOIN mapel mp ON m.id_mapel = mp.id_mapel
    LEFT JOIN kelas k ON m.id_kelas = k.id_kelas
    LEFT JOIN tutor t ON m.id_tutor = t.id_tutor
    $whereSql
    ORDER BY m.created_at DESC
    LIMIT $perPage OFFSET $offset
";
$resList = mysqli_query($conn, $sql);

// dropdown mapel
$mapelRes = mysqli_query($conn, "
    SELECT DISTINCT mp.id_mapel, mp.nama_mapel
    FROM materi m
    JOIN mapel mp ON m.id_mapel = mp.id_mapel
    ORDER BY mp.nama_mapel ASC
");
$mapelList = [];
while ($m = mysqli_fetch_assoc($mapelRes)) $mapelList[] = $m;

// dropdown kelas
$kelasRes = mysqli_query($conn, "
    SELECT DISTINCT k.id_kelas, k.nama_kelas
    FROM materi m
    JOIN kelas k ON m.id_kelas = k.id_kelas
    ORDER BY k.nama_kelas ASC
");
$kelasList = [];
while ($k = mysqli_fetch_assoc($kelasRes)) $kelasList[] = $k;

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Materi Pembelajaran - Sistem Informasi Bimbel</title>
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
                    <a class="nav-link active" href="materi.php"><i class="bi bi-journal-text"></i> Materi</a>
                    <a class="nav-link" href="tugas.php"><i class="bi bi-clipboard-check"></i> Tugas</a>
                    <a class="nav-link" href="nilai.php"><i class="bi bi-bar-chart"></i> Nilai</a>
                    <a class="nav-link" href="pembayaran.php"><i class="bi bi-cash-coin"></i> Pembayaran</a>
                    <a class="nav-link" href="profil.php"><i class="bi bi-person"></i> Profil</a>
                    <a class="nav-link" href="../logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 content-wrapper">
                <div class="page-header">
                    <h2>Materi Pembelajaran</h2>
                    <p class="text-muted">Akses materi pembelajaran dari tutor Anda</p>
                </div>

                <!-- Search Form -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-2">
                            <div class="col-md-8">
                                <input type="text" name="q" value="<?= h($q) ?>" class="form-control" placeholder="Cari materi berdasarkan judul, deskripsi, atau nama tutor...">
                            </div>
                            <div class="col-md-2">
                                <button class="btn btn-primary w-100">
                                    <i class="bi bi-search"></i> Cari
                                </button>
                            </div>
                            <div class="col-md-2">
                                <a href="materi.php" class="btn btn-secondary w-100">
                                    <i class="bi bi-arrow-clockwise"></i> Reset
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Stats -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="bi bi-journal-text text-primary" style="font-size: 2rem;"></i>
                                <h3 class="mt-2"><?= h($totalMateri) ?></h3>
                                <p class="text-muted mb-0">Total Materi</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="bi bi-file-earmark-pdf text-danger" style="font-size: 2rem;"></i>
                                <h3 class="mt-2"><?= h($pdfCount) ?></h3>
                                <p class="text-muted mb-0">PDF</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="bi bi-file-earmark-ppt text-warning" style="font-size: 2rem;"></i>
                                <h3 class="mt-2"><?= h($pptCount) ?></h3>
                                <p class="text-muted mb-0">PowerPoint</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="bi bi-calendar-plus text-success" style="font-size: 2rem;"></i>
                                <h3 class="mt-2"><?= h($newCount) ?></h3>
                                <p class="text-muted mb-0">Materi Baru</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Materi Terbaru -->
                <h5 class="mb-3">Materi Terbaru</h5>
                <div class="row mb-4">
                    <?php foreach ($latestList as $mat):
                        // Tentukan warna badge berdasarkan mapel
                        $badgeClass = 'bg-primary';
                        $mapelLower = strtolower($mat['nama_mapel'] ?? '');
                        if (strpos($mapelLower, 'IPA') !== false) $badgeClass = 'bg-success';
                        elseif (strpos($mapelLower, 'IPS') !== false) $badgeClass = 'bg-warning';
                        elseif (strpos($mapelLower, 'bahasa') !== false) $badgeClass = 'bg-danger';
                        elseif (strpos($mapelLower, 'matematika') !== false) $badgeClass = 'bg-info';
                        
                        // Ambil ekstensi file
                        $fileExt = '';
                        $fileIcon = 'bi-file-earmark';
                        $fileColor = 'text-secondary';
                        
                        if (!empty($mat['file'])) {
                            $fileExt = pathinfo($mat['file'], PATHINFO_EXTENSION);
                            $fileExtLower = strtolower($fileExt);
                            
                            if (in_array($fileExtLower, ['pdf'])) {
                                $fileIcon = 'bi-file-earmark-pdf';
                                $fileColor = 'text-danger';
                            } elseif (in_array($fileExtLower, ['ppt', 'pptx'])) {
                                $fileIcon = 'bi-file-earmark-ppt';
                                $fileColor = 'text-warning';
                            } elseif (in_array($fileExtLower, ['doc', 'docx'])) {
                                $fileIcon = 'bi-file-earmark-word';
                                $fileColor = 'text-primary';
                            } elseif (in_array($fileExtLower, ['xls', 'xlsx'])) {
                                $fileIcon = 'bi-file-earmark-excel';
                                $fileColor = 'text-success';
                            }
                        }
                    ?>
                        <div class="col-md-4 mb-3">
                            <div class="card h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <span class="badge <?= h($badgeClass) ?>"><?= h($mat['nama_mapel'] ?? '-') ?></span>
                                        <?php if (strtotime($mat['created_at']) >= strtotime('-14 days')): ?>
                                            <span class="badge bg-success">Baru</span>
                                        <?php endif; ?>
                                    </div>
                                    <h5 class="card-title"><?= h($mat['judul']) ?></h5>
                                    <p class="text-muted small mb-2">
                                        Kelas <?= h($mat['nama_kelas'] ?? '-') ?>
                                    </p>
                                    <p class="card-text small"><?= h(mb_strimwidth($mat['deskripsi'] ?? '', 0, 120, '...')) ?></p>
                                    <hr>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <small class="text-muted">
                                            <?php if (!empty($mat['file'])): ?>
                                                <i class="bi <?= $fileIcon ?> <?= $fileColor ?>"></i>
                                                <?= h(strtoupper($fileExt)) ?>
                                            <?php else: ?>
                                                <span class="text-muted">Tidak ada file</span>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted"><i class="bi bi-person"></i> <?= h($mat['nama_tutor'] ?? 'Tutor') ?></small>
                                    </div>
                                    <small class="text-muted"><i class="bi bi-calendar"></i> <?= h(date('j M Y', strtotime($mat['created_at'] ?? 'now'))) ?></small>
                                    <div class="mt-3 d-grid gap-2">
                                        <button class="btn btn-primary btn-detail-materi"
                                            data-item='<?= json_encode($mat, JSON_HEX_APOS | JSON_HEX_QUOT) ?>'>
                                            <i class="bi bi-eye"></i> Lihat Detail
                                        </button>
                                        <?php if (!empty($mat['file'])): ?>
                                            <a href="<?= h($mat['file']) ?>" class="btn btn-success" download>
                                                <i class="bi bi-download"></i> Download
                                            </a>
                                        <?php else: ?>
                                            <button class="btn btn-success" disabled><i class="bi bi-download"></i> Download</button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Semua Materi -->
                <h5 class="mb-3">Semua Materi</h5>
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Mata Pelajaran</th>
                                        <th>Judul Materi</th>
                                        <th>Kelas</th>
                                        <th>Tutor</th>
                                        <th>Tanggal Upload</th>
                                        <th>File</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = mysqli_fetch_assoc($resList)):
                                        // Tentukan ikon berdasarkan ekstensi file
                                        $fileExt = '';
                                        $fileIcon = 'bi-file-earmark';
                                        $fileColor = 'text-secondary';
                                        
                                        if (!empty($row['file'])) {
                                            $fileExt = pathinfo($row['file'], PATHINFO_EXTENSION);
                                            $fileExtLower = strtolower($fileExt);
                                            
                                            if (in_array($fileExtLower, ['pdf'])) {
                                                $fileIcon = 'bi-file-earmark-pdf';
                                                $fileColor = 'text-danger';
                                            } elseif (in_array($fileExtLower, ['ppt', 'pptx'])) {
                                                $fileIcon = 'bi-file-earmark-ppt';
                                                $fileColor = 'text-warning';
                                            } elseif (in_array($fileExtLower, ['doc', 'docx'])) {
                                                $fileIcon = 'bi-file-earmark-word';
                                                $fileColor = 'text-primary';
                                            } elseif (in_array($fileExtLower, ['xls', 'xlsx'])) {
                                                $fileIcon = 'bi-file-earmark-excel';
                                                $fileColor = 'text-success';
                                            }
                                        }
                                        
                                        // Tentukan warna badge
                                        $badgeClass = 'bg-primary';
                                        $mapelLower = strtolower($row['nama_mapel'] ?? '');
                                        if (strpos($mapelLower, 'fisika') !== false) $badgeClass = 'bg-success';
                                        elseif (strpos($mapelLower, 'kimia') !== false) $badgeClass = 'bg-warning';
                                        elseif (strpos($mapelLower, 'biologi') !== false) $badgeClass = 'bg-danger';
                                        elseif (strpos($mapelLower, 'matematika') !== false) $badgeClass = 'bg-info';
                                    ?>
                                        <tr>
                                            <td>
                                                <span class="badge <?= h($badgeClass) ?>">
                                                    <?= h($row['nama_mapel'] ?? '-') ?>
                                                </span>
                                            </td>
                                            <td><?= h($row['judul']) ?></td>
                                            <td><?= h($row['nama_kelas'] ?? '-') ?></td>
                                            <td><?= h($row['nama_tutor']) ?></td>
                                            <td><?= h(date('j M Y', strtotime($row['created_at'] ?? 'now'))) ?></td>
                                            <td>
                                                <?php if (!empty($row['file'])): ?>
                                                    <i class="bi <?= h($fileIcon) ?> <?= h($fileColor) ?>"></i>
                                                    <?= h(strtoupper($fileExt)) ?>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-primary btn-detail-materi"
                                                    data-item='<?= json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT) ?>'>
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                <?php if (!empty($row['file'])): ?>
                                                    <a href="<?= h($row['file']) ?>" class="btn btn-sm btn-success" download>
                                                        <i class="bi bi-download"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <button class="btn btn-sm btn-success" disabled><i class="bi bi-download"></i></button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>

                                    <?php if ($totalFiltered == 0): ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted">Tidak ada materi.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <nav>
                            <ul class="pagination justify-content-end">
                                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">Previous</a>
                                </li>
                                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                                    <li class="page-item <?= ($p == $page) ? 'active' : '' ?>">
                                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>"><?= $p ?></a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Next</a>
                                </li>
                            </ul>
                        </nav>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Detail Materi Modal (dinamis via JS) -->
    <div class="modal fade" id="detailMateriModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Materi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detailMateriBody">
                    <!-- akan diisi oleh JS -->
                </div>
                <div class="modal-footer">
                    <a id="detailDownloadBtn" class="btn btn-success" href="#" download><i class="bi bi-download"></i> Download Materi</a>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('click', function(ev) {
            const t = ev.target.closest('.btn-detail-materi');
            if (!t) return;
            const dataRaw = t.getAttribute('data-item');
            if (!dataRaw) return;
            try {
                const item = JSON.parse(dataRaw);
                showDetail(item);
                // show modal
                const modal = new bootstrap.Modal(document.getElementById('detailMateriModal'));
                modal.show();
            } catch (e) {
                console.error(e);
            }
        });

        function escapeHtml(unsafe) {
            if (unsafe === null || unsafe === undefined) return '';
            return String(unsafe)
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", "&#039;");
        }

        function showDetail(item) {
            const b = document.getElementById('detailMateriBody');
            const tanggal = item.created_at ? new Date(item.created_at).toLocaleDateString('id-ID', {
                day: 'numeric',
                month: 'short',
                year: 'numeric'
            }) : '';
            
            // Ambil ekstensi file
            let fileExt = '';
            let fileIcon = 'bi-file-earmark';
            let fileColor = 'text-secondary';
            
            if (item.file) {
                fileExt = item.file.split('.').pop().toUpperCase();
                const fileExtLower = fileExt.toLowerCase();
                
                if (['PDF'].includes(fileExtLower)) {
                    fileIcon = 'bi-file-earmark-pdf';
                    fileColor = 'text-danger';
                } else if (['PPT', 'PPTX'].includes(fileExtLower)) {
                    fileIcon = 'bi-file-earmark-ppt';
                    fileColor = 'text-warning';
                } else if (['DOC', 'DOCX'].includes(fileExtLower)) {
                    fileIcon = 'bi-file-earmark-word';
                    fileColor = 'text-primary';
                } else if (['XLS', 'XLSX'].includes(fileExtLower)) {
                    fileIcon = 'bi-file-earmark-excel';
                    fileColor = 'text-success';
                }
            }

            b.innerHTML = `
        <div class="row">
            <div class="col-md-8">
                <div class="d-flex align-items-center mb-3">
                    <span class="badge bg-primary me-2">${escapeHtml(item.nama_mapel || '')}</span>
                </div>
                <h4>${escapeHtml(item.judul)}</h4>
                <p class="text-muted">
                    <i class="bi bi-people"></i> Kelas ${escapeHtml(item.nama_kelas || '')}
                </p>
                <hr>
                <h6><i class="bi bi-card-text"></i> Deskripsi Materi</h6>
                <p class="mb-3">${escapeHtml(item.deskripsi || 'Tidak ada deskripsi')}</p>
                <hr>
                <h6><i class="bi bi-file-earmark"></i> Informasi File</h6>
                <table class="table table-sm">
                    <tr>
                        <td width="30%"><strong>Nama File</strong></td>
                        <td>${item.file ? escapeHtml(item.file.split('/').pop()) : 'Tidak ada file'}</td>
                    </tr>
                    <tr>
                        <td><strong>Format</strong></td>
                        <td>
                            ${item.file ? `
                                <i class="bi ${fileIcon} ${fileColor}"></i> 
                                ${fileExt}
                            ` : '-'}
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Status</strong></td>
                        <td>
                            <span class="badge ${item.status == 'aktif' ? 'bg-success' : 'bg-secondary'}">
                                ${escapeHtml(item.status || 'aktif')}
                            </span>
                        </td>
                    </tr>
                </table>
            </div>
            <div class="col-md-4">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <h6><i class="bi bi-person-badge"></i> Informasi Tutor</h6>
                        <div class="text-center mb-3">
                            <img src="https://ui-avatars.com/api/?name=${encodeURIComponent(item.nama_tutor || 'Tutor')}&size=80&background=0D6EFD&color=fff" class="rounded-circle" alt="Tutor">
                            <h6 class="mt-2">${escapeHtml(item.nama_tutor || '')}</h6>
                            <small class="text-muted">Tutor ${escapeHtml(item.nama_mapel || '')}</small>
                        </div>
                        <hr>
                        <div class="text-start">
                            <p class="small mb-1"><i class="bi bi-calendar"></i> <strong>Diupload:</strong> ${tanggal}</p>
                            <p class="small mb-1"><i class="bi bi-clock-history"></i> <strong>Terakhir Update:</strong> ${item.updated_at ? new Date(item.updated_at).toLocaleDateString('id-ID') : tanggal}</p>
                            <p class="small mb-0"><i class="bi bi-eye"></i> <strong>Dilihat:</strong> -</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;

            const dlBtn = document.getElementById('detailDownloadBtn');
            if (item.file) {
                dlBtn.style.display = 'inline-block';
                dlBtn.href = item.file;
            } else {
                dlBtn.style.display = 'none';
            }
        }
    </script>
</body>

</html>