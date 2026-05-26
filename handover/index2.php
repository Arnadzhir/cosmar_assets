<?php
include '../auth/auth.php';
allowRole([1,2]); // Admin dan Operator

include '../config/koneksi.php';

/* =====================
   AMBIL DATA USER LOGIN
===================== */
$user_id    = $_SESSION['user_id'];
$user_level = $_SESSION['user_level'];

include '../menu/header.php';
include '../menu/sidebar.php';
include '../menu/topbar.php';
?>

<style>
    .badge-warning-custom {
        background-color: #f6c23e;
        color: #856404;
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        display: inline-block;
    }
    .lokasi-list {
        font-size: 12px;
        line-height: 1.4;
    }
    .lokasi-list small {
        display: block;
        color: #666;
    }
    .table-hover tbody tr:hover {
        background-color: #f8f9fc;
    }
    .btn-group-tools {
        display: flex;
        gap: 5px;
        justify-content: center;
        flex-wrap: nowrap;
    }
    .btn-sm {
        padding: 0.2rem 0.4rem;
        font-size: 0.7rem;
    }
</style>

<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-check-circle"></i> Approval Assets
        </h1>
    </div>

    <!-- DataTales -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-list"></i> Daftar Assets Menunggu Approval
            </h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                    <thead class="thead-light">
                        <tr>
                            <th width="80">Tools</th>
                            <th width="40">No</th>
                            <th width="120">Kode Assets</th>
                            <th width="200">Nama Assets</th>
                            <th width="80">Total Qty</th>
                            <th width="250">Detail Lokasi (Total per Lokasi)</th>
                            <th width="150">Pengusul</th>
                            <th width="150">Departemen</th>
                            <th width="150">Tanggal Pengajuan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // PERBAIKAN: Query untuk assets yang belum memiliki kode
                        // Menggunakan tbl_karyawan bukan tbl_user
                        $query = "
                            SELECT 
                                a.assets_id,
                                a.assets_kode,
                                a.assets_name,
                                a.assets_qty,
                                a.assets_date,
                                a.assets_price,
                                a.assets_life,
                                a.assets_model,
                                a.assets_spec,
                                a.assets_target,
                                a.assets_cap,
                                a.assets_uom,
                                a.assets_note,
                                a.timestamp,
                                a.kategori_id,
                                a.merk_id,
                                a.type_id,
                                a.supplier_id,
                                a.produsen_id,
                                
                                kat.kategori_name,
                                kat.kategori_line,
                                
                                COUNT(DISTINCT p.lokasi_id) as jumlah_lokasi,
                                
                                kar.karyawan_id,
                                kar.karyawan_name,
                                
                                d.dep_id,
                                d.dep_name,
                                d.dep_code
                                
                            FROM tbl_assets a
                            INNER JOIN tbl_primary p ON a.assets_id = p.assets_id
                            INNER JOIN tbl_lokasi l ON p.lokasi_id = l.lokasi_id
                            INNER JOIN tbl_karyawan kar ON p.karyawan_id = kar.karyawan_id
                            INNER JOIN tbl_dep d ON kar.dep_id = d.dep_id
                            LEFT JOIN tbl_kategori kat ON a.kategori_id = kat.kategori_id
                            WHERE a.assets_kode IS NULL 
                                OR a.assets_kode = ''
                            GROUP BY a.assets_id
                            ORDER BY a.assets_id DESC
                        ";
                        
                        $result = mysqli_query($conn, $query);

                        if (!$result) {
                            echo "<tr><td colspan='9' class='text-center text-danger'>Error query: " . mysqli_error($conn) . "</td></tr>";
                        } elseif (mysqli_num_rows($result) > 0) {
                            $no = 1;
                            while ($row = mysqli_fetch_assoc($result)) {
                                
                                // Ambil detail lokasi dengan total per lokasi menggunakan query terpisah
                                $assets_id = $row['assets_id'];
                                $qLokasi = mysqli_query($conn, "
                                    SELECT 
                                        l.lokasi_name,
                                        l.lokasi_lantai,
                                        SUM(p.primary_qty) as total_qty
                                    FROM tbl_primary p
                                    INNER JOIN tbl_lokasi l ON p.lokasi_id = l.lokasi_id
                                    WHERE p.assets_id = $assets_id
                                    GROUP BY p.lokasi_id, l.lokasi_name, l.lokasi_lantai
                                    ORDER BY l.lokasi_name
                                ");
                                
                                $detail_lokasi = [];
                                while ($lokasi = mysqli_fetch_assoc($qLokasi)) {
                                    $lokasi_display = $lokasi['lokasi_name'];
                                    if (!empty($lokasi['lokasi_lantai'])) {
                                        $lokasi_display .= ' (Lt.' . $lokasi['lokasi_lantai'] . ')';
                                    }
                                    $detail_lokasi[] = '<span class="badge badge-light">' . htmlspecialchars($lokasi_display) . ':</span> ' . $lokasi['total_qty'] . ' pcs';
                                }
                                
                                $detail_lokasi_str = !empty($detail_lokasi) 
                                    ? implode('<br>', $detail_lokasi) 
                                    : '<span class="text-muted">-</span>';
                                
                                // Warna background berbeda untuk assets dengan multiple lokasi
                                $row_class = ($row['jumlah_lokasi'] > 1) ? 'table-info' : '';
                                
                                // Tampilkan model dan spesifikasi
                                $asset_info = htmlspecialchars($row['assets_name']);
                                if (!empty($row['assets_model'])) {
                                    $asset_info .= '<br><small class="text-muted">Model: ' . htmlspecialchars($row['assets_model']) . '</small>';
                                }
                                if (!empty($row['assets_spec'])) {
                                    $asset_info .= '<br><small class="text-muted">' . substr(htmlspecialchars($row['assets_spec']), 0, 50) . '...</small>';
                                }
                                ?>
                                <tr class="<?= $row_class ?>">
                                    <!-- TOOLS DI DEPAN -->
                                    <td class='text-center'>
                                        <div class='btn-group-tools'>
                                            <a href='detail.php?id=<?= $row['assets_id'] ?>' class='btn btn-sm btn-info' title='Lihat Detail'>
                                                <i class='fas fa-eye'></i>
                                            </a>                                            
                                            <a href='edit2.php?id=<?= $row['assets_id'] ?>' class='btn btn-sm btn-warning' title='Proses Approval'>
                                                <i class='fas fa-edit'></i>
                                            </a>
                                            <a href='proses2.php?hapus=1&id=<?= $row['assets_id'] ?>' 
                                                class='btn btn-sm btn-danger'
                                                onclick="return confirm('Yakin ingin menghapus data ini?')">
                                                <i class='fas fa-trash'></i>
                                            </a>
                                        </div>    
                                     </div>
                                    
                                    <td class="text-center"><?= $no++ ?> </div>
                                    <td>
                                        <span class="badge-warning-custom">
                                            <i class="fas fa-clock"></i> Menunggu
                                        </span>
                                     </div>
                                    <td>
                                        <strong><?= htmlspecialchars($row['assets_name']) ?></strong>
                                        <?php if (!empty($row['assets_model'])): ?>
                                            <br><small class="text-muted"><?= htmlspecialchars($row['assets_model']) ?></small>
                                        <?php endif; ?>
                                     </div>
                                    <td class="text-center">
                                        <span class="badge badge-primary badge-pill" style="font-size: 14px;">
                                            <?= $row['assets_qty'] ?> pcs
                                        </span>
                                        <br>
                                        <small class="text-muted"><?= $row['jumlah_lokasi'] ?> lokasi</small>
                                     </div>
                                    <td class="lokasi-list">
                                        <?= $detail_lokasi_str ?>
                                     </div>
                                    <td><?= htmlspecialchars($row['karyawan_name'] ?? '-') ?> </div>
                                    <td><?= htmlspecialchars($row['dep_name'] ?? '-') ?> (<?= $row['dep_code'] ?? '-' ?>)</div>
                                    <td>
                                        <?php 
                                        if (!empty($row['assets_date']) && $row['assets_date'] != '0000-00-00') {
                                            echo date('d/m/Y', strtotime($row['assets_date']));
                                        } else {
                                            echo '<span class="text-muted">-</span>';
                                        }
                                        ?>
                                        <br>
                                        <small class="text-muted">Pengajuan: <?= date('d/m/Y H:i', strtotime($row['timestamp'])) ?></small>
                                     </div>
                                </tr>
                                <?php
                            }
                        } else {
                            ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    <i class="fas fa-check-circle fa-3x mb-3 text-success"></i>
                                    <p class="mb-0">Semua assets sudah memiliki kode.</p>
                                    <p class="text-muted">Tidak ada data menunggu approval.</p>
                                 </div>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Informasi Tambahan -->
    <div class="row">
        <div class="col-md-12">
            <div class="card bg-light mb-3">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="font-weight-bold text-primary">
                                <i class="fas fa-info-circle"></i> Keterangan:
                            </h6>
                            <ul class="mb-0">
                                <li><span class="badge badge-info" style="background-color: #d1ecf1;">&nbsp;&nbsp;&nbsp;&nbsp;</span> Assets dengan multiple lokasi (lebih dari 1 lokasi)</li>
                                <li><span class="badge-warning-custom">Menunggu</span> Assets yang belum memiliki kode</li>
                                <li><i class="fas fa-eye text-info"></i> Lihat detail lengkap per unit</li>
                                <li><i class="fas fa-edit text-warning"></i> Proses approval (isi kode, kategori, harga, dll)</li>
                                <li><i class="fas fa-trash text-danger"></i> Tolak / Hapus data</li>
                            </ul>
                        </div>
                        <div class="col-md-6 text-right">
                            <p class="mb-0 text-muted">
                                <i class="fas fa-clock"></i> Data diurutkan dari yang terbaru
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Script untuk DataTable -->
<script>
$(document).ready(function() {
    // Inisialisasi DataTable
    if ($('#dataTable').length && !$.fn.DataTable.isDataTable('#dataTable')) {
        $('#dataTable').DataTable({
            "pageLength": 25,
            "lengthMenu": [10, 25, 50, 100],
            "order": [[8, "desc"]], // Urut berdasarkan kolom Tanggal Pengajuan (index ke-8)
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
                { "orderable": false, "targets": 0 } // Kolom Tools tidak bisa diurutkan
            ]
        });
    }
});
</script>

<?php include '../menu/footer.php'; ?>

</body>
</html>