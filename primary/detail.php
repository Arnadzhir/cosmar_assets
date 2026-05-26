<?php
include '../auth/auth.php';
allowRole([1,2,3]);

include '../config/koneksi.php';

/* =====================
   AMBIL DATA USER LOGIN
===================== */
$user_id    = $_SESSION['user_id'];
$user_level = $_SESSION['user_level'];

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$primary_id = intval($_GET['id']);

// Ambil data primary dan assets terkait
$query = mysqli_query($conn, 
    "SELECT 
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
        a.assets_qty as total_qty,
        a.assets_note,
        a.timestamp as assets_timestamp,
        
        k.kondisi_id,
        k.kondisi_name,
        
        kat.kategori_id,
        kat.kategori_name,
        kat.kategori_line,
        kat.kategori_code,
        
        t.type_id,
        t.type_name,
        
        s.supplier_id,
        s.supplier_name,
        s.supplier_mail,
        s.supplier_no,
        
        pr.produsen_id,
        pr.produsen_region,
        pr.produsen_code,
        
        m.merk_id,
        m.merk_name,
        
        l.lokasi_id,
        l.lokasi_name,
        l.lokasi_lantai,
        
        kar.karyawan_id,
        kar.karyawan_name,
        kar.karyawan_no,
        kar.karyawan_gender,
        kar.karyawan_level,
        
        d.dep_id,
        d.dep_name
        
    FROM tbl_primary p
    INNER JOIN tbl_assets a      ON p.assets_id = a.assets_id
    LEFT JOIN tbl_kondisi k      ON p.kondisi_id = k.kondisi_id
    LEFT JOIN tbl_kategori kat   ON a.kategori_id = kat.kategori_id
    LEFT JOIN tbl_type t         ON a.type_id = t.type_id
    LEFT JOIN tbl_supplier s     ON a.supplier_id = s.supplier_id
    LEFT JOIN tbl_produsen pr    ON a.produsen_id = pr.produsen_id
    LEFT JOIN tbl_merk m         ON a.merk_id = m.merk_id
    LEFT JOIN tbl_lokasi l       ON p.lokasi_id = l.lokasi_id
    LEFT JOIN tbl_karyawan kar   ON p.karyawan_id = kar.karyawan_id
    LEFT JOIN tbl_dep d          ON kar.dep_id = d.dep_id
    WHERE p.primary_id = $primary_id
");

$data = mysqli_fetch_assoc($query);

if (!$data) {
    $_SESSION['alert'] = [
        'type' => 'danger',
        'msg'  => 'Data tidak ditemukan'
    ];
    header("Location: index.php");
    exit;
}

// Ambil semua lokasi lain untuk assets yang sama (jika ada multiple lokasi)
$query_lokasi_lain = mysqli_query($conn, "
    SELECT 
        p.primary_id,
        p.primary_qty,
        p.primary_image,
        k.kondisi_name,
        l.lokasi_name,
        l.lokasi_lantai
    FROM tbl_primary p
    LEFT JOIN tbl_kondisi k ON p.kondisi_id = k.kondisi_id
    LEFT JOIN tbl_lokasi l ON p.lokasi_id = l.lokasi_id
    WHERE p.assets_id = {$data['assets_id']}
    AND p.primary_id != $primary_id
    ORDER BY p.primary_id ASC
");

$primaryData = $data;
include '../menu/header.php';
include '../menu/sidebar.php';
include '../menu/topbar.php';
$data = $primaryData;
?>

<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-info-circle"></i> Detail Master Assets
        </h1>
        <div>
            <?php if ($user_level == 1 || $user_level == 2): ?>
            <a href="edit.php?id=<?= $primary_id ?>" class="btn btn-warning btn-sm">
                <i class="fas fa-edit"></i> Edit
            </a>
            <?php endif; ?>
            <a href="javascript:history.back()" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>            
        </div>
    </div>

    <!-- Status Card -->
    <div class="row">
        <div class="col-md-12">
            <div class="card border-left-success shadow mb-4">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <p class="mb-0">
                                <strong>Kode Assets:</strong> 
                                <span class="badge badge-primary p-2" style="font-size: 14px;"><?= $data['assets_kode'] ?></span>
                            </p>
                        </div>
                        <div class="col-md-4 text-right">
                            <p class="mb-0">
                                <i class="fas fa-calendar-alt"></i> 
                                Tanggal Approve: <?= date('d M Y H:i', strtotime($data['assets_timestamp'])) ?>
                            </p>
                            <p class="mb-0">
                                <i class="fas fa-cubes"></i> 
                                Total Assets: <?= $data['total_qty'] ?> pcs
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Kolom Kiri: Gambar -->
        <div class="col-md-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-image"></i> Gambar Assets
                    </h6>
                </div>
                <div class="card-body text-center">
                    <?php 
                    $img = !empty($data['primary_image']) 
                        ? "../master/img/assets/" . $data['primary_image'] 
                        : "../master/img/no-image.png";
                    ?>
                    <img src="<?= $img ?>" class="img-fluid rounded shadow" style="max-height:300px; margin-bottom:15px;">
                    
                    <?php if (!empty($data['primary_image'])): ?>
                    <div>
                        <small class="text-muted">Filename: <?= $data['primary_image'] ?></small>
                    </div>
                    <?php endif; ?>
                    
                    <hr>
                    
                    <div class="text-left">
                        <h6 class="font-weight-bold">Informasi Item Ini:</h6>
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td>Lokasi:</td>
                                <td class="font-weight-bold"><?= $data['lokasi_name'] ?> (<?= $data['lokasi_lantai'] ?>)</td>
                            </tr>
                            <tr>
                                <td>Qty di lokasi ini:</td>
                                <td class="font-weight-bold"><?= $data['primary_qty'] ?> pcs</td>
                            </tr>
                            <tr>
                                <td>Kondisi:</td>
                                <td><span class="badge badge-info"><?= $data['kondisi_name'] ?></span></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Detail Informasi -->
        <div class="col-md-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-info-circle"></i> Informasi Lengkap Assets
                    </h6>
                </div>
                <div class="card-body">
                    
                    <!-- Nav tabs -->
                    <ul class="nav nav-tabs mb-3" id="detailTab" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="basic-tab" data-toggle="tab" href="#basic" role="tab">
                                <i class="fas fa-box"></i> Dasar
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="spec-tab" data-toggle="tab" href="#spec" role="tab">
                                <i class="fas fa-cog"></i> Spesifikasi
                            </a>
                        </li>
                        <?php if (isset($_SESSION['user_level']) && in_array($_SESSION['user_level'], [1, 2])) : ?>
                        <li class="nav-item">
                            <a class="nav-link" id="financial-tab" data-toggle="tab" href="#financial" role="tab">
                                <i class="fas fa-money-bill"></i> Keuangan
                            </a>
                        </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link" id="supplier-tab" data-toggle="tab" href="#supplier" role="tab">
                                <i class="fas fa-truck"></i> Supplier
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="user-tab" data-toggle="tab" href="#user" role="tab">
                                <i class="fas fa-user"></i> User
                            </a>
                        </li>
                    </ul>

                    <!-- Tab content -->
                    <div class="tab-content" id="detailTabContent">
                        
                        <!-- Tab 1: Informasi Dasar -->
                        <div class="tab-pane fade show active" id="basic" role="tabpanel">
                            <table class="table table-bordered">
                                <tr>
                                    <th width="35%">Kode Assets</th>
                                    <td><strong><?= $data['assets_kode'] ?></strong></td>
                                </tr>
                                <tr>
                                    <th>Nama Assets</th>
                                    <td><?= htmlspecialchars($data['assets_name']) ?></td>
                                </tr>
                                <tr>
                                    <th>Kategori</th>
                                    <td>
                                        <?= $data['kategori_name'] ?? '-' ?> 
                                        <?= !empty($data['kategori_line']) ? ' - ' . $data['kategori_line'] : '' ?>
                                        <br><small class="text-muted">Kode: <?= $data['kategori_code'] ?? '-' ?></small>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Merk / Brand</th>
                                    <td><?= $data['merk_name'] ?? '-' ?></td>
                                </tr>
                                <tr>
                                    <th>Type</th>
                                    <td><?= $data['type_name'] ?? '-' ?></td>
                                </tr>
                                <tr>
                                    <th>Estimasi Masa Manfaat</th>
                                    <td><?= $data['assets_life'] ?> Tahun</td>
                                </tr>
                                <tr>
                                    <th>Kondisi</th>
                                    <td><?= $data['kondisi_name'] ?? '-' ?></td>
                                </tr>
                                <tr>
                                    <th>Lokasi</th>
                                    <td><?= $data['lokasi_name'] ?> - <?= $data['lokasi_lantai'] ?></td>
                                </tr>
                                <tr>
                                    <th>Quantity di Lokasi Ini</th>
                                    <td><strong><?= $data['primary_qty'] ?></strong> pcs</td>
                                </tr>
                                <tr>
                                    <th>Total Quantity Assets</th>
                                    <td><strong><?= $data['total_qty'] ?></strong> pcs (dari semua lokasi)</td>
                                </tr>
                                <tr>
                                    <th>Catatan</th>
                                    <td><?= !empty($data['assets_note']) ? nl2br(htmlspecialchars($data['assets_note'])) : '-' ?></td>
                                </tr>
                            </table>
                        </div>

                        <!-- Tab 2: Spesifikasi -->
                        <div class="tab-pane fade" id="spec" role="tabpanel">
                            <table class="table table-bordered">
                                <tr>
                                    <th width="35%">Spesifikasi</th>
                                    <td><?= !empty($data['assets_spec']) ? nl2br(htmlspecialchars($data['assets_spec'])) : '-' ?></td>
                                </tr>
                                <tr>
                                    <th>Peruntukan / Target</th>
                                    <td><?= !empty($data['assets_target']) ? nl2br(htmlspecialchars($data['assets_target'])) : '-' ?></td>
                                </tr>
                                <tr>
                                    <th>Kapasitas</th>
                                    <td>
                                        <?php if (!empty($data['assets_cap'])): ?>
                                            <?= $data['assets_cap'] ?> <?= $data['assets_uom'] ?? '' ?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <?php if (isset($_SESSION['user_level']) && in_array($_SESSION['user_level'], [1, 2])) : ?>
                        <!-- Tab 3: Keuangan -->
                        <div class="tab-pane fade" id="financial" role="tabpanel">
                            <table class="table table-bordered">
                                <tr>
                                    <th width="35%">Harga Beli</th>
                                    <td class="font-weight-bold text-primary">
                                        Rp <?= number_format($data['assets_price'], 0, ',', '.') ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Tanggal Pembelian</th>
                                    <td>
                                        <?php 
                                        if (!empty($data['assets_date']) && $data['assets_date'] != '0000-00-00') {
                                            echo date('d F Y', strtotime($data['assets_date']));
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Nilai per Item</th>
                                    <td>
                                        Rp <?= number_format($data['assets_price'] * $data['total_qty'], 0, ',', '.') ?>
                                        <small class="text-muted">(estimasi)</small>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <?php endif; ?>

                        <!-- Tab 4: Supplier & Produsen -->
                        <div class="tab-pane fade" id="supplier" role="tabpanel">
                            <table class="table table-bordered">
                                <tr>
                                    <th width="35%">Supplier</th>
                                    <td><?= $data['supplier_name'] ?? '-' ?></td>
                                </tr>
                                <tr>
                                    <th>Email Supplier</th>
                                    <td><?= $data['supplier_mail'] ?? '-' ?></td>
                                </tr>
                                <tr>
                                    <th>No. Telp Supplier</th>
                                    <td><?= $data['supplier_no'] ?? '-' ?></td>
                                </tr>
                                <tr>
                                    <th>Produsen (Negara)</th>
                                    <td>
                                        <?php if (!empty($data['produsen_region'])): ?>
                                            <?= $data['produsen_region'] ?> (<?= $data['produsen_code'] ?>)
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <!-- Tab 5: User & Department -->
                        <div class="tab-pane fade" id="user" role="tabpanel">
                            <table class="table table-bordered">
                                <tr>
                                    <th width="35%">User Name</th>
                                    <td><?= $data['karyawan_name'] ?? '-' ?></td>
                                </tr>
                                <tr>
                                    <th>No. Telp User</th>
                                    <td><?= $data['karyawan_no'] ?? '-' ?></td>
                                </tr>
                                <tr>
                                    <th>Department</th>
                                    <td><?= $data['dep_name'] ?? '-' ?></td>
                                </tr>
                                <tr>
                                    <th>Jabatan</th>
                                    <td><?= $data['karyawan_level'] ?? '-' ?></td>
                                </tr>
                                <tr>
                                    <th>Input Pada</th>
                                    <td><?= date('d M Y H:i:s', strtotime($data['primary_timestamp'])) ?></td>
                                </tr>
                                <tr>
                                    <th>Terakhir Update</th>
                                    <td><?= date('d M Y H:i:s', strtotime($data['assets_timestamp'])) ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Jika ada lokasi lain untuk assets yang sama -->
            <?php if (mysqli_num_rows($query_lokasi_lain) > 0): ?>
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-map-marker-alt"></i> Distribusi Lokasi Lainnya
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead class="thead-light">
                                <tr>
                                    <th>No</th>
                                    <th>Lokasi</th>
                                    <th>Qty</th>
                                    <th>Kondisi</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no_lokasi = 1;
                                while($lok = mysqli_fetch_assoc($query_lokasi_lain)): 
                                ?>
                                <tr>
                                    <td class="text-center"><?= $no_lokasi++ ?></td>
                                    <td><?= $lok['lokasi_name'] ?> (<?= $lok['lokasi_lantai'] ?>)</td>
                                    <td class="text-center"><strong><?= $lok['primary_qty'] ?></strong> pcs</td>
                                    <td><?= $lok['kondisi_name'] ?? '-' ?></td>
                                    <td class="text-center">
                                        <a href="detail.php?id=<?= $lok['primary_id'] ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>

</div>

<?php include '../menu/footer.php'; ?>

</body>
</html>