<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'error_log.txt');

if (!isset($_SESSION['login'])) {
    header("Location: auth/login.php");
    exit;
}

include 'config/koneksi.php';
include 'menu/header.php';
include 'menu/sidebar.php';
include 'menu/topbar.php';

// ===================== AMBIL DATA UNTUK DASHBOARD =====================

// 1. Total Asset
$query_total_asset = "SELECT COUNT(*) as total FROM tbl_assets WHERE assets_kode IS NOT NULL AND assets_kode != ''";
$result_total_asset = mysqli_query($conn, $query_total_asset);
$total_asset = mysqli_fetch_assoc($result_total_asset)['total'] ?? 0;

// 2. Total Sparepart
$query_total_sparepart = "SELECT COUNT(*) as total FROM tbl_sparepart";
$result_total_sparepart = mysqli_query($conn, $query_total_sparepart);
$total_sparepart = mysqli_fetch_assoc($result_total_sparepart)['total'] ?? 0;

// 3. Total Nilai Asset (admin/operator)
$query_total_nilai_asset = "SELECT SUM(assets_price * assets_qty) as total FROM tbl_assets WHERE assets_kode IS NOT NULL AND assets_kode != ''";
$result_total_nilai_asset = mysqli_query($conn, $query_total_nilai_asset);
$total_nilai_asset = mysqli_fetch_assoc($result_total_nilai_asset)['total'] ?? 0;

// Fungsi untuk format angka singkat
function formatRupiahSingkat($angka) {
    if ($angka === null || $angka === 0) return 'Rp 0';
    
    $nilai = (float)$angka;
    
    // Miliar (M)
    if ($nilai >= 1000000000) {
        $milyar = $nilai / 1000000000;
        if (floor($milyar) == $milyar) {
            return 'Rp ' . number_format($milyar, 0, ',', '.') . ' M';
        } else {
            return 'Rp ' . number_format($milyar, 1, ',', '.') . ' M';
        }
    }
    // Juta (JT)
    elseif ($nilai >= 1000000) {
        $juta = $nilai / 1000000;
        if (floor($juta) == $juta) {
            return 'Rp ' . number_format($juta, 0, ',', '.') . ' JT';
        } else {
            return 'Rp ' . number_format($juta, 1, ',', '.') . ' JT';
        }
    }
    // Ribu (RB)
    elseif ($nilai >= 1000) {
        $ribu = $nilai / 1000;
        return 'Rp ' . number_format($ribu, 0, ',', '.') . ' RB';
    }
    // Kurang dari 1000
    else {
        return 'Rp ' . number_format($nilai, 0, ',', '.');
    }
}

// Query total nilai asset
$query_total_nilai_asset = "SELECT SUM(assets_price * assets_qty) as total FROM tbl_assets WHERE assets_kode IS NOT NULL AND assets_kode != ''";
$result_total_nilai_asset = mysqli_query($conn, $query_total_nilai_asset);
$total_nilai_asset = mysqli_fetch_assoc($result_total_nilai_asset)['total'] ?? 0;

// Format angka menjadi singkat
$total_nilai_asset_format = formatRupiahSingkat($total_nilai_asset);


// 4. Total Nilai Sparepart (admin/operator)
$query_total_nilai_sparepart = "SELECT SUM(sparepart_price * sparepart_qty) as total FROM tbl_sparepart";
$result_total_nilai_sparepart = mysqli_query($conn, $query_total_nilai_sparepart);
$total_nilai_sparepart = mysqli_fetch_assoc($result_total_nilai_sparepart)['total'] ?? 0;

// 3. Total Lokasi (user)
$query_total_lokasi = "SELECT COUNT(*) as total FROM tbl_lokasi";
$result_total_lokasi = mysqli_query($conn, $query_total_lokasi);
$total_lokasi = mysqli_fetch_assoc($result_total_lokasi)['total'] ?? 0;

// 4. Total Supplier (user)
$query_total_supplier = "SELECT COUNT(*) as total FROM tbl_supplier";
$result_total_supplier = mysqli_query($conn, $query_total_supplier);
$total_supplier = mysqli_fetch_assoc($result_total_supplier)['total'] ?? 0;

