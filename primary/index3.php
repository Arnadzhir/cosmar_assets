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
$show_price = in_array($user_level, [1, 2]);

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
        word-wrap: break-word;
        word-break: break-word;
        white-space: normal;
        font-style: normal;
    }
    /* Atur lebar minimal kolom */
    .table th:nth-child(1) { min-width: 80px; }  /* Tools */
    .table th:nth-child(2) { min-width: 50px; }  /* No */
    .table th:nth-child(3) { min-width: 70px; }  /* Image */
    .table th:nth-child(4) { min-width: 120px; } /* Kode Asset */
    .table th:nth-child(5) { min-width: 200px; } /* Nama Asset */
    .table th:nth-child(6) { min-width: 150px; } /* Lokasi */
    .table th:nth-child(7) { min-width: 100px; } /* Kondisi */
    .table th:nth-child(8) { min-width: 80px; }  /* Qty */
    
    .filter-card { margin-bottom: 20px; }
    .filter-card .card-header { background-color: #f8f9fc; border-bottom: 1px solid #e3e6f0; padding: 12px 20px; }
    .filter-card .card-body { padding: 20px; }
    .select2-container--default .select2-selection--single { height: 38px; border: 1px solid #d1d3e2; border-radius: 0.35rem; }
    .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 36px; }
    .select2-container--default .select2-selection--single .select2-selection__arrow { height: 36px; }
    .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .total-badge { background-color: #17a2b8; color: white; padding: 8px 16px; border-radius: 20px; font-size: 14px; font-weight: 600; }
    .btn-sm { padding: 0.25rem 0.5rem; font-size: 0.75rem; }
    .tools-column { width: 80px; text-align: center; }
    .gap-2 { gap: 5px; }
    .d-flex { display: flex; }
    .justify-content-center { justify-content: center; }
    
    <?php if ($show_price): ?>
    .table th:nth-child(9) { min-width: 120px; } /* Harga */
    .table th:nth-child(10) { min-width: 100px; } /* Tgl Input */
    <?php else: ?>
    .table th:nth-child(9) { min-width: 100px; } /* Tgl Input */
    <?php endif; ?>
    
    /* Reset font untuk memastikan tidak miring */
    .table th, 
    .table td,
    .table th *,
    .table td * {
        font-style: normal !important;
    }
</style>

<div class="container-fluid">

    <div class="page-header">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-user-slash"></i> Assets Tanpa Penanggung Jawab
        </h1>
        <div class="total-badge">
            <i class="fas fa-boxes"></i> Total Asset Tanpa User: <span id="total-asset">0</span>
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
                            <label>Lokasi</label>
                            <select name="lokasi_id" id="filter-lokasi" class="form-control select2">
                                <option value="">-- Semua Lokasi --</option>
                                <?php
                                $qLokasi = mysqli_query($conn, "
                                    SELECT lokasi_id, lokasi_name, lokasi_lantai
                                    FROM tbl_lokasi 
                                    ORDER BY lokasi_name
                                ");
                                if ($qLokasi) {
                                    while ($lok = mysqli_fetch_assoc($qLokasi)) {
                                        $selected = (isset($_GET['lokasi_id']) && $_GET['lokasi_id'] == $lok['lokasi_id']) ? 'selected' : '';
                                        echo "<option value='{$lok['lokasi_id']}' {$selected}>{$lok['lokasi_name']} ({$lok['lokasi_lantai']})</option>";
                                    }
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
                <i class="fas fa-list"></i> Daftar Asset Tanpa Penanggung Jawab
            </h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th width="80">Tools</th>
                            <th width="50">No</th>
                            <th width="70">Image</th>
                            <th width="150">Kode Asset</th>
                            <th width="200">Nama Asset</th>
                            <th width="150">Lokasi</th>
                            <th width="100">Kondisi</th>
                            <th width="100">Qty</th>
                            <?php if ($show_price): ?>
                            <th width="120">Harga</th>
                            <?php endif; ?>
                            <th width="100">Tgl Input</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        $total_asset = 0;
                        
                        $filter_kategori = isset($_GET['kategori_id']) && !empty($_GET['kategori_id']) 
                            ? mysqli_real_escape_string($conn, $_GET['kategori_id']) 
                            : '';
                        $filter_lokasi = isset($_GET['lokasi_id']) && !empty($_GET['lokasi_id']) 
                            ? mysqli_real_escape_string($conn, $_GET['lokasi_id']) 
                            : '';
                        
                        // PERBAIKAN: Query untuk menampilkan asset tanpa karyawan (karyawan_id IS NULL)
                        // dan juga asset yang tidak memiliki gambar (opsional - sesuai kebutuhan)
                        $query = "SELECT 
                                p.primary_id,
                                p.primary_qty,
                                p.primary_image,
                                p.timestamp,
                                a.assets_id,
                                a.assets_kode,
                                a.assets_name,
                                a.assets_price,
                                l.lokasi_id,
                                l.lokasi_name,
                                l.lokasi_lantai,
                                kond.kondisi_id,
                                kond.kondisi_name
                            FROM tbl_primary p
                            INNER JOIN tbl_assets a ON p.assets_id = a.assets_id
                            LEFT JOIN tbl_lokasi l ON p.lokasi_id = l.lokasi_id
                            LEFT JOIN tbl_kondisi kond ON p.kondisi_id = kond.kondisi_id
                            WHERE (p.karyawan_id IS NULL OR p.karyawan_id = 0)
                            OR (p.primary_image IS NULL OR p.primary_image = '')
                        ";
                        
                        // Filter kategori
                        if (!empty($filter_kategori)) {
                            $query .= " AND a.kategori_id = '$filter_kategori'";
                        }
                        
                        // Filter lokasi
                        if (!empty($filter_lokasi)) {
                            $query .= " AND p.lokasi_id = '$filter_lokasi'";
                        }
                        
                        $query .= " ORDER BY a.assets_date DESC";
                        
                        $result = mysqli_query($conn, $query);
                        
                        if ($result && mysqli_num_rows($result) > 0):
                            $total_asset = mysqli_num_rows($result);
                            while ($row = mysqli_fetch_assoc($result)):
                                // Format harga
                                $harga = !empty($row['assets_price']) && $row['assets_price'] > 0 
                                    ? 'Rp ' . number_format($row['assets_price'], 0, ',', '.') 
                                    : '-';
                                
                                // Format tanggal
                                $tanggal_input = (!empty($row['timestamp']) && $row['timestamp'] != '0000-00-00 00:00:00') 
                                    ? date('d/m/Y H:i', strtotime($row['timestamp'])) 
                                    : '-';
                                
                                // Lokasi
                                $lokasi = $row['lokasi_name'] ?? '-';
                                if (!empty($row['lokasi_lantai'])) {
                                    $lokasi .= ' (' . $row['lokasi_lantai'] . ')';
                                }
                                
                                // Kode asset display
                                $assets_kode_display = !empty($row['assets_kode']) 
                                    ? "<strong>{$row['assets_kode']}</strong>" 
                                    : '<span class="text-muted">-</span>';
                        ?>
                        <tr>
                            <td class="tools-column">
                                <div class="d-flex justify-content-center gap-2">
                                    <a href="edit.php?id=<?= $row['primary_id'] ?>" 
                                       class="btn btn-sm btn-primary" 
                                       title="Assign Penanggung Jawab">
                                        <i class="fas fa-user-plus"></i>
                                    </a>
                                    <a href="detail.php?id=<?= $row['primary_id'] ?>" 
                                       class="btn btn-sm btn-info" 
                                       title="Lihat Detail">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                             </div>
                            <td class="text-center"><?= $no++ ?> </div>
                            <td class="text-center">
                                <?php if (!empty($row['primary_image'])): ?>
                                    <a href="../master/img/assets/<?= $row['primary_image'] ?>" target="_blank">
                                        <img src="../master/img/assets/<?= $row['primary_image'] ?>" 
                                            style="width:40px;height:40px;object-fit:cover;border-radius:4px;border:1px solid #ddd;"
                                            onerror="this.src='../master/img/no-image.png'">
                                    </a>
                                <?php else: ?>
                                    <span class="badge badge-secondary">No Image</span>
                                <?php endif; ?>
                             </div>
                            <td><?= $assets_kode_display ?> </div>
                            <td><?= htmlspecialchars($row['assets_name']) ?> </div>
                            <td><?= htmlspecialchars($lokasi) ?> </div>
                            <td class="text-center">
                                <span class="badge badge-info"><?= htmlspecialchars($row['kondisi_name'] ?? '-') ?></span>
                             </div>
                            <td class="text-center">
                                <span class="badge badge-primary"><?= number_format($row['primary_qty']) ?> unit</span>
                             </div>
                            <?php if ($show_price): ?>
                            <td class="text-right"><?= $harga ?> </div>
                            <?php endif; ?>
                            <td class="text-center"><?= $tanggal_input ?> </div>
                         </tr>
                        <?php 
                            endwhile;
                        else: 
                        ?>
                        <tr>
                            <td colspan="<?= $show_price ? '10' : '9' ?>" class="text-center text-success">
                                <i class="fas fa-check-circle"></i> Semua asset sudah memiliki penanggung jawab.
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
            { "orderable": false, "targets": [0, 2] }
        ]
    });
    
    // Update total asset
    var totalAsset = <?= $total_asset ?>;
    $('#total-asset').text(totalAsset);
});
</script>

</body>
</html>