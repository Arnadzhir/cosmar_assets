<?php
include '../auth/auth.php';
allowRole([1,2,3]);

include '../config/koneksi.php';

$user_level = $_SESSION['user_level'] ?? 0;

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// PERBAIKAN: Query menggunakan tbl_karyawan
$query = "
SELECT 
    s.*,
    a.assets_kode,
    a.assets_name,
    a.kategori_id,
    a.assets_spec as asset_spec,
    a.assets_price as asset_price,
    a.assets_date as asset_date,
    a.assets_life,
    a.assets_qty as asset_qty,
    a.assets_target,
    a.assets_cap,
    a.assets_uom,
    a.merk_id,
    a.type_id,
    a.supplier_id,
    a.produsen_id,
    kar.karyawan_name,
    kar.karyawan_no,
    d.dep_code,
    d.dep_name,
    k.kategori_name,
    k.kategori_line,
    m.merk_name,
    t.type_name,
    p.produsen_region,
    sup.supplier_name
FROM tbl_sparepart s 
LEFT JOIN tbl_assets a ON s.assets_id = a.assets_id
LEFT JOIN tbl_karyawan kar ON s.user_id = kar.karyawan_id
LEFT JOIN tbl_dep d ON kar.dep_id = d.dep_id
LEFT JOIN tbl_kategori k ON a.kategori_id = k.kategori_id
LEFT JOIN tbl_merk m ON a.merk_id = m.merk_id
LEFT JOIN tbl_type t ON a.type_id = t.type_id
LEFT JOIN tbl_produsen p ON a.produsen_id = p.produsen_id
LEFT JOIN tbl_supplier sup ON a.supplier_id = sup.supplier_id
WHERE s.sparepart_id = $id
";

$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);

if (!$row) {
    header("Location: index.php?error=Data sparepart tidak ditemukan");
    exit();
}

include '../menu/header.php';
include '../menu/sidebar.php';
include '../menu/topbar.php';
?>

<style>
    .detail-card {
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        margin-bottom: 20px;
        overflow: hidden;
    }
    .detail-header {
        background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
        color: white;
        padding: 20px 25px;
    }
    .detail-header h4 {
        margin: 0;
        font-weight: 600;
    }
    .detail-header small {
        opacity: 0.8;
        font-size: 12px;
    }
    .detail-section {
        padding: 20px 25px;
        border-bottom: 1px solid #e3e6f0;
    }
    .detail-section:last-child {
        border-bottom: none;
    }
    .detail-label {
        font-weight: 700;
        color: #4e73df;
        margin-bottom: 15px;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-left: 3px solid #4e73df;
        padding-left: 10px;
    }
    .info-row {
        display: flex;
        margin-bottom: 12px;
        padding-bottom: 8px;
        border-bottom: 1px dashed #f0f0f0;
    }
    .info-label {
        width: 160px;
        font-weight: 600;
        color: #5a5c69;
        font-size: 13px;
    }
    .info-value {
        flex: 1;
        color: #2c3e50;
        font-size: 13px;
    }
    .info-value strong {
        color: #4e73df;
    }
    .detail-image {
        max-width: 100%;
        max-height: 300px;
        border-radius: 8px;
        object-fit: contain;
    }
    .image-container {
        text-align: center;
        padding: 20px;
        background: #f8f9fc;
        border-radius: 10px;
    }
    .total-value {
        font-size: 20px;
        font-weight: 700;
        color: #4e73df;
    }
    @media (max-width: 768px) {
        .info-row {
            flex-direction: column;
        }
        .info-label {
            width: 100%;
            margin-bottom: 5px;
        }
        .detail-section {
            padding: 15px;
        }
    }
</style>

