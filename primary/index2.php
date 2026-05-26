<?php
include '../auth/auth.php';
allowRole([1,2]);

include '../config/koneksi.php';

/* =====================
   AMBIL DATA USER LOGIN
===================== */
$user_id    = $_SESSION['user_id'] ?? 0;
$user_level = $_SESSION['user_level'] ?? 0;

// Filter berdasarkan level user
if (in_array($user_level, [1, 2])) {
    $is_admin = true;
} else {
    $is_admin = false;
}

include '../menu/header.php';
include '../menu/sidebar.php';
include '../menu/topbar.php';
?>

<!-- SweetAlert2 CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<style>
    .table-responsive { overflow-x: auto; }
    .table th, .table td { 
        vertical-align: middle; 
        font-size: 12px; 
        padding: 10px; 
        /* HAPUS white-space: nowrap; */
    }
    /* Tambahkan ini agar teks bisa membungkus */
    .table th, .table td {
        word-wrap: break-word;
        word-break: break-word;
        white-space: normal;
    }
    /* Atur lebar minimal kolom */
    .table th:nth-child(1) { min-width: 80px; }  /* Tools */
    .table th:nth-child(2) { min-width: 50px; }  /* No */
    .table th:nth-child(3) { min-width: 120px; } /* Kode Asset */
    .table th:nth-child(4) { min-width: 200px; } /* Nama Asset */
    .table th:nth-child(5) { min-width: 150px; } /* Kategori */
    .table th:nth-child(6) { min-width: 100px; } /* Qty Master */
    .table th:nth-child(7) { min-width: 100px; } /* Qty Primary */
    .table th:nth-child(8) { min-width: 100px; } /* Selisih */
    .table th:nth-child(9) { min-width: 100px; } /* Status */
    
    .filter-card { margin-bottom: 20px; }
    .filter-card .card-header { background-color: #f8f9fc; border-bottom: 1px solid #e3e6f0; padding: 12px 20px; }
    .filter-card .card-body { padding: 20px; }
    .select2-container--default .select2-selection--single { height: 38px; border: 1px solid #d1d3e2; border-radius: 0.35rem; }
    .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 36px; }
    .select2-container--default .select2-selection--single .select2-selection__arrow { height: 36px; }
    .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .total-badge { background-color: #dc3545; color: white; padding: 8px 16px; border-radius: 20px; font-size: 14px; font-weight: 600; }
    .btn-sm { padding: 0.25rem 0.5rem; font-size: 0.75rem; }
    .badge-kurang { background-color: #dc3545; color: white; padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; }
    .badge-lebih { background-color: #ffc107; color: #212529; padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; }
    .badge-sesuai { background-color: #28a745; color: white; padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; }
    .tools-column {
        width: 80px;
        text-align: center;
    }
    .gap-2 {
        gap: 5px;
    }
</style>

<div class="container-fluid">

    <div class="page-header">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-check-double"></i> Validasi Asset
        </h1>
        <div class="total-badge">
            <i class="fas fa-exclamation-triangle"></i> Asset Tidak Sesuai: <span id="total-mismatch">0</span>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card shadow mb-4 filter-card">
        <div class="card-header">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-filter"></i> Filter Data
            </h6>
        </div>
        <div class="card-body">
            <form id="filterForm" method="GET" action="">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Kategori</label>
                            <select name="kategori_id" id="filter-kategori" class="form-control select2">
                                <option value="">-- Semua Kategori --</option>
                                <?php
                                $qKategori = mysqli_query($conn, "
                                    SELECT kategori_id, kategori_name, kategori_line
                                    FROM tbl_kategori 
                                    ORDER BY kategori_name
                                ");
                                if ($qKategori) {
                                    while ($kat = mysqli_fetch_assoc($qKategori)) {
                                        $selected = (isset($_GET['kategori_id']) && $_GET['kategori_id'] == $kat['kategori_id']) ? 'selected' : '';
                                        echo "<option value='{$kat['kategori_id']}' {$selected}>{$kat['kategori_name']} - {$kat['kategori_line']}</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" id="filter-status" class="form-control select2">
                                <option value="all" <?= (!isset($_GET['status']) || $_GET['status'] == 'all') ? 'selected' : '' ?>>-- Semua --</option>
                                <option value="kurang" <?= (isset($_GET['status']) && $_GET['status'] == 'kurang') ? 'selected' : '' ?>>Kurang</option>
                                <option value="lebih" <?= (isset($_GET['status']) && $_GET['status'] == 'lebih') ? 'selected' : '' ?>>Lebih</option>
                                <option value="sesuai" <?= (isset($_GET['status']) && $_GET['status'] == 'sesuai') ? 'selected' : '' ?>>Sesuai</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <div class="d-flex">
                                <button type="submit" class="btn btn-primary mr-2">
                                    <i class="fas fa-search"></i> Filter
                                </button>
                                <a href="index2.php" class="btn btn-secondary">
                                    <i class="fas fa-undo"></i> Reset
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Tombol Kembali -->
    <div class="mb-3">
        <a href="index.php" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Kembali ke Primary Assets
        </a>
    </div>

    <!-- Data Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-list"></i> Daftar Asset dengan Ketidaksesuaian Jumlah
            </h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th width="80">Tools</th>
                            <th width="50">No</th>
                            <th width="150">Kode Asset</th>
                            <th width="250">Nama Asset</th>
                            <th width="150">Kategori</th>
                            <th width="100">Qty Master</th>
                            <th width="100">Qty Primary</th>
                            <th width="120">Status</th>
                            <th width="100">Selisih</th>
                            
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        $total_mismatch = 0;
                        $filter_status = isset($_GET['status']) ? $_GET['status'] : 'all';
                        $filter_kategori = isset($_GET['kategori_id']) ? $_GET['kategori_id'] : '';
                        
                        // Query untuk mendapatkan perbandingan qty master dan qty primary
                        $query = "SELECT 
                                a.assets_id,
                                a.assets_kode,
                                a.assets_name,
                                a.assets_qty as qty_master,
                                a.kategori_id,
                                k.kategori_name,
                                k.kategori_line,
                                COALESCE(SUM(p.primary_qty), 0) as qty_primary,
                                (a.assets_qty - COALESCE(SUM(p.primary_qty), 0)) as selisih
                            FROM tbl_assets a
                            LEFT JOIN tbl_primary p ON a.assets_id = p.assets_id
                            LEFT JOIN tbl_kategori k ON a.kategori_id = k.kategori_id
                            WHERE a.assets_kode IS NOT NULL AND a.assets_kode != ''
                        ";
                        
                        // Filter kategori
                        if (!empty($filter_kategori)) {
                            $query .= " AND a.kategori_id = '$filter_kategori'";
                        }
                        
                        $query .= " GROUP BY a.assets_id";
                        
                        // Filter status (menggunakan HAVING)
                        if ($filter_status == 'kurang') {
                            $query .= " HAVING qty_master > qty_primary";
                        } elseif ($filter_status == 'lebih') {
                            $query .= " HAVING qty_master < qty_primary";
                        } elseif ($filter_status == 'sesuai') {
                            $query .= " HAVING qty_master = qty_primary";
                        }
                        
                        $query .= " ORDER BY a.assets_date DESC";
                        
                        $result = mysqli_query($conn, $query);
                        
                        if ($result && mysqli_num_rows($result) > 0):
                            while ($row = mysqli_fetch_assoc($result)):
                                $selisih = $row['selisih'];
                                $qty_master = $row['qty_master'];
                                $qty_primary = $row['qty_primary'];
                                
                                // Tentukan status
                                if ($qty_master > $qty_primary) {
                                    $status_class = 'badge-kurang';
                                    $status_text = 'KURANG'; 
                                    $status_detail = "Primary kurang " . abs($selisih) . " unit";
                                    $action_link = 'index.php';
                                    $action_title = 'Tambah Assets';
                                    $action_icon = 'fa-plus';
                                    $action_color = 'success';
                                } elseif ($qty_master < $qty_primary) {
                                    $status_class = 'badge-lebih';
                                    $status_text = 'LEBIH';
                                    $status_detail = "Primary lebih " . abs($selisih) . " unit";
                                    $action_link = 'index.php';
                                    $action_title = 'Hapus Assets';
                                    $action_icon = 'fa-trash';
                                    $action_color = 'danger';
                                } else {
                                    $status_class = 'badge-sesuai';
                                    $status_text = 'SESUAI';
                                    $status_detail = "Jumlah sudah sesuai";
                                    $action_link = '#';
                                    $action_title = 'Sudah Sesuai';
                                    $action_icon = 'fa-check';
                                    $action_color = 'secondary';
                                }
                                
                                if ($qty_master != $qty_primary) {
                                    $total_mismatch++;
                                }
                        ?>
                        <tr>
                            <td class="tools-column">
                                <div class="d-flex justify-content-center gap-2">
                                    <?php if ($qty_master > $qty_primary): ?>
                                        <a href="<?= $action_link ?>" 
                                           class="btn btn-sm btn-<?= $action_color ?>" 
                                           title="<?= $action_title ?>">
                                            <i class="fas <?= $action_icon ?>"></i>
                                        </a>
                                    <?php elseif ($qty_master < $qty_primary): ?>
                                        <a href="<?= $action_link ?>" 
                                           class="btn btn-sm btn-<?= $action_color ?>" 
                                           title="<?= $action_title ?>">
                                            <i class="fas <?= $action_icon ?>"></i>
                                        </a>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-secondary" disabled title="<?= $action_title ?>">
                                            <i class="fas <?= $action_icon ?>"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                             </div>
                            <td class="text-center"><?= $no++ ?> </div>
                            <td><strong><?= htmlspecialchars($row['assets_kode']) ?></strong> </div>
                            <td><?= htmlspecialchars($row['assets_name']) ?> </div>
                            <td><?= htmlspecialchars($row['kategori_name'] ?? '-') ?> - <?= htmlspecialchars($row['kategori_line'] ?? '-') ?> </div>
                            <td class="text-center"><?= number_format($qty_master) ?> unit </div>
                            <td class="text-center"><?= number_format($qty_primary) ?> unit </div>
                            <td class="text-center">
                                <strong class="<?= $selisih > 0 ? 'text-danger' : ($selisih < 0 ? 'text-warning' : 'text-success') ?>">
                                    <?= $selisih > 0 ? '+' : '' ?><?= number_format($selisih) ?> unit
                                </strong>
                                <br>
                                <small class="text-muted"><?= $status_detail ?></small>
                            </td>
                            <td class="text-center">
                                <span class="badge <?= $status_class ?>"><?= $status_text ?></span>
                             </div>
                         </tr>
                        <?php 
                            endwhile;
                        else: 
                        ?>
                         <tr>
                            <td colspan="9" class="text-center text-success">
                                <i class="fas fa-check-circle"></i> Semua asset sudah sesuai! Tidak ada ketidaksesuaian jumlah.
                             </div>
                         </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<?php include '../menu/footer.php'; ?>

<script>
$(document).ready(function() {
    // Hancurkan DataTable jika sudah ada
    if ($.fn.DataTable.isDataTable('#dataTable')) {
        $('#dataTable').DataTable().destroy();
    }
    
    // Inisialisasi Select2
    $('.select2').select2({
        placeholder: '-- Pilih --',
        allowClear: true,
        width: '100%'
    });
    
    // Inisialisasi DataTable
    var table = $('#dataTable').DataTable({
        "pageLength": 25,
        "lengthMenu": [10, 25, 50, 100],
        "order": [[1, "asc"]],
        "scrollX": true,
        "autoWidth": false,
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
            { "orderable": false, "targets": [0] }
        ]
    });
    
    // Update total mismatch
    var totalMismatch = <?= $total_mismatch ?>;
    $('#total-mismatch').text(totalMismatch);
});
</script>

</body>
</html>