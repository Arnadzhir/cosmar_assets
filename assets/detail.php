<?php
include '../auth/auth.php';
allowRole([1,2,3]); // Hanya Admin

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

$assets_id = intval($_GET['id']);

// Ambil data assets
$query = mysqli_query($conn, "
    SELECT 
        a.*,
        kat.kategori_id as kat_id,
        kat.kategori_name,
        kat.kategori_line,
        kat.kategori_code,
        
        m.merk_id as m_id,
        m.merk_name,
        
        t.type_id as t_id,
        t.type_name,
        
        s.supplier_id as s_id,
        s.supplier_name,
        s.supplier_mail,
        s.supplier_no,
        
        pr.produsen_id as pr_id,
        pr.produsen_region,
        pr.produsen_code
        
    FROM tbl_assets a
    LEFT JOIN tbl_kategori kat ON a.kategori_id = kat.kategori_id
    LEFT JOIN tbl_merk m ON a.merk_id = m.merk_id
    LEFT JOIN tbl_type t ON a.type_id = t.type_id
    LEFT JOIN tbl_supplier s ON a.supplier_id = s.supplier_id
    LEFT JOIN tbl_produsen pr ON a.produsen_id = pr.produsen_id
    WHERE a.assets_id = $assets_id
");

if (!$query) {
    die("Error query: " . mysqli_error($conn));
}

$data = mysqli_fetch_assoc($query);

if (!$data) {
    $_SESSION['alert'] = [
        'type' => 'danger',
        'msg'  => 'Data tidak ditemukan'
    ];
    header("Location: index.php");
    exit;
}

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
            <i class="fas fa-info-circle"></i> Detail Sistem Assets
        </h1>
        <div>
            <?php if ($_SESSION['user_level'] == 1): ?>
            <a href="edit.php?id=<?= $assets_id ?>" class="btn btn-warning btn-sm">
                <i class="fas fa-edit"></i> Edit
            </a>
            <?php endif; ?>
            <a href="index.php" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <!-- Status Card -->
    <div class="row">
        <div class="col-md-12">
            <div class="card border-left-primary shadow mb-4">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h5 class="font-weight-bold text-primary">
                                <i class="fas fa-box"></i> 
                                Informasi Master Assets
                            </h5>
                            <p class="mb-0">
                                <strong>Kode Assets:</strong> 
                                <span class="badge badge-primary p-2" style="font-size: 14px;"><?= $data['assets_kode'] ?? '-' ?></span>
                            </p>
                        </div>
                        <div class="col-md-4 text-right">
                            <p class="mb-0">
                                <i class="fas fa-calendar-alt"></i> 
                                Terakhir Update: <?= date('d/m/Y H:i', strtotime($data['timestamp'])) ?>
                            </p>
                            <p class="mb-0">
                                <i class="fas fa-cubes"></i> 
                                Total Quantity: <strong><?= $data['assets_qty'] ?? 0 ?></strong> pcs
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Detail Informasi -->
        <div class="col-md-12">
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
                                <i class="fas fa-truck"></i> Supplier & Produsen
                            </a>
                        </li>
                    </ul>

                    <!-- Tab content -->
                    <div class="tab-content" id="detailTabContent">
                        
                        <!-- Tab 1: Informasi Dasar -->
                        <div class="tab-pane fade show active" id="basic" role="tabpanel">
                            <table class="table table-bordered">
                                <tr>
                                    <th width="30%">Kode Assets</th>
                                    <td><strong><?= $data['assets_kode'] ?? '-' ?></strong></td>
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
                                    <td><?= $data['assets_life'] ?? '-' ?> Tahun</td>
                                </tr>
                                <tr>
                                    <th>Total Quantity</th>
                                    <td><strong><?= $data['assets_qty'] ?? 0 ?></strong> pcs</td>
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
                                    <th width="30%">Spesifikasi</th>
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

                        <!-- Tab 3: Keuangan -->
                        <?php if (isset($_SESSION['user_level']) && in_array($_SESSION['user_level'], [1, 2])) : ?>
                        <div class="tab-pane fade" id="financial" role="tabpanel">
                            <table class="table table-bordered">
                                <tr>
                                    <th width="30%">Harga Beli</th>
                                    <td class="font-weight-bold text-primary">
                                        Rp <?= number_format($data['assets_price'] ?? 0, 0, ',', '.') ?>
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
                                    <th>Total Harga</th>
                                    <td>
                                        <?php 
                                        $total_qty = $data['assets_qty'] ?? 1;
                                        $nilai_per_item = ($data['assets_price'] ?? 0) * max($total_qty, 1);
                                        ?>
                                        Rp <?= number_format($nilai_per_item, 0, ',', '.') ?>
                                        <small class="text-muted">(Dari <?= $data['assets_qty'] ?? 0 ?> Pcs)</small>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <?php endif; ?>

                        <!-- Tab 4: Supplier & Produsen -->
                        <div class="tab-pane fade" id="supplier" role="tabpanel">
                            <table class="table table-bordered">
                                <tr>
                                    <th width="30%">Supplier</th>
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
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- Informasi Sistem -->
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-clock"></i> Informasi Sistem
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php if ($_SESSION['user_level'] == 1): ?>
                        <div class="col-md-3">
                            <small class="text-muted">Dibuat Pada</small>
                            <p class="font-weight-bold"><?= date('d/m/Y H:i:s', strtotime($data['timestamp'])) ?></p>
                        </div>
                        <?php endif; ?>
                        <div class="col-md-3">
                            <small class="text-muted">Terakhir Diupdate</small>
                            <p class="font-weight-bold"><?= date('d/m/Y H:i:s', strtotime($data['timestamp'])) ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tombol Aksi -->
    <div class="row mb-4">
        <div class="col-md-12 text-center">
            <?php if ($_SESSION['user_level'] == 1): ?>
            <a href="edit.php?id=<?= $assets_id ?>" class="btn btn-warning">
                <i class="fas fa-edit"></i> Edit Assets
            </a>
            <?php endif; ?>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

</div>

<?php include '../menu/footer.php'; ?>

</body>
</html>