<div class="container-fluid">

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-info-circle"></i> Detail Sparepart
        </h1>
        <div>
            <a href="index.php" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
            <?php if (in_array($user_level, [1, 2])): ?>
            <a href="edit.php?id=<?= $id ?>" class="btn btn-warning btn-sm">
                <i class="fas fa-edit"></i> Edit
            </a>
            <?php endif; ?>
            <?php if ($user_level == 1): ?>
            <a href="proses.php?hapus=1&id=<?= $id ?>" class="btn btn-danger btn-sm" 
               onclick="return confirm('Yakin ingin menghapus sparepart ini?')">
                <i class="fas fa-trash"></i> Hapus
            </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="row">
        <!-- Kolom Gambar -->
        <div class="col-md-4">
            <div class="detail-card">
                <div class="image-container">
                    <?php if (!empty($row['sparepart_image'])): ?>
                        <img src="../master/img/assets/<?= htmlspecialchars($row['sparepart_image']) ?>" class="detail-image" 
                             onerror="this.src='../master/img/no-image.png'">
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-image fa-4x text-gray-300"></i>
                            <p class="mt-2 text-muted">Tidak ada gambar</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Ringkasan Nilai -->
            <div class="detail-card mt-3">
                <div class="detail-header" style="background: linear-gradient(135deg, #1cc88a 0%, #13855c 100%);">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-line"></i> Ringkasan Nilai
                    </h5>
                </div>
                <div class="detail-section">
                    <div class="text-center">
                        <div class="total-value">
                            Rp <?= number_format(($row['sparepart_price'] ?? 0) * ($row['sparepart_qty'] ?? 0), 0, ',', '.') ?>
                        </div>
                        <small class="text-muted">Total Nilai (Harga × Qty)</small>
                    </div>
                    <hr>
                    <div class="info-row">
                        <div class="info-label">Harga per Unit</div>
                        <div class="info-value">Rp <?= number_format($row['sparepart_price'] ?? 0, 0, ',', '.') ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Quantity</div>
                        <div class="info-value"><?= $row['sparepart_qty'] ?? 0 ?> unit</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kolom Informasi -->
        <div class="col-md-8">
            <div class="detail-card">
                <div class="detail-header">
                    <h4 class="mb-0">
                        <i class="fas fa-microchip"></i> <?= htmlspecialchars($row['sparepart_name'] ?? '-') ?>
                    </h4>
                    <small>ID Sparepart: #<?= $id ?> | ID Asset: <?= $row['assets_id'] ?? '-' ?></small>
                </div>

                <!-- Informasi Sparepart -->
                <div class="detail-section">
                    <h6 class="detail-label">
                        <i class="fas fa-microchip"></i> INFORMASI SPAREPART
                    </h6>
                    <div class="info-row">
                        <div class="info-label">Nama Sparepart</div>
                        <div class="info-value"><strong><?= htmlspecialchars($row['sparepart_name'] ?? '-') ?></strong></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Merk</div>
                        <div class="info-value"><?= htmlspecialchars($row['sparepart_merk'] ?? '-') ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Quantity</div>
                        <div class="info-value"><?= $row['sparepart_qty'] ?? 0 ?> unit</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Harga</div>
                        <div class="info-value">Rp <?= number_format($row['sparepart_price'] ?? 0, 0, ',', '.') ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Tanggal Pembelian</div>
                        <div class="info-value"><?= isset($row['sparepart_date']) && $row['sparepart_date'] != '0000-00-00' ? date('d F Y', strtotime($row['sparepart_date'])) : '-' ?></div>
                    </div>
                    <?php if (!empty($row['sparepart_spec'])): ?>
                    <div class="info-row">
                        <div class="info-label">Spesifikasi</div>
                        <div class="info-value"><?= nl2br(htmlspecialchars($row['sparepart_spec'])) ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($row['sparepart_note'])): ?>
                    <div class="info-row">
                        <div class="info-label">Catatan</div>
                        <div class="info-value"><?= nl2br(htmlspecialchars($row['sparepart_note'])) ?></div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Informasi Asset -->
                <div class="detail-section">
                    <h6 class="detail-label">
                        <i class="fas fa-building"></i> INFORMASI ASSET
                    </h6>
                    <div class="info-row">
                        <div class="info-label">Kode Asset</div>
                        <div class="info-value"><strong><?= htmlspecialchars($row['assets_kode'] ?? '-') ?></strong></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Nama Asset</div>
                        <div class="info-value"><?= htmlspecialchars($row['assets_name'] ?? '-') ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Kategori</div>
                        <div class="info-value"><?= htmlspecialchars($row['kategori_name'] ?? '-') ?> - <?= htmlspecialchars($row['kategori_line'] ?? '-') ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Merk Asset</div>
                        <div class="info-value"><?= htmlspecialchars($row['merk_name'] ?? '-') ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Tipe Asset</div>
                        <div class="info-value"><?= htmlspecialchars($row['type_name'] ?? '-') ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Masa Pakai</div>
                        <div class="info-value"><?= $row['assets_life'] ?? '-' ?> tahun</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Produsen</div>
                        <div class="info-value"><?= htmlspecialchars($row['produsen_region'] ?? '-') ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Supplier</div>
                        <div class="info-value"><?= htmlspecialchars($row['supplier_name'] ?? '-') ?></div>
                    </div>
                </div>

                <!-- PERBAIKAN: Informasi Penanggung Jawab -->
                <div class="detail-section">
                    <h6 class="detail-label">
                        <i class="fas fa-user"></i> INFORMASI PENANGGUNG JAWAB
                    </h6>
                    <div class="info-row">
                        <div class="info-label">Nama Penanggung Jawab</div>
                        <div class="info-value"><strong><?= htmlspecialchars($row['karyawan_name'] ?? '-') ?></strong></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">ID Karyawan</div>
                        <div class="info-value"><?= htmlspecialchars($row['karyawan_no'] ?? '-') ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Departemen</div>
                        <div class="info-value"><?= htmlspecialchars($row['dep_code'] ?? '-') ?> - <?= htmlspecialchars($row['dep_name'] ?? '-') ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<?php include '../menu/footer.php'; ?>

<!-- Script untuk mencegah error addEventListener -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Buat elemen dummy untuk mencegah error di footer
    if (!document.getElementById('primary_image')) {
        var dummy = document.createElement('input');
        dummy.type = 'hidden';
        dummy.id = 'primary_image';
        document.body.appendChild(dummy);
    }
    if (!document.getElementById('uploadArea')) {
        var dummy2 = document.createElement('div');
        dummy2.id = 'uploadArea';
        dummy2.style.display = 'none';
        document.body.appendChild(dummy2);
    }
    if (!document.getElementById('previewImage')) {
        var dummy3 = document.createElement('img');
        dummy3.id = 'previewImage';
        dummy3.style.display = 'none';
        document.body.appendChild(dummy3);
    }
    if (!document.getElementById('removeImage')) {
        var dummy4 = document.createElement('button');
        dummy4.id = 'removeImage';
        dummy4.style.display = 'none';
        document.body.appendChild(dummy4);
    }
    if (!document.getElementById('uploadContent')) {
        var dummy5 = document.createElement('div');
        dummy5.id = 'uploadContent';
        dummy5.style.display = 'none';
        document.body.appendChild(dummy5);
    }
});
</script>

</body>
</html>