// 5. Total Handover Asset
$query_total_handover = "SELECT COUNT(*) as total FROM tbl_primary WHERE return_status IS NULL OR return_status != 2";
$result_total_handover = mysqli_query($conn, $query_total_handover);
$total_handover = mysqli_fetch_assoc($result_total_handover)['total'] ?? 0;

// 6. Total Return Asset
$query_total_return = "SELECT COUNT(*) as total FROM tbl_primary WHERE return_status = 2";
$result_total_return = mysqli_query($conn, $query_total_return);
$total_return = mysqli_fetch_assoc($result_total_return)['total'] ?? 0;

// 7. Total Departemen
$query_total_dep = "SELECT COUNT(DISTINCT dep_code) as total FROM tbl_dep";
$result_total_dep = mysqli_query($conn, $query_total_dep);
$total_dep = mysqli_fetch_assoc($result_total_dep)['total'] ?? 0;

// 8. Total User
$query_total_user = "SELECT COUNT(*) as total FROM tbl_karyawan";
$result_total_user = mysqli_query($conn, $query_total_user);
$total_user = mysqli_fetch_assoc($result_total_user)['total'] ?? 0;

// 9. Asset per Kategori (Pie Chart)
$query_asset_per_kategori = "
    SELECT k.kategori_name, COUNT(a.assets_id) as total 
    FROM tbl_assets a 
    LEFT JOIN tbl_kategori k ON a.kategori_id = k.kategori_id 
    WHERE a.assets_kode IS NOT NULL AND a.assets_kode != '' 
    GROUP BY k.kategori_name
    ORDER BY total DESC
";
$result_asset_per_kategori = mysqli_query($conn, $query_asset_per_kategori);
$kategori_labels = [];
$kategori_data = [];
while ($row = mysqli_fetch_assoc($result_asset_per_kategori)) {
    $kategori_labels[] = $row['kategori_name'] ?? 'Tidak Terkategori';
    $kategori_data[] = (int)$row['total'];
}

// 10. Asset per Departemen (Bar Chart)
$query_asset_per_departemen = "
SELECT 
    d.dep_code, 
    COUNT(DISTINCT a.assets_id) as total 
FROM tbl_assets a 
INNER JOIN tbl_primary p ON a.assets_id = p.assets_id
INNER JOIN tbl_karyawan u ON p.karyawan_id = u.karyawan_id
INNER JOIN tbl_dep d ON u.dep_id = d.dep_id
WHERE a.assets_kode IS NOT NULL AND a.assets_kode != ''
GROUP BY d.dep_code
ORDER BY total DESC
";
$result_asset_per_departemen = mysqli_query($conn, $query_asset_per_departemen);
$departemen_labels = [];
$departemen_data = [];
while ($row = mysqli_fetch_assoc($result_asset_per_departemen)) {
    $departemen_labels[] = $row['dep_code'] ?? 'Tidak Diketahui';
    $departemen_data[] = (int)$row['total'];
}

// 11. Asset per Kondisi (Bar Chart)
$query_asset_per_kondisi = "
SELECT 
    k.kondisi_name, 
    COUNT(DISTINCT p.assets_id) as total 
FROM tbl_primary p 
LEFT JOIN tbl_kondisi k ON p.kondisi_id = k.kondisi_id 
GROUP BY p.kondisi_id 
ORDER BY total DESC
";
$result_asset_per_kondisi = mysqli_query($conn, $query_asset_per_kondisi);
$kondisi_labels = [];
$kondisi_data = [];
while ($row = mysqli_fetch_assoc($result_asset_per_kondisi)) {
    $kondisi_labels[] = $row['kondisi_name'] ?? 'Tidak Diketahui';
    $kondisi_data[] = (int)$row['total'];
}

// 12. Total Harga Asset per Bulan (Line Chart)
$query_harga_asset_per_bulan = "
    SELECT 
        DATE_FORMAT(assets_date, '%Y-%m') as bulan,
        DATE_FORMAT(assets_date, '%M %Y') as bulan_nama,
        CAST(ROUND(SUM(assets_price * assets_qty), 0) AS UNSIGNED) as total
    FROM tbl_assets
    WHERE assets_kode IS NOT NULL AND assets_kode != ''
        AND assets_date >= DATE_SUB(NOW(), INTERVAL 24 MONTH)
    GROUP BY DATE_FORMAT(assets_date, '%Y-%m')
    ORDER BY bulan ASC
