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
            <i class="fas fa-users"></i> Data Karyawan
        </h1>
        <?php if (isset($_SESSION['user_level']) && ($_SESSION['user_level'] == 1)) : ?>
        <a href="tambah.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
            <i class="fas fa-plus fa-sm text-white-50"></i> Tambah Karyawan
        </a>
        <?php endif; ?>
    </div>

    <!-- DataTales Example -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-list"></i> Daftar Karyawan
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
                            <th width="70">Foto</th>
                            <th width="120">ID Karyawan</th>
                            <th width="200">Nama Karyawan</th>
                            <th width="120">Jenis Kelamin</th>
                            <th width="120">Level</th>
                            <th width="150">No. Telepon</th>
                            <th width="200">Departemen</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $no = 1;
                    // Query dari tbl_karyawan
                    $q = mysqli_query($conn, "
                        SELECT 
                            k.*,
                            d.dep_code,
                            d.dep_name
                        FROM tbl_karyawan k
                        LEFT JOIN tbl_dep d ON k.dep_id = d.dep_id
                        ORDER BY k.karyawan_id DESC
                    ");

                    if ($q && mysqli_num_rows($q) > 0):
                    while ($row = mysqli_fetch_assoc($q)) {
                        // Format nomor ke 62 untuk WhatsApp
                        $no_hp = preg_replace('/[^0-9]/', '', $row['karyawan_no'] ?? '');
                        if (!empty($no_hp) && substr($no_hp, 0, 1) == '0') {
                            $no_hp = '62' . substr($no_hp, 1);
                        }
                        
                        // Level badge
                        $karyawan_level = $row['karyawan_level'] ?? '';
                        if ($karyawan_level == 'Manager') {
                            $level_badge = '<span class="badge badge-danger">Manager</span>';
                        } elseif ($karyawan_level == 'Supervisor') {
                            $level_badge = '<span class="badge badge-warning">Supervisor</span>';
                        } elseif ($karyawan_level == 'Head') {
                            $level_badge = '<span class="badge badge-info">Head</span>';
                        } elseif ($karyawan_level == 'Leader') {
                            $level_badge = '<span class="badge badge-primary">Leader</span>';
                        } elseif ($karyawan_level == 'Staff') {
                            $level_badge = '<span class="badge badge-secondary">Staff</span>';
                        } else {
                            $level_badge = '<span class="badge badge-secondary">' . htmlspecialchars($karyawan_level) . '</span>';
                        }
                        
                        // Gender text
                        $gender_text = '';
                        if ($row['karyawan_gender'] == 'Male') {
                            $gender_text = 'Laki-laki';
                        } elseif ($row['karyawan_gender'] == 'Female') {
                            $gender_text = 'Perempuan';
                        } else {
                            $gender_text = '-';
                        }
                        
                        // Inisial untuk avatar
                        $initial = !empty($row['karyawan_name']) ? strtoupper(substr($row['karyawan_name'], 0, 1)) : '?';
                        
                        echo "<tr>";
                        echo "<td class='text-center'>{$no}</td>";
                        
                        if ($_SESSION['user_level'] == 1) {
                            echo "<td class='text-center'>
                                    <div class='d-flex justify-content-center align-items-center' style='gap:5px; white-space:nowrap;'>
                                        <a href='detail.php?id={$row['karyawan_id']}' class='btn btn-sm btn-info' title='Lihat Detail'>
                                            <i class='fas fa-eye'></i>
                                        </a>                                             
                                        <a href='edit.php?id={$row['karyawan_id']}' class='btn btn-sm btn-warning' title='Edit'>
                                            <i class='fas fa-edit'></i>
                                        </a>
                                        <a href='proses.php?hapus=1&id={$row['karyawan_id']}' 
                                           class='btn btn-sm btn-danger' title='Hapus'
                                           onclick=\"return confirm('Hapus data karyawan ini?')\">
                                            <i class='fas fa-trash'></i>
                                        </a>
                                    </div>
                                 </td>";
                        }
                        
                        echo "<td class='text-center'>
                                <div class='rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mx-auto' 
                                     style='width:40px;height:40px;font-size:16px;font-weight:bold;'>
                                    {$initial}
                                </div>
                              </td>";
                        echo "<td><strong>" . htmlspecialchars($row['karyawan_id']) . "</strong></td>";
                        echo "<td>" . htmlspecialchars($row['karyawan_name']) . "</td>";
                        echo "<td class='text-center'>{$gender_text}</td>";
                        echo "<td class='text-center'>{$level_badge}</td>";
                        echo "<td>";
                        if (!empty($no_hp)) {
                            echo "<a href='https://wa.me/{$no_hp}' target='_blank' class='text-success'>
                                    <i class='fab fa-whatsapp'></i> " . htmlspecialchars($row['karyawan_no']) . "
                                  </a>";
                        } else {
                            echo "-";
                        }
                        echo "</td>";
                        echo "<td>" . htmlspecialchars($row['dep_code'] ?? '-') . " - " . htmlspecialchars($row['dep_name'] ?? '-') . "</td>";
                        echo "</tr>";
                        $no++;
                    }
                    else:
                    ?>
                        <tr>
                            <td colspan="<?= ($_SESSION['user_level'] == 1) ? '9' : '8' ?>" class="text-center text-muted py-5">
                                <i class="fas fa-inbox fa-3x mb-3"></i>
                                <p class="mb-0">Belum ada data karyawan</p>
                                <small>Silakan tambah karyawan baru</small>
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
        "order": [[1, "asc"]],
        "scrollX": true,
        "autoWidth": false,
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