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
            <i class="fas fa-users"></i> Data User Akses
        </h1>
        <?php if (isset($_SESSION['user_level']) && ($_SESSION['user_level'] == 1)) : ?>
        <a href="tambah.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
            <i class="fas fa-plus fa-sm text-white-50"></i> Tambah User
        </a>
        <?php endif; ?>
    </div>

    <!-- DataTales Example -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-list"></i> Daftar User Akses Sistem
            </h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th width="50">No</th>
                            <?php if (isset($_SESSION['user_level']) && ($_SESSION['user_level'] == 1)) : ?>
                            <th width="100">Tools</th>
                            <?php endif; ?>
                            <th width="70">Profile</th>
                            <th width="100">Level</th>
                            <th width="100">User ID</th>
                            <th width="150">Nama User</th>
                            <th width="150">Departemen</th>
                            <th width="150">Email</th>
                            <th width="150">No. Telepon</th>
                            <th width="100">Gender</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $no = 1;
                    $q = mysqli_query($conn, "
                        SELECT 
                            u.*,
                            d.dep_code,
                            d.dep_name
                        FROM tbl_user u
                        LEFT JOIN tbl_dep d ON u.dep_id = d.dep_id
                        ORDER BY u.user_id DESC
                    ");

                    if ($q && mysqli_num_rows($q) > 0):
                    while ($row = mysqli_fetch_assoc($q)) {
                        // Format nomor ke 62 untuk WhatsApp
                        $no_hp = preg_replace('/[^0-9]/', '', $row['user_no'] ?? ''); // hapus selain angka

                        if (substr($no_hp, 0, 1) == '0') {
                            $no_hp = '62' . substr($no_hp, 1);
                        }
                        
                        // Level badge
                        $level_badge = '';
                        if ($row['user_level'] == 1) {
                            $level_badge = '<span class="badge badge-info">Administrator</span>';
                        } elseif ($row['user_level'] == 2) {
                            $level_badge = '<span class="badge badge-success">Operator</span>';
                        } elseif ($row['user_level'] == 3) {
                            $level_badge = '<span class="badge badge-secondary">User</span>';
                        } else {
                            $level_badge = '<span class="badge badge-danger">Unknown</span>';
                        }
                        
                        echo "<tr>";
                        echo "<td class='text-center'>{$no}</div>";
                        
                        if ($_SESSION['user_level'] == 1) {
                            echo "<td class='text-center'>
                                    <div class='d-flex justify-content-center align-items-center' style='gap:5px; white-space:nowrap;'>
                                        <a href='detail.php?id={$row['user_id']}' class='btn btn-sm btn-info' title='Lihat Detail'>
                                            <i class='fas fa-eye'></i>
                                        </a>                                             
                                        <a href='edit.php?id={$row['user_id']}' class='btn btn-sm btn-warning' title='Edit'>
                                            <i class='fas fa-edit'></i>
                                        </a>
                                        <a href='proses.php?hapus=1&id={$row['user_id']}' 
                                           class='btn btn-sm btn-danger' title='Hapus'
                                           onclick=\"return confirm('Hapus user ini? User akan kehilangan akses ke sistem.')\">
                                            <i class='fas fa-trash'></i>
                                        </a>
                                    </div>
                                 </div>";
                        }
                        
                        echo "<td class='text-center'>" . 
                                (!empty($row['user_image']) 
                                    ? "<a href='../master/img/user/{$row['user_image']}' target='_blank'>
                                        <img src='../master/img/user/{$row['user_image']}' 
                                            style='width:50px;height:50px;object-fit:cover;border-radius:6px;border:1px solid #ddd;'>
                                      </a>"
                                    : "<span class='badge badge-secondary'>No Image</span>"
                                ) . 
                            "</div>";
                        echo "<td class='text-center'>{$level_badge}</div>";
                        echo "<td><strong>" . htmlspecialchars($row['user_id']) . "</strong></div>";
                        echo "<td>" . htmlspecialchars($row['user_name']) . "</div>";
                        echo "<td>" . htmlspecialchars($row['dep_name'] ?? '-') . " (" . htmlspecialchars($row['dep_code'] ?? '-') . ")</div>";
                        echo "<td>
                                <a href='mailto:{$row['user_mail']}' class='text-primary'>
                                    <i class='fas fa-envelope'></i> " . htmlspecialchars($row['user_mail'] ?? '-') . "
                                </a>
                              </div>";
                        echo "<td>
                                <a href='https://wa.me/{$no_hp}' target='_blank' class='text-success'>
                                    <i class='fab fa-whatsapp'></i> " . htmlspecialchars($row['user_no'] ?? '-') . "
                                </a>
                              </div>";
                        echo "<td>" . htmlspecialchars($row['user_gender'] ?? '-') . "</div>";
                        echo "</tr>";
                        $no++;
                    }
                    else:
                    ?>
                        <tr>
                            <td colspan="<?= ($_SESSION['user_level'] == 1) ? '10' : '9' ?>" class="text-center text-muted py-5">
                                <i class="fas fa-inbox fa-3x mb-3"></i>
                                <p class="mb-0">Belum ada data user</p>
                                <small>Silakan tambah user baru</small>
                            </td>
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
        "order": [[3, "asc"]],
        "language": {
            "lengthMenu": "Tampilkan _MENU_ data per halaman",
            "zeroRecords": "Tidak ada data",
            "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
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