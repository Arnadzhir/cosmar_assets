<?php
include '../auth/auth.php';
allowRole([1,2,3]);

include '../config/koneksi.php';

// Cek apakah ada ID
if(!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['alert'] = [
        'type' => 'danger',
        'msg'  => 'ID asset tidak ditemukan'
    ];
    header("Location: index.php");
    exit();
}

$assets_id = mysqli_real_escape_string($conn, $_GET['id']);

/* =====================
   AMBIL DATA USER LOGIN
===================== */
$user_id    = $_SESSION['user_id'];
$user_level = $_SESSION['user_level'];

include '../menu/header.php';
include '../menu/sidebar.php';
include '../menu/topbar.php';
?>

<!-- SweetAlert2 CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<!-- SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Lightbox2 CSS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/css/lightbox.min.css">

<style>
    .detail-card {
        margin-bottom: 20px;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    }
    .detail-card .card-header {
        background-color: #f8f9fc;
        border-bottom: 1px solid #e3e6f0;
        padding: 15px 20px;
    }
    .detail-card .card-header h6 {
        margin: 0;
        color: #4e73df;
        font-weight: 700;
    }
    .detail-card .card-body {
        padding: 20px;
    }
    .info-label {
        font-weight: 600;
        color: #4e73df;
        background-color: #f8f9fc;
        padding: 8px 12px;
        border-radius: 4px;
        margin-bottom: 5px;
    }
    .info-value {
        padding: 8px 12px;
        background-color: white;
        border: 1px solid #e3e6f0;
        border-radius: 4px;
        margin-bottom: 15px;
        min-height: 40px;
    }
    .table-detail {
        width: 100%;
        border-collapse: collapse;
    }
    .table-detail th {
        background-color: #f8f9fc;
        font-weight: 600;
        color: #4e73df;
        padding: 10px;
        border: 1px solid #e3e6f0;
        text-align: left;
        width: 200px;
    }
    .table-detail td {
        padding: 10px;
        border: 1px solid #e3e6f0;
    }
    .badge-lokasi {
        display: inline-block;
        background-color: #e8f0fe;
        color: #4e73df;
        padding: 5px 10px;
        margin: 2px;
        border-radius: 4px;
        font-size: 13px;
    }
    .unit-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
    }
    .unit-table th {
        background-color: #f8f9fc;
        font-weight: 600;
        color: #4e73df;
        padding: 10px;
        border: 1px solid #e3e6f0;
        text-align: center;
        font-size: 12px;
    }
    .unit-table td {
        padding: 8px;
        border: 1px solid #e3e6f0;
        text-align: center;
        vertical-align: middle;
        font-size: 12px;
    }
    .unit-table tbody tr:hover {
        background-color: #f8f9fc;
    }
    .btn-back {
        background-color: #858796;
        border-color: #858796;
        color: white;
        transition: all 0.3s;
    }
    .btn-back:hover {
        background-color: #6c6e7c;
        border-color: #6c6e7c;
        transform: translateY(-2px);
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        color: white;
    }
    .btn-print {
        background-color: #4e73df;
        border-color: #4e73df;
        color: white;
        transition: all 0.3s;
    }
    .btn-print:hover {
        background-color: #2e59d9;
        border-color: #2e59d9;
        transform: translateY(-2px);
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        color: white;
    }
    .gallery-thumb {
        width: 80px;
        height: 80px;
        object-fit: cover;
        border-radius: 4px;
        border: 1px solid #ddd;
        cursor: pointer;
        transition: all 0.3s;
        margin: 5px;
    }
    .gallery-thumb:hover {
        transform: scale(1.1);
        box-shadow: 0 2px 10px rgba(0,0,0,0.2);
    }
    .clickable-code {
        cursor: pointer;
        color: #4e73df;
        font-weight: 600;
    }
    .clickable-code:hover {
        color: #224abe;
        text-decoration: underline;
    }
    .badge-waiting {
        background-color: #f6c23e;
        color: #856404;
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        display: inline-block;
    }
    .badge-approved {
        background-color: #1cc88a;
        color: white;
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
    }
</style>

