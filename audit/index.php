<?php
include '../auth/auth.php';
allowRole([1,2]); // Hanya level 1 dan 2 yang dapat mengakses

include '../config/koneksi.php';

/* =====================
   AMBIL DATA USER LOGIN
===================== */
$user_id    = $_SESSION['user_id'];
$user_level = $_SESSION['user_level'];
$dep_id     = $_SESSION['dep_id'] ?? 0;

$is_admin = in_array($user_level, [1, 2]);

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

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap4.min.css">
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>

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
        min-width: 1200px;
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
        white-space: nowrap;
    }
    .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }
    .badge {
        font-size: 10px;
        padding: 3px 6px;
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
    
    /* Status Badge */
    .badge-status-0 {
        background-color: #6c757d;
        color: white;
    }
    .badge-status-1 {
        background-color: #dc3545;
        color: white;
    }
    .badge-status-2 {
        background-color: #ffc107;
        color: #212529;
    }
    .badge-status-3 {
        background-color: #28a745;
        color: white;
    }
    
    /* Custom scrollbar */
    .table-responsive::-webkit-scrollbar {
        height: 8px;
    }
    .table-responsive::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }
    .table-responsive::-webkit-scrollbar-thumb {
        background: #4e73df;
        border-radius: 4px;
    }
    .table-responsive::-webkit-scrollbar-thumb:hover {
        background: #2e59d9;
    }
</style>

