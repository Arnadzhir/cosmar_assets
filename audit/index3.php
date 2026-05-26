<?php
include '../auth/auth.php';
allowRole([1,2]);

include '../config/koneksi.php';

$user_level = $_SESSION['user_level'];
$user_id = $_SESSION['user_id'];
$dep_id = $_SESSION['dep_id'] ?? 0;

include '../menu/header.php';
include '../menu/sidebar.php';
include '../menu/topbar.php';
?>

<!-- SweetAlert2 & DataTables -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap4.min.css">
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>

<style>
    .asset-code-link { color: #4e73df; text-decoration: underline; cursor: pointer; }
    .progress { height: 20px; }
    .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .total-badge { background-color: #4e73df; color: white; padding: 8px 16px; border-radius: 20px; font-size: 14px; font-weight: 600; }
    .badge-status-1 { background-color: #dc3545; color: white; }
    .badge-status-2 { background-color: #ffc107; color: #212529; }
    .badge-status-3 { background-color: #28a745; color: white; }
    .table th, .table td { vertical-align: middle; font-size: 12px; white-space: nowrap; }
    .table-responsive { overflow-x: auto; }
    .progress-bar { transition: width 0.5s ease; }
</style>

<div class="container-fluid">

    <!-- Card Progress Bar Global -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card shadow">
                <div class="card-body">
                    <h5 class="font-weight-bold text-primary mb-3">
                        <i class="fas fa-chart-line"></i> Progress Audit Keseluruhan
                    </h5>
                    <?php
                    $totalAsset = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM tbl_assets WHERE assets_kode IS NOT NULL AND assets_kode != ''"))['total'];
                    $completedAsset = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT assets_id) as total FROM tbl_audit WHERE status = 2 AND audit_status = 3"))['total'];
                    $percentGlobal = ($totalAsset > 0) ? round(($completedAsset / $totalAsset) * 100) : 0;
                    ?>
                    <div class="d-flex justify-content-between mb-1">
                        <span>Asset Selesai Audit: <strong><?= $completedAsset ?></strong> dari <strong><?= $totalAsset ?></strong> asset</span>
                        <span><?= $percentGlobal ?>%</span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar bg-success progress-bar-striped" style="width: <?= $percentGlobal ?>%;"><?= $percentGlobal ?>%</div>
                    </div>
                    <small class="text-muted mt-2 d-block">
                        <i class="fas fa-info-circle"></i> Asset dianggap selesai audit jika status "Done" dan Qty Sesuai.
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Page Heading -->
    <div class="page-header">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-chart-line"></i> Progress Audit
        </h1>
        <div class="total-badge">
            <i class="fas fa-boxes"></i> Total Asset: <span id="total-assets">0</span>
        </div>
    </div>

    <!-- Tabel Progress per Asset -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-list"></i> Detail Progress Audit per Asset
            </h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Kode Asset</th>
                            <th>Nama Asset</th>
                            <th>Qty Sistem</th>
                            <th>Qty Selesai Audit</th>
                            <th>Sisa</th>
                            <th>Progress</th>
                            <th>Status Qty</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Query: ambil qty yang sudah sesuai (audit_status = 3) dari audit dengan status = 2 (Done)
                        $sql = "SELECT 
                            ast.assets_id,
                            ast.assets_kode,
                            ast.assets_name,
                            ast.assets_qty as master_qty,

                            COALESCE(SUM(
                                CASE 
                                    WHEN a.audit_status = 3 AND a.status = 2 
                                    THEN a.audit_qty 
                                    ELSE 0 
                                END
                            ), 0) as qty_sesuai,

                            MAX(a.auditor) as last_auditor

                        FROM tbl_assets ast
                        LEFT JOIN tbl_audit a 
                            ON ast.assets_id = a.assets_id

                        WHERE ast.assets_kode IS NOT NULL 
                        AND ast.assets_kode != ''

                        GROUP BY ast.assets_id
                        ORDER BY ast.assets_kode ASC
                        ";
                        $result = mysqli_query($conn, $sql);
                        $total_rows = mysqli_num_rows($result);
                        $no = 1;

                        while ($row = mysqli_fetch_assoc($result)):
                            $master = $row['master_qty'];
                            $selesai = $row['qty_sesuai'];
                            $belum   = $master - $selesai;

                            $percent = ($master > 0) ? round(($selesai / $master) * 100) : 0;

                            if ($belum > 0) {
                                $status_text = 'Kurang';
                                $badge_class = 'badge-status-1';
                            } elseif ($belum < 0) {
                                $status_text = 'Lebih';
                                $badge_class = 'badge-status-2';
                            } else {
                                $status_text = 'Sesuai';
                                $badge_class = 'badge-status-3';
                            }
                        ?>

                        <tr>
                            <td class="text-center"><?= $no++ ?> </div>
                            <td>
                                <span class="asset-code-link" onclick="copyToClipboard('<?= htmlspecialchars($row['assets_kode']) ?>')">
                                    <i class="fas fa-copy text-muted mr-1"></i>
                                    <strong><?= htmlspecialchars($row['assets_kode']) ?></strong>
                                </span>
                             </div>                      
                            <td><?= htmlspecialchars($row['assets_name']) ?> </div>
                            <td class="text-center"><?= number_format($master) ?> unit</div>
                            <td class="text-center"><?= number_format($selesai) ?> unit</div>
                            <td class="text-center"><?= number_format($belum) ?> unit</div>
                            <td>
                                <div class="progress">
                                    <div class="progress-bar <?= ($percent >= 100) ? 'bg-success' : 'bg-primary' ?>" 
                                         role="progressbar" style="width: <?= $percent ?>%;">
                                        <?= $percent ?>%
                                    </div>
                                </div>
                             </div>
                            <td class="text-center"><span class="badge <?= $badge_class ?>"><?= $status_text ?></span></div>
                        </tr>
                        <?php endwhile; ?>
                        
                        <?php if ($total_rows == 0): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="fas fa-inbox fa-3x mb-3"></i>
                                <p class="mb-0">Belum ada data asset</p>
                             </div>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="7" class="text-right">Total Asset:</th>
                            <th class="text-center"><?= number_format($total_rows) ?> asset</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../menu/footer.php'; ?>

<script>
$(document).ready(function() {
    // Inisialisasi DataTable
    if ($('#dataTable').length && !$.fn.DataTable.isDataTable('#dataTable')) {
        var table = $('#dataTable').DataTable({
            pageLength: 25,
            lengthMenu: [10, 25, 50, 100],
            order: [[0, "asc"]],
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
        
        // Update total assets badge
        var totalRows = <?= $total_rows ?>;
        $('#total-assets').text(totalRows);
        
        // Update total assets saat search/filter
        table.on('draw', function () {
            var totalFiltered = table.rows({ filter: 'applied' }).count();
            $('#total-assets').text(totalFiltered);
        });
    }
});

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        Swal.fire({
            icon: 'success',
            title: 'Tersalin!',
            text: 'Kode ' + text + ' berhasil disalin',
            timer: 1500,
            showConfirmButton: false
        });
    }).catch(function() {
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