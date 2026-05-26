<?php
include '../auth/auth.php';
allowRole([1,2,3]);

include '../config/koneksi.php';

$user_level = $_SESSION['user_level'] ?? 0;

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header("Location: index.php?error=ID tidak valid");
    exit;
}

// PERBAIKAN: Query dari tbl_tools
$query = "
    SELECT 
        t.*,
        kar.karyawan_name,
        kar.karyawan_no,
        d.dep_code,
        d.dep_name,
        kond.kondisi_name
    FROM tbl_tools t
    LEFT JOIN tbl_karyawan kar ON t.user_id = kar.karyawan_id
    LEFT JOIN tbl_dep d ON kar.dep_id = d.dep_id
    LEFT JOIN tbl_kondisi kond ON t.kondisi_id = kond.kondisi_id
    WHERE t.tools_id = $id
";

$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);

if (!$row) {
    header("Location: index.php?error=Data tools tidak ditemukan");
    exit();
}

// Format harga
$harga = !empty($row['tools_price']) ? 'Rp ' . number_format($row['tools_price'], 0, ',', '.') : '-';
$total_nilai = !empty($row['tools_price']) && !empty($row['tools_qty']) 
    ? 'Rp ' . number_format($row['tools_price'] * $row['tools_qty'], 0, ',', '.') 
    : '-';

// Format tanggal
$tanggal = (!empty($row['tools_date']) && $row['tools_date'] != '0000-00-00') 
    ? date('d F Y', strtotime($row['tools_date'])) 
    : '-';

// Badge kondisi
$kondisi_name = $row['kondisi_name'] ?? '-';
$badge_class = 'badge-secondary';
if (stripos($kondisi_name, 'BAGUS') !== false) {
    $badge_class = 'badge-success';
} elseif (stripos($kondisi_name, 'RUSAK') !== false) {
    $badge_class = 'badge-danger';
} elseif (stripos($kondisi_name, 'SEDANG') !== false) {
    $badge_class = 'badge-warning';
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
    .badge-condition {
        display: inline-block;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    .badge-success {
        background-color: #d4edda;
        color: #155724;
    }
    .badge-danger {
        background-color: #f8d7da;
        color: #721c24;
    }
    .badge-warning {
        background-color: #fff3cd;
        color: #856404;
    }
    .badge-secondary {
        background-color: #e9ecef;
        color: #6c757d;
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
            <i class="fas fa-tools"></i> Detail Tools
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
               onclick="return confirm('Yakin ingin menghapus tools ini?')">
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
                    <?php if (!empty($row['tools_image'])): ?>
                        <img src="../master/img/tools/<?= htmlspecialchars($row['tools_image']) ?>" class="detail-image" 
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
                            <?= $total_nilai ?>
                        </div>
                        <small class="text-muted">Total Nilai (Harga × Qty)</small>
                    </div>
                    <hr>
                    <div class="info-row">
                        <div class="info-label">Harga per Unit</div>
                        <div class="info-value"><?= $harga ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Quantity</div>
                        <div class="info-value"><?= number_format($row['tools_qty'] ?? 0) ?> unit</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kolom Informasi -->
        <div class="col-md-8">
            <div class="detail-card">
                <div class="detail-header">
                    <h4 class="mb-0">
                        <i class="fas fa-tools"></i> <?= htmlspecialchars($row['tools_name'] ?? '-') ?>
                    </h4>
                    <small>ID Tools: #<?= $id ?></small>
                </div>

                <!-- Informasi Tools -->
                <div class="detail-section">
                    <h6 class="detail-label">
                        <i class="fas fa-tools"></i> INFORMASI TOOLS
                    </h6>
                    <div class="info-row">
                        <div class="info-label">Nama Tools</div>
                        <div class="info-value"><strong><?= htmlspecialchars($row['tools_name'] ?? '-') ?></strong></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Merk</div>
                        <div class="info-value"><?= htmlspecialchars($row['tools_merk'] ?? '-') ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Quantity</div>
                        <div class="info-value"><?= number_format($row['tools_qty'] ?? 0) ?> unit</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Harga</div>
                        <div class="info-value"><?= $harga ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Tanggal Pembelian</div>
                        <div class="info-value"><?= $tanggal ?></div>
                    </div>
                    <?php if (!empty($row['tools_spec'])): ?>
                    <div class="info-row">
                        <div class="info-label">Spesifikasi</div>
                        <div class="info-value"><?= nl2br(htmlspecialchars($row['tools_spec'])) ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($row['tools_note'])): ?>
                    <div class="info-row">
                        <div class="info-label">Catatan</div>
                        <div class="info-value"><?= nl2br(htmlspecialchars($row['tools_note'])) ?></div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Informasi Kondisi -->
                <div class="detail-section">
                    <h6 class="detail-label">
                        <i class="fas fa-clipboard-check"></i> INFORMASI KONDISI
                    </h6>
                    <div class="info-row">
                        <div class="info-label">Kondisi</div>
                        <div class="info-value">
                            <span class="badge-condition <?= $badge_class ?>">
                                <?= htmlspecialchars($kondisi_name) ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Informasi Penanggung Jawab -->
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