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
    // Admin/Operator: bisa melihat semua sparepart (termasuk kondisi 70006)
    $filterUser = "";
    $is_admin = true;
} else {
    // User biasa: hanya melihat sparepart dari departemennya DAN sembunyikan kondisi 70006
    $filterUser = "AND d.dep_id = '$dep_id' AND (s.kondisi_id != 70006 OR s.kondisi_id IS NULL)";
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
    .table th, .table td { vertical-align: middle; font-size: 12px; padding: 8px; white-space: nowrap; }
    .asset-code-link { color: #4e73df; text-decoration: underline; cursor: pointer; }
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
    /* Perbaikan lebar kolom */
    .table th:nth-child(1), .table td:nth-child(1) { width: 80px; } /* Tools */
    .table th:nth-child(2), .table td:nth-child(2) { width: 50px; } /* No */
    .table th:nth-child(3), .table td:nth-child(3) { width: 70px; } /* Image */
    .table th:nth-child(4), .table td:nth-child(4) { width: 150px; } /* Nama Sparepart */
    .table th:nth-child(5), .table td:nth-child(5) { width: 120px; } /* Kode Asset */
    .table th:nth-child(6), .table td:nth-child(6) { width: 200px; } /* Nama Asset */
    .table th:nth-child(7), .table td:nth-child(7) { width: 100px; } /* Merk */
    .table th:nth-child(8), .table td:nth-child(8) { width: 220px; } /* Spesifikasi */
    .table th:nth-child(9), .table td:nth-child(9) { width: 80px; } /* Qty */
    .table th:nth-child(10), .table td:nth-child(10) { width: 120px; } /* Harga */
    .table th:nth-child(11), .table td:nth-child(11) { width: 100px; } /* Tanggal */
    .table th:nth-child(12), .table td:nth-child(12) { width: 150px; } /* Penanggung Jawab */
    .table th:nth-child(13), .table td:nth-child(13) { width: 150px; } /* Departemen */
    .table th:nth-child(14), .table td:nth-child(14) { width: 200px; } /* Catatan */
</style>

<div class="container-fluid">

    <div class="page-header">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-microchip"></i> Manajemen Sparepart
        </h1>
        <div class="total-badge">
            <i class="fas fa-boxes"></i> Total Sparepart: <span id="total-sparepart">0</span>
        </div>
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
                            <label>Departemen</label>
                            <select name="dep_code" id="filter-dep" class="form-control select2">
                                <option value="">-- Pilih Departemen --</option>
                                <?php
                                $qDep = mysqli_query($conn, "
                                    SELECT DISTINCT dep_code, MIN(dep_name) as dep_name
                                    FROM tbl_dep 
                                    GROUP BY dep_code 
                                    ORDER BY dep_code
                                ");
                                if ($qDep) {
                                    while ($dep = mysqli_fetch_assoc($qDep)) {
                                        $selected = (isset($_GET['dep_code']) && $_GET['dep_code'] == $dep['dep_code']) ? 'selected' : '';
                                        echo "<option value='{$dep['dep_code']}' {$selected}>{$dep['dep_code']} - {$dep['dep_name']}</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Penanggung Jawab</label>
                            <select name="karyawan_id" id="filter-karyawan" class="form-control select2">
                                <option value="">-- Pilih Penanggung Jawab --</option>
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

    <!-- Tombol Tambah -->
    <div class="mb-3 text-right">
        <a href="tambah.php" class="btn btn-success btn-sm">
            <i class="fas fa-plus"></i> Tambah Sparepart
        </a>
    </div>

    <!-- Data Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-list"></i> Daftar Sparepart
            </h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Tools</th>
                            <th>No</th>
                            <th>Image</th>
                            <th>Nama Sparepart</th>
                            <th>Kode Asset</th>
                            <th>Nama Asset</th>
                            <th>Merk</th>
                            <th>Spesifikasi</th>
                            <th>Kondisi</th>
                            <th>Qty</th>
                            <th>Harga Satuan</th>
                            <th>Tanggal</th>
                            <th>Penanggung Jawab</th>
                            <th>Departemen</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        
                        // Query menggunakan tbl_karyawan dan filter dinamis
                        $query = "
                            SELECT 
                                s.*,
                                a.assets_kode,
                                a.assets_name,
                                kar.karyawan_name,
                                k.kondisi_name,
                                d.dep_name,
                                d.dep_code
                            FROM tbl_sparepart s
                            INNER JOIN tbl_assets a ON s.assets_id = a.assets_id
                            INNER JOIN tbl_karyawan kar ON s.user_id = kar.karyawan_id
                            LEFT JOIN tbl_kondisi k ON s.kondisi_id = k.kondisi_id
                            LEFT JOIN tbl_dep d ON kar.dep_id = d.dep_id
                            WHERE 1=1
                            $filterUser
                        ";
                        
                        // Filter departemen
                        if (isset($_GET['dep_code']) && !empty($_GET['dep_code'])) {
                            $dep_code = mysqli_real_escape_string($conn, $_GET['dep_code']);
                            $query .= " AND d.dep_code = '$dep_code'";
                        }
                        
                        // Filter karyawan
                        if (isset($_GET['karyawan_id']) && !empty($_GET['karyawan_id'])) {
                            $filter_karyawan_id = mysqli_real_escape_string($conn, $_GET['karyawan_id']);
                            $query .= " AND s.user_id = '$filter_karyawan_id'";
                        }
                        
                        $query .= " ORDER BY s.sparepart_id DESC";
                        
                        $result = mysqli_query($conn, $query);
                        
                        $total_data = 0;
                        if ($result && mysqli_num_rows($result) > 0):
                            $total_data = mysqli_num_rows($result);
                            while ($row = mysqli_fetch_assoc($result)):
                                // Format harga
                                $harga = !empty($row['sparepart_price']) && $row['sparepart_price'] > 0 
                                    ? 'Rp ' . number_format($row['sparepart_price'], 0, ',', '.') 
                                    : '-';
                                
                                // Format tanggal
                                $tanggal = (!empty($row['sparepart_date']) && $row['sparepart_date'] != '0000-00-00') 
                                    ? date('d/m/Y', strtotime($row['sparepart_date'])) 
                                    : '-';
                                
                                // Truncate spesifikasi jika terlalu panjang
                                $spec = !empty($row['sparepart_spec']) ? htmlspecialchars($row['sparepart_spec']) : '-';
                                if (strlen($spec) > 50) {
                                    $spec = '<span title="' . htmlspecialchars($row['sparepart_spec']) . '">' . substr($spec, 0, 50) . '...</span>';
                                }
                                
                                // Truncate catatan jika terlalu panjang
                                $note = !empty($row['sparepart_note']) ? htmlspecialchars($row['sparepart_note']) : '-';
                                if (strlen($note) > 50) {
                                    $note = '<span title="' . htmlspecialchars($row['sparepart_note']) . '">' . substr($note, 0, 50) . '...</span>';
                                }
                                
                                // Tools
                                $tools = '
                                    <div class="d-flex justify-content-center align-items-center" style="gap:5px;">
                                        <a href="detail.php?id=' . $row['sparepart_id'] . '" class="btn btn-sm btn-info" title="Lihat Detail">
                                            <i class="fas fa-eye"></i>
                                        </a>';
                                
                                if (in_array($user_level, [1, 2])) {
                                    $tools .= '<a href="edit.php?id=' . $row['sparepart_id'] . '" class="btn btn-sm btn-warning" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>';
                                }
                                
                                if ($user_level == 1) {
                                    $tools .= '<a href="proses.php?hapus=1&id=' . $row['sparepart_id'] . '" 
                                                class="btn btn-sm btn-danger" title="Hapus"
                                                onclick="return confirm(\'Yakin ingin menghapus sparepart ini?\')">
                                                <i class="fas fa-trash"></i>
                                            </a>';
                                }
                                
                                $tools .= '</div>';
                        ?>
                        <tr>
                            <td class="text-center"><?= $tools ?></td>
                            <td class="text-center"><?= $no++ ?></td>
                            <td class="text-center">
                                <?php if (!empty($row['sparepart_image'])): ?>
                                    <a href="../master/img/assets/<?= $row['sparepart_image'] ?>" target="_blank">
                                        <img src="../master/img/assets/<?= $row['sparepart_image'] ?>" 
                                            style="width:40px;height:40px;object-fit:cover;border-radius:4px;border:1px solid #ddd;">
                                    </a>
                                <?php else: ?>
                                    <span class="badge badge-secondary">No Image</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($row['sparepart_name']) ?></td>
                            <td class="text-truncate" title="<?= htmlspecialchars($row['assets_kode'] ?? '') ?>">
                                <a href="../assets/detail.php?id=<?= $row['assets_id'] ?>" 
                                class="asset-code-link">
                                    <strong><?= htmlspecialchars($row['assets_kode'] ?? '-') ?></strong>
                                </a>
                            </td>                            
                            <td class="text-truncate" title="<?= htmlspecialchars($row['assets_name'] ?? '') ?>">
                                <?= htmlspecialchars($row['assets_name'] ?? '-') ?>
                            </td>
                            <td><?= htmlspecialchars($row['sparepart_merk']) ?></td>
                            <td class="spec-text text-truncate" style="max-width:200px;" title="<?= htmlspecialchars($row['sparepart_spec']) ?>">
                                <?= $spec ?>
                            </td>
                            <td><?= htmlspecialchars($row['kondisi_name'] ?? '-') ?></td>
                            <td class="text-center"><?= $row['sparepart_qty'] ?></td>
                            <td class="text-right"><?= $harga ?></td>
                            <td class="text-center"><?= $tanggal ?></td>
                            <td><?= htmlspecialchars($row['karyawan_name'] ?? '-') ?></td>
                            <td><?= ($row['dep_code'] ?? '-') ?> - <?= ($row['dep_name'] ?? '-') ?></td>
                            <td class="text-truncate" style="max-width:200px;" title="<?= htmlspecialchars($row['sparepart_note']) ?>">
                                <?= $note ?>
                            </td>
                        </tr>
                        <?php 
                            endwhile;
                        else: 
                        ?>
                        <tr>
                            <td colspan="15" class="text-center">Tidak ada data sparepart</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<?php include '../menu/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    // Hancurkan DataTable jika sudah ada (dari footer)
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
    
    // Update total sparepart
    var totalData = <?= $total_data ?? 0 ?>;
    $('#total-sparepart').text(totalData);
    
    // Filter user berdasarkan departemen
    $('#filter-dep').on('change', function() {
        var depCode = $(this).val();
        var userSelect = $('#filter-karyawan');
        
        if (depCode && depCode !== '') {
            userSelect.empty().append('<option value="">Loading...</option>');
            userSelect.prop('disabled', true);
            
            $.ajax({
                url: '../primary/get_users_by_dep_code.php',
                type: 'POST',
                data: { dep_code: depCode },
                dataType: 'html',
                timeout: 5000,
                success: function(response) {
                    if (response && response.trim() !== '') {
                        userSelect.empty().html(response);
                    } else {
                        userSelect.empty().append('<option value="">-- Tidak ada penanggung jawab --</option>');
                    }
                    userSelect.prop('disabled', false);
                },
                error: function() {
                    userSelect.empty().append('<option value="">-- Error loading users --</option>');
                    userSelect.prop('disabled', false);
                }
            });
        } else {
            userSelect.empty().append('<option value="">-- Pilih Penanggung Jawab --</option>');
            userSelect.prop('disabled', false);
        }
    });
    
    // Set nilai filter dari URL
    <?php if (isset($_GET['dep_code']) && !empty($_GET['dep_code'])): ?>
    $('#filter-dep').val('<?= htmlspecialchars($_GET['dep_code']) ?>').trigger('change');
    <?php endif; ?>
    
    <?php if (isset($_GET['karyawan_id']) && !empty($_GET['karyawan_id'])): ?>
    setTimeout(function() {
        $('#filter-karyawan').val('<?= htmlspecialchars($_GET['karyawan_id']) ?>').trigger('change');
    }, 500);
    <?php endif; ?>
});

function copyToClipboard(text) {
    navigator.clipboard.writeText(text);
    Swal.fire({ icon: 'success', title: 'Tersalin!', timer: 1500, showConfirmButton: false });
}
</script>

</body>
</html>