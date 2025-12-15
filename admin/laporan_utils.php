<?php
/**
 * LAPORAN UTILS
 * Kumpulan fungsi helper untuk sistem laporan
 * 
 * @package SIMBLES
 * @author Admin
 */

// =====================================================
// FUNGSI UTAMA
// =====================================================

/**
 * Format angka ke Rupiah
 * 
 * @param int|float $angka Angka yang akan diformat
 * @return string Angka yang sudah diformat dalam Rupiah
 */
function formatRupiah($angka)
{
    $angka = $angka ?? 0;
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

/**
 * Escape string untuk output HTML (mencegah XSS)
 * 
 * @param string $str String yang akan di-escape
 * @return string String yang sudah di-escape
 */
function h($str)
{
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Dapatkan nama bulan dalam Bahasa Indonesia
 * 
 * @param int $month Nomor bulan (1-12)
 * @return string Nama bulan
 */
function getMonthName($month)
{
    $months = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember'
    ];
    return $months[(int)$month] ?? 'Bulan Tidak Valid';
}

/**
 * Generate daftar tahun untuk dropdown filter
 * 
 * @param int $start Tahun mulai (default: 2020)
 * @param int $endPlus Tambahan tahun dari tahun sekarang (default: 1)
 * @return array Daftar tahun
 */
function getTahunList($start = 2020, $endPlus = 1)
{
    $tahunList = [];
    $currentYear = date('Y');
    $end = $currentYear + $endPlus;
    
    for ($i = $start; $i <= $end; $i++) {
        $tahunList[] = $i;
    }
    
    return $tahunList;
}

/**
 * Generate daftar bulan untuk dropdown filter
 * 
 * @return array Daftar bulan dengan format [nomor => nama]
 */
function getBulanList()
{
    $bulanList = [];
    for ($i = 1; $i <= 12; $i++) {
        $bulanList[$i] = getMonthName($i);
    }
    return $bulanList;
}

// =====================================================
// FUNGSI DATABASE
// =====================================================

/**
 * Ambil daftar kelas aktif untuk filter
 * 
 * @param mysqli $conn Koneksi database
 * @return array Daftar kelas dengan format [id_kelas => 'nama_kelas - nama_mapel']
 */
function getKelasList($conn)
{
    $qKelas = mysqli_query($conn, "
        SELECT k.id_kelas, k.nama_kelas, m.nama_mapel
        FROM kelas k
        JOIN mapel m ON k.id_mapel = m.id_mapel
        WHERE k.status = 'Aktif'
        ORDER BY k.nama_kelas
    ");
    
    $kelasList = [];
    while ($row = mysqli_fetch_assoc($qKelas)) {
        $kelasList[$row['id_kelas']] = h($row['nama_kelas'] . ' - ' . $row['nama_mapel']);
    }
    
    return $kelasList;
}

/**
 * Ambil daftar mata pelajaran aktif untuk filter
 * 
 * @param mysqli $conn Koneksi database
 * @return array Daftar mapel dengan format [id_mapel => nama_mapel]
 */
function getMapelList($conn)
{
    $qMapel = mysqli_query($conn, "
        SELECT id_mapel, nama_mapel 
        FROM mapel 
        WHERE status = 'Aktif'
        ORDER BY nama_mapel
    ");
    
    $mapelList = [];
    while ($row = mysqli_fetch_assoc($qMapel)) {
        $mapelList[$row['id_mapel']] = h($row['nama_mapel']);
    }
    
    return $mapelList;
}

/**
 * Cek apakah tabel tertentu ada di database
 * 
 * @param mysqli $conn Koneksi database
 * @param string $tableName Nama tabel
 * @return bool True jika tabel ada, false jika tidak
 */
function tableExists($conn, $tableName)
{
    $result = mysqli_query($conn, "SHOW TABLES LIKE '$tableName'");
    return mysqli_num_rows($result) > 0;
}

/**
 * Build SQL WHERE condition dari array filter
 * 
 * @param array $filters Array filter dengan key:
 *                      - id_kelas
 *                      - id_mapel
 *                      - status_pembayaran
 *                      - tahun
 *                      - bulan
 * @param string $prefix Prefix untuk kolom (default: '')
 * @return string SQL WHERE condition
 */
function buildWhereCondition($filters, $prefix = '')
{
    $conditions = ["1=1"];
    
    if (!empty($prefix) && substr($prefix, -1) !== '.') {
        $prefix .= '.';
    }
    
    if (!empty($filters['id_kelas'])) {
        $conditions[] = $prefix . "id_kelas = '" . mysqli_real_escape_string($GLOBALS['conn'], $filters['id_kelas']) . "'";
    }
    
    if (!empty($filters['id_mapel'])) {
        $conditions[] = $prefix . "id_mapel = '" . mysqli_real_escape_string($GLOBALS['conn'], $filters['id_mapel']) . "'";
    }
    
    if (!empty($filters['status_pembayaran'])) {
        $conditions[] = $prefix . "status = '" . mysqli_real_escape_string($GLOBALS['conn'], $filters['status_pembayaran']) . "'";
    }
    
    if (!empty($filters['tahun'])) {
        if ($prefix === 'p.' || $prefix === '') {
            $conditions[] = "YEAR(" . $prefix . "tgl_bayar) = '" . mysqli_real_escape_string($GLOBALS['conn'], $filters['tahun']) . "'";
        }
    }
    
    if (!empty($filters['bulan'])) {
        if ($prefix === 'p.' || $prefix === '') {
            $conditions[] = "MONTH(" . $prefix . "tgl_bayar) = '" . mysqli_real_escape_string($GLOBALS['conn'], $filters['bulan']) . "'";
        } elseif ($prefix === 'pr.') {
            $conditions[] = "MONTH(" . $prefix . "tanggal) = '" . mysqli_real_escape_string($GLOBALS['conn'], $filters['bulan']) . "'";
            $conditions[] = "YEAR(" . $prefix . "tanggal) = '" . mysqli_real_escape_string($GLOBALS['conn'], $filters['tahun'] ?? date('Y')) . "'";
        } elseif ($prefix === 'j.') {
            $conditions[] = "MONTH(" . $prefix . "tanggal) = '" . mysqli_real_escape_string($GLOBALS['conn'], $filters['bulan']) . "'";
            $conditions[] = "YEAR(" . $prefix . "tanggal) = '" . mysqli_real_escape_string($GLOBALS['conn'], $filters['tahun'] ?? date('Y')) . "'";
        }
    }
    
    return implode(' AND ', $conditions);
}

// =====================================================
// FUNGSI STATISTIK
// =====================================================

/**
 * Hitung statistik keuangan
 * 
 * @param mysqli $conn Koneksi database
 * @param int $tahun Tahun filter
 * @param int $bulan Bulan filter
 * @return array Statistik keuangan
 */
function getStatistikKeuangan($conn, $tahun, $bulan)
{
    $statistik = [
        'total_pendapatan' => 0,
        'transaksi_bulan' => 0,
        'tunggakan_total' => 0,
        'siswa_tunggak' => 0
    ];
    
    // Total Pendapatan Tahun Ini
    $qTotalPendapatan = mysqli_query($conn, "
        SELECT SUM(nominal) as total 
        FROM pembayaran 
        WHERE status = 'Lunas'
        AND YEAR(tgl_bayar) = '$tahun'
    ");
    $statistik['total_pendapatan'] = mysqli_fetch_assoc($qTotalPendapatan)['total'] ?? 0;
    
    // Transaksi Bulan Ini
    $qTransaksiBulan = mysqli_query($conn, "
        SELECT COUNT(*) as total 
        FROM pembayaran 
        WHERE status = 'Lunas'
        AND MONTH(tgl_bayar) = '$bulan'
        AND YEAR(tgl_bayar) = '$tahun'
    ");
    $statistik['transaksi_bulan'] = mysqli_fetch_assoc($qTransaksiBulan)['total'] ?? 0;
    
    // Total Tunggakan
    $qTunggakanTotal = mysqli_query($conn, "
        SELECT SUM(nominal) as total 
        FROM pembayaran 
        WHERE status = 'Belum Lunas'
        AND tgl_bayar <= CURDATE()
    ");
    $statistik['tunggakan_total'] = mysqli_fetch_assoc($qTunggakanTotal)['total'] ?? 0;
    
    // Siswa Menunggak
    $qSiswaTunggak = mysqli_query($conn, "
        SELECT COUNT(DISTINCT id_siswa) as total 
        FROM pembayaran 
        WHERE status = 'Belum Lunas'
        AND tgl_bayar <= CURDATE()
    ");
    $statistik['siswa_tunggak'] = mysqli_fetch_assoc($qSiswaTunggak)['total'] ?? 0;
    
    return $statistik;
}

/**
 * Hitung statistik akademik
 * 
 * @param mysqli $conn Koneksi database
 * @param int $tahun Tahun filter
 * @return array Statistik akademik
 */
function getStatistikAkademik($conn, $tahun)
{
    $statistik = [
        'total_tugas' => 0,
        'rata_nilai' => 0,
        'siswa_tuntas' => 0,
        'persentase_kumpul' => 0
    ];
    
    // Cek tabel tersedia
    if (!tableExists($conn, 'tugas') || !tableExists($conn, 'penilaian_tugas')) {
        return $statistik;
    }
    
    // Total Tugas
    $qTotalTugas = mysqli_query($conn, "
        SELECT COUNT(*) as total 
        FROM tugas 
        WHERE YEAR(created_at) = '$tahun'
    ");
    $statistik['total_tugas'] = mysqli_fetch_assoc($qTotalTugas)['total'] ?? 0;
    
    // Rata-rata Nilai
    $qRataNilai = mysqli_query($conn, "
        SELECT AVG(nilai) as rata_rata 
        FROM penilaian_tugas 
        WHERE nilai IS NOT NULL
    ");
    $statistik['rata_nilai'] = mysqli_fetch_assoc($qRataNilai)['rata_rata'] ?? 0;
    
    // Siswa Tuntas
    $qSiswaTuntas = mysqli_query($conn, "
        SELECT COUNT(DISTINCT id_siswa) as total 
        FROM penilaian_tugas 
        WHERE nilai >= 75
    ");
    $statistik['siswa_tuntas'] = mysqli_fetch_assoc($qSiswaTuntas)['total'] ?? 0;
    
    // Persentase Kumpul
    $qPersentaseKumpul = mysqli_query($conn, "
        SELECT 
            ROUND((COUNT(*) * 100.0 / 
            (SELECT COUNT(*) FROM penilaian_tugas)), 2) as persentase
        FROM penilaian_tugas
        WHERE nilai IS NOT NULL
    ");
    $statistik['persentase_kumpul'] = mysqli_fetch_assoc($qPersentaseKumpul)['persentase'] ?? 0;
    
    return $statistik;
}

/**
 * Hitung statistik kehadiran
 * 
 * @param mysqli $conn Koneksi database
 * @param int $tahun Tahun filter
 * @param int $bulan Bulan filter
 * @return array Statistik kehadiran
 */
function getStatistikKehadiran($conn, $tahun, $bulan)
{
    $statistik = [
        'total_kehadiran' => 0,
        'rata_hadir' => 0,
        'total_alpha' => 0,
        'tutor_hadir' => 0
    ];
    
    // Cek tabel presensi
    if (!tableExists($conn, 'presensi')) {
        return $statistik;
    }
    
    // Total Kehadiran
    $qTotalKehadiran = mysqli_query($conn, "
        SELECT COUNT(*) as total 
        FROM presensi 
        WHERE MONTH(tanggal) = '$bulan'
        AND YEAR(tanggal) = '$tahun'
    ");
    $statistik['total_kehadiran'] = mysqli_fetch_assoc($qTotalKehadiran)['total'] ?? 0;
    
    // Rata-rata Kehadiran
    $qRataHadir = mysqli_query($conn, "
        SELECT ROUND(AVG(
            CASE WHEN status = 'Hadir' THEN 100
                 WHEN status = 'Izin' THEN 50
                 ELSE 0 END
        ), 2) as rata_hadir
        FROM presensi 
        WHERE MONTH(tanggal) = '$bulan'
        AND YEAR(tanggal) = '$tahun'
    ");
    $statistik['rata_hadir'] = mysqli_fetch_assoc($qRataHadir)['rata_hadir'] ?? 0;
    
    // Total Alpha
    $qTotalAlpha = mysqli_query($conn, "
        SELECT COUNT(*) as total 
        FROM presensi 
        WHERE status = 'Alpha'
        AND MONTH(tanggal) = '$bulan'
        AND YEAR(tanggal) = '$tahun'
    ");
    $statistik['total_alpha'] = mysqli_fetch_assoc($qTotalAlpha)['total'] ?? 0;
    
    // Tutor Hadir (jika tabel ada)
    if (tableExists($conn, 'jadwal') && tableExists($conn, 'presensi_tutor')) {
        $qTutorHadir = mysqli_query($conn, "
            SELECT COUNT(DISTINCT id_tutor) as total 
            FROM jadwal j
            JOIN presensi_tutor pt ON j.id_jadwal = pt.id_jadwal
            WHERE MONTH(j.tanggal) = '$bulan'
            AND YEAR(j.tanggal) = '$tahun'
            AND pt.status = 'Hadir'
        ");
        $statistik['tutor_hadir'] = mysqli_fetch_assoc($qTutorHadir)['total'] ?? 0;
    }
    
    return $statistik;
}

/**
 * Hitung statistik kelas
 * 
 * @param mysqli $conn Koneksi database
 * @return array Statistik kelas
 */
function getStatistikKelas($conn)
{
    $statistik = [
        'total_kelas' => 0,
        'kelas_aktif' => 0,
        'kelas_nonaktif' => 0,
        'rata_siswa_per_kelas' => 0
    ];
    
    $qStatistikKelas = mysqli_query($conn, "
        SELECT 
            COUNT(*) as total_kelas,
            COUNT(CASE WHEN status = 'Aktif' THEN 1 END) as kelas_aktif,
            COUNT(CASE WHEN status = 'Nonaktif' THEN 1 END) as kelas_nonaktif,
            AVG(jumlah_siswa) as rata_siswa_per_kelas
        FROM (
            SELECT 
                k.*,
                COUNT(ks.id_siswa) as jumlah_siswa
            FROM kelas k
            LEFT JOIN kelas_siswa ks ON k.id_kelas = ks.id_kelas
            GROUP BY k.id_kelas
        ) as kelas_detail
    ");
    
    $data = mysqli_fetch_assoc($qStatistikKelas);
    if ($data) {
        $statistik = array_merge($statistik, $data);
    }
    
    return $statistik;
}

/**
 * Hitung statistik siswa
 * 
 * @param mysqli $conn Koneksi database
 * @return array Statistik siswa
 */
function getStatistikSiswa($conn)
{
    $statistik = [
        'total_siswa' => 0,
        'siswa_aktif' => 0,
        'siswa_nonaktif' => 0,
        'siswa_alumni' => 0,
        'siswa_baru_bulan_ini' => 0
    ];
    
    $qStatistikSiswa = mysqli_query($conn, "
        SELECT 
            COUNT(*) AS total_siswa,
            COUNT(CASE WHEN status_aktif = 'Aktif' THEN 1 END) AS siswa_aktif,
            COUNT(CASE WHEN status_aktif = 'Nonaktif' THEN 1 END) AS siswa_nonaktif,
            COUNT(CASE WHEN status_aktif = 'Alumni' THEN 1 END) AS siswa_alumni,
            COUNT(
                CASE 
                    WHEN MONTH(tgl_daftar) = MONTH(CURDATE())
                     AND YEAR(tgl_daftar) = YEAR(CURDATE())
                    THEN id_siswa
                END
            ) AS siswa_baru_bulan_ini
        FROM siswa
    ");
    
    $data = mysqli_fetch_assoc($qStatistikSiswa);
    if ($data) {
        $statistik = array_merge($statistik, $data);
    }
    
    return $statistik;
}

/**
 * Hitung statistik tutor
 * 
 * @param mysqli $conn Koneksi database
 * @return array Statistik tutor
 */
function getStatistikTutor($conn)
{
    $statistik = [
        'total_tutor' => 0,
        'tutor_aktif' => 0,
        'tutor_nonaktif' => 0,
        'rata_pengalaman' => 0
    ];
    
    $qStatistikTutor = mysqli_query($conn, "
        SELECT 
            COUNT(*) as total_tutor,
            COUNT(CASE WHEN status = 'Aktif' THEN 1 END) as tutor_aktif,
            COUNT(CASE WHEN status = 'Nonaktif' THEN 1 END) as tutor_nonaktif,
            AVG(pengalaman) as rata_pengalaman
        FROM tutor
    ");
    
    $data = mysqli_fetch_assoc($qStatistikTutor);
    if ($data) {
        $statistik = array_merge($statistik, $data);
    }
    
    return $statistik;
}

// =====================================================
// FUNGSI QUERY DATA
// =====================================================

/**
 * Ambil data pendapatan per bulan
 * 
 * @param mysqli $conn Koneksi database
 * @param int $tahun Tahun filter
 * @return mysqli_result|false Hasil query
 */
function getPendapatanBulanan($conn, $tahun)
{
    return mysqli_query($conn, "
        SELECT 
            DATE_FORMAT(tgl_bayar, '%Y-%m') as periode,
            SUM(nominal) as total_pendapatan,
            COUNT(*) as jumlah_transaksi
        FROM pembayaran 
        WHERE status = 'Lunas'
        AND YEAR(tgl_bayar) = '$tahun'
        GROUP BY DATE_FORMAT(tgl_bayar, '%Y-%m')
        ORDER BY periode DESC
    ");
}

/**
 * Ambil data pembayaran siswa dengan filter
 * 
 * @param mysqli $conn Koneksi database
 * @param array $filters Filter yang akan diterapkan
 * @param int $limit Jumlah data maksimal
 * @return mysqli_result|false Hasil query
 */
function getPembayaranSiswa($conn, $filters, $limit = 50)
{
    $where = buildWhereCondition($filters, 'p');
    
    return mysqli_query($conn, "
        SELECT 
            s.nama,
            k.nama_kelas,
            m.nama_mapel,
            DATE_FORMAT(p.tgl_bayar, '%M %Y') as bulan_tagihan,
            p.nominal,
            p.status,
            DATE_FORMAT(p.tgl_bayar, '%d %b %Y') as tgl_bayar_format
        FROM pembayaran p
        JOIN siswa s ON p.id_siswa = s.id_siswa
        LEFT JOIN kelas k ON p.id_kelas = k.id_kelas
        LEFT JOIN mapel m ON k.id_mapel = m.id_mapel
        WHERE $where
        ORDER BY p.tgl_bayar DESC
        LIMIT $limit
    ");
}

/**
 * Ambil data tunggakan siswa
 * 
 * @param mysqli $conn Koneksi database
 * @param int $limit Jumlah data maksimal
 * @return mysqli_result|false Hasil query
 */
function getTunggakanSiswa($conn, $limit = 20)
{
    return mysqli_query($conn, "
        SELECT 
            s.nama,
            k.nama_kelas,
            m.nama_mapel,
            COUNT(p.id_pembayaran) as jumlah_tunggakan,
            SUM(p.nominal) as total_tunggakan,
            MIN(p.tgl_bayar) as tanggal_tertua
        FROM pembayaran p
        JOIN siswa s ON p.id_siswa = s.id_siswa
        LEFT JOIN kelas k ON p.id_kelas = k.id_kelas
        LEFT JOIN mapel m ON k.id_mapel = m.id_mapel
        WHERE p.status = 'Belum Lunas'
        AND p.tgl_bayar <= CURDATE()
        GROUP BY s.id_siswa, k.id_kelas
        ORDER BY total_tunggakan DESC
        LIMIT $limit
    ");
}

/**
 * Ambil data rekap pemasukan per kelas/mapel
 * 
 * @param mysqli $conn Koneksi database
 * @param int $tahun Tahun filter
 * @return mysqli_result|false Hasil query
 */
function getRekapPemasukanKelas($conn, $tahun)
{
    return mysqli_query($conn, "
        SELECT 
            m.nama_mapel,
            k.nama_kelas,
            COUNT(DISTINCT p.id_siswa) as jumlah_siswa,
            COUNT(p.id_pembayaran) as jumlah_transaksi,
            SUM(CASE WHEN p.status = 'Lunas' THEN p.nominal ELSE 0 END) as total_pemasukan,
            SUM(CASE WHEN p.status = 'Belum Lunas' THEN p.nominal ELSE 0 END) as total_tunggakan
        FROM pembayaran p
        LEFT JOIN kelas k ON p.id_kelas = k.id_kelas
        LEFT JOIN mapel m ON k.id_mapel = m.id_mapel
        WHERE YEAR(p.tgl_bayar) = '$tahun'
        GROUP BY k.id_kelas, m.id_mapel
        ORDER BY total_pemasukan DESC
    ");
}

/**
 * Ambil data siswa dengan tunggakan
 * 
 * @param mysqli $conn Koneksi database
 * @param int $limit Jumlah data maksimal
 * @return mysqli_result|false Hasil query
 */
function getSiswaTunggakan($conn, $limit = 15)
{
    return mysqli_query($conn, "
        SELECT 
            s.nama,
            s.email,
            s.telepon,
            COUNT(p.id_pembayaran) as jumlah_tunggakan,
            SUM(p.nominal) as total_tunggakan,
            GROUP_CONCAT(DISTINCT CONCAT(k.nama_kelas, ' (', m.nama_mapel, ')') SEPARATOR '; ') as kelas_tunggakan
        FROM siswa s
        JOIN pembayaran p ON s.id_siswa = p.id_siswa
        LEFT JOIN kelas k ON p.id_kelas = k.id_kelas
        LEFT JOIN mapel m ON k.id_mapel = m.id_mapel
        WHERE p.status = 'Belum Lunas'
        AND p.tgl_bayar <= CURDATE()
        GROUP BY s.id_siswa
        HAVING total_tunggakan > 0
        ORDER BY total_tunggakan DESC
        LIMIT $limit
    ");
}

/**
 * Ambil data siswa per kelas
 * 
 * @param mysqli $conn Koneksi database
 * @return mysqli_result|false Hasil query
 */
function getSiswaPerKelas($conn)
{
    return mysqli_query($conn, "
        SELECT 
            k.nama_kelas,
            m.nama_mapel,
            COUNT(ks.id_siswa) as jumlah_siswa,
            GROUP_CONCAT(s.nama ORDER BY s.nama SEPARATOR ', ') as daftar_siswa
        FROM kelas k
        JOIN mapel m ON k.id_mapel = m.id_mapel
        LEFT JOIN kelas_siswa ks ON k.id_kelas = ks.id_kelas
        LEFT JOIN siswa s ON ks.id_siswa = s.id_siswa
        WHERE k.status = 'Aktif'
        GROUP BY k.id_kelas
        ORDER BY jumlah_siswa DESC
    ");
}

// =====================================================
// FUNGSI TAMPILAN
// =====================================================

/**
 * Generate badge status dengan warna yang sesuai
 * 
 * @param string $status Status (Lunas, Belum Lunas, Aktif, Nonaktif, dll)
 * @param string $text Teks yang akan ditampilkan (default: status itu sendiri)
 * @return string HTML badge
 */
function generateStatusBadge($status, $text = null)
{
    $text = $text ?? $status;
    $colorClass = '';
    
    switch (strtolower($status)) {
        case 'lunas':
        case 'aktif':
        case 'selesai':
        case 'hadir':
        case 'sukses':
            $colorClass = 'bg-success';
            break;
        case 'belum lunas':
        case 'menunggak':
        case 'proses':
        case 'pending':
        case 'izin':
            $colorClass = 'bg-warning';
            break;
        case 'nonaktif':
        case 'batal':
        case 'alpha':
        case 'gagal':
            $colorClass = 'bg-danger';
            break;
        case 'alumni':
        case 'info':
            $colorClass = 'bg-info';
            break;
        default:
            $colorClass = 'bg-secondary';
    }
    
    return '<span class="badge ' . $colorClass . '">' . h($text) . '</span>';
}

/**
 * Generate progress bar dengan warna yang sesuai
 * 
 * @param float $persentase Persentase (0-100)
 * @param int $height Tinggi progress bar dalam px (default: 8)
 * @return string HTML progress bar
 */
function generateProgressBar($persentase, $height = 8)
{
    $colorClass = '';
    
    if ($persentase >= 80) {
        $colorClass = 'bg-success';
    } elseif ($persentase >= 60) {
        $colorClass = 'bg-warning';
    } else {
        $colorClass = 'bg-danger';
    }
    
    return '
        <div class="progress" style="height: ' . $height . 'px;">
            <div class="progress-bar ' . $colorClass . '" 
                 role="progressbar" 
                 style="width: ' . $persentase . '%" 
                 aria-valuenow="' . $persentase . '" 
                 aria-valuemin="0" 
                 aria-valuemax="100">
            </div>
        </div>
    ';
}

/**
 * Tampilkan pesan "tidak ada data"
 * 
 * @param string $icon Nama icon Bootstrap (tanpa 'bi-')
 * @param string $message Pesan yang akan ditampilkan
 * @return string HTML untuk tampilan "tidak ada data"
 */
function showNoData($icon = 'bar-chart', $message = 'Tidak ada data yang ditemukan')
{
    return '
        <div class="no-data">
            <i class="bi bi-' . h($icon) . ' display-4"></i>
            <p class="mt-3">' . h($message) . '</p>
        </div>
    ';
}

/**
 * Generate filter form untuk laporan
 * 
 * @param array $options Opsi filter:
 *                      - tahun_list: array daftar tahun
 *                      - bulan_list: array daftar bulan
 *                      - kelas_list: array daftar kelas
 *                      - mapel_list: array daftar mapel (opsional)
 *                      - current_filters: array filter saat ini
 *                      - show_mapel: boolean tampilkan filter mapel (default: false)
 *                      - show_status: boolean tampilkan filter status pembayaran (default: false)
 * @return string HTML form filter
 */
function generateFilterForm($options)
{
    $options = array_merge([
        'tahun_list' => [],
        'bulan_list' => [],
        'kelas_list' => [],
        'mapel_list' => [],
        'current_filters' => [],
        'show_mapel' => false,
        'show_status' => false,
        'form_action' => '',
        'include_tab' => false,
        'tab_name' => 'tab',
        'tab_value' => ''
    ], $options);
    
    $current = $options['current_filters'];
    
    ob_start();
    ?>
    <div class="filter-section no-print">
        <h5><i class="bi bi-funnel"></i> Filter Laporan</h5>
        <form method="GET" action="<?= h($options['form_action']) ?>" class="row g-3 mt-2">
            <?php if ($options['include_tab'] && $options['tab_value']): ?>
                <input type="hidden" name="<?= h($options['tab_name']) ?>" value="<?= h($options['tab_value']) ?>">
            <?php endif; ?>
            
            <div class="col-md-3">
                <label class="form-label">Tahun</label>
                <select name="tahun" class="form-select">
                    <?php foreach ($options['tahun_list'] as $thn): ?>
                        <option value="<?= $thn ?>" <?= ($current['tahun'] ?? '') == $thn ? 'selected' : '' ?>>
                            <?= $thn ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-3">
                <label class="form-label">Bulan</label>
                <select name="bulan" class="form-select">
                    <?php foreach ($options['bulan_list'] as $key => $nama): ?>
                        <option value="<?= sprintf('%02d', $key) ?>" <?= ($current['bulan'] ?? '') == sprintf('%02d', $key) ? 'selected' : '' ?>>
                            <?= h($nama) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-3">
                <label class="form-label">Kelas</label>
                <select name="id_kelas" class="form-select">
                    <option value="">Semua Kelas</option>
                    <?php foreach ($options['kelas_list'] as $id => $nama): ?>
                        <option value="<?= $id ?>" <?= ($current['id_kelas'] ?? '') == $id ? 'selected' : '' ?>>
                            <?= h($nama) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <?php if ($options['show_mapel'] && !empty($options['mapel_list'])): ?>
                <div class="col-md-3">
                    <label class="form-label">Mata Pelajaran</label>
                    <select name="id_mapel" class="form-select">
                        <option value="">Semua Mapel</option>
                        <?php foreach ($options['mapel_list'] as $id => $nama): ?>
                            <option value="<?= $id ?>" <?= ($current['id_mapel'] ?? '') == $id ? 'selected' : '' ?>>
                                <?= h($nama) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            
            <?php if ($options['show_status']): ?>
                <div class="col-md-3">
                    <label class="form-label">Status Pembayaran</label>
                    <select name="status_pembayaran" class="form-select">
                        <option value="">Semua Status</option>
                        <option value="Lunas" <?= ($current['status_pembayaran'] ?? '') == 'Lunas' ? 'selected' : '' ?>>Lunas</option>
                        <option value="Belum Lunas" <?= ($current['status_pembayaran'] ?? '') == 'Belum Lunas' ? 'selected' : '' ?>>Belum Lunas</option>
                    </select>
                </div>
            <?php endif; ?>
            
            <div class="col-md-12">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-filter"></i> Terapkan Filter
                </button>
                <a href="<?= h($options['form_action']) ?><?= $options['include_tab'] ? '?' . $options['tab_name'] . '=' . $options['tab_value'] : '' ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-clockwise"></i> Reset
                </a>
            </div>
        </form>
    </div>
    <?php
    return ob_get_clean();
}

// =====================================================
// FUNGSI VALIDASI
// =====================================================

/**
 * Validasi dan sanitize input filter
 * 
 * @param array $input Data input dari $_GET
 * @return array Data yang sudah divalidasi
 */
function validateFilters($input)
{
    $filters = [
        'tahun' => date('Y'),
        'bulan' => date('m'),
        'id_kelas' => '',
        'id_mapel' => '',
        'status_pembayaran' => ''
    ];
    
    // Validasi tahun
    if (!empty($input['tahun']) && is_numeric($input['tahun'])) {
        $tahun = (int)$input['tahun'];
        if ($tahun >= 2020 && $tahun <= date('Y') + 1) {
            $filters['tahun'] = $tahun;
        }
    }
    
    // Validasi bulan
    if (!empty($input['bulan']) && is_numeric($input['bulan'])) {
        $bulan = (int)$input['bulan'];
        if ($bulan >= 1 && $bulan <= 12) {
            $filters['bulan'] = sprintf('%02d', $bulan);
        }
    }
    
    // Validasi id_kelas (harus numeric jika ada)
    if (!empty($input['id_kelas'])) {
        if (is_numeric($input['id_kelas'])) {
            $filters['id_kelas'] = (int)$input['id_kelas'];
        }
    }
    
    // Validasi id_mapel (harus numeric jika ada)
    if (!empty($input['id_mapel'])) {
        if (is_numeric($input['id_mapel'])) {
            $filters['id_mapel'] = (int)$input['id_mapel'];
        }
    }
    
    // Validasi status_pembayaran
    if (!empty($input['status_pembayaran'])) {
        $allowed_status = ['Lunas', 'Belum Lunas'];
        if (in_array($input['status_pembayaran'], $allowed_status)) {
            $filters['status_pembayaran'] = $input['status_pembayaran'];
        }
    }
    
    return $filters;
}

/**