";
$result_harga_asset_per_bulan = mysqli_query($conn, $query_harga_asset_per_bulan);
$asset_bulan_labels = [];
$asset_bulan_data = [];
while ($row = mysqli_fetch_assoc($result_harga_asset_per_bulan)) {
    $asset_bulan_labels[] = $row['bulan_nama'];
    $asset_bulan_data[] = (int)$row['total'];
}

// 13. Total Harga Sparepart per Bulan (Line Chart)
$query_harga_sparepart_per_bulan = "
    SELECT 
        DATE_FORMAT(sparepart_date, '%Y-%m') as bulan,
        DATE_FORMAT(sparepart_date, '%M %Y') as bulan_nama,
        CAST(ROUND(SUM(sparepart_price * sparepart_qty), 0) AS UNSIGNED) as total
    FROM tbl_sparepart
    WHERE sparepart_date >= DATE_SUB(NOW(), INTERVAL 24 MONTH)
    GROUP BY DATE_FORMAT(sparepart_date, '%Y-%m')
    ORDER BY bulan ASC
";
$result_harga_sparepart_per_bulan = mysqli_query($conn, $query_harga_sparepart_per_bulan);
$sparepart_bulan_labels = [];
$sparepart_bulan_data = [];
while ($row = mysqli_fetch_assoc($result_harga_sparepart_per_bulan)) {
    $sparepart_bulan_labels[] = $row['bulan_nama'];
    $sparepart_bulan_data[] = (int)$row['total'];
}
?>

<style>
    .dashboard-card {
        transition: transform 0.3s ease;
        cursor: pointer;
    }
    .dashboard-card:hover {
        transform: translateY(-5px);
    }
    .stat-number {
        font-size: 28px;
        font-weight: 700;
    }
    .stat-label {
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    /* Container untuk Pie Chart - full width tanpa scroll (pie chart tidak perlu scroll) */
    .chart-container-pie {
        position: relative;
        width: 100%;
        height: 350px;
    }
    .chart-container-pie canvas {
        width: 100% !important;
        height: 100% !important;
    }
    
    /* Container untuk Bar Chart - dengan scroll horizontal jika data banyak */
    .chart-container-bar {
        position: relative;
        width: 100%;
        overflow-x: auto;
        overflow-y: hidden;
    }
    .chart-container-bar canvas {
        min-width: 100%;
        height: 350px !important;
    }
    
    /* Container untuk Line Chart - dengan scroll horizontal jika data banyak */
    .chart-container-line {
        position: relative;
        width: 100%;
        overflow-x: auto;
        overflow-y: hidden;
    }
    .chart-container-line canvas {
        min-width: 100%;
        height: 350px !important;
    }
    
    /* Jika data lebih dari batas tertentu, tambahkan lebar minimum agar bisa di-scroll */
    .chart-container-bar.scrollable canvas,
    .chart-container-line.scrollable canvas {
        min-width: 800px;
    }
    
    .card-header-custom {
        background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
        color: white;
    }
    .card-header-custom h6 {
        color: white;
    }
    
    /* Custom scrollbar */
    .chart-container-bar::-webkit-scrollbar,
    .chart-container-line::-webkit-scrollbar {
        height: 8px;
    }
    .chart-container-bar::-webkit-scrollbar-track,
    .chart-container-line::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }
    .chart-container-bar::-webkit-scrollbar-thumb,
    .chart-container-line::-webkit-scrollbar-thumb {
        background: #4e73df;
        border-radius: 10px;
    }
    .chart-container-bar::-webkit-scrollbar-thumb:hover,
    .chart-container-line::-webkit-scrollbar-thumb:hover {
        background: #224abe;
    }
    
    /* Info scroll */
    .scroll-info {
        font-size: 11px;
        color: #858796;
        margin-top: 8px;
        text-align: center;
    }
    .scroll-info i {
        margin-right: 5px;
    }
    
    /* Responsif untuk mobile */
    @media (max-width: 768px) {
        .chart-container-pie,
        .chart-container-bar,
        .chart-container-line {
            height: 280px;
        }
        .chart-container-bar.scrollable canvas,
        .chart-container-line.scrollable canvas {
            min-width: 600px;
        }
    }
