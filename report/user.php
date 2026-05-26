<?php
include '../auth/auth.php';
allowRole([1,2]);

include '../config/koneksi.php';

/* =====================
   AMBIL DATA USER LOGIN
===================== */
$user_id    = $_SESSION['user_id'];
$user_level = $_SESSION['user_level'];
$dep_id     = $_SESSION['dep_id'] ?? 0;

// Daftar tab manual + IDLE
$daftar_tab = [
    'ENG' => 'Engineering',
    'FAT' => 'Finance & Accounting',
    'IT' => 'Information Technology',
    'HRGA' => 'Human Resources & General Affairs',
    'PROD' => 'Production',
    'PPIC' => 'Production Planning & Inventory Control',
    'PRC' => 'Purchasing & Packaging Development',
    'QAQC' => 'Quality Assurance & Quality Control',
    'RND' => 'Research & Development',
    'MKT' => 'Sales & Marketing',
    'WH' => 'Warehouse',
    'IDLE' => 'Assets Tanpa Pemilik'
];

// Hitung total karyawan per departemen (menggunakan tbl_karyawan)
$user_counts = [];
foreach (array_keys($daftar_tab) as $dep_code) {
    if ($dep_code == 'IDLE') {
        $q_count = mysqli_query($conn, "
            SELECT COUNT(DISTINCT p.primary_id) as total
            FROM tbl_primary p
            WHERE p.karyawan_id IS NULL
        ");
    } else {
        $q_count = mysqli_query($conn, "
            SELECT COUNT(DISTINCT k.karyawan_id) as total
            FROM tbl_karyawan k
            INNER JOIN tbl_dep d ON k.dep_id = d.dep_id
            WHERE d.dep_code = '$dep_code'
        ");
    }
    $count = mysqli_fetch_assoc($q_count);
    $user_counts[$dep_code] = $count['total'] ?? 0;
}

include '../menu/header.php';
include '../menu/sidebar.php';
include '../menu/topbar.php';
?>

<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-users"></i> Laporan User Asset
        </h1>
    </div>

    <!-- Card Statistik -->
    <div class="row">
        <div class="col-md-3 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Karyawan</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php 
                                $total_user = mysqli_query($conn, "SELECT COUNT(*) as total FROM tbl_karyawan");
                                $tu = mysqli_fetch_assoc($total_user);
                                echo $tu['total'];
                                ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Total Assets</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php 
                                $total_assets = mysqli_query($conn, "SELECT SUM(assets_qty) as total FROM tbl_assets");
                                $ta = mysqli_fetch_assoc($total_assets);
                                echo number_format($ta['total'] ?? 0, 0, ',', '.');
                                ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-boxes fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Total Nilai</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php 
                                $total_nilai = mysqli_query($conn, "SELECT SUM(assets_price) as total FROM tbl_assets");
                                $tn = mysqli_fetch_assoc($total_nilai);
                                echo 'Rp ' . number_format($tn['total'] ?? 0, 0, ',', '.');
                                ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-money-bill fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Assets Idle</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php 
                                $total_idle = mysqli_query($conn, "
                                    SELECT SUM(primary_qty) as total 
                                    FROM tbl_primary 
                                    WHERE karyawan_id IS NULL
                                ");
                                $ti = mysqli_fetch_assoc($total_idle);
                                echo number_format($ti['total'] ?? 0, 0, ',', '.');
                                ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-pause-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Nav Tabs Departemen + IDLE -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-list"></i> Daftar Karyawan per Departemen
            </h6>
        </div>
        <div class="card-body">
            
            <!-- Nav tabs -->
            <ul class="nav nav-tabs mb-3" id="depTab" role="tablist">
                <?php 
                $first = true;
                foreach ($daftar_tab as $dep_code => $dep_name): 
                    $total = $user_counts[$dep_code] ?? 0;
                    $tab_id = preg_replace('/[^a-zA-Z0-9]/', '', $dep_code);
                    
                    $tab_class = ($dep_code == 'IDLE') ? 'tab-idle' : '';
                ?>
                <li class="nav-item">
                    <a class="nav-link <?= $first ? 'active' : '' ?> <?= $tab_class ?>" 
                       id="tab-<?= $tab_id ?>" 
                       data-toggle="tab" 
                       href="#content-<?= $tab_id ?>" 
                       role="tab">
                        <?= $dep_code ?>
                        <span class="badge <?= ($dep_code == 'IDLE') ? 'badge-warning' : 'badge-primary' ?> ml-1">
                            <?= $total ?>
                        </span>
                    </a>
                </li>
                <?php 
                    $first = false;
                endforeach; 
                ?>
            </ul>

            <!-- Tab content -->
            <div class="tab-content" id="depTabContent">
                <?php 
                $first = true;
                foreach ($daftar_tab as $dep_code => $dep_name): 
                    $tab_id = preg_replace('/[^a-zA-Z0-9]/', '', $dep_code);
                ?>
                <div class="tab-pane fade <?= $first ? 'show active' : '' ?>" 
                     id="content-<?= $tab_id ?>" 
                     role="tabpanel">
                    
                    <h6 class="text-primary mb-3">
                        <i class="fas <?= ($dep_code == 'IDLE') ? 'fa-pause-circle' : 'fa-building' ?>"></i> 
                        <?= htmlspecialchars($dep_name) ?>
                    </h6>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th>No</th>
                                    <th>Nama Karyawan</th>
                                    <th>ID Karyawan</th>
                                    <th>Jenis Kelamin</th>
                                    <th>No. Telp</th>
                                    <th>Total Assets</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                if ($dep_code == 'IDLE') {
                                    // Tampilkan assets tanpa pemilik
                                    $q_idle = mysqli_query($conn, "
                                        SELECT 
                                            p.primary_id,
                                            p.primary_qty,
                                            a.assets_kode,
                                            a.assets_name,
                                            a.assets_model,
                                            a.assets_spec,
                                            kond.kondisi_name,
                                            kat.kategori_name,
                                            l.lokasi_name,
                                            l.lokasi_lantai
                                        FROM tbl_primary p
                                        INNER JOIN tbl_assets a ON p.assets_id = a.assets_id
                                        LEFT JOIN tbl_kondisi kond ON p.kondisi_id = kond.kondisi_id
                                        LEFT JOIN tbl_kategori kat ON a.kategori_id = kat.kategori_id
                                        LEFT JOIN tbl_lokasi l ON p.lokasi_id = l.lokasi_id
                                        WHERE p.karyawan_id IS NULL
                                        ORDER BY a.assets_name ASC
                                    ");
                                    
                                    $no = 1;
                                    if ($q_idle && mysqli_num_rows($q_idle) > 0):
                                        while($idle = mysqli_fetch_assoc($q_idle)):
                                ?>
                                <tr>
                                    <td class="text-center"><?= $no++ ?> </div>
                                    <td colspan="2">
                                        <strong><?= htmlspecialchars($idle['assets_kode']) ?></strong>
                                        <br>
                                        <small class="text-muted"><?= htmlspecialchars($idle['assets_name']) ?></small>
                                        <?php if (!empty($idle['assets_model'])): ?>
                                            <br><small class="text-muted">Model: <?= htmlspecialchars($idle['assets_model']) ?></small>
                                        <?php endif; ?>
                                     </div>
                                    <td><?= htmlspecialchars($idle['kategori_name'] ?? '-') ?> </div>
                                    <td><?= htmlspecialchars($idle['assets_spec'] ?? '-') ?> </div>
                                    <td><?= htmlspecialchars($idle['lokasi_name'] ?? '-') ?> <?= !empty($idle['lokasi_lantai']) ? '(' . $idle['lokasi_lantai'] . ')' : '' ?> </div>
                                    <td class="text-center">
                                        <span class="badge badge-warning">
                                            <?= $idle['primary_qty'] ?> pcs
                                        </span>
                                     </div>
                                    <td class="text-center">
                                        <a href="../primary/detail.php?id=<?= $idle['primary_id'] ?>" 
                                           class="btn btn-sm btn-info" target="_blank">
                                            <i class="fas fa-eye"></i> Detail
                                        </a>
                                     </div>
                                </tr>
                                <?php 
                                        endwhile;
                                    else:
                                ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-3">
                                        <i class="fas fa-check-circle fa-2x mb-2 text-success"></i>
                                        <p>Tidak ada assets tanpa pemilik</p>
                                     </div>
                                </tr>
                                <?php 
                                    endif;
                                    
                                } else {
                                    // PERBAIKAN: Tampilkan karyawan berdasarkan departemen (menggunakan tbl_karyawan)
                                    $q_users = mysqli_query($conn, "
                                        SELECT k.karyawan_id, k.karyawan_name, k.karyawan_no, k.karyawan_gender,
                                               d.dep_id, d.dep_name, d.dep_code
                                        FROM tbl_karyawan k
                                        INNER JOIN tbl_dep d ON k.dep_id = d.dep_id
                                        WHERE d.dep_code = '$dep_code'
                                        ORDER BY k.karyawan_name ASC
                                    ");
                                    
                                    $no = 1;
                                    if ($q_users && mysqli_num_rows($q_users) > 0):
                                        while($karyawan = mysqli_fetch_assoc($q_users)):
                                            
                                            // Hitung total assets yang dimiliki karyawan ini
                                            $q_total = mysqli_query($conn, "
                                                SELECT 
                                                    COUNT(DISTINCT p.primary_id) as total_item,
                                                    SUM(p.primary_qty) as total_qty
                                                FROM tbl_primary p
                                                WHERE p.karyawan_id = {$karyawan['karyawan_id']}
                                            ");
                                            $total = mysqli_fetch_assoc($q_total);
                                            
                                            $total_item = $total['total_item'] ?? 0;
                                            $total_qty = $total['total_qty'] ?? 0;
                                ?>
                                <tr>
                                    <td class="text-center"><?= $no++ ?> </div>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <strong><?= htmlspecialchars($karyawan['karyawan_name']) ?></strong>
                                        </div>
                                     </div>
                                    <td><?= htmlspecialchars($karyawan['karyawan_no'] ?? '-') ?> </div>
                                    <td><?= htmlspecialchars($karyawan['karyawan_gender'] ?? '-') ?> </div>
                                    <td><?= htmlspecialchars($karyawan['karyawan_no'] ?? '-') ?> </div>
                                    <td class="text-center">
                                        <span class="badge badge-info">
                                            <?= $total_item ?> item
                                        </span>
                                        <br>
                                        <small class="text-muted"><?= $total_qty ?> pcs</small>
                                     </div>
                                    <td class="text-center">
                                        <a href="user_detail.php?id=<?= $karyawan['karyawan_id'] ?>" 
                                           class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i> Detail
                                        </a>
                                     </div>
                                </tr>
                                <?php 
                                        endwhile;
                                    else:
                                ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-3">
                                        <i class="fas fa-user-slash fa-2x mb-2"></i>
                                        <p>Tidak ada karyawan di departemen ini</p>
                                     </div>
                                </tr>
                                <?php 
                                    endif;
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php 
                    $first = false;
                endforeach; 
                ?>
            </div>

        </div>
    </div>

</div>

<?php include '../menu/footer.php'; ?>

<script>
$(document).ready(function() {
    // Inisialisasi DataTable untuk setiap tabel di tab
    $('.tab-pane table').DataTable({
        "pageLength": 10,
        "lengthMenu": [5, 10, 25, 50],
        "ordering": true,
        "language": {
            "lengthMenu": "Tampilkan _MENU_ data",
            "zeroRecords": "Tidak ada data",
            "info": "Menampilkan _START_ - _END_ dari _TOTAL_ data",
            "infoEmpty": "Tidak ada data",
            "search": "Cari:",
            "paginate": {
                "first": "Pertama",
                "last": "Terakhir",
                "next": "Selanjutnya",
                "previous": "Sebelumnya"
            }
        },
        "columnDefs": [
            { "orderable": false, "targets": [6] }
        ]
    });
    
    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
    });
});
</script>

<style>
    .nav-link.tab-idle {
        background-color: #fff3cd !important;
        color: #856404 !important;
    }
    .nav-link.tab-idle.active {
        background-color: #ffeeba !important;
        border-color: #ffc107 !important;
    }
    .badge-warning {
        background-color: #ffc107;
        color: #212529;
    }
</style>

</body>
</html>