<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-file-alt"></i> Detail Asset
        </h1>
        <div>
            <a href="javascript:history.back()" class="btn btn-back btn-sm">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <?php
    // Ambil data header asset
    $qAsset = mysqli_query($conn, "
        SELECT 
            a.*,
            kat.kategori_name,
            kat.kategori_line,
            t.type_name,
            s.supplier_name,
            pr.produsen_region,
            pr.produsen_code,
            m.merk_name
        FROM tbl_assets a
        LEFT JOIN tbl_kategori kat ON a.kategori_id = kat.kategori_id
        LEFT JOIN tbl_type t ON a.type_id = t.type_id
        LEFT JOIN tbl_supplier s ON a.supplier_id = s.supplier_id
        LEFT JOIN tbl_produsen pr ON a.produsen_id = pr.produsen_id
        LEFT JOIN tbl_merk m ON a.merk_id = m.merk_id
        WHERE a.assets_id = '$assets_id'
    ");
    
    if (mysqli_num_rows($qAsset) == 0) {
        echo "<div class='alert alert-danger'>Data asset tidak ditemukan</div>";
        include '../menu/footer.php';
        exit;
    }
    
    $asset = mysqli_fetch_assoc($qAsset);
    
    // PERBAIKAN: Ambil data penanggung jawab dari primary (menggunakan tbl_karyawan)
    $qUser = mysqli_query($conn, "
        SELECT 
            kar.karyawan_id,
            kar.karyawan_name,
            d.dep_id,
            d.dep_name,
            d.dep_code
        FROM tbl_primary p
        LEFT JOIN tbl_karyawan kar ON p.karyawan_id = kar.karyawan_id
        LEFT JOIN tbl_dep d ON kar.dep_id = d.dep_id
        WHERE p.assets_id = '$assets_id'
        LIMIT 1
    ");
    
    $userData = mysqli_fetch_assoc($qUser);
    
    // Cek status approval
    $is_approved = !empty($asset['assets_kode']);
    
    // PERBAIKAN: Ambil semua unit (primary) dari asset ini
    $qUnits = mysqli_query($conn, "
        SELECT 
            p.primary_id,
            p.primary_qty,
            p.primary_image,
            p.timestamp as primary_timestamp,
            kond.kondisi_name,
            l.lokasi_name,
            l.lokasi_lantai,
            kar.karyawan_name as unit_user,
            d.dep_code as unit_dep_code
        FROM tbl_primary p
        LEFT JOIN tbl_kondisi kond ON p.kondisi_id = kond.kondisi_id
        LEFT JOIN tbl_lokasi l ON p.lokasi_id = l.lokasi_id
        LEFT JOIN tbl_karyawan kar ON p.karyawan_id = kar.karyawan_id
        LEFT JOIN tbl_dep d ON kar.dep_id = d.dep_id
        WHERE p.assets_id = '$assets_id'
        ORDER BY p.primary_id ASC
    ");
    
    // Hitung total unit
    $total_unit = mysqli_num_rows($qUnits);
    
    // Ambil semua gambar untuk gallery
    $qImages = mysqli_query($conn, "
        SELECT primary_image 
        FROM tbl_primary 
        WHERE assets_id = '$assets_id' 
        AND primary_image IS NOT NULL 
        AND primary_image != ''
        ORDER BY primary_id ASC
    ");
    ?>

    <!-- Status Asset -->
    <div class="alert <?= $is_approved ? 'alert-success' : 'alert-warning' ?> mb-4">
        <i class="fas <?= $is_approved ? 'fa-check-circle' : 'fa-clock' ?> mr-2"></i>
        <strong>Status: <?= $is_approved ? 'Approved' : 'Menunggu Approval' ?></strong>
        <?php if ($is_approved): ?>
            - Kode Asset: <span class="clickable-code" onclick="copyToClipboard('<?= $asset['assets_kode'] ?>')"><?= $asset['assets_kode'] ?></span>
            <i class="fas fa-copy text-muted ml-1" style="cursor: pointer;" onclick="copyToClipboard('<?= $asset['assets_kode'] ?>')"></i>
        <?php endif; ?>
    </div>

    <!-- Informasi Asset -->
    <div class="card shadow mb-4 detail-card">
        <div class="card-header">
            <h6><i class="fas fa-info-circle"></i> Informasi Asset</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table-detail">
                        <tr>
                            <th>Nama Asset</th>
                            <td><?= htmlspecialchars($asset['assets_name']) ?></td>
                        </tr>
                        <tr>
                            <th>Kategori</th>
                            <td><?= !empty($asset['kategori_name']) ? $asset['kategori_name'] . ' - ' . $asset['kategori_line'] : '-' ?></div>
                        </tr>
                        <tr>
                            <th>Type</th>
                            <td><?= $asset['type_name'] ?? '-' ?></div>
                        </tr>
                        <tr>
                            <th>Merk</th>
                            <td><?= $asset['merk_name'] ?? '-' ?></div>
                        </tr>
                        <tr>
                            <th>Spesifikasi</th>
                            <td><?= !empty($asset['assets_spec']) ? nl2br(htmlspecialchars($asset['assets_spec'])) : '-' ?></div>
                        </tr>
                        <tr>
                            <th>Peruntukan</th>
                            <td><?= !empty($asset['assets_target']) ? htmlspecialchars($asset['assets_target']) : '-' ?></div>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table-detail">
                        <tr>
                            <th>Kapasitas</th>
                            <td><?= !empty($asset['assets_cap']) ? $asset['assets_cap'] . ' ' . $asset['assets_uom'] : '-' ?></div>
                        </tr>
                        <tr>
                            <th>Masa Manfaat</th>
                            <td><?= !empty($asset['assets_life']) ? $asset['assets_life'] . ' Tahun' : '-' ?></div>
                        </tr>
                        <tr>
                            <th>Supplier</th>
                            <td><?= $asset['supplier_name'] ?? '-' ?></div>
                        </tr>
                        <tr>
                            <th>Produsen</th>
                            <td><?= !empty($asset['produsen_region']) ? $asset['produsen_region'] . ' (' . $asset['produsen_code'] . ')' : '-' ?></div>
                        </tr>
                        <tr>
                            <th>Harga per 1 Pcs</th>
                            <td>
                                <?php if ($user_level == 1 || $user_level == 2): ?>
                                <?= !empty($asset['assets_price']) ? 'Rp ' . number_format($asset['assets_price'], 0, ',', '.') : '-' ?>
                                <?php endif; ?>
                            </div>
                        </tr>
                        <tr>
                            <th>Tanggal Beli</th>
                            <td><?= !empty($asset['assets_date']) && $asset['assets_date'] != '0000-00-00' ? date('d/m/Y', strtotime($asset['assets_date'])) : '-' ?></div>
                        </tr>
                        <tr>
                            <th>Catatan</th>
                            <td><?= !empty($asset['assets_note']) ? htmlspecialchars($asset['assets_note']) : '-' ?></div>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Informasi Penanggung Jawab -->
    <div class="card shadow mb-4 detail-card">
        <div class="card-header">
            <h6><i class="fas fa-user"></i> Informasi Penanggung Jawab</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table-detail">
                        <tr>
                            <th>Nama Penanggung Jawab</th>
                            <td><?= htmlspecialchars($userData['karyawan_name'] ?? '-') ?></div>
                        </tr>
                        <tr>
                            <th>ID Karyawan</th>
                            <td><?= htmlspecialchars($userData['karyawan_id'] ?? '-') ?></div>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table-detail">
                        <tr>
                            <th>Departemen</th>
                            <td><?= htmlspecialchars($userData['dep_name'] ?? '-') ?> (<?= $userData['dep_code'] ?? '-' ?>)</div>
                        </tr>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-12">
                    <div class="info-value text-center" style="background-color: #e8f0fe;">
                        <strong>Total Unit: <?= $total_unit ?> unit</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Gallery Gambar -->
    <?php if (mysqli_num_rows($qImages) > 0): ?>
    <div class="card shadow mb-4 detail-card">
        <div class="card-header">
            <h6><i class="fas fa-images"></i> Gallery Gambar (<?= mysqli_num_rows($qImages) ?>)</h6>
        </div>
        <div class="card-body text-center">
            <?php while ($img = mysqli_fetch_assoc($qImages)): ?>
                <a href="../master/img/assets/<?= $img['primary_image'] ?>" data-lightbox="asset-gallery" data-title="<?= htmlspecialchars($asset['assets_name']) ?>">
                    <img src="../master/img/assets/<?= $img['primary_image'] ?>" class="gallery-thumb" alt="Asset Image" onerror="this.src='../master/img/no-image.png'">
                </a>
            <?php endwhile; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Daftar Unit -->
    <div class="card shadow mb-4 detail-card">
        <div class="card-header">
            <h6><i class="fas fa-list"></i> Daftar Unit (<?= $total_unit ?> unit)</h6>
        </div>
        <div class="card-body">
            <?php if ($total_unit > 0): ?>
                <div class="table-responsive">
                    <table class="unit-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Lokasi</th>
                                <th>Kondisi</th>
                                <th>Penanggung Jawab</th>
                                <th>Department</th>
                                <th>Tanggal Input</th>
                                <th>Gambar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            mysqli_data_seek($qUnits, 0);
                            while ($unit = mysqli_fetch_assoc($qUnits)): 
                            ?>
                            <tr>
                                <td><?= $no++ ?></div>
                                <td>
                                    <?= $unit['lokasi_name'] ?? '-' ?> 
                                    <?= !empty($unit['lokasi_lantai']) ? '(' . $unit['lokasi_lantai'] . ')' : '' ?>
                                 </div>
                                <td><?= $unit['kondisi_name'] ?? '-' ?></div>
                                <td><?= htmlspecialchars($unit['unit_user'] ?? '-') ?></div>
                                <td><?= $unit['unit_dep_code'] ?? '-' ?></div>
                                <td><?= date('d/m/Y H:i', strtotime($unit['primary_timestamp'])) ?></div>
                                <td>
                                    <?php if (!empty($unit['primary_image'])): ?>
                                        <a href="../master/img/assets/<?= $unit['primary_image'] ?>" data-lightbox="unit-<?= $unit['primary_id'] ?>" title="Unit <?= $no-1 ?>">
                                            <img src="../master/img/assets/<?= $unit['primary_image'] ?>" style="width:40px;height:40px;object-fit:cover;border-radius:4px;" onerror="this.src='../master/img/no-image.png'">
                                        </a>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">No Image</span>
                                    <?php endif; ?>
                                 </div>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-center text-muted">Tidak ada unit untuk asset ini</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Ringkasan Lokasi -->
    <div class="card shadow mb-4 detail-card">
        <div class="card-header">
            <h6><i class="fas fa-map-marker-alt"></i> Ringkasan per Lokasi</h6>
        </div>
        <div class="card-body">
            <?php
            $qRingkasan = mysqli_query($conn, "
                SELECT 
                    l.lokasi_name,
                    l.lokasi_lantai,
                    COUNT(p.primary_id) as jumlah_unit,
                    GROUP_CONCAT(DISTINCT kond.kondisi_name SEPARATOR ', ') as kondisi_list
                FROM tbl_primary p
                INNER JOIN tbl_lokasi l ON p.lokasi_id = l.lokasi_id
                LEFT JOIN tbl_kondisi kond ON p.kondisi_id = kond.kondisi_id
                WHERE p.assets_id = '$assets_id'
                GROUP BY l.lokasi_id
                ORDER BY l.lokasi_name
            ");
            
            if (mysqli_num_rows($qRingkasan) > 0):
            ?>
                <div class="row">
                    <?php while ($r = mysqli_fetch_assoc($qRingkasan)): ?>
                        <div class="col-md-4 mb-3">
                            <div class="info-label">
                                <i class="fas fa-map-pin"></i> <?= $r['lokasi_name'] ?> <?= !empty($r['lokasi_lantai']) ? '(' . $r['lokasi_lantai'] . ')' : '' ?>
                            </div>
                            <div class="info-value">
                                <strong><?= $r['jumlah_unit'] ?></strong> unit<br>
                                <small>Kondisi: <?= $r['kondisi_list'] ?? '-' ?></small>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p class="text-center text-muted">Tidak ada data lokasi</p>
            <?php endif; ?>
        </div>
    </div>

</div>
<!-- /.container-fluid -->

<!-- Copy Notification -->
<div class="copy-notification" id="copyNotification" style="display:none; position:fixed; top:20px; right:20px; background:#28a745; color:white; padding:10px 20px; border-radius:5px; z-index:9999;">
    <i class="fas fa-check-circle"></i> Kode berhasil disalin!
</div>

<?php include '../menu/footer.php'; ?>

<!-- Lightbox2 JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/js/lightbox.min.js"></script>

<script>
// Fungsi copy ke clipboard
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        // Tampilkan notifikasi
        var notification = document.getElementById('copyNotification');
        notification.style.display = 'block';
        
        // Hilangkan notifikasi setelah 2 detik
        setTimeout(function() {
            notification.style.display = 'none';
        }, 2000);
        
        // SweetAlert alternatif
        Swal.fire({
            icon: 'success',
            title: 'Tersalin!',
            text: 'Kode ' + text + ' berhasil disalin ke clipboard',
            timer: 1500,
            showConfirmButton: false
        });
    }).catch(function(err) {
        console.error('Gagal menyalin: ', err);
        
        // Fallback untuk browser lama
        var textarea = document.createElement('textarea');
        textarea.value = text;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        
        alert('Kode berhasil disalin!');
    });
}

// Inisialisasi Lightbox
lightbox.option({
    'resizeDuration': 200,
    'wrapAround': true,
    'albumLabel': 'Gambar %1 dari %2'
});
</script>

</body>
</html>