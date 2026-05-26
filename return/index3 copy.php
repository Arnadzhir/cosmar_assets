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
    .table-responsive { overflow-x: auto; }
    .table th, .table td { vertical-align: middle; font-size: 12px; padding: 10px; }
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
    .badge-bagus { background-color: #1cc88a; color: white; padding: 3px 8px; border-radius: 20px; font-size: 10px; }
    .badge-rusak { background-color: #e74a3b; color: white; padding: 3px 8px; border-radius: 20px; font-size: 10px; }
    .badge-normal { background-color: #36b9cc; color: white; padding: 3px 8px; border-radius: 20px; font-size: 10px; }
</style>

<div class="container-fluid">

    <div class="page-header">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-check-print"></i> Print Approval Pengembalian Asset
        </h1>
        <div class="total-badge">
            <i class="fas fa-boxes"></i> Total Approval: <span id="total-requests">0</span>
        </div>
    </div>

    <?php if (isset($_SESSION['user_level']) && in_array($_SESSION['user_level'], [1, 2])) : ?>
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
                                while ($dep = mysqli_fetch_assoc($qDep)) {
                                    $selected = (isset($_GET['dep_code']) && $_GET['dep_code'] == $dep['dep_code']) ? 'selected' : '';
                                    echo "<option value='{$dep['dep_code']}' data-dep-code='{$dep['dep_code']}' {$selected}>{$dep['dep_code']} - {$dep['dep_name']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Penanggung Jawab</label>
                            <select name="karyawan_id" id="filter-karyawan" class="form-control select2">
                                <option value="">-- Pilih Penanggung Jawab --</option>
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
                                <a href="index3.php" class="btn btn-secondary">
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

    <form method="POST" action="print_return.php" id="cetakForm" target="_blank">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-list"></i> Daftar Request Pengembalian
                </h6>
                <div>
                    <button type="submit" name="cetak" class="btn btn-success btn-sm mr-2" id="btnCetak">
                        <i class="fas fa-print"></i> Cetak
                    </button>
                    <button type="button" name="return" class="btn btn-danger btn-sm" id="btnReturn">
                        <i class="fas fa-arrow-circle-left"></i> Kembalikan Assets
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th width="30"><input type="checkbox" id="checkAll"></th>
                                <th width="50">No</th>
                                <th width="80">Tools</th>
                                <th width="70">Image</th>
                                <th width="150">Kode Asset</th>
                                <th width="200">Nama Asset</th>
                                <th width="150">Penanggung Jawab</th>
                                <th width="150">Departemen</th>
                                <th width="150">Lokasi</th>
                                <th width="150">Kondisi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $no = 1;
                            
                            // PERBAIKAN: Query menggunakan tbl_karyawan
                            $query = "
                                SELECT 
                                    p.primary_id,
                                    p.primary_image,
                                    a.assets_kode,
                                    a.assets_name,
                                    l.lokasi_name,
                                    l.lokasi_lantai,
                                    kond.kondisi_name,
                                    kar.karyawan_id,
                                    kar.karyawan_name,
                                    d.dep_id,
                                    d.dep_code,
                                    d.dep_name
                                FROM tbl_primary p
                                INNER JOIN tbl_assets a ON p.assets_id = a.assets_id
                                LEFT JOIN tbl_lokasi l ON p.lokasi_id = l.lokasi_id
                                LEFT JOIN tbl_kondisi kond ON p.kondisi_id = kond.kondisi_id
                                LEFT JOIN tbl_karyawan kar ON p.karyawan_id = kar.karyawan_id
                                LEFT JOIN tbl_dep d ON kar.dep_id = d.dep_id
                                WHERE p.return_status = 2
                            ";
                            
                            // Filter berdasarkan level user
                            if (!in_array($user_level, [1, 2])) {
                                // User biasa: hanya asset dari departemennya
                                $query .= " AND d.dep_id = '$dep_id'";
                            }
                            
                            // Filter departemen
                            if (isset($_GET['dep_code']) && !empty($_GET['dep_code'])) {
                                $dep_code = mysqli_real_escape_string($conn, $_GET['dep_code']);
                                $query .= " AND d.dep_code = '$dep_code'";
                            }
                            
                            // Filter karyawan
                            if (isset($_GET['karyawan_id']) && !empty($_GET['karyawan_id'])) {
                                $filter_karyawan_id = mysqli_real_escape_string($conn, $_GET['karyawan_id']);
                                $query .= " AND p.karyawan_id = '$filter_karyawan_id'";
                            }
                            
                            $query .= " ORDER BY p.primary_id ASC";
                            
                            $result = mysqli_query($conn, $query);
                            
                            if (!$result) {
                                echo "<tr><td colspan='10' class='text-center text-danger'>Error query: " . mysqli_error($conn) . "</div></tr>";
                            }
                            
                            while ($row = mysqli_fetch_assoc($result)):
                                $lokasi = $row['lokasi_name'] ?? '-';
                                if (!empty($row['lokasi_lantai'])) {
                                    $lokasi .= ' (Lt.' . $row['lokasi_lantai'] . ')';
                                }
                                
                                // Badge kondisi
                                $badge_class = 'badge-normal';
                                $kondisi_name = $row['kondisi_name'] ?? '-';
                                if (stripos($kondisi_name, 'BAGUS') !== false) {
                                    $badge_class = 'badge-bagus';
                                } elseif (stripos($kondisi_name, 'RUSAK') !== false) {
                                    $badge_class = 'badge-rusak';
                                }
                            ?>
                            <tr>
                                <td class="text-center">
                                    <input type="checkbox" name="primary_ids[]" value="<?= $row['primary_id'] ?>" class="check-item">
                                 </div>
                                <td class="text-center"><?= $no++ ?> </div>
                                <td class="text-center">
                                    <a href="../primary/detail.php?id=<?= $row['primary_id'] ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                 </div>                                
                                <td class="text-center">
                                    <?php if (!empty($row['primary_image'])): ?>
                                        <img src="../master/img/assets/<?= $row['primary_image'] ?>" style="width:40px;height:40px;object-fit:cover;border-radius:4px;">
                                    <?php else: ?>
                                        <span class="badge badge-secondary">No Image</span>
                                    <?php endif; ?>
                                 </div>
                                <td>
                                    <span class="asset-code-link" onclick="copyToClipboard('<?= $row['assets_kode'] ?>')">
                                        <?= $row['assets_kode'] ?>
                                    </span>
                                 </div>
                                <td><?= htmlspecialchars($row['assets_name']) ?> </div>
                                <td><?= htmlspecialchars($row['karyawan_name'] ?? '-') ?> </div>
                                <td><?= $row['dep_code'] ?? '-' ?> - <?= $row['dep_name'] ?? '-' ?> </div>
                                <td><?= $lokasi ?> </div>
                                <td><span class="<?= $badge_class ?>"><?= $kondisi_name ?></span> </div>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </form>
</div>

<?php include '../menu/footer.php'; ?>

<script>
$(document).ready(function() {
    // Inisialisasi Select2
    $('.select2').select2({
        placeholder: '-- Pilih --',
        allowClear: true,
        width: '100%'
    });
    
    // Filter user berdasarkan departemen
    $('#filter-dep').on('change', function() {
        var depCode = $(this).val();
        var userSelect = $('#filter-karyawan');
        
        if (depCode) {
            userSelect.empty().append('<option value="">Loading...</option>');
            
            $.ajax({
                url: '../primary/get_users_by_dep_code.php',
                type: 'POST',
                data: { dep_code: depCode },
                dataType: 'html',
                timeout: 5000,
                success: function(response) {
                    console.log('Response:', response);
                    if (response && response.trim() !== '') {
                        userSelect.empty().html(response);
                    } else {
                        userSelect.empty().append('<option value="">-- Tidak ada penanggung jawab --</option>');
                    }
                },
                error: function(xhr, status, error) {
                    console.log('AJAX Error:', status, error);
                    userSelect.empty().append('<option value="">-- Error loading users --</option>');
                }
            });
        } else {
            userSelect.empty().append('<option value="">-- Pilih Penanggung Jawab --</option>');
        }
    });
    
    // Gunakan DataTable yang sudah ada di footer
    var table = $('#dataTable').DataTable();
    
    // Update total requests
    $('#total-requests').text(table.rows().count());
    
    // Check All menggunakan DataTable API
    $('#checkAll').on('click', function() {
        var rows = table.rows({ 'search': 'applied' }).nodes();
        $('input.check-item', rows).prop('checked', this.checked);
    });
    
    // SYNC CHECKBOX
    $('#dataTable tbody').on('change', '.check-item', function() {
        var total = table.rows({ 'search': 'applied' }).nodes().length;
        var checked = $('input.check-item:checked', table.rows().nodes()).length;
        $('#checkAll').prop('checked', total === checked);
    });
    
    // Set nilai filter dari URL
    <?php if (isset($_GET['dep_code'])): ?>
    $('#filter-dep').val('<?= $_GET['dep_code'] ?>');
    <?php endif; ?>
    
    <?php if (isset($_GET['karyawan_id'])): ?>
    setTimeout(function() {
        $('#filter-karyawan').val('<?= $_GET['karyawan_id'] ?>');
    }, 500);
    <?php endif; ?>
    
    // Tombol Cetak
    $('#btnCetak').on('click', function(e) {
        e.preventDefault();
        
        var checkedItems = $('input.check-item:checked').length;
        
        if (checkedItems === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Tidak Ada Data Dipilih',
                text: 'Silakan pilih minimal 1 asset yang akan dicetak!',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'OK'
            });
            return false;
        }
        
        // Submit form cetak
        $('#cetakForm').submit();
    });
    
    // Tombol Kembalikan Assets
    $('#btnReturn').on('click', function(e) {
        e.preventDefault();
        
        var checkedItems = $('input.check-item:checked').length;
        
        if (checkedItems === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Tidak Ada Data Dipilih',
                text: 'Silakan pilih minimal 1 asset yang akan dikembalikan!',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'OK'
            });
            return false;
        }
        
        // Tampilkan peringatan untuk memastikan user sudah mencetak data
        Swal.fire({
            title: 'Peringatan!',
            html: '<strong>Pastikan Anda sudah mencetak dokumen Berita Acara Pengembalian terlebih dahulu!</strong><br><br>' +
                  'Setelah proses ini, data penanggung jawab akan dihapus dari asset.<br>' +
                  'Asset akan kembali ke pool perusahaan dan tidak bisa dicetak ulang.<br><br>' +
                  '<span style="color: red;">Apakah Anda yakin ingin melanjutkan?</span>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Kembalikan!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                // Buat form baru untuk proses return
                var returnForm = $('<form>', {
                    'method': 'POST',
                    'action': 'proses3.php'
                });
                
                // Tambahkan semua checkbox yang dipilih
                $('input.check-item:checked').each(function() {
                    returnForm.append($('<input>', {
                        'type': 'hidden',
                        'name': 'primary_ids[]',
                        'value': $(this).val()
                    }));
                });
                
                // Tambahkan name setujui
                returnForm.append($('<input>', {
                    'type': 'hidden',
                    'name': 'setujui',
                    'value': '1'
                }));
                
                // Submit form
                $('body').append(returnForm);
                returnForm.submit();
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