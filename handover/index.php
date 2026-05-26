<?php
include '../auth/auth.php';
allowRole([1,2,3]); // Admin, Operator, User

include '../config/koneksi.php';

/* =====================
   AMBIL DATA USER LOGIN
===================== */
$user_id = $_SESSION['user_id'];
$user_level = $_SESSION['user_level'];
$dep_id = $_SESSION['dep_id'] ?? 0;

// Tentukan filter berdasarkan level user
if (in_array($user_level, [1, 2])) {
    // Admin/Operator: bisa melihat semua draft asset
    $filterUser = "";
    $is_admin = true;
} else {
    // User biasa: hanya melihat asset dari departemen yang sama
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

<style>
    .badge-waiting {
        background-color: #f6c23e;
        color: #856404;
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        display: inline-block;
        white-space: nowrap;
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
    
    /* Table styling untuk scroll horizontal */
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
    
    /* Kolom spesifikasi boleh wrap karena panjang */
    #dataTable tbody td.spec-col {
        white-space: normal;
        min-width: 200px;
        max-width: 250px;
    }
    
    /* Scrollbar styling */
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
    
    /* Badge untuk menampilkan role */
    .role-badge {
        background-color: #4e73df;
        color: white;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        margin-left: 10px;
    }
</style>

<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-pause-circle"></i> Draft Assets
            </h1>
            <?php if (!$is_admin): ?>
            <small class="text-muted">
                <i class="fas fa-filter"></i> Menampilkan draft asset dari departemen Anda
            </small>
            <?php else: ?>
            <small class="text-muted">
                <i class="fas fa-globe"></i> Menampilkan semua draft asset (Admin/Operator)
            </small>
            <?php endif; ?>
        </div>
        <a href="tambah.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
            <i class="fas fa-plus fa-sm text-white-50"></i> Ajukan Asset Baru
        </a>
    </div>

    <!-- Data Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-list"></i> Daftar Draft Asset (Menunggu Approval)
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
                            <th width="150">Department</th>
                            <th width="150">Penanggung Jawab</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        
                        // Query untuk asset yang belum approved (assets_kode NULL)
                        // Dengan filter berdasarkan level user
                        $query = "
                            SELECT 
                                a.assets_id,
                                a.assets_kode,
                                a.assets_name,
                                a.assets_spec,
                                a.assets_target,
                                a.assets_cap,
                                a.assets_uom,
                                a.assets_life,
                                a.assets_note,
                                a.assets_qty as total_qty,
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
                                
                                (SELECT kond.kondisi_name 
                                 FROM tbl_primary p2 
                                 LEFT JOIN tbl_kondisi kond ON p2.kondisi_id = kond.kondisi_id 
                                 WHERE p2.assets_id = a.assets_id 
                                 LIMIT 1) as kondisi_name,
                                
                                (SELECT primary_image 
                                 FROM tbl_primary 
                                 WHERE assets_id = a.assets_id 
                                 LIMIT 1) as sample_image
                                
                            FROM tbl_assets a
                            INNER JOIN tbl_primary p ON a.assets_id = p.assets_id
                            LEFT JOIN tbl_kategori kat ON a.kategori_id = kat.kategori_id
                            LEFT JOIN tbl_type t ON a.type_id = t.type_id
                            LEFT JOIN tbl_supplier s ON a.supplier_id = s.supplier_id
                            LEFT JOIN tbl_produsen pr ON a.produsen_id = pr.produsen_id
                            LEFT JOIN tbl_merk m ON a.merk_id = m.merk_id
                            LEFT JOIN tbl_karyawan kar ON p.karyawan_id = kar.karyawan_id
                            LEFT JOIN tbl_dep d ON kar.dep_id = d.dep_id
                            WHERE (a.assets_kode IS NULL OR a.assets_kode = '')
                            $filterUser
                            GROUP BY a.assets_id
                            ORDER BY a.assets_id DESC
                        ";
                        
                        $result = mysqli_query($conn, $query);
                        
                        if (!$result) {
                            echo "<tr><td colspan='18' class='text-center text-danger'>Error query: " . mysqli_error($conn) . "</td></tr>";
                        }

                        while ($row = mysqli_fetch_assoc($result)) {
                            $assets_id = $row['assets_id'];
                            
                            // Ambil detail lokasi dengan query terpisah
                            $qLokasi = mysqli_query($conn, "
                                SELECT 
                                    l.lokasi_name,
                                    l.lokasi_lantai,
                                    SUM(p.primary_qty) as total_qty
                                FROM tbl_primary p
                                INNER JOIN tbl_lokasi l ON p.lokasi_id = l.lokasi_id
                                WHERE p.assets_id = $assets_id
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
                            
                            // Status menunggu approval
                            $assets_kode_display = '<span class="badge-waiting"><i class="fas fa-clock"></i> Menunggu Approval</span>';
                            $kategori_display = '<span class="badge-waiting"><i class="fas fa-clock"></i> Menunggu Approval</span>';
                            $kondisi_display = '<span class="badge-waiting"><i class="fas fa-clock"></i> Menunggu Approval</span>';
                            
                            // Tools: View, Edit, Delete
                            // Untuk admin/operator: semua tools tersedia
                            // Untuk user: hanya untuk asset miliknya (sudah difilter oleh query)
                            $tools = '
                                <div class="tools-group">
                                    <a href="detail.php?id=' . $assets_id . '" class="btn btn-sm btn-info" title="Lihat Detail">
                                        <i class="fas fa-eye"></i>
                                    </a>';
                            
                            // Tombol Edit dan Delete hanya untuk admin/operator atau pemilik asset
                            if ($is_admin) {
                                $tools .= '
                                    <a href="edit2.php?id=' . $assets_id . '" class="btn btn-sm btn-warning" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>';
                            }else {
                                $tools .= '
                                    <a href="edit.php?id=' . $assets_id . '" class="btn btn-sm btn-warning" title="Edit (User)">
                                        <i class="fas fa-edit"></i>
                                    </a>';
                            }
                            
                            $tools .= '
                                    <a href="proses.php?hapus_assets=1&id=' . $assets_id . '" 
                                       class="btn btn-sm btn-danger" title="Hapus"
                                       onclick="return confirm(\'Hapus data asset ini? Semua unit akan terhapus.\')">
                                        <i class="fas fa-trash"></i>
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
                                <td>" . htmlspecialchars($row['assets_name']) . "</div>
                                <td>{$kategori_display}</div>
                                <td class='spec-col'>" . (!empty($row['assets_spec']) ? htmlspecialchars($row['assets_spec']) : '-') . "</div>
                                <td>" . (!empty($row['assets_target']) ? htmlspecialchars($row['assets_target']) : '-') . "</div>
                                <td>" . (!empty($row['assets_cap']) ? $row['assets_cap'] . ' ' . ($row['assets_uom'] ?? '') : '-') . "</div>
                                <td>{$kondisi_display}</div>
                                <td>" . ($row['type_name'] ?? '-') . "</div>
                                <td>" . ($row['supplier_name'] ?? '-') . "</div>
                                <td>" . (!empty($row['produsen_region']) ? $row['produsen_region'] . ' (' . $row['produsen_code'] . ')' : '-') . "</div>
                                <td>" . ($row['merk_name'] ?? '-') . "</div>
                                <td class='text-center total-qty'>{$row['total_qty']} unit</div>
                                <td>{$detail_lokasi_str}</div>
                                <td>" . ($row['dep_name'] ?? '-') . "</div>
                                <td>" . ($row['karyawan_name'] ?? '-') . "</div>
                              </tr>";
                            $no++;
                        }
                        
                        if ($no == 1) {
                            echo "<tr><td colspan='18' class='text-center text-muted py-5'>
                                    <i class='fas fa-inbox fa-3x mb-3'></i>
                                    <p class='mb-0'>Belum ada draft asset</p>
                                    <small>Silakan ajukan asset baru dengan tombol di atas</small>
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

<script>
$(document).ready(function() {
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
});
</script>

</body>
</html>