<?php
    include '../auth/auth.php';
    allowRole([1,2,3]);

    /* =====================
    AMBIL DATA USER LOGIN
    ===================== */
    $user_id    = $_SESSION['user_id'];
    $user_level = $_SESSION['user_level'];
    $dep_id     = $_SESSION['dep_id'] ?? 0; // TAMBAHKAN INI - ambil dep_id dari session

    // Filter berdasarkan level user
    if (in_array($user_level, [1, 2])) {
        // Admin/Operator: bisa melihat semua asset (termasuk kondisi 70006)
        $filterUser = "";
        $is_admin = true;
    } else {
        // User biasa: hanya melihat asset dari departemennya DAN sembunyikan kondisi 70006
        $filterUser = "AND d.dep_id = '$dep_id' AND (p.kondisi_id != 70006 OR p.kondisi_id IS NULL)";
        $is_admin = false;
    }

    include '../config/koneksi.php';
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
            <i class="fas fa-info-circle"></i> Primary Assets
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
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>User</label>
                            <select name="karyawan_id" id="filter-karyawan" class="form-control select2">
                                <option value="">-- Pilih User --</option>
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

    <!-- DataTales Example -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-list"></i> Primary Assets
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
                            <th>Assets Code</th>
                            <th>Assets Name</th>
                            <th>Category</th>
                            <th>Specification</th>
                            <th>Target</th>
                            <th>Capacity</th>
                            <th>UOM</th>
                            <th>Condition</th>
                            <th>Type</th>
                            <th>Supplier</th>
                            <th>Manufacturer</th>
                            <th>Brand</th>
                            <th>Location</th>
                            <th>Department</th>
                            <th>User</th>
                            <?php if (isset($_SESSION['user_level']) && in_array($_SESSION['user_level'], [1, 2])) : ?>
                            <th>Price</th>
                            <?php endif; ?>
                            <th>Purchase Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;

                        // Bangun query dasar
                        $query = "SELECT 
                                p.primary_id,
                                p.primary_qty,
                                p.primary_image,
                                p.timestamp as primary_timestamp,
                                
                                a.assets_id,
                                a.assets_kode,
                                a.assets_name,
                                a.assets_life,
                                a.assets_spec,
                                a.assets_target,
                                a.assets_cap,
                                a.assets_uom,
                                a.assets_price,
                                a.assets_date,
                                a.assets_qty,
                                a.assets_note,
                                a.timestamp as assets_timestamp,
                                
                                kond.kondisi_id,
                                kond.kondisi_name,
                                
                                kat.kategori_id,
                                kat.kategori_name,
                                kat.kategori_line,
                                
                                t.type_id,
                                t.type_name,
                                
                                s.supplier_id,
                                s.supplier_name,
                                
                                pr.produsen_id,
                                pr.produsen_region,
                                pr.produsen_code,
                                
                                m.merk_id,
                                m.merk_name,
                                
                                l.lokasi_id,
                                l.lokasi_name,
                                l.lokasi_lantai,
                                
                                kary.karyawan_id,
                                kary.karyawan_name,
                                
                                d.dep_id,
                                d.dep_name,
                                d.dep_code
                                
                            FROM tbl_primary p
                            INNER JOIN tbl_assets a      ON p.assets_id = a.assets_id
                            LEFT JOIN tbl_kondisi kond   ON p.kondisi_id = kond.kondisi_id
                            LEFT JOIN tbl_kategori kat   ON a.kategori_id = kat.kategori_id
                            LEFT JOIN tbl_type t         ON a.type_id = t.type_id
                            LEFT JOIN tbl_supplier s     ON a.supplier_id = s.supplier_id
                            LEFT JOIN tbl_produsen pr    ON a.produsen_id = pr.produsen_id
                            LEFT JOIN tbl_merk m         ON a.merk_id = m.merk_id
                            LEFT JOIN tbl_lokasi l       ON p.lokasi_id = l.lokasi_id
                            LEFT JOIN tbl_karyawan kary  ON p.karyawan_id = kary.karyawan_id
                            LEFT JOIN tbl_dep d          ON kary.dep_id = d.dep_id
                            WHERE a.assets_kode IS NOT NULL
                            AND a.assets_kode != ''
                            $filterUser
                        ";
                        
                        // Filter kategori
                        if (isset($_GET['kategori_id']) && !empty($_GET['kategori_id'])) {
                            $kategori_id = mysqli_real_escape_string($conn, $_GET['kategori_id']);
                            $query .= " AND kat.kategori_id = '$kategori_id'";
                        }
                        
                        // Filter departemen - menggunakan dep_code
                        if (isset($_GET['dep_code']) && !empty($_GET['dep_code'])) {
                            $dep_code = mysqli_real_escape_string($conn, $_GET['dep_code']);
                            $query .= " AND d.dep_code = '$dep_code'";
                        }
                        
                        // Filter user berdasarkan karyawan_id
                        if (isset($_GET['karyawan_id']) && !empty($_GET['karyawan_id']) && $is_admin) {
                            $karyawan_id = mysqli_real_escape_string($conn, $_GET['karyawan_id']);
                            $query .= " AND kary.karyawan_id = '$karyawan_id'";
                        }
                        
                        $query .= " ORDER BY a.assets_date DESC, p.primary_id ASC";
                        
                        $q = mysqli_query($conn, $query);

                        while ($row = mysqli_fetch_assoc($q)) {
                            // Format harga
                            $harga = !empty($row['assets_price']) && $row['assets_price'] > 0 
                                ? 'Rp ' . number_format($row['assets_price'], 0, ',', '.') 
                                : '-';
                            
                            // Format tanggal
                            $tanggal_beli = (!empty($row['assets_date']) && $row['assets_date'] != '0000-00-00') 
                                ? date('d/m/Y', strtotime($row['assets_date'])) 
                                : '-';
                            
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
                            
                            // Cek user level untuk menampilkan harga
                            $show_price = in_array($_SESSION['user_level'], [1, 2]);
                            
                            echo '<tr>';
                            echo '<td class="text-center">
                                    <div class="d-flex justify-content-center align-items-center" style="gap:5px; white-space:nowrap;">
                                        <a href="detail.php?id=' . $row['primary_id'] . '" class="btn btn-sm btn-info" title="Lihat Detail">
                                            <i class="fas fa-eye"></i>
                                        </a>';
                            
                            // Tombol Edit - untuk level 1 dan 2
                            if (in_array($user_level, [1, 2])) {
                                echo '<a href="edit.php?id=' . $row['primary_id'] . '" class="btn btn-sm btn-warning" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>';
                            }
                            
                            // Tombol Delete - hanya untuk level 1
                            if ($user_level == 1) {
                                echo '<a href="proses.php?hapus=1&id=' . $row['primary_id'] . '" 
                                        class="btn btn-sm btn-danger" title="Hapus"
                                        onclick="return confirm(\'Hapus data ini?\')">
                                        <i class="fas fa-trash"></i>
                                    </a>';
                            }
                            
                            echo '  </div>
                                </td>';
                            echo '<td class="text-center">' . $no . '</td>';
                            echo '<td class="text-center">' . 
                                (!empty($row['primary_image']) 
                                    ? '<a href="../master/img/assets/' . $row['primary_image'] . '" target="_blank">
                                        <img src="../master/img/assets/' . $row['primary_image'] . '" 
                                            style="width:40px;height:40px;object-fit:cover;border-radius:4px;border:1px solid #ddd;">
                                    </a>'
                                    : '<span class="badge badge-secondary">No Image</span>'
                                ) . 
                            '</td>';
                            echo '<td class="text-center">' . $assets_kode_display . '</td>';
                            echo '<td class="text-truncate" title="' . $row['assets_name'] . '">' . htmlspecialchars($row['assets_name']) . '</td>';
                            echo '<td class="text-truncate" style="max-width:150px;" title="' . (!empty($row['kategori_name']) ? $row['kategori_name'] . ' - ' . $row['kategori_line'] : '-') . '">' . 
                                (!empty($row['kategori_name']) 
                                    ? $row['kategori_name'] . ' - ' . $row['kategori_line']
                                    : '<span class="text-muted">-</span>'
                                ) . 
                            '</td>';
                            echo '<td class="text-truncate" style="max-width:150px;" title="' . (!empty($row['assets_spec']) ? htmlspecialchars($row['assets_spec']) : '-') . '">' . $spec . '</td>';
                            echo '<td class="text-truncate" style="max-width:100px;" title="' . (!empty($row['assets_target']) ? htmlspecialchars($row['assets_target']) : '-') . '">' . (!empty($row['assets_target']) ? htmlspecialchars($row['assets_target']) : '-') . '</td>';
                            echo '<td class="text-center">' . ($row['assets_cap'] ?? '-') . '</td>';
                            echo '<td class="text-center">' . ($row['assets_uom'] ?? '-') . '</td>';
                            echo '<td class="text-truncate" style="max-width:100px;" title="' . ($row['kondisi_name'] ?? '-') . '">' . ($row['kondisi_name'] ?? '-') . '</td>';
                            echo '<td class="text-truncate" style="max-width:100px;" title="' . ($row['type_name'] ?? '-') . '">' . ($row['type_name'] ?? '-') . '</td>';
                            echo '<td class="text-truncate" style="max-width:100px;" title="' . ($row['supplier_name'] ?? '-') . '">' . ($row['supplier_name'] ?? '-') . '</td>';
                            echo '<td class="text-truncate" style="max-width:100px;" title="' . (!empty($row['produsen_region']) ? $row['produsen_region'] . ' (' . $row['produsen_code'] . ')' : '-') . '">' . (!empty($row['produsen_region']) ? $row['produsen_region'] . ' (' . $row['produsen_code'] . ')' : '-') . '</td>';
                            echo '<td class="text-truncate" style="max-width:100px;" title="' . ($row['merk_name'] ?? '-') . '">' . ($row['merk_name'] ?? '-') . '</td>';
                            echo '<td class="text-truncate" style="max-width:120px;" title="' . ($row['lokasi_name'] ?? '-') . ' ' . (!empty($row['lokasi_lantai']) ? '(' . $row['lokasi_lantai'] . ')' : '') . '">' . ($row['lokasi_name'] ?? '-') . ' ' . (!empty($row['lokasi_lantai']) ? '(' . $row['lokasi_lantai'] . ')' : '') . '</td>';
                            echo '<td class="text-truncate" style="max-width:100px;" title="' . ($row['dep_name'] ?? '-') . '">' . ($row['dep_code'] ?? '-') . '</td>';
                            echo '<td class="text-truncate" style="max-width:100px;" title="' . ($row['karyawan_name'] ?? '-') . '">' . ($row['karyawan_name'] ?? '-') . '</td>';
                            
                            // Tambahkan kolom harga hanya untuk admin & operator
                            if ($show_price) {
                                echo '<td class="text-right" style="font-size:11px;">' . $harga . '</td>';
                            }
                            
                            echo '<td class="text-center" style="font-size:11px;">' . $tanggal_beli . '</td>';
                            echo '</tr>';
                            $no++;
                        }
                        
                        if ($no == 1) {
                            echo "<tr><td colspan='21' class='text-center text-muted py-5'>
                                    <i class='fas fa-inbox fa-3x mb-3'></i>
                                    <p class='mb-0'>Belum ada asset yang terdaftar</p>
                                </td>
                            </tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
<!-- /.container-fluid -->

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
                    if (response && response.trim() !== '') {
                        userSelect.empty().html(response);
                    } else {
                        userSelect.empty().append('<option value="">-- Tidak ada user --</option>');
                    }
                },
                error: function() {
                    userSelect.empty().append('<option value="">-- Error loading users --</option>');
                }
            });
        } else {
            userSelect.empty().append('<option value="">-- Pilih User --</option>');
        }
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
                { "orderable": false, "targets": [0, 2] }
            ]
        });
    }
    
    // Set nilai filter dari URL
    <?php if (isset($_GET['kategori_id'])): ?>
    $('#filter-kategori').val('<?= $_GET['kategori_id'] ?>');
    <?php endif; ?>
    
    <?php if (isset($_GET['dep_code'])): ?>
    $('#filter-dep').val('<?= $_GET['dep_code'] ?>');
    <?php endif; ?>
    
    <?php if (isset($_GET['karyawan_id'])): ?>
    setTimeout(function() {
        $('#filter-karyawan').val('<?= $_GET['karyawan_id'] ?>');
    }, 500);
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