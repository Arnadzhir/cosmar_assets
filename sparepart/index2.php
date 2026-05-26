<?php
include '../auth/auth.php';
allowRole([1,2,3]);

include '../config/koneksi.php';

/* =====================
   AMBIL DATA USER LOGIN
===================== */
$user_id    = $_SESSION['user_id'] ?? 0;
$user_level = $_SESSION['user_level'] ?? 0;
$dep_id     = $_SESSION['dep_id'] ?? 0;

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

<style>
    .table-responsive { overflow-x: auto; }
    .table th, .table td { vertical-align: middle; font-size: 12px; padding: 10px; }
    .filter-card { margin-bottom: 20px; }
    .filter-card .card-header { background-color: #f8f9fc; border-bottom: 1px solid #e3e6f0; padding: 12px 20px; }
    .filter-card .card-body { padding: 20px; }
    .select2-container--default .select2-selection--single { height: 38px; border: 1px solid #d1d3e2; border-radius: 0.35rem; }
    .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 36px; }
    .select2-container--default .select2-selection--single .select2-selection__arrow { height: 36px; }
    .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .total-badge { background-color: #4e73df; color: white; padding: 8px 16px; border-radius: 20px; font-size: 14px; font-weight: 600; }
    .btn-sm { padding: 0.25rem 0.5rem; font-size: 0.75rem; }
    .text-truncate { max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .badge-count {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    .badge-low { background-color: #f8d7da; color: #721c24; }
    .badge-medium { background-color: #fff3cd; color: #856404; }
    .badge-high { background-color: #d4edda; color: #155724; }
    .detail-sparepart {
        display: none;
        background: #f8f9fc;
        padding: 15px;
        margin-top: 10px;
        border-radius: 8px;
    }
    .detail-sparepart.show {
        display: table-row;
    }
    .sub-table {
        width: 100%;
        margin-top: 10px;
    }
    .sub-table th, .sub-table td {
        padding: 8px;
        font-size: 11px;
    }
    .btn-detail {
        cursor: pointer;
    }
    .modal-body .sub-table th {
        background-color: #f8f9fc;
    }
</style>

<div class="container-fluid">

    <div class="page-header">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-chart-bar"></i> Laporan Sparepart per Asset
        </h1>
        <div class="total-badge">
            <i class="fas fa-boxes"></i> Total Asset: <span id="total-asset">0</span>
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
                    <div class="col-md-4">
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
                    <div class="col-md-4">
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
            <i class="fas fa-arrow-left"></i> Kembali ke Manajemen Sparepart
        </a>
    </div>

    <!-- Data Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-list"></i> Daftar Asset dan Jumlah Sparepart
            </h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th width="50">No</th>
                            <th width="150">Kode Asset</th>
                            <th width="250">Nama Asset</th>
                            <th width="150">Kategori</th>
                            <th width="100">Jumlah Sparepart</th>
                            <th width="100">Total Qty</th>
                            <th width="150">Total Nilai</th>
                            <th width="100">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        
                        // Query untuk mendapatkan ringkasan sparepart per asset
                        $query = "
                            SELECT 
                                a.assets_id,
                                a.assets_kode,
                                a.assets_name,
                                a.assets_model,
                                a.kategori_id,
                                k.kategori_name,
                                k.kategori_line,
                                COUNT(s.sparepart_id) as total_sparepart,
                                COALESCE(SUM(s.sparepart_qty), 0) as total_qty,
                                COALESCE(SUM(s.sparepart_price * s.sparepart_qty), 0) as total_nilai
                            FROM tbl_assets a
                            LEFT JOIN tbl_sparepart s ON a.assets_id = s.assets_id
                            LEFT JOIN tbl_kategori k ON a.kategori_id = k.kategori_id
                            WHERE a.assets_kode IS NOT NULL AND a.assets_kode != ''
                        ";
                        
                        // Filter kategori
                        if (isset($_GET['kategori_id']) && !empty($_GET['kategori_id'])) {
                            $kategori_id = mysqli_real_escape_string($conn, $_GET['kategori_id']);
                            $query .= " AND a.kategori_id = '$kategori_id'";
                        }
                        
                        $query .= " GROUP BY a.assets_id ORDER BY a.assets_kode ASC";
                        
                        $result = mysqli_query($conn, $query);
                        $total_rows = ($result) ? mysqli_num_rows($result) : 0;
                        
                        if ($result && $total_rows > 0):
                            while ($row = mysqli_fetch_assoc($result)):
                                $total_sparepart = $row['total_sparepart'] ?? 0;
                                $total_qty = $row['total_qty'] ?? 0;
                                $total_nilai = $row['total_nilai'] ?? 0;
                                
                                // Nama asset dengan model
                                $asset_name = htmlspecialchars($row['assets_name']);
                                if (!empty($row['assets_model'])) {
                                    $asset_name .= '<br><small class="text-muted">Model: ' . htmlspecialchars($row['assets_model']) . '</small>';
                                }
                                
                                // Badge warna berdasarkan jumlah sparepart
                                if ($total_sparepart == 0) {
                                    $badge_class = 'badge-low';
                                    $badge_text = 'Tidak Ada';
                                } elseif ($total_sparepart <= 2) {
                                    $badge_class = 'badge-medium';
                                    $badge_text = $total_sparepart . ' Sparepart';
                                } else {
                                    $badge_class = 'badge-high';
                                    $badge_text = $total_sparepart . ' Sparepart';
                                }
                        ?>
                        <tr>
                            <td class="text-center"><?= $no++ ?> </div>
                            <td>
                                <strong><?= htmlspecialchars($row['assets_kode']) ?></strong>
                             </div>
                            <td><?= $asset_name ?> </div>
                            <td><?= htmlspecialchars($row['kategori_name'] ?? '-') ?> - <?= htmlspecialchars($row['kategori_line'] ?? '-') ?> </div>
                            <td class="text-center">
                                <span class="badge-count <?= $badge_class ?>"><?= $badge_text ?></span>
                             </div>
                            <td class="text-center"><?= $total_qty ?> unit</div>
                            <td class="text-right">
                                <?= $total_nilai > 0 ? 'Rp ' . number_format($total_nilai, 0, ',', '.') : '-' ?>
                             </div>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-info btn-detail" data-id="<?= $row['assets_id'] ?>" data-kode="<?= htmlspecialchars($row['assets_kode']) ?>">
                                    <i class="fas fa-eye"></i> Detail Sparepart
                                </button>
                             </div>
                        </tr>
                        <?php 
                            endwhile;
                        else: 
                        ?>
                        <tr>
                            <td colspan="8" class="text-center">Tidak ada data asset</div>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Detail Sparepart -->
    <div class="modal fade" id="detailModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-microchip"></i> Detail Sparepart - <span id="modal-asset-kode"></span>
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="modal-body">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                        <p class="mt-2">Memuat data...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

</div>

<?php include '../menu/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

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
        }
    });
    
    // Update total asset
    var totalData = <?= $total_rows ?? 0 ?>;
    $('#total-asset').text(totalData);
    
    // Detail button click
    $('.btn-detail').on('click', function() {
        var assetsId = $(this).data('id');
        var assetsKode = $(this).data('kode');
        
        $('#modal-asset-kode').text(assetsKode);
        $('#detailModal').modal('show');
        
        // Load detail sparepart via AJAX
        $.ajax({
            url: 'get_sparepart_by_asset.php',
            type: 'POST',
            data: { assets_id: assetsId },
            dataType: 'html',
            success: function(response) {
                $('#modal-body').html(response);
            },
            error: function(xhr, status, error) {
                console.log('AJAX Error:', error);
                $('#modal-body').html('<div class="alert alert-danger">Gagal memuat data sparepart: ' + error + '</div>');
            }
        });
    });
});

function copyToClipboard(text) {
    navigator.clipboard.writeText(text);
    Swal.fire({ icon: 'success', title: 'Tersalin!', timer: 1500, showConfirmButton: false });
}
</script>

</body>
</html>