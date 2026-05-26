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

// Ambil ID karyawan dari URL
$target_karyawan_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($target_karyawan_id == 0) {
    $_SESSION['alert'] = [
        'type' => 'danger',
        'msg'  => 'ID karyawan tidak valid'
    ];
    header("Location: user.php");
    exit;
}

// PERBAIKAN: Ambil data karyawan dari tbl_karyawan
$q_karyawan = mysqli_query($conn, "
    SELECT k.*, d.dep_name, d.dep_code
    FROM tbl_karyawan k
    LEFT JOIN tbl_dep d ON k.dep_id = d.dep_id
    WHERE k.karyawan_id = $target_karyawan_id
");

if (!$q_karyawan || mysqli_num_rows($q_karyawan) == 0) {
    $_SESSION['alert'] = [
        'type' => 'danger',
        'msg'  => 'Karyawan tidak ditemukan'
    ];
    header("Location: user.php");
    exit;
}

$karyawan = mysqli_fetch_assoc($q_karyawan);

// PERBAIKAN: Ambil semua assets yang dimiliki karyawan
$query_assets = mysqli_query($conn, "
    SELECT 
        p.primary_id,
        p.primary_qty,
        p.primary_image,
        p.timestamp as tgl_input,
        
        a.assets_id,
        a.assets_kode,
        a.assets_name,
        a.assets_model,
        a.assets_spec,
        a.assets_price,
        
        kond.kondisi_name,
        
        kat.kategori_name,
        
        l.lokasi_name,
        l.lokasi_lantai
        
    FROM tbl_primary p
    INNER JOIN tbl_assets a ON p.assets_id = a.assets_id
    LEFT JOIN tbl_kondisi kond ON p.kondisi_id = kond.kondisi_id
    LEFT JOIN tbl_kategori kat ON a.kategori_id = kat.kategori_id
    LEFT JOIN tbl_lokasi l ON p.lokasi_id = l.lokasi_id
    WHERE p.karyawan_id = $target_karyawan_id
    ORDER BY a.assets_name ASC
");

// Hitung total dan ringkasan
$total_item = mysqli_num_rows($query_assets);
$total_qty = 0;
$total_nilai = 0;

// Simpan data ke array untuk digunakan nanti
$assets_list = [];
while($row = mysqli_fetch_assoc($query_assets)) {
    $total_qty += $row['primary_qty'];
    $total_nilai += $row['assets_price'];
    $assets_list[] = $row;
}

include '../menu/header.php';
include '../menu/sidebar.php';
include '../menu/topbar.php';
?>

<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-users"></i> Detail Karyawan Asset
        </h1>
        <div>
            <a href="user.php" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <!-- Profil Karyawan Card -->
    <div class="row">
        <div class="col-md-12">
            <div class="card border-left-primary shadow mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-2 text-center">
                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mx-auto" 
                                 style="width: 100px; height: 100px; font-size: 40px;">
                                <?= strtoupper(substr($karyawan['karyawan_name'] ?? '?', 0, 1)) ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h4 class="font-weight-bold"><?= htmlspecialchars($karyawan['karyawan_name'] ?? '-') ?></h4>
                            <p class="mb-1">
                                <i class="fas fa-id-card text-primary"></i> 
                                ID Karyawan: <?= htmlspecialchars($karyawan['karyawan_no'] ?? '-') ?>
                            </p>
                            <p class="mb-1">
                                <i class="fas fa-building text-primary"></i> 
                                Departemen: <?= htmlspecialchars($karyawan['dep_name'] ?? '-') ?> (<?= htmlspecialchars($karyawan['dep_code'] ?? '-') ?>)
                            </p>
                            <p class="mb-1">
                                <i class="fas fa-venus-mars text-primary"></i> 
                                Jenis Kelamin: <?= htmlspecialchars($karyawan['karyawan_gender'] ?? '-') ?>
                            </p>
                            <?php if (!empty($karyawan['karyawan_level'])): ?>
                            <p class="mb-1">
                                <i class="fas fa-tag text-primary"></i> 
                                Level: <?= htmlspecialchars($karyawan['karyawan_level'] ?? '-') ?>
                            </p>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-4">
                            <div class="row text-center">
                                <div class="col-md-4">
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $total_item ?></div>
                                    <div class="text-xs text-muted">Total Item</div>
                                </div>
                                <div class="col-md-4">
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($total_qty) ?></div>
                                    <div class="text-xs text-muted">Total Qty</div>
                                </div>
                                <div class="col-md-4">
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">Rp <?= number_format($total_nilai, 0, ',', '.') ?></div>
                                    <div class="text-xs text-muted">Total Nilai</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistik Card -->
    <div class="row">
        <div class="col-md-3 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Total Item</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $total_item ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-box fa-2x text-gray-300"></i>
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
                                Total Quantity</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($total_qty) ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-cubes fa-2x text-gray-300"></i>
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
                                Total Nilai</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">Rp <?= number_format($total_nilai, 0, ',', '.') ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-money-bill fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Rata-rata per Item</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php 
                                $rata = $total_item > 0 ? $total_nilai / $total_item : 0;
                                echo 'Rp ' . number_format($rata, 0, ',', '.');
                                ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Daftar Assets -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-boxes"></i> Daftar Assets (<?= $total_item ?> item)
            </h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Gambar</th>
                            <th>Kode Assets</th>
                            <th>Nama Assets</th>
                            <th>Model</th>
                            <th>Kategori</th>
                            <th>Spesifikasi</th>
                            <th>Lokasi</th>
                            <th>Kondisi</th>
                            <th>Qty</th>
                            <th>Harga</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        foreach ($assets_list as $row): 
                            
                            // Nama asset dengan model
                            $asset_name = htmlspecialchars($row['assets_name']);
                            if (!empty($row['assets_model'])) {
                                $asset_name .= '<br><small class="text-muted">' . htmlspecialchars($row['assets_model']) . '</small>';
                            }
                            
                            // Badge kondisi
                            $badge_class = 'badge-secondary';
                            $kondisi_name = $row['kondisi_name'] ?? '';
                            if (stripos($kondisi_name, 'BAGUS') !== false) {
                                $badge_class = 'badge-success';
                            } elseif (stripos($kondisi_name, 'RUSAK') !== false) {
                                $badge_class = 'badge-danger';
                            }
                        ?>
                        <tr>
                            <td class="text-center"><?= $no++ ?> </div>
                            <td class="text-center">
                                <?php if (!empty($row['primary_image'])): ?>
                                    <a href="../master/img/assets/<?= $row['primary_image'] ?>" target="_blank">
                                        <img src="../master/img/assets/<?= $row['primary_image'] ?>" 
                                             style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                                    </a>
                                <?php else: ?>
                                    <span class="badge badge-secondary">No Image</span>
                                <?php endif; ?>
                             </div>
                            <td><strong><?= htmlspecialchars($row['assets_kode']) ?></strong> </div>
                            <td><?= $asset_name ?> </div>
                            <td><?= !empty($row['assets_model']) ? htmlspecialchars($row['assets_model']) : '-' ?> </div>
                            <td><?= htmlspecialchars($row['kategori_name'] ?? '-') ?> </div>
                            <td><?= htmlspecialchars($row['assets_spec'] ?? '-') ?> </div>
                            <td><?= htmlspecialchars($row['lokasi_name'] ?? '-') ?> <?= !empty($row['lokasi_lantai']) ? '(Lt.' . $row['lokasi_lantai'] . ')' : '' ?> </div>
                            <td><span class="badge <?= $badge_class ?>"><?= htmlspecialchars($kondisi_name ?: '-') ?></span> </div>
                            <td class="text-center"><strong><?= number_format($row['primary_qty']) ?></strong> </div>
                            <td class="text-right">Rp <?= number_format($row['assets_price'], 0, ',', '.') ?> </div>
                            <td class="text-center">
                                <div class="d-flex justify-content-center align-items-center" style="gap:5px;">
                                    <a href="../primary/detail.php?id=<?= $row['primary_id'] ?>" 
                                       class="btn btn-sm btn-info" target="_blank" title="Detail">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    
                                    <?php if ($user_level == 1 || $user_level == 2): ?>
                                    <a href="../primary/edit.php?id=<?= $row['primary_id'] ?>" 
                                       class="btn btn-sm btn-warning" target="_blank" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php endif; ?>
                                </div>
                             </div>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($assets_list)): ?>
                        <tr>
                            <td colspan="12" class="text-center text-muted py-4">
                                <i class="fas fa-box-open fa-3x mb-3"></i>
                                <p class="mb-0">Karyawan ini belum memiliki assets</p>
                             </div>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot class="table-info">
                        <tr>
                            <th colspan="9" class="text-right">TOTAL</th>
                            <th class="text-center"><?= number_format($total_qty) ?></th>
                            <th class="text-right">Rp <?= number_format($total_nilai, 0, ',', '.') ?></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Tombol Aksi -->
    <div class="row mb-4">
        <div class="col-md-12 text-center">
            <a href="user.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Kembali ke Daftar Karyawan
            </a>
        </div>
    </div>

</div>

<?php include '../menu/footer.php'; ?>

</body>
</html>