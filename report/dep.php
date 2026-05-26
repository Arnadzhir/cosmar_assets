<?php
include '../auth/auth.php';
allowRole([1,2,3]);

include '../config/koneksi.php';

/* =====================
   AMBIL DATA USER LOGIN
===================== */
$user_id    = $_SESSION['user_id'];
$user_level = $_SESSION['user_level'];
$dep_id     = $_SESSION['dep_id'] ?? 0;

include '../menu/header.php';
include '../menu/sidebar.php';
include '../menu/topbar.php';
?>

<!-- SweetAlert2 CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<!-- SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<style>
    .filter-card {
        margin-bottom: 20px;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    }
    .filter-card .card-header {
        background-color: #f8f9fc;
        border-bottom: 1px solid #e3e6f0;
        padding: 15px 20px;
    }
    .filter-card .card-header h6 {
        margin: 0;
        color: #4e73df;
        font-weight: 700;
    }
    .filter-card .card-body {
        padding: 20px;
    }
    .btn-search {
        background-color: #4e73df;
        border-color: #4e73df;
        color: white;
        transition: all 0.3s;
    }
    .btn-search:hover {
        background-color: #2e59d9;
        border-color: #2e59d9;
        transform: translateY(-2px);
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        color: white;
    }
    .btn-reset {
        background-color: #858796;
        border-color: #858796;
        color: white;
        transition: all 0.3s;
    }
    .btn-reset:hover {
        background-color: #6c6e7c;
        border-color: #6c6e7c;
        transform: translateY(-2px);
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        color: white;
    }
    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    .table {
        width: 100%;
        margin-bottom: 1rem;
        color: #858796;
    }
    .table th {
        white-space: nowrap;
        background-color: #f8f9fc;
        vertical-align: middle;
        font-size: 12px;
        padding: 10px;
    }
    .table td {
        vertical-align: middle;
        padding: 10px;
        font-size: 12px;
    }
    .badge-kondisi {
        display: inline-block;
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        text-align: center;
        min-width: 100px;
    }
    .badge-bagus {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    .badge-rusak {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    .badge-normal {
        background-color: #fff3cd;
        color: #856404;
        border: 1px solid #ffeeba;
    }
    .badge-sedang {
        background-color: #d1ecf1;
        color: #0c5460;
        border: 1px solid #bee5eb;
    }
    .asset-code-link {
        color: #4e73df;
        text-decoration: underline;
        transition: all 0.2s;
    }
    .asset-code-link:hover {
        color: #224abe;
        text-decoration: underline;
        cursor: pointer;
    }
    .asset-code-link strong {
        font-weight: 600;
    }
    .empty-state {
        text-align: center;
        padding: 50px 20px;
        color: #858796;
    }
    .empty-state i {
        font-size: 48px;
        margin-bottom: 15px;
        color: #dddfeb;
    }
    .empty-state h5 {
        color: #5a5c69;
        margin-bottom: 10px;
    }
    .info-header {
        background-color: #e8f0fe;
        border-left: 4px solid #4e73df;
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 5px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .info-header .total-assets {
        font-weight: bold;
        color: #4e73df;
        font-size: 18px;
    }
    .info-header .total-assets i {
        margin-right: 8px;
        font-size: 20px;
    }
    .info-header .filter-info {
        color: #6c757d;
        font-size: 14px;
    }
    .select2-container--default .select2-selection--single {
        height: 38px;
        border: 1px solid #d1d3e2;
        border-radius: 0.35rem;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 36px;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px;
    }
    .dep-group {
        background-color: #f8f9fc;
    }
    .dep-group td {
        background-color: #f8f9fc;
        border-bottom: 2px solid #4e73df;
        font-weight: bold;
    }
    .total-row {
        background-color: #f2f2f2;
    }
    .total-row td {
        background-color: #f2f2f2;
        font-weight: bold;
    }
</style>

<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-building"></i> Laporan Departemen Asset
        </h1>
    </div>

    <!-- CARD 1: FILTER - Hanya untuk Admin dan Operator (Level 1 & 2) -->
    <?php if (in_array($user_level, [1, 2])): ?>
    <div class="card shadow mb-4 filter-card">
        <div class="card-header">
            <h6><i class="fas fa-filter"></i> Filter Data</h6>
        </div>
        <div class="card-body">
            <form id="filterForm" method="GET" action="">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Departemen</label>
                            <select name="dep_code" id="dep_code" class="form-control select2">
                                <option value="">-- Pilih Departemen --</option>
                                <?php
                                $qDep = mysqli_query($conn, "
                                    SELECT MIN(dep_id) as dep_id, dep_code, MIN(dep_name) as dep_name
                                    FROM tbl_dep 
                                    GROUP BY dep_code 
                                    ORDER BY dep_code
                                ");
                                
                                while ($dep = mysqli_fetch_assoc($qDep)) {
                                    echo "<option value='{$dep['dep_code']}'>{$dep['dep_code']} - {$dep['dep_name']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <div class="d-flex">
                                <button type="submit" class="btn btn-search mr-2">
                                    <i class="fas fa-search"></i> Tampilkan
                                </button>
                                <a href="dep.php" class="btn btn-reset">
                                    <i class="fas fa-undo"></i> Reset
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- CARD 2: DAFTAR ASSETS PER DEPARTEMEN -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-list"></i> Daftar Asset
            </h6>
        </div>
        <div class="card-body">
            <?php
            // Tentukan apakah akan menampilkan data
            $show_data = false;
            
            if (in_array($user_level, [1, 2])) {
                // Admin/Operator: tampilkan data hanya jika ada dep_code yang dipilih
                if (isset($_GET['dep_code']) && !empty($_GET['dep_code'])) {
                    $show_data = true;
                }
            } else {
                // User biasa: langsung tampilkan data (tanpa filter)
                $show_data = true;
            }
            
            if ($show_data) {
                
                // PERBAIKAN: Bangun query menggunakan tbl_karyawan
                $query = "
                    SELECT 
                        p.primary_id,
                        p.primary_qty,
                        p.primary_image,
                        p.timestamp as primary_timestamp,
                        
                        a.assets_id,
                        a.assets_kode,
                        a.assets_name,
                        a.assets_model,
                        a.assets_spec,
                        a.assets_target,
                        a.assets_cap,
                        a.assets_uom,
                        a.assets_price,
                        a.assets_date,
                        a.assets_life,
                        
                        kond.kondisi_id,
                        kond.kondisi_name,
                        
                        kat.kategori_name,
                        kat.kategori_line,
                        
                        t.type_name,
                        m.merk_name,
                        
                        l.lokasi_name,
                        l.lokasi_lantai,
                        
                        kar.karyawan_id,
                        kar.karyawan_name,
                        d.dep_id,
                        d.dep_code,
                        d.dep_name
                        
                    FROM tbl_primary p
                    INNER JOIN tbl_assets a ON p.assets_id = a.assets_id
                    INNER JOIN tbl_karyawan kar ON p.karyawan_id = kar.karyawan_id
                    INNER JOIN tbl_dep d ON kar.dep_id = d.dep_id
                    LEFT JOIN tbl_kondisi kond ON p.kondisi_id = kond.kondisi_id
                    LEFT JOIN tbl_kategori kat ON a.kategori_id = kat.kategori_id
                    LEFT JOIN tbl_type t ON a.type_id = t.type_id
                    LEFT JOIN tbl_merk m ON a.merk_id = m.merk_id
                    LEFT JOIN tbl_lokasi l ON p.lokasi_id = l.lokasi_id
                    WHERE 1=1
                ";
                
                // Tambahkan filter departemen jika ada
                $filter_nama = '';
                if (isset($_GET['dep_code']) && !empty($_GET['dep_code'])) {
                    $filter_dep = mysqli_real_escape_string($conn, $_GET['dep_code']);
                    $query .= " AND d.dep_code = '$filter_dep'";
                    
                    // Ambil nama departemen untuk ditampilkan
                    $qNamaDep = mysqli_query($conn, "SELECT MIN(dep_name) as dep_name FROM tbl_dep WHERE dep_code = '$filter_dep'");
                    $namaDep = mysqli_fetch_assoc($qNamaDep);
                    $filter_nama = $filter_dep . ' - ' . ($namaDep['dep_name'] ?? '');
                }
                
                // Filter berdasarkan user level
                if ($user_level == 3) {
                    // User biasa hanya melihat asset dari departemennya
                    $query .= " AND d.dep_id = '$dep_id'";
                }
                
                $query .= " ORDER BY d.dep_code ASC, kar.karyawan_name ASC, a.assets_name ASC, p.primary_id ASC";
                
                $qAssets = mysqli_query($conn, $query);
                
                if (!$qAssets) {
                    echo '<div class="alert alert-danger">Error query: ' . mysqli_error($conn) . '</div>';
                }
                
                $total_display = ($qAssets) ? mysqli_num_rows($qAssets) : 0;
                ?>
                
                <!-- Info Header dengan Total Asset -->
                <div class="info-header">
                    <div class="filter-info">
                        <?php if (isset($_GET['dep_code']) && !empty($_GET['dep_code'])): ?>
                            <i class="fas fa-filter"></i> Menampilkan asset dengan departemen: <strong><?= htmlspecialchars($filter_nama) ?></strong>
                        <?php else: ?>
                            <i class="fas fa-list"></i> Menampilkan semua asset
                        <?php endif; ?>
                    </div>
                    <div class="total-assets">
                        <i class="fas fa-boxes"></i> Total Asset: <?= $total_display ?> unit
                    </div>
                </div>

                <?php if ($total_display > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="dataTable">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Image</th>
                                    <th>Kode Asset</th>
                                    <th>Nama Asset</th>
                                    <th>Departemen</th>
                                    <th>Penanggung Jawab</th>
                                    <th>Kondisi</th>
                                    <th>Kategori</th>
                                    <th>Lokasi</th>
                                    <th>Type</th>
                                    <th>Merk</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = 1;
                                $current_dep = '';
                                $dep_total = 0;
                                
                                while ($row = mysqli_fetch_assoc($qAssets)): 
                                    
                                    $dep_display = $row['dep_code'] . ' - ' . $row['dep_name'];
                                    
                                    // Tampilkan separator departemen jika berganti departemen dan tidak dalam mode filter
                                    if (!isset($_GET['dep_code']) && $current_dep != $dep_display) {
                                        if ($current_dep != '') {
                                            ?>
                                            <tr class="total-row">
                                                <td colspan="11" class="font-weight-bold text-right pr-4">
                                                    Total <?= $current_dep ?>: <?= $dep_total ?> unit
                                                 </div>
                                             </tr>
                                            <?php
                                        }
                                        
                                        $current_dep = $dep_display;
                                        $dep_total = 0;
                                        ?>
                                        <tr class="dep-group">
                                            <td colspan="11" class="font-weight-bold">
                                                <i class="fas fa-building text-primary mr-2"></i> 
                                                Departemen: <?= $current_dep ?>
                                             </div>
                                         </tr>
                                        <?php
                                    }
                                    
                                    $dep_total++;
                                    
                                    // Class badge kondisi
                                    $badge_class = 'badge-normal';
                                    $kondisi_name = $row['kondisi_name'] ?? '';
                                    if (stripos($kondisi_name, 'BAGUS') !== false) {
                                        $badge_class = 'badge-bagus';
                                    } elseif (stripos($kondisi_name, 'RUSAK') !== false) {
                                        $badge_class = 'badge-rusak';
                                    } elseif (stripos($kondisi_name, 'SEDANG') !== false) {
                                        $badge_class = 'badge-sedang';
                                    }
                                    
                                    // Nama asset dengan model
                                    $asset_name = htmlspecialchars($row['assets_name']);
                                    if (!empty($row['assets_model'])) {
                                        $asset_name .= '<br><small class="text-muted">Model: ' . htmlspecialchars($row['assets_model']) . '</small>';
                                    }
                                ?>
                                <tr>
                                    <td class="text-center"><?= $no++ ?> </div>
                                    <td class="text-center">
                                        <?php if (!empty($row['primary_image'])): ?>
                                            <a href="../master/img/assets/<?= $row['primary_image'] ?>" target="_blank">
                                                <img src="../master/img/assets/<?= $row['primary_image'] ?>" 
                                                     style="width:40px;height:40px;object-fit:cover;border-radius:4px;border:1px solid #ddd;">
                                            </a>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">No Image</span>
                                        <?php endif; ?>
                                     </div>
                                    <td>
                                        <a href="../primary/detail.php?id=<?= $row['primary_id'] ?>" class="asset-code-link" title="Klik untuk lihat detail">
                                            <strong><?= htmlspecialchars($row['assets_kode']) ?></strong>
                                        </a>
                                     </div>
                                    <td><?= $asset_name ?> </div>
                                    <td><strong><?= $dep_display ?></strong> </div>
                                    <td><?= htmlspecialchars($row['karyawan_name'] ?? '-') ?> </div>
                                    <td>
                                        <span class="badge-kondisi <?= $badge_class ?>">
                                            <?= $kondisi_name ?: '-' ?>
                                        </span>
                                     </div>
                                    <td>
                                        <?= !empty($row['kategori_name']) ? htmlspecialchars($row['kategori_name'] . ' - ' . $row['kategori_line']) : '-' ?>
                                     </div>
                                    <td>
                                        <?= htmlspecialchars($row['lokasi_name'] ?? '-') ?>
                                        <?= !empty($row['lokasi_lantai']) ? '(' . $row['lokasi_lantai'] . ')' : '' ?>
                                     </div>
                                    <td><?= htmlspecialchars($row['type_name'] ?? '-') ?> </div>
                                    <td><?= htmlspecialchars($row['merk_name'] ?? '-') ?> </div>
                                </tr>
                                <?php endwhile; ?>
                                
                                <!-- Tampilkan total untuk departemen terakhir -->
                                <?php if (!isset($_GET['dep_code']) && $current_dep != ''): ?>
                                <tr class="total-row">
                                    <td colspan="11" class="font-weight-bold text-right pr-4">
                                        Total <?= $current_dep ?>: <?= $dep_total ?> unit
                                     </div>
                                 </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-building"></i>
                        <h5>Tidak Ada Data</h5>
                        <p>Tidak ada asset yang ditemukan untuk departemen yang dipilih.</p>
                    </div>
                <?php endif; ?>
                
            <?php 
            } else {
                // Admin/Operator belum memilih departemen
            ?>
                <div class="empty-state">
                    <i class="fas fa-search"></i>
                    <h5>Belum Ada Data</h5>
                    <p>Silakan pilih departemen terlebih dahulu.</p>
                </div>
            <?php } ?>
        </div>
    </div>

</div>
<!-- /.container-fluid -->

<?php include '../menu/footer.php'; ?>

<script>
$(document).ready(function() {
    // Inisialisasi Select2 untuk dropdown
    $('.select2').select2({
        placeholder: '-- Pilih Departemen --',
        allowClear: true,
        width: '100%'
    });
    
    // CEK APAKAH DATATABLE SUDAH DIINISIALISASI
    if ($('#dataTable').length && !$.fn.DataTable.isDataTable('#dataTable')) {
        $('#dataTable').DataTable({
            "pageLength": 25,
            "lengthMenu": [10, 25, 50, 100],
            "order": [[0, "asc"]],
            "language": {
                "lengthMenu": "Tampilkan _MENU_ data per halaman",
                "zeroRecords": "Tidak ada data",
                "info": "Menampilkan halaman _PAGE_ dari _PAGES_",
                "infoEmpty": "Tidak ada data",
                "infoFiltered": "(difilter dari _MAX_ total data)",
                "search": "Cari:",
                "paginate": {
                    "first": "Pertama",
                    "last": "Terakhir",
                    "next": "Selanjutnya",
                    "previous": "Sebelumnya"
                }
            },
            "columnDefs": [
                { "orderable": false, "targets": [1] } // Kolom Image tidak bisa diurutkan
            ]
        });
    }
    
    <?php if (isset($_GET['dep_code']) && !empty($_GET['dep_code'])): ?>
    $('#dep_code').val('<?= $_GET['dep_code'] ?>').trigger('change');
    <?php endif; ?>
});
</script>

</body>
</html>