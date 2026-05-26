<?php
    session_start();
    if (!isset($_SESSION['login'])) {
        header("Location: ../auth/login.php");
        exit;
    }

    include '../config/koneksi.php';
    include '../menu/header.php';
    include '../menu/sidebar.php';
    include '../menu/topbar.php';
?>

<!-- SweetAlert2 CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-building"></i> Data Departemen
        </h1>
        <?php if (isset($_SESSION['user_level']) && ($_SESSION['user_level'] == 1)) : ?>
        <a href="tambah.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
            <i class="fas fa-plus fa-sm text-white-50"></i> Tambah Departemen
        </a>
        <?php endif; ?>
    </div>

    <!-- DataTales Example -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-list"></i> Data Departemen
            </h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th width="50">No</th>
                            <th width="100">Kode Departemen</th>
                            <th width="250">Nama Departemen</th>
                            <?php if (isset($_SESSION['user_level']) && ($_SESSION['user_level'] == 1)) : ?>
                            <th width="100">Tools</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $no = 1;
                    // PERBAIKAN: Hanya mengambil dep_code dan dep_name (GROUP BY untuk menghindari duplikasi)
                    $q = mysqli_query($conn, "
                        SELECT MIN(dep_id) as dep_id, dep_code, MIN(dep_name) as dep_name
                        FROM tbl_dep 
                        GROUP BY dep_code
                        ORDER BY dep_code ASC
                    ");

                    if ($q && mysqli_num_rows($q) > 0):
                    while ($row = mysqli_fetch_assoc($q)) {
                        echo "<tr>";
                        echo "<td class='text-center'>{$no}</div>";
                        echo "<td><strong>" . htmlspecialchars($row['dep_code']) . "</strong></div>";
                        echo "<td>" . htmlspecialchars($row['dep_name']) . "</div>";
                        
                        if ($_SESSION['user_level'] == 1) {
                            echo "<td class='text-center'>
                                    <div class='d-flex justify-content-center' style='gap:5px;'>
                                        <a href='edit.php?id={$row['dep_id']}' class='btn btn-sm btn-warning' title='Edit'>
                                            <i class='fas fa-edit'></i>
                                        </a>
                                        <a href='proses.php?hapus=1&id={$row['dep_id']}' 
                                           class='btn btn-sm btn-danger' title='Hapus'
                                           onclick=\"return confirm('Hapus data departemen ini? Data karyawan yang terkait akan kehilangan referensi.')\">
                                            <i class='fas fa-trash'></i>
                                        </a>
                                    </div>
                                 </div>";
                        }
                        echo "</tr>";
                        $no++;
                    }
                    else:
                    ?>
                        <tr>
                            <td colspan="<?= ($_SESSION['user_level'] == 1) ? '4' : '3' ?>" class="text-center text-muted py-5">
                                <i class="fas fa-inbox fa-3x mb-3"></i>
                                <p class="mb-0">Belum ada data departemen</p>
                                <small>Silakan tambah departemen baru</small>
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

<!-- DataTables JS -->
<script>
$(document).ready(function() {
    // Hancurkan DataTable jika sudah ada
    if ($.fn.DataTable.isDataTable('#dataTable')) {
        $('#dataTable').DataTable().destroy();
    }
    
    // Inisialisasi DataTable
    $('#dataTable').DataTable({
        "pageLength": 25,
        "lengthMenu": [10, 25, 50, 100],
        "order": [[1, "asc"]],
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
            { "orderable": false, "targets": [0] } // Kolom No tidak bisa diurutkan
        ]
    });
});
</script>

</body>
</html>