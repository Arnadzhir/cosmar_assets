<?php
    include '../auth/auth.php';
    allowRole([1,2,3]);

    include '../config/koneksi.php';

    /* =====================
    AMBIL DATA USER LOGIN
    ===================== */
    $user_id = $_SESSION['user_id'];
    $user_level = $_SESSION['user_level'];
    $dep_id = $_SESSION['dep_id'] ?? 0;

    // Filter berdasarkan level user
    if (in_array($user_level, [1, 2])) {
        // Admin/Operator: bisa melihat semua asset yang sudah approved
        $filterUser = "";
        $is_admin = true;
    } else {
        // User biasa: hanya melihat asset dari departemennya yang sudah approved
        $filterUser = "AND d.dep_id = '$dep_id'";
        $is_admin = false;
    }

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
    .badge-approved {
        background-color: #1cc88a;
        color: white;
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        white-space: nowrap;
    }
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
    .lokasi-badge {
        display: inline-block;
        background-color: #e8f0fe;
        color: #4e73df;
        padding: 3px 8px;
        margin: 2px;
        border-radius: 3px;
        font-size: 11px;
        white-space: nowrap;
    }
    .total-qty {
        font-weight: bold;
        color: #4e73df;
        font-size: 14px;
        text-align: center;
        white-space: nowrap;
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
        border: none;
        border-radius: 5px;
    }
    
    #dataTable {
        min-width: 1600px;
        width: 100%;
        white-space: nowrap;
    }
    
    #dataTable thead th {
        white-space: nowrap;
        background-color: #f8f9fc;
        vertical-align: middle;
        padding: 10px 8px;
        font-size: 12px;
    }
    
    #dataTable tbody td {
        white-space: nowrap;
        padding: 8px;
        vertical-align: middle;
        font-size: 12px;
    }
    
    #dataTable tbody td.spec-col {
        white-space: normal;
        min-width: 200px;
        max-width: 250px;
    }
    
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
    
    .btn-sm {
        padding: 0.2rem 0.4rem;
        font-size: 0.7rem;
    }
    
    .tools-group {
        display: flex;
        gap: 3px;
        justify-content: center;
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
            <i class="fas fa-print"></i> Print Assets (Approved)
        </h1>
    </div>

    <!-- Filter Card (Hanya untuk Admin/Operator) -->
    <?php if (in_array($user_level, [1, 2])): ?>
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

    <!-- Data Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-list"></i> Daftar Asset (Sudah Approved)
            </h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th width="80">Tools</th>
                            <th width="40">No</th>
                            <th width="70">Image</th>
                            <th width="150">Assets Code</th>
                            <th width="200">Assets Name</th>
                            <th width="180">Category</th>
                            <th width="220">Specification</th>
                            <th width="180">Target</th>
                            <th width="120">Capacity</th>
                            <th width="150">Kondisi</th>
                            <th width="150">Type</th>
                            <th width="180">Supplier</th>
                            <th width="180">Manufacturer</th>
                            <th width="150">Brand</th>
                            <th width="80">Total Qty</th>
                            <th width="250">Lokasi</th>
                            <th width="150">Departemen</th>
                            <th width="150">Penanggung Jawab</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        
                        // PERBAIKAN: Query menggunakan tbl_karyawan
                        $query = "SELECT 
                                a.assets_id,
                                a.assets_kode,
                                a.assets_name,
                                a.assets_model,
                                a.assets_spec,
                                a.assets_target,
                                a.assets_date,
                                a.assets_cap,
                                a.assets_uom,
                                a.assets_life,
                                a.assets_note,
                                a.timestamp,
                                
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
                                
                                kar.karyawan_id,
                                kar.karyawan_name,
                                
                                d.dep_id,
                                d.dep_name,
                                d.dep_code,
                                
                                SUM(p.primary_qty) as total_qty,
                                
                                MIN(kond.kondisi_name) as kondisi_name,
                                MIN(p.primary_image) as sample_image
                                
                            FROM tbl_primary p
                            INNER JOIN tbl_assets a ON p.assets_id = a.assets_id
                            LEFT JOIN tbl_kategori kat ON a.kategori_id = kat.kategori_id
                            LEFT JOIN tbl_type t ON a.type_id = t.type_id
                            LEFT JOIN tbl_supplier s ON a.supplier_id = s.supplier_id
                            LEFT JOIN tbl_produsen pr ON a.produsen_id = pr.produsen_id
                            LEFT JOIN tbl_merk m ON a.merk_id = m.merk_id
                            LEFT JOIN tbl_karyawan kar ON p.karyawan_id = kar.karyawan_id
                            LEFT JOIN tbl_dep d ON kar.dep_id = d.dep_id
                            LEFT JOIN tbl_kondisi kond ON p.kondisi_id = kond.kondisi_id
                            
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
                        
                        // Filter karyawan
                        if (isset($_GET['karyawan_id']) && !empty($_GET['karyawan_id'])) {
                            $filter_karyawan_id = mysqli_real_escape_string($conn, $_GET['karyawan_id']);
                            $query .= " AND kar.karyawan_id = '$filter_karyawan_id'";
                        }
                        
                        // GROUP BY dan ORDER BY
                        $query .= " GROUP BY 
                                a.assets_id,
                                p.karyawan_id
                            ORDER BY 
                                a.assets_date DESC,
                                kar.karyawan_name DESC";
                        
                        $result = mysqli_query($conn, $query);

                        if (!$result) {
                            echo "<tr><td colspan='17' class='text-center text-danger'>Error query: " . mysqli_error($conn) . "</div></tr>";
                        }

                        while ($row = mysqli_fetch_assoc($result)) {
                            $assets_id = $row['assets_id'];
                            $karyawan_id = $row['karyawan_id'];
                            
                            // Ambil detail lokasi
                            $qLokasi = mysqli_query($conn, "
                                SELECT 
                                    l.lokasi_name,
                                    l.lokasi_lantai,
                                    SUM(p.primary_qty) as total_qty
                                FROM tbl_primary p
                                INNER JOIN tbl_lokasi l ON p.lokasi_id = l.lokasi_id
                                WHERE p.assets_id = $assets_id
                                AND p.karyawan_id = '$karyawan_id'
                                GROUP BY l.lokasi_id, l.lokasi_name, l.lokasi_lantai
                                ORDER BY l.lokasi_name
                            ");
                            
                            $detail_lokasi = [];
                            while ($lokasi = mysqli_fetch_assoc($qLokasi)) {
                                $lokasi_display = $lokasi['lokasi_name'];
                                if (!empty($lokasi['lokasi_lantai'])) {
                                    $lokasi_display .= ' (Lt.' . $lokasi['lokasi_lantai'] . ')';
                                }
                                $detail_lokasi[] = '<span class="lokasi-badge">' . htmlspecialchars($lokasi_display) . ': ' . $lokasi['total_qty'] . '</span>';
                            }
                            $detail_lokasi_str = !empty($detail_lokasi) ? implode(' ', $detail_lokasi) : '-';
                            
                            // Kode asset (bisa di-copy)
                            $assets_kode_display = '<span class="asset-code-link" onclick="copyToClipboard(\'' . $row['assets_kode'] . '\')" title="Klik untuk menyalin kode">
                                <i class="fas fa-copy text-muted mr-1"></i>
                                <strong>' . $row['assets_kode'] . '</strong>
                            </span>';
                            
                            $kategori_display = !empty($row['kategori_name']) 
                                ? $row['kategori_name'] . ' - ' . $row['kategori_line']
                                : '-';
                            
                            // Tampilkan nama asset dengan model
                            $asset_name_display = htmlspecialchars($row['assets_name']);
                            if (!empty($row['assets_model'])) {
                                $asset_name_display .= '<br><small class="text-muted">Model: ' . htmlspecialchars($row['assets_model']) . '</small>';
                            }
                            
                            // Tools: View dan Print
                            $tools = '
                                <div class="tools-group">
                                    <a href="detail.php?id=' . $assets_id . '" class="btn btn-sm btn-info" title="Lihat Detail">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="print.php?id=' . $assets_id . '&karyawan_id=' . $karyawan_id . '" class="btn btn-sm btn-primary" target="_blank" title="Cetak BA">
                                        <i class="fas fa-print"></i>
                                    </a>
                                </div>';
                            
                            echo "<tr>
                                <td class='text-center'>{$tools}</div>
                                <td class='text-center'>{$no}</div>
                                <td class='text-center'>" . 
                                    (!empty($row['sample_image']) 
                                        ? "<a href='../master/img/assets/{$row['sample_image']}' target='_blank'>
                                            <img src='../master/img/assets/{$row['sample_image']}' 
                                                style='width:40px;height:40px;object-fit:cover;border-radius:4px;border:1px solid #ddd;'>
                                          </a>"
                                        : "<span class='badge badge-secondary'>No Image</span>"
                                    ) . 
                                "</div>
                                <td>{$assets_kode_display}</div>
                                <td>{$asset_name_display}</div>
                                <td>{$kategori_display}</div>
                                <td class='spec-col'>" . (!empty($row['assets_spec']) ? htmlspecialchars($row['assets_spec']) : '-') . "</div>
                                <td>" . (!empty($row['assets_target']) ? htmlspecialchars($row['assets_target']) : '-') . "</div>
                                <td>" . (!empty($row['assets_cap']) ? $row['assets_cap'] . ' ' . ($row['assets_uom'] ?? '') : '-') . "</div>
                                <td>" . ($row['kondisi_name'] ?? '-') . "</div>
                                <td>" . ($row['type_name'] ?? '-') . "</div>
                                <td>" . ($row['supplier_name'] ?? '-') . "</div>
                                <td>" . (!empty($row['produsen_region']) ? $row['produsen_region'] . ' (' . $row['produsen_code'] . ')' : '-') . "</div>
                                <td>" . ($row['merk_name'] ?? '-') . "</div>
                                <td class='text-center total-qty'>{$row['total_qty']} unit</div>
                                <td>{$detail_lokasi_str}</div>
                                <td>" . ($row['dep_name'] ?? '-') . " (" . ($row['dep_code'] ?? '-') . ")</div>
                                <td>" . ($row['karyawan_name'] ?? '-') . "</div>
                            </tr>";
                            $no++;
                        }
                        
                        if ($no == 1) {
                            echo "<tr>
                            <td colspan='17' class='text-center text-muted py-5'>
                                    <i class='fas fa-inbox fa-3x mb-3'></i>
                                    <p class='mb-0'>Belum ada asset yang sudah approved</p>
                                    <small>Asset akan muncul setelah di-approve oleh admin</small>
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
                        userSelect.empty().append('<option value="">-- Tidak ada penanggung jawab --</option>');
                    }
                },
                error: function(xhr, status, error) {
                    console.log('Error AJAX:', error);
                    userSelect.empty().append('<option value="">-- Error loading users --</option>');
                }
            });
        } else {
            userSelect.empty().append('<option value="">-- Pilih Penanggung Jawab --</option>');
        }
    });
    
    // Inisialisasi DataTable
    if ($('#dataTable').length && !$.fn.DataTable.isDataTable('#dataTable')) {
        $('#dataTable').DataTable({
            "pageLength": 25,
            "lengthMenu": [10, 25, 50, 100],
            "order": [[1, "asc"]],
            "scrollX": true,
            "scrollCollapse": true,
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