</style>

<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-tachometer-alt"></i> Dashboard Cosmar Assets
        </h1>
        <a href="#" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm" onclick="window.print()">
            <i class="fas fa-print fa-sm text-white-50"></i> Cetak Dashboard
        </a>
    </div>

    <!-- Content Row - Cards (Row 1) -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2 dashboard-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="stat-label font-weight-bold text-primary mb-1">
                                <i class="fas fa-boxes"></i> TOTAL ASSET
                            </div>
                            <div class="stat-number mb-0 font-weight-bold text-gray-800"><?= number_format($total_asset) ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-building fa-3x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2 dashboard-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="stat-label font-weight-bold text-success mb-1">
                                <i class="fas fa-microchip"></i> TOTAL SPAREPART
                            </div>
                            <div class="stat-number mb-0 font-weight-bold text-gray-800"><?= number_format($total_sparepart) ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-microchip fa-3x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (isset($_SESSION['user_level']) && in_array($_SESSION['user_level'], [1, 2])) : ?>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2 dashboard-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="stat-label font-weight-bold text-info mb-1">
                                <i class="fas fa-chart-line"></i> NILAI ASSET
                            </div>
                            <div class="stat-number mb-0 font-weight-bold text-gray-800">
                                <?= $total_nilai_asset_format ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-line fa-3x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2 dashboard-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="stat-label font-weight-bold text-warning mb-1">
                                <i class="fas fa-chart-line"></i> NILAI SPAREPART
                            </div>
                            <div class="stat-number mb-0 font-weight-bold text-gray-800">
                                Rp <?= number_format($total_nilai_sparepart, 0, ',', '.') ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-line fa-3x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>  

        <?php if (isset($_SESSION['user_level']) && in_array($_SESSION['user_level'], [3])) : ?>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2 dashboard-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="stat-label font-weight-bold text-info mb-1">
                                <i class="fas fa-map-marker"></i> TOTAL LOKASI
                            </div>
                            <div class="stat-number mb-0 font-weight-bold text-gray-800"><?= number_format($total_lokasi) ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-map-marker fa-3x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2 dashboard-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="stat-label font-weight-bold text-warning mb-1">
                                <i class="fas fa-industry"></i> TOTAL SUPPLIER
                            </div>
                            <div class="stat-number mb-0 font-weight-bold text-gray-800"><?= number_format($total_supplier) ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-industry fa-3x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>  

    <!-- Content Row - Cards (Row 2) -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2 dashboard-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="stat-label font-weight-bold text-info mb-1">
                                <i class="fas fa-handshake"></i> HANDOVER ASSET
                            </div>
                            <div class="stat-number mb-0 font-weight-bold text-gray-800"><?= number_format($total_handover) ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-handshake fa-3x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2 dashboard-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="stat-label font-weight-bold text-success mb-1">
                                <i class="fas fa-undo-alt"></i> RETURN ASSET
                            </div>
                            <div class="stat-number mb-0 font-weight-bold text-gray-800"><?= number_format($total_return) ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-undo-alt fa-3x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2 dashboard-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="stat-label font-weight-bold text-primary mb-1">
                                <i class="fas fa-building"></i> TOTAL DEPARTEMEN
                            </div>
                            <div class="stat-number mb-0 font-weight-bold text-gray-800"><?= number_format($total_dep) ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-building fa-3x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2 dashboard-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="stat-label font-weight-bold text-danger mb-1">
                                <i class="fas fa-users"></i> TOTAL KARYAWAN
                            </div>
                            <div class="stat-number mb-0 font-weight-bold text-gray-800"><?= number_format($total_user) ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-3x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Content Row - Charts (Row 3: Pie Chart & Bar Chart 1) -->
    <div class="row">
        <!-- Pie Chart: Total Asset per Kategori -->
        <div class="col-xl-6 col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 card-header-custom">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-chart-pie"></i> Total Asset per Kategori
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($kategori_data)): ?>
                    <div class="chart-container-pie">
                        <canvas id="assetKategoriChart"></canvas>
                    </div>
                    <?php else: ?>
                    <div class="text-center text-muted py-5">Belum ada data asset</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Bar Chart: Total Asset per Departemen -->
        <div class="col-xl-6 col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 card-header-custom">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-chart-bar"></i> Total Asset per Departemen
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($departemen_data)): ?>
                    <?php $scrollable = count($departemen_data) > 6; ?>
                    <div class="chart-container-bar <?= $scrollable ? 'scrollable' : '' ?>">
                        <canvas id="assetDepartemenChart"></canvas>
                    </div>
                    <?php if ($scrollable): ?>
                    <div class="scroll-info">
                        <i class="fas fa-arrows-alt-h"></i> Geser ke kanan/kiri untuk melihat semua departemen
                    </div>
                    <?php endif; ?>
                    <?php else: ?>
                    <div class="text-center text-muted py-5">Belum ada data asset yang digunakan oleh departemen</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Content Row - Charts (Row 4: Bar Chart 2) -->
    <div class="row">
        <div class="col-xl-12 col-lg-12 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 card-header-custom">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-chart-bar"></i> Total Asset per Kondisi
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($kondisi_data)): ?>
                    <?php $scrollable = count($kondisi_data) > 6; ?>
                    <div class="chart-container-bar <?= $scrollable ? 'scrollable' : '' ?>">
                        <canvas id="assetKondisiChart"></canvas>
                    </div>
                    <?php if ($scrollable): ?>
                    <div class="scroll-info">
                        <i class="fas fa-arrows-alt-h"></i> Geser ke kanan/kiri untuk melihat semua kondisi asset
                    </div>
                    <?php endif; ?>
                    <?php else: ?>
                    <div class="text-center text-muted py-5">Belum ada data kondisi asset</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Content Row - Charts (Row 5: Line Charts) -->
    <div class="row">
        <!-- Line Chart: Total Harga Asset per Bulan -->
        <div class="col-xl-6 col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 card-header-custom">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-chart-line"></i> Total Harga Asset per Bulan
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($asset_bulan_data)): ?>
                    <?php $scrollable = count($asset_bulan_data) > 6; ?>
                    <div class="chart-container-line <?= $scrollable ? 'scrollable' : '' ?>">
                        <canvas id="assetLineChart"></canvas>
                    </div>
                    <?php if ($scrollable): ?>
                    <div class="scroll-info">
                        <i class="fas fa-arrows-alt-h"></i> Geser ke kanan/kiri untuk melihat semua bulan
                    </div>
                    <?php endif; ?>
                    <?php else: ?>
                    <div class="text-center text-muted py-5">Belum ada data asset</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Line Chart: Total Harga Sparepart per Bulan -->
        <div class="col-xl-6 col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 card-header-custom">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-chart-line"></i> Total Harga Sparepart per Bulan
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($sparepart_bulan_data)): ?>
                    <?php $scrollable = count($sparepart_bulan_data) > 6; ?>
                    <div class="chart-container-line <?= $scrollable ? 'scrollable' : '' ?>">
                        <canvas id="sparepartLineChart"></canvas>
                    </div>
                    <?php if ($scrollable): ?>
                    <div class="scroll-info">
                        <i class="fas fa-arrows-alt-h"></i> Geser ke kanan/kiri untuk melihat semua bulan
                    </div>
                    <?php endif; ?>
                    <?php else: ?>
                    <div class="text-center text-muted py-5">Belum ada data sparepart</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</div>
