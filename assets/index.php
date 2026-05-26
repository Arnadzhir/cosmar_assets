<?php
include '../auth/auth.php';
allowRole([1,2,3]); // Admin dan Operator

include '../config/koneksi.php';

/* =====================
   AMBIL DATA USER LOGIN
===================== */
$user_id    = $_SESSION['user_id'];
$user_level = $_SESSION['user_level'];

// Filter berdasarkan level user
$is_admin = in_array($user_level, [1, 2]);

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
    .asset-code-link {
        color: #4e73df;
        text-decoration: underline;
        transition: all 0.2s;
        cursor: pointer;
        white-space: nowrap;
    }
    .asset-code-link:hover {
        color: #224abe;
        text-decoration: underline;
    }
    .filter-card {
        margin-bottom: 20px;
    }
    .filter-card .card-header {
        background-color: #f8f9fc;
        border-bottom: 1px solid #e3e6f0;
        padding: 12px 20px;
    }
    .filter-card .card-body {
        padding: 20px;
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
    .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }
    .badge {
        font-size: 10px;
        padding: 3px 6px;
    }
    .text-truncate {
        max-width: 200px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .table td.text-right {
        font-size: 11px;
    }
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    .total-badge {
        background-color: #4e73df;
        color: white;
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 14px;
        font-weight: 600;
    }
</style>

<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="page-header">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-info-circle"></i> Master Assets
        </h1>   
    </div>

    <!-- Filter Card (Hanya untuk Admin/Operator) -->
    <?php if ($is_admin): ?>
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
                                <option value="">Semua Kategori</option>
                                <?php
                                $qKategori = mysqli_query($conn, "
                                    SELECT MIN(kategori_id) as kategori_id, kategori_name 
                                    FROM tbl_kategori 
                                    GROUP BY kategori_name 
                                    ORDER BY kategori_name
                                ");
                                while($k = mysqli_fetch_assoc($qKategori)) {
                                    $selected = (isset($_GET['kategori_id']) && $_GET['kategori_id'] == $k['kategori_id']) ? 'selected' : '';
                                    echo "<option value='{$k['kategori_id']}' {$selected}>{$k['kategori_name']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Merk</label>
                            <select name="merk_id" id="filter-merk" class="form-control select2">
                                <option value="">Semua Merk</option>
                                <?php
                                $qMerk = mysqli_query($conn, "SELECT merk_id, merk_name FROM tbl_merk ORDER BY merk_name");
                                while($m = mysqli_fetch_assoc($qMerk)) {
                                    $selected = (isset($_GET['merk_id']) && $_GET['merk_id'] == $m['merk_id']) ? 'selected' : '';
                                    echo "<option value='{$m['merk_id']}' {$selected}>{$m['merk_name']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Type</label>
                            <select name="type_id" id="filter-type" class="form-control select2">
                                <option value="">Semua Type</option>
                                <?php
                                $qType = mysqli_query($conn, "SELECT type_id, type_name FROM tbl_type ORDER BY type_name");
                                while($t = mysqli_fetch_assoc($qType)) {
                                    $selected = (isset($_GET['type_id']) && $_GET['type_id'] == $t['type_id']) ? 'selected' : '';
                                    echo "<option value='{$t['type_id']}' {$selected}>{$t['type_name']}</option>";
                                }
                                ?>
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
                                <a href="index.php" class="btn btn-secondary">
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

    <!-- DataTales -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-list"></i> Master Assets
            </h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Tools</th>
                            <th>No</th>
                            <th>Kode Assets</th>
                            <th>Nama Assets</th>
                            <th>Kategori</th>
                            <th>Merk</th>
                            <th>Type</th>
                            <th>Spesifikasi</th>
                            <th>Target</th>
                            <th>Kapasitas</th>
                            <th>UOM</th>
                            <?php if (isset($_SESSION['user_level']) && in_array($_SESSION['user_level'], [1, 2])) : ?>
                            <th>Harga</th>
                            <?php endif; ?>
                            <th>Tgl Beli</th>
                            <th>Masa Manfaat</th>
                            <th>Total Qty</th>
                            <th>Catatan</th>
                            <th>Supplier</th>
                            <th>Produsen</th>
                            <th>Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        
                        // Bangun query dasar
                        $query = "
                            SELECT 
                                a.*,
                                kat.kategori_id,
                                kat.kategori_name,
                                kat.kategori_line,
                                m.merk_id,
                                m.merk_name,
                                t.type_id,
                                t.type_name,
                                s.supplier_name,
                                pr.produsen_region,
                                pr.produsen_code
                            FROM tbl_assets a
                            LEFT JOIN tbl_kategori kat ON a.kategori_id = kat.kategori_id
                            LEFT JOIN tbl_merk m ON a.merk_id = m.merk_id
                            LEFT JOIN tbl_type t ON a.type_id = t.type_id
                            LEFT JOIN tbl_supplier s ON a.supplier_id = s.supplier_id
                            LEFT JOIN tbl_produsen pr ON a.produsen_id = pr.produsen_id
                            WHERE 1=1
                        ";
                        
                        // Filter kategori
                        if (isset($_GET['kategori_id']) && !empty($_GET['kategori_id'])) {
                            $kategori_id = mysqli_real_escape_string($conn, $_GET['kategori_id']);
                            $query .= " AND kat.kategori_id = '$kategori_id'";
                        }
                        
                        // Filter merk
                        if (isset($_GET['merk_id']) && !empty($_GET['merk_id'])) {
                            $merk_id = mysqli_real_escape_string($conn, $_GET['merk_id']);
                            $query .= " AND m.merk_id = '$merk_id'";
                        }
                        
                        // Filter type
                        if (isset($_GET['type_id']) && !empty($_GET['type_id'])) {
                            $type_id = mysqli_real_escape_string($conn, $_GET['type_id']);
                            $query .= " AND t.type_id = '$type_id'";
                        }
                        
                        $query .= " ORDER BY a.assets_date DESC";
                        
                        $result = mysqli_query($conn, $query);

                        while ($row = mysqli_fetch_assoc($result)) {
                            $kategori = ($row['kategori_name'] ?? '-') . ($row['kategori_line'] ? ' - ' . $row['kategori_line'] : '');
                            $kapasitas = !empty($row['assets_cap']) ? $row['assets_cap'] : '-';
                            $harga = $row['assets_price'] > 0 ? 'Rp ' . number_format($row['assets_price'], 0, ',', '.') : '-';
                            $tanggal = (!empty($row['assets_date']) && $row['assets_date'] != '0000-00-00') ? date('d/m/Y', strtotime($row['assets_date'])) : '-';
                            $produsen = !empty($row['produsen_region']) ? $row['produsen_region'] . ' (' . $row['produsen_code'] . ')' : '-';
                            
                            // Truncate spesifikasi jika terlalu panjang
                            $spec = !empty($row['assets_spec']) ? htmlspecialchars($row['assets_spec']) : '-';
                            if (strlen($spec) > 50) {
                                $spec = '<span title="' . htmlspecialchars($row['assets_spec']) . '">' . substr($spec, 0, 50) . '...</span>';
                            }
                            
                            // Kode asset (bisa di-copy)
                            $assets_kode_display = '<span class="asset-code-link" onclick="copyToClipboard(\'' . $row['assets_kode'] . '\')" title="Klik untuk menyalin kode">
                                <i class="fas fa-copy text-muted mr-1"></i>
                                <strong>' . $row['assets_kode'] . '</strong>
                            </span>';
                        ?>
                        <tr>
                            <td class="text-center">
                                <div class="d-flex justify-content-center align-items-center" style="gap:5px;">
                                    <a href="detail.php?id=<?= $row['assets_id'] ?>" class="btn btn-sm btn-info" title="Detail">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    
                                    <?php if (in_array($user_level, [1, 2])) : ?>
                                        <a href="edit.php?id=<?= $row['assets_id'] ?>" class="btn btn-sm btn-warning" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if ($user_level == 1): ?>
                                        <a href="proses.php?hapus_assets=1&id=<?= $row['assets_id'] ?>" 
                                           class="btn btn-sm btn-danger" 
                                           title="Hapus"
                                           onclick="return confirm('Yakin ingin menghapus assets ini?\nSemua data item terkait juga akan ikut terhapus.')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                             </td>
                            <td class="text-center"><?= $no++ ?></td>
                            <td><?= $assets_kode_display ?></td>
                            <td class="text-truncate" title="<?= htmlspecialchars($row['assets_name']) ?>"><?= htmlspecialchars($row['assets_name']) ?></td>
                            <td class="text-truncate" style="max-width:150px;" title="<?= $kategori ?>"><?= $kategori ?></td>
                            <td class="text-truncate" style="max-width:100px;" title="<?= $row['merk_name'] ?? '-' ?>"><?= $row['merk_name'] ?? '-' ?></td>
                            <td class="text-truncate" style="max-width:100px;" title="<?= $row['type_name'] ?? '-' ?>"><?= $row['type_name'] ?? '-' ?></td>
                            <td class="text-truncate" style="max-width:150px;" title="<?= htmlspecialchars($row['assets_spec'] ?? '-') ?>"><?= $spec ?></td>
                            <td class="text-truncate" style="max-width:100px;" title="<?= htmlspecialchars($row['assets_target'] ?? '-') ?>"><?= htmlspecialchars($row['assets_target'] ?? '-') ?></td>
                            <td class="text-center"><?= $kapasitas ?></td>
                            <td class="text-center"><?= $row['assets_uom'] ?? '-' ?></td>
                            <?php if (isset($_SESSION['user_level']) && in_array($_SESSION['user_level'], [1, 2])) : ?>
                            <td class="text-right" style="font-size:11px;"><?= $harga ?></td>
                            <?php endif; ?>
                            <td class="text-center" style="font-size:11px;"><?= $tanggal ?></td>
                            <td class="text-center"><?= $row['assets_life'] ?? '-' ?> thn</td>
                            <td class="text-center"><strong><?= $row['assets_qty'] ?? 0 ?></strong></td>
                            <td class="text-truncate" style="max-width:150px;" title="<?= htmlspecialchars($row['assets_note'] ?? '-') ?>"><?= htmlspecialchars($row['assets_note'] ?? '-') ?></td>
                            <td class="text-truncate" style="max-width:100px;" title="<?= $row['supplier_name'] ?? '-' ?>"><?= $row['supplier_name'] ?? '-' ?></td>
                            <td class="text-truncate" style="max-width:100px;" title="<?= $produsen ?>"><?= $produsen ?></td>
                            <td class="text-center" style="font-size:11px;"><?= date('d/m/Y H:i', strtotime($row['timestamp'])) ?></td>
                        </tr>
                        <?php 
                        }
                        
                        if ($no == 1) {
                            echo "<tr>
                                    <td colspan='19' class='text-center text-muted py-5'>
                                        <i class='fas fa-inbox fa-3x mb-3'></i>
                                        <p class='mb-0'>Belum ada asset yang terdaftar</p>
                                    </td>
                                </tr>
                            </div>
                        </div>
                        ";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<?php include '../menu/footer.php'; ?>

<!-- Script untuk DataTable dan Filter -->
<script>
$(document).ready(function() {
    // Inisialisasi Select2
    $('.select2').select2({
        placeholder: '-- Pilih --',
        allowClear: true,
        width: '100%'
    });
    
    // Inisialisasi DataTable
    if ($('#dataTable').length && !$.fn.DataTable.isDataTable('#dataTable')) {
        $('#dataTable').DataTable({
            "pageLength": 25,
            "lengthMenu": [10, 25, 50, 100],
            "order": [[1, "desc"]],
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
    }
    
    // Set nilai filter dari URL
    <?php if (isset($_GET['kategori_id'])): ?>
    $('#filter-kategori').val('<?= $_GET['kategori_id'] ?>');
    <?php endif; ?>
    
    <?php if (isset($_GET['merk_id'])): ?>
    $('#filter-merk').val('<?= $_GET['merk_id'] ?>');
    <?php endif; ?>
    
    <?php if (isset($_GET['type_id'])): ?>
    $('#filter-type').val('<?= $_GET['type_id'] ?>');
    <?php endif; ?>
});

// Fungsi copy ke clipboard
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        Swal.fire({
            icon: 'success',
            title: 'Tersalin!',
            text: 'Kode ' + text + ' berhasil disalin ke clipboard',
            timer: 1500,
            showConfirmButton: false
        });
    }).catch(function(err) {
        var textarea = document.createElement('textarea');
        textarea.value = text;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        alert('Kode berhasil disalin!');
    });
}
</script>

</body>
</html>