<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="page-header">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-clipboard-list"></i> Outstanding Audit Assets
        </h1>
        <div class="total-badge">
            <i class="fas fa-boxes"></i> Total Audit: <span id="total-data">0</span>
        </div>
    </div>

    <!-- Tombol Tambah -->
    <div class="mb-3 text-right">
        <a href="transfer_from_primary.php" class="btn btn-primary btn-sm" onclick="return confirm('Transfer semua data primary ke audit? Data audit lama akan dihapus?')">
            <i class="fas fa-download"></i> Transfer Data dari Primary Assets
        </a>
        <a href="index.php" class="btn btn-secondary btn-sm">
            <i class="fas fa-sync-alt"></i> Refresh
        </a>
    </div>

    <!-- Filter Card (Hanya Filter Kategori) -->
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
                            <label>Kategori Asset</label>
                            <select name="kategori_id" id="filter-kategori" class="form-control select2">
                                <option value="">-- Semua Kategori --</option>
                                <?php
                                $qKategori = mysqli_query($conn, "
                                    SELECT kategori_id, kategori_name, kategori_line
                                    FROM tbl_kategori 
                                    ORDER BY kategori_name
                                ");
                                while($k = mysqli_fetch_assoc($qKategori)) {
                                    $selected = (isset($_GET['kategori_id']) && $_GET['kategori_id'] == $k['kategori_id']) ? 'selected' : '';
                                    echo "<option value='{$k['kategori_id']}' {$selected}>{$k['kategori_name']} - {$k['kategori_line']}</option>";
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

    <!-- DataTales Example -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-list"></i> Daftar Audit Asset
            </h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th width="80">Tools</th>
                            <th width="50">No</th>
                            <th width="120">Status</th>
                            <th width="150">Kode Asset</th>
                            <th width="200">Nama Asset</th>
                            <th width="120">Selisih Qty</th>
                            <th width="100">Qty Audit</th>
                            <th width="100">Qty Sistem</th>
                            <th width="150">Kategori</th>
                            <th width="150">Auditor</th>
                            <th width="150">Penanggung Jawab</th>
                            <th width="180">Tanggal Audit</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        $total_data = 0;
                        
                        // PERBAIKAN: Query menggunakan tbl_karyawan
                        $query = "SELECT 
                                a.audit_id,
                                a.audit_qty,
                                a.auditor,
                                a.audit_status,
                                a.status,
                                a.timestamp,
                                
                                ast.assets_id,
                                ast.assets_kode,
                                ast.assets_name,
                                ast.assets_qty as sistem_qty,
                                
                                kat.kategori_id,
                                kat.kategori_name,
                                kat.kategori_line,
                                
                                kar.karyawan_id,
                                kar.karyawan_name
                                
                            FROM tbl_audit a
                            INNER JOIN tbl_assets ast ON a.assets_id = ast.assets_id
                            LEFT JOIN tbl_kategori kat ON ast.kategori_id = kat.kategori_id
                            LEFT JOIN tbl_karyawan kar ON a.user_id = kar.karyawan_id
                            WHERE a.status = 1
                            AND ast.assets_kode IS NOT NULL
                            AND ast.assets_kode != ''
                        ";
                        
                        // Filter kategori
                        if (isset($_GET['kategori_id']) && !empty($_GET['kategori_id'])) {
                            $kategori_id = mysqli_real_escape_string($conn, $_GET['kategori_id']);
                            $query .= " AND kat.kategori_id = '$kategori_id'";
                        }
                        
                        $query .= " GROUP BY a.audit_id ORDER BY a.audit_id DESC";
                        
                        $q = mysqli_query($conn, $query);
                        
                        if ($q) {
                            $total_data = mysqli_num_rows($q);
                        }

                        if ($q && mysqli_num_rows($q) > 0):
                        while ($row = mysqli_fetch_assoc($q)):
                        // Status Audit (qty)
                        $audit_status = $row['audit_status'];
                        if ($audit_status == 0) {
                            $status_text = 'Belum';
                            $status_class = 'badge-status-0';
                        } elseif ($audit_status == 1) {
                            $status_text = 'Kurang';
                            $status_class = 'badge-status-1';
                        } elseif ($audit_status == 2) {
                            $status_text = 'Lebih';
                            $status_class = 'badge-status-2';
                        } else {
                            $status_text = 'Sesuai';
                            $status_class = 'badge-status-3';
                        }
                        // Status Audit
                        $status2 = $row['status'];
                        if ($status2 == 0) {
                            $status_text2 = 'Lost';
                            $status_class2 = 'badge-status-0';
                        } elseif ($status2 == 1) {
                            $status_text2 = 'Pending';
                            $status_class2 = 'badge-status-2';
                        } else {
                            $status_text2 = 'Done';
                            $status_class2 = 'badge-status-3';
                        }    

                        // Hitung selisih
                        $selisih = $row['audit_qty'] - $row['sistem_qty'];
                        $selisih_abs = abs($selisih);
                        $selisih_text = ($selisih > 0 ? '+' : '') . number_format($selisih_abs);

                            // Kode asset (bisa di-copy)
                            $assets_kode_display = '<span class="asset-code-link" onclick="copyToClipboard(\'' . $row['assets_kode'] . '\')" title="Klik untuk menyalin kode">
                                <i class="fas fa-copy text-muted mr-1"></i>
                                <strong>' . htmlspecialchars($row['assets_kode']) . '</strong>
                            </span>';

                            // Tools
                            $tools = '<div class="d-flex justify-content-center" style="gap:5px;">';
                            $tools .= '<a href="edit.php?id=' . $row['audit_id'] . '" class="btn btn-sm btn-warning" title="Edit"><i class="fas fa-edit"></i></a>';
                            if ($user_level == 1) {
                                $tools .= '<a href="proses.php?hapus=1&id=' . $row['audit_id'] . '" class="btn btn-sm btn-danger" title="Hapus" onclick="return confirm(\'Hapus data ini?\')"><i class="fas fa-trash"></i></a>';
                            }
                            $tools .= '</div>';
                        ?>
                        <tr>
                            <td class="text-center"><?= $tools ?> </div>
                            <td class="text-center"><?= $no++ ?> </div>
                            <td class="text-center">
                                <span class="badge <?= $status_class2 ?>"><?= $status_text2 ?></span>
                             </div>
                            <td><?= $assets_kode_display ?> </div>
                            <td><?= htmlspecialchars($row['assets_name']) ?> </div>
                            <td class="text-center">
                                <span class="badge <?= $status_class ?>"><?= $status_text ?> (<?= $selisih_text ?>)</span>
                             </div>
                            <td class="text-center"><?= number_format($row['audit_qty']) ?> unit</div>
                            <td class="text-center"><?= number_format($row['sistem_qty']) ?> unit</div>
                            <td><?= htmlspecialchars($row['kategori_name'] ?? '-') ?> - <?= htmlspecialchars($row['kategori_line'] ?? '-') ?> </div>
                            <td><?= htmlspecialchars($row['auditor'] ?? '-') ?> </div>
                            <td><?= htmlspecialchars($row['karyawan_name'] ?? '-') ?> </div>
                            <td class="text-center"><?= date('d/m/Y H:i', strtotime($row['timestamp'])) ?> </div>
                        </tr>
                        <?php
                        endwhile;
                        else:
                        ?>
                        <tr>
                            <td colspan="12" class="text-center text-muted py-5">
                                <i class="fas fa-inbox fa-3x mb-3"></i>
                                <p class="mb-0">Belum ada data audit</p>
                                <small>Silakan transfer data dari primary assets</small>
                            </div>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
<!-- /.container-fluid -->

<?php include '../menu/footer.php'; ?>

<script>
$(document).ready(function() {
    // Inisialisasi Select2
    $('.select2').select2({
        placeholder: '-- Pilih --',
        allowClear: true,
        width: '100%'
    });

    // Set nilai filter dari URL (WAJIB pakai trigger)
    <?php if (!empty($_GET['kategori_id'])): ?>
        $('#filter-kategori')
            .val('<?= $_GET['kategori_id'] ?>')
            .trigger('change');
    <?php endif; ?>

    // Inisialisasi DataTable (HANYA SEKALI)
    if ($('#dataTable').length && !$.fn.DataTable.isDataTable('#dataTable')) {
        var table = $('#dataTable').DataTable({
            pageLength: 25,
            lengthMenu: [10, 25, 50, 100],
            order: [[1, "desc"]],
            scrollX: true,
            autoWidth: false,
            language: {
                lengthMenu: "Tampilkan _MENU_ data per halaman",
                zeroRecords: "Tidak ada data",
                info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                infoEmpty: "Tidak ada data",
                infoFiltered: "(difilter dari _MAX_ total data)",
                search: "Cari:",
                paginate: {
                    first: "Pertama",
                    last: "Terakhir",
                    next: "Selanjutnya",
                    previous: "Sebelumnya"
                }
            },
            columnDefs: [
                { orderable: false, targets: [0] }
            ]
        });

        // Update total saat search/filter DataTable
        table.on('draw', function () {
            var totalFiltered = table.rows({ filter: 'applied' }).count();
            $('#total-data').text(totalFiltered);
        });
    }
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
        Swal.fire({
            icon: 'success',
            title: 'Tersalin!',
            text: 'Kode ' + text + ' berhasil disalin',
            timer: 1500,
            showConfirmButton: false
        });
    });
}
</script>

</body>
</html>