<!-- /.container-fluid -->

<?php include 'menu/footer.php'; ?>

<!-- Script untuk mencegah error addEventListener -->
<script>
(function() {
    var elements = ['primary_image', 'uploadArea', 'previewImage', 'removeImage', 'uploadContent'];
    elements.forEach(function(id) {
        if (!document.getElementById(id)) {
            var dummy = document.createElement('div');
            dummy.id = id;
            dummy.style.display = 'none';
            document.body.appendChild(dummy);
        }
    });
})();

// Tunggu hingga DOM sepenuhnya dimuat
document.addEventListener('DOMContentLoaded', function() {
    
    // ===================== FORMAT ANGKA =====================
    function formatRupiahSingkat(angka) {
        if (angka === null || angka === undefined || isNaN(angka)) return 'Rp 0';
        let nilai = Math.round(Number(angka));
        if (nilai === 0) return 'Rp 0';
        
        if (nilai >= 1000000000) {
            let milyar = nilai / 1000000000;
            return 'Rp ' + (Math.floor(milyar) === milyar ? milyar.toFixed(0) : milyar.toFixed(1).replace(/\.0$/, '')) + ' M';
        }
        else if (nilai >= 1000000) {
            let juta = nilai / 1000000;
            return 'Rp ' + (Math.floor(juta) === juta ? juta.toFixed(0) : juta.toFixed(1).replace(/\.0$/, '')) + ' JT';
        }
        else if (nilai >= 1000) {
            return 'Rp ' + (nilai / 1000).toFixed(0) + ' RB';
        }
        else {
            return 'Rp ' + nilai.toLocaleString('id-ID');
        }
    }
    
    function formatRupiahLengkap(angka) {
        if (angka === null || angka === undefined || isNaN(angka)) return 'Rp 0';
        return 'Rp ' + Math.round(Number(angka)).toLocaleString('id-ID');
    }
    
    // ===================== DATA =====================
    var assetData = <?= json_encode($asset_bulan_data) ?>;
    var assetLabels = <?= json_encode($asset_bulan_labels) ?>;
    var sparepartData = <?= json_encode($sparepart_bulan_data) ?>;
    var sparepartLabels = <?= json_encode($sparepart_bulan_labels) ?>;
    
    // ===================== 1. PIE CHART =====================
    var ctxKategori = document.getElementById('assetKategoriChart');
    if (ctxKategori && <?= !empty($kategori_data) ? 'true' : 'false' ?>) {
        new Chart(ctxKategori, {
            type: 'pie',
            data: {
                labels: <?= json_encode($kategori_labels) ?>,
                datasets: [{
                    data: <?= json_encode($kategori_data) ?>,
                    backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796', '#5a5c69', '#6f42c1', '#fd7e14', '#20c997'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: { position: 'bottom', labels: { fontSize: 11 } },
                tooltips: { callbacks: { label: function(tooltipItem, data) { 
                    return data.labels[tooltipItem.index] + ': ' + data.datasets[0].data[tooltipItem.index] + ' asset';
                } } }
            }
        });
    }

    // ===================== 2. BAR CHART: ASSET PER DEPARTEMEN =====================
    var ctxDepartemen = document.getElementById('assetDepartemenChart');
    if (ctxDepartemen && <?= !empty($departemen_data) ? 'true' : 'false' ?>) {
        new Chart(ctxDepartemen, {
            type: 'bar',
            data: {
                labels: <?= json_encode($departemen_labels) ?>,
                datasets: [{
                    label: 'Jumlah Asset',
                    data: <?= json_encode($departemen_data) ?>,
                    backgroundColor: 'rgba(54, 185, 204, 0.8)',
                    borderColor: 'rgba(54, 185, 204, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    yAxes: [{ 
                        ticks: { beginAtZero: true, stepSize: 1, precision: 0 }, 
                        scaleLabel: { display: true, labelString: 'Jumlah Asset' } 
                    }],
                    xAxes: [{ 
                        ticks: { 
                            autoSkip: true, 
                            maxRotation: 0,
                            minRotation: 0,
                            autoSkipPadding: 10
                        }, 
                        scaleLabel: { display: true, labelString: 'Departemen' } 
                    }]
                },
                legend: { position: 'top' },
                tooltips: { callbacks: { label: function(tooltipItem) { return 'Jumlah Asset: ' + tooltipItem.yLabel + ' unit'; } } }
            }
        });
    }

    // ===================== 3. BAR CHART: ASSET PER KONDISI =====================
    var ctxKondisi = document.getElementById('assetKondisiChart');
    if (ctxKondisi && <?= !empty($kondisi_data) ? 'true' : 'false' ?>) {
        new Chart(ctxKondisi, {
            type: 'bar',
            data: {
                labels: <?= json_encode($kondisi_labels) ?>,
                datasets: [{
                    label: 'Jumlah Asset',
                    data: <?= json_encode($kondisi_data) ?>,
                    backgroundColor: 'rgba(246, 194, 62, 0.8)',
                    borderColor: 'rgba(246, 194, 62, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    yAxes: [{ 
                        ticks: { beginAtZero: true, stepSize: 1, precision: 0 }, 
                        scaleLabel: { display: true, labelString: 'Jumlah Asset' } 
                    }],
                    xAxes: [{ 
                        ticks: { 
                            autoSkip: true, 
                            maxRotation: 0,
                            minRotation: 0,
                            autoSkipPadding: 10
                        }, 
                        scaleLabel: { display: true, labelString: 'Kondisi Asset' } 
                    }]
                },
                legend: { position: 'top' },
                tooltips: { callbacks: { label: function(tooltipItem) { return 'Jumlah Asset: ' + tooltipItem.yLabel + ' unit'; } } }
            }
        });
    }

    // ===================== 4. LINE CHART: HARGA ASSET =====================
    var ctxAssetLine = document.getElementById('assetLineChart');
    if (ctxAssetLine && assetData.length > 0) {
        new Chart(ctxAssetLine, {
            type: 'line',
            data: {
                labels: assetLabels,
                datasets: [{
                    label: 'Total Harga Asset',
                    data: assetData,
                    backgroundColor: 'rgba(78, 115, 223, 0.2)',
                    borderColor: 'rgba(78, 115, 223, 1)',
                    borderWidth: 2,
                    pointBackgroundColor: 'rgba(78, 115, 223, 1)',
                    pointBorderColor: '#fff',
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    yAxes: [{ 
                        ticks: { 
                            beginAtZero: true,
                            callback: function(value) { return formatRupiahSingkat(value); }
                        },
                        scaleLabel: { display: true, labelString: 'Total Harga' }
                    }],
                    xAxes: [{ 
                        ticks: { 
                            autoSkip: true, 
                            maxRotation: 0,
                            minRotation: 0,
                            autoSkipPadding: 15
                        }, 
                        scaleLabel: { display: true, labelString: 'Bulan' } 
                    }]
                },
                legend: { position: 'top' },
                tooltips: { 
                    callbacks: { 
                        label: function(tooltipItem) { 
                            return 'Total Harga: ' + formatRupiahLengkap(tooltipItem.yLabel);
                        }
                    }
                }
            }
        });
    }

    // ===================== 5. LINE CHART: HARGA SPAREPART =====================
    var ctxSparepartLine = document.getElementById('sparepartLineChart');
    if (ctxSparepartLine && sparepartData.length > 0) {
        new Chart(ctxSparepartLine, {
            type: 'line',
            data: {
                labels: sparepartLabels,
                datasets: [{
                    label: 'Total Harga Sparepart',
                    data: sparepartData,
                    backgroundColor: 'rgba(28, 200, 138, 0.2)',
                    borderColor: 'rgba(28, 200, 138, 1)',
                    borderWidth: 2,
                    pointBackgroundColor: 'rgba(28, 200, 138, 1)',
                    pointBorderColor: '#fff',
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    yAxes: [{ 
                        ticks: { 
                            beginAtZero: true,
                            callback: function(value) { return formatRupiahSingkat(value); }
                        },
                        scaleLabel: { display: true, labelString: 'Total Harga' }
                    }],
                    xAxes: [{ 
                        ticks: { 
                            autoSkip: true, 
                            maxRotation: 0,
                            minRotation: 0,
                            autoSkipPadding: 15
                        }, 
                        scaleLabel: { display: true, labelString: 'Bulan' } 
                    }]
                },
                legend: { position: 'top' },
                tooltips: { 
                    callbacks: { 
                        label: function(tooltipItem) { 
                            return 'Total Harga: ' + formatRupiahLengkap(tooltipItem.yLabel);
                        }
                    }
                }
            }
        });
    }

});
</script>

</body>
</html>