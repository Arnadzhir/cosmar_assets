<?php
include '../auth/auth.php';
allowRole([1,2]); // Admin dan Operator

include '../config/koneksi.php';

/* =====================
   AMBIL DATA USER LOGIN
===================== */
$user_id    = $_SESSION['user_id'];
$user_level = $_SESSION['user_level'];
$dep_id     = $_SESSION['dep_id'] ?? 0;

/* =====================
   AMBIL DATA ASSETS
===================== */
if (!isset($_GET['id'])) {
    header("Location: index2.php");
    exit;
}

$assets_id = intval($_GET['id']);

// Ambil data assets
$qAssets = mysqli_query($conn, 
    "SELECT 
        a.*,
        kat.kategori_id,
        kat.kategori_name,
        kat.kategori_code,
        m.merk_id,
        m.merk_name,
        t.type_id,
        t.type_name,
        s.supplier_id,
        s.supplier_name,
        pr.produsen_id,
        pr.produsen_region,
        pr.produsen_code,
        kar.karyawan_id,
        kar.karyawan_name,
        d.dep_id,
        d.dep_name,
        d.dep_code
    FROM tbl_assets a
    LEFT JOIN tbl_kategori kat ON a.kategori_id = kat.kategori_id
    LEFT JOIN tbl_merk m ON a.merk_id = m.merk_id
    LEFT JOIN tbl_type t ON a.type_id = t.type_id
    LEFT JOIN tbl_supplier s ON a.supplier_id = s.supplier_id
    LEFT JOIN tbl_produsen pr ON a.produsen_id = pr.produsen_id
    LEFT JOIN tbl_primary p ON a.assets_id = p.assets_id
    LEFT JOIN tbl_karyawan kar ON p.karyawan_id = kar.karyawan_id
    LEFT JOIN tbl_dep d ON kar.dep_id = d.dep_id
    WHERE a.assets_id = $assets_id
    GROUP BY a.assets_id
");

$assets = mysqli_fetch_assoc($qAssets);

if (!$assets) {
    echo "<script>alert('Data tidak ditemukan');window.location='index2.php';</script>";
    exit;
}

// Ambil semua data primary untuk assets ini (multi lokasi)
$qPrimary = mysqli_query($conn, "
    SELECT  
        p.*,
        kond.kondisi_name,
        l.lokasi_name,
        l.lokasi_lantai
    FROM tbl_primary p
    LEFT JOIN tbl_kondisi kond ON p.kondisi_id = kond.kondisi_id
    LEFT JOIN tbl_lokasi l ON p.lokasi_id = l.lokasi_id
    WHERE p.assets_id = $assets_id
    ORDER BY p.primary_id ASC
");

$all_data = [];
while ($row = mysqli_fetch_assoc($qPrimary)) {
    $all_data[] = $row;
}

// Hitung total qty
$total_qty = array_sum(array_column($all_data, 'primary_qty'));

// Ambil data untuk dropdown
$kategori = mysqli_query($conn, "SELECT * FROM tbl_kategori ORDER BY kategori_name ASC");
$merk     = mysqli_query($conn, "SELECT * FROM tbl_merk ORDER BY merk_name ASC");
$type     = mysqli_query($conn, "SELECT * FROM tbl_type ORDER BY type_name ASC");
$kondisi  = mysqli_query($conn, "SELECT * FROM tbl_kondisi ORDER BY kondisi_name ASC");
$supplier = mysqli_query($conn, "SELECT * FROM tbl_supplier ORDER BY supplier_name ASC");
$produsen = mysqli_query($conn, "SELECT * FROM tbl_produsen ORDER BY produsen_region ASC");
$lokasi   = mysqli_query($conn, "SELECT * FROM tbl_lokasi ORDER BY lokasi_name ASC");

// ==================== PERBAIKAN: Ambil karyawan berdasarkan departemen dari ASSET (bukan dari admin login) ====================
// Gunakan dep_id dari asset yang diajukan, bukan dari session admin
$dep_id_asset = $assets['dep_id'] ?? 0;

$qKaryawan = mysqli_query($conn, "
    SELECT karyawan_id, karyawan_name, karyawan_no 
    FROM tbl_karyawan 
    WHERE dep_id = '$dep_id_asset' 
    ORDER BY karyawan_name ASC
");

// Simpan options lokasi untuk JavaScript
$lokasi_options_html = '';
mysqli_data_seek($lokasi, 0);
while ($l = mysqli_fetch_assoc($lokasi)) {
    $lokasi_options_html .= "<option value='{$l['lokasi_id']}'>{$l['lokasi_name']} - {$l['lokasi_lantai']}</option>";
}

// Simpan options kondisi untuk JavaScript
$kondisi_options_html = '';
mysqli_data_seek($kondisi, 0);
while ($k = mysqli_fetch_assoc($kondisi)) {
    $kondisi_options_html .= "<option value='{$k['kondisi_id']}'>{$k['kondisi_name']}</option>";
}

include '../menu/header.php';
include '../menu/sidebar.php';
include '../menu/topbar.php';
?>

<style>
    .upload-box {
        border: 2px dashed #d1d3e2;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.3s ease;
        background: #f8f9fc;
    }
    .upload-box:hover {
        background: #eaecf4;
        border-color: #4e73df;
    }
    .upload-box.dragover {
        background-color: #d4e3fd;
        border-color: #4e73df !important;
    }
    .table td {
        vertical-align: middle;
    }
    .input-group {
        width: 100%;
    }
    .bg-light {
        background-color: #f8f9fc !important;
    }
    .select2-container--default .select2-selection--single {
        height: 38px;
        border: 1px solid #d1d3e2;
        border-radius: 0.35rem;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 36px;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px;
    }
    .current-image {
        max-width: 100%;
        max-height: 150px;
        object-fit: cover;
        border-radius: 4px;
    }
    .section-title {
        background-color: #f8f9fc;
        padding: 10px 15px;
        border-radius: 5px;
        margin-bottom: 20px;
        border-left: 4px solid #4e73df;
    }
    .section-title h5 {
        margin: 0;
        color: #4e73df;
        font-weight: 600;
    }
    .required-field:after {
        content: " *";
        color: red;
    }
    .auto-generate {
        background-color: #f0f2f5;
        font-weight: bold;
        color: #4e73df;
    }
</style>

<div class="container-fluid">

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-edit"></i> Edit Approval Asset
        </h1>
        <a href="index2.php" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="card shadow">
        <div class="card-body">

            <form action="proses2.php" method="POST" enctype="multipart/form-data" id="approvalForm">

                <input type="hidden" name="assets_id" value="<?= $assets['assets_id'] ?>">
                <input type="hidden" name="total_lokasi" value="<?= count($all_data) ?>">
                <input type="hidden" name="dep_id" value="<?= $assets['dep_id'] ?? $dep_id ?>">

                <!-- ==================== SECTION 1: DATA PENGAJU ==================== -->
                <div class="section-title">
                    <h5><i class="fas fa-user-check"></i> Data Pengaju</h5>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="required-field">Departemen</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($assets['dep_name'] ?? '-') ?> (<?= $assets['dep_code'] ?? '-' ?>)" readonly>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="required-field">Penanggung Jawab</label>
                            <select name="karyawan_id" id="karyawan_id" class="form-control select2" required>
                                <option value="">-- Pilih Karyawan --</option>
                                <?php 
                                mysqli_data_seek($qKaryawan, 0);
                                while($karyawan = mysqli_fetch_assoc($qKaryawan)): 
                                    $selected = ($karyawan['karyawan_id'] == ($all_data[0]['karyawan_id'] ?? '')) ? 'selected' : '';
                                ?>
                                    <option value="<?= $karyawan['karyawan_id'] ?>" <?= $selected ?>>
                                        <?= htmlspecialchars($karyawan['karyawan_name']) ?> - <?= $karyawan['karyawan_no'] ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <small class="text-muted">Karyawan yang akan menerima asset ini</small>
                        </div>
                    </div>
                </div>

                <!-- ==================== SECTION 2: DATA ASSET ==================== -->
                <div class="section-title mt-4">
                    <h5><i class="fas fa-box"></i> Data Asset</h5>
                </div>

                <div class="row">
                    <!-- 1. Type -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="required-field">Type</label>
                            <select name="type_id" id="type_id" class="form-control select2" required>
                                <option value="">-- Pilih Type --</option>
                                <?php 
                                mysqli_data_seek($type, 0);
                                while($r = mysqli_fetch_assoc($type)): 
                                    $selected = ($r['type_id'] == ($assets['type_id'] ?? '')) ? 'selected' : '';
                                ?>
                                    <option value="<?= $r['type_id'] ?>" <?= $selected ?>><?= htmlspecialchars($r['type_name']) ?></option>
                                <?php endwhile; ?>
                            </select>
                            <small><a href="../type/tambah.php" target="_blank">+ Tambah Type</a></small>
                        </div>
                    </div>

                    <!-- 2. Merk -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="required-field">Merk</label>
                            <select name="merk_id" id="merk_id" class="form-control select2" required>
                                <option value="">-- Pilih Merk --</option>
                                <?php 
                                mysqli_data_seek($merk, 0);
                                while($r = mysqli_fetch_assoc($merk)): 
                                    $selected = ($r['merk_id'] == ($assets['merk_id'] ?? '')) ? 'selected' : '';
                                ?>
                                    <option value="<?= $r['merk_id'] ?>" <?= $selected ?>><?= htmlspecialchars($r['merk_name']) ?></option>
                                <?php endwhile; ?>
                            </select>
                            <small><a href="../merk/tambah.php" target="_blank">+ Tambah Merk</a></small>                    
                        </div>
                    </div>

                    <!-- 3. Model / Spesifikasi (assets_model) -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="required-field">Model / Spesifikasi</label>
                            <input type="text" name="assets_model" id="assets_model" class="form-control" 
                                   value="<?= htmlspecialchars($assets['assets_model'] ?? '') ?>"
                                   placeholder="Contoh: Xenia 1.3L, ThinkPad T490, dll" required>
                        </div>
                    </div>

                    <!-- 4. Nama Asset (Auto Generate - Read Only) -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="required-field">Nama Asset</label>
                            <input type="text" name="assets_name" id="assets_name" class="form-control auto-generate" 
                                   value="<?= htmlspecialchars($assets['assets_name']) ?>" readonly required>
                        </div>
                    </div>

                    <!-- 5. Estimasi Masa Manfaat -->
                    <div class="col-md-2">
                        <div class="form-group">
                            <label class="required-field">Estimasi Masa Manfaat</label>
                            <select name="assets_life" class="form-control" required>
                                <option value="">-- Pilih Tahun --</option>
                                <option value="4" <?= ($assets['assets_life'] ?? '') == '4' ? 'selected' : '' ?>>4 Tahun</option>
                                <option value="8" <?= ($assets['assets_life'] ?? '') == '8' ? 'selected' : '' ?>>8 Tahun</option>
                                <option value="16" <?= ($assets['assets_life'] ?? '') == '16' ? 'selected' : '' ?>>16 Tahun</option>
                                <option value="20" <?= ($assets['assets_life'] ?? '') == '20' ? 'selected' : '' ?>>20 Tahun</option>
                            </select>
                        </div>
                    </div>

                    <!-- 6. Kondisi Default -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Kondisi Default</label>
                            <select name="kondisi_default" id="kondisi_default" class="form-control select2">
                                <option value="">-- Pilih Kondisi (Opsional) --</option>
                                <?php 
                                mysqli_data_seek($kondisi, 0);
                                while($k = mysqli_fetch_assoc($kondisi)): ?>
                                    <option value="<?= $k['kondisi_id'] ?>"><?= $k['kondisi_name'] ?></option>
                                <?php endwhile; ?>                          
                            </select>
                            <small class="text-muted">Akan diterapkan ke semua lokasi jika diisi</small>
                        </div>
                    </div>

                    <!-- 7. Detail Spesifikasi (assets_spec) -->
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>Detail Spesifikasi</label>
                            <textarea name="assets_spec" id="assets_spec" class="form-control" rows="3" 
                                      placeholder="Contoh: Processor Intel i5-1135G7, RAM 8GB, SSD 512GB, WiFi 6, Bluetooth 5.0, dll"><?= htmlspecialchars($assets['assets_spec'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <!-- 8. Kode Asset Terkait (assets_target) -->
                    <div class="col-md-5">
                        <div class="form-group">
                            <label>Kode Asset Terkait</label>
                            <select name="assets_target" id="assets_target" class="form-control select2">
                                <option value="">-- Pilih Kode Asset --</option>
                                <?php
                                $queryAssets = mysqli_query($conn, "SELECT assets_id, assets_kode, assets_name FROM tbl_assets WHERE assets_kode IS NOT NULL AND assets_kode != '' AND assets_id != '$assets_id' ORDER BY assets_kode ASC");
                                while ($assetOption = mysqli_fetch_assoc($queryAssets)) {
                                    $selected = ($assets['assets_target'] == $assetOption['assets_kode']) ? 'selected' : '';
                                    echo "<option value='{$assetOption['assets_kode']}' {$selected}>{$assetOption['assets_kode']} - " . htmlspecialchars($assetOption['assets_name']) . "</option>";
                                }
                                ?>
                            </select>
                            <small class="text-muted">Pilih kode asset yang terkait dengan asset ini (opsional)</small>
                        </div>
                    </div>

                    <!-- 9. Kapasitas -->
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Kapasitas</label>
                            <input type="number" name="assets_cap" class="form-control" 
                                   value="<?= $assets['assets_cap'] ?? '' ?>" placeholder="Isi Angka">
                        </div>
                    </div>

                    <!-- 10. Unit -->
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Unit</label>
                            <select name="assets_uom" class="form-control select2">
                                <option value="">-- Pilih Unit --</option>
                                <option value="GB" <?= ($assets['assets_uom'] ?? '') == 'GB' ? 'selected' : '' ?>>Gigabyte (GB)</option>
                                <option value="TB" <?= ($assets['assets_uom'] ?? '') == 'TB' ? 'selected' : '' ?>>Terabyte (TB)</option>
                                <option value="g" <?= ($assets['assets_uom'] ?? '') == 'g' ? 'selected' : '' ?>>gram (g)</option>
                                <option value="kg" <?= ($assets['assets_uom'] ?? '') == 'kg' ? 'selected' : '' ?>>kilogram (kg)</option>
                                <option value="L" <?= ($assets['assets_uom'] ?? '') == 'L' ? 'selected' : '' ?>>Liter (L)</option>
                                <option value="ml" <?= ($assets['assets_uom'] ?? '') == 'ml' ? 'selected' : '' ?>>Mililiter (ml)</option>
                                <option value="mm" <?= ($assets['assets_uom'] ?? '') == 'mm' ? 'selected' : '' ?>>Milimeter (mm)</option>
                                <option value="cm" <?= ($assets['assets_uom'] ?? '') == 'cm' ? 'selected' : '' ?>>Centimeter (cm)</option>
                                <option value="m" <?= ($assets['assets_uom'] ?? '') == 'm' ? 'selected' : '' ?>>Meter (m)</option>
                                <option value="cc" <?= ($assets['assets_uom'] ?? '') == 'cc' ? 'selected' : '' ?>>Cubic Centimeter (cc)</option>
                                <option value="Pcs" <?= ($assets['assets_uom'] ?? '') == 'Pcs' ? 'selected' : '' ?>>Pieces (Pcs)</option>
                            </select>
                        </div>
                    </div>

                    <!-- 11. Supplier -->
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Supplier</label>
                            <select name="supplier_id" class="form-control select2">
                                <option value="">-- Pilih Supplier --</option>
                                <?php 
                                mysqli_data_seek($supplier, 0);
                                while($r = mysqli_fetch_assoc($supplier)): 
                                    $selected = ($r['supplier_id'] == ($assets['supplier_id'] ?? '')) ? 'selected' : '';
                                ?>
                                    <option value="<?= $r['supplier_id'] ?>" <?= $selected ?>><?= htmlspecialchars($r['supplier_name']) ?></option>
                                <?php endwhile; ?>
                            </select>
                            <small><a href="../supplier/tambah.php" target="_blank">+ Tambah Supplier</a></small>
                        </div>
                    </div>

                    <!-- 12. Asal Negara Produsen -->
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Asal Negara Produsen</label>
                            <select name="produsen_id" class="form-control select2">
                                <option value="">-- Pilih Produsen --</option>
                                <?php 
                                mysqli_data_seek($produsen, 0);
                                while($r = mysqli_fetch_assoc($produsen)): 
                                    $selected = ($r['produsen_id'] == ($assets['produsen_id'] ?? '')) ? 'selected' : '';
                                ?>
                                    <option value="<?= $r['produsen_id'] ?>" <?= $selected ?>><?= htmlspecialchars($r['produsen_region']) ?> (<?= $r['produsen_code'] ?>)</option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>

                    <!-- 13. Harga Assets -->
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Harga Beli Assets <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">Rp</span>
                                </div>
                                <input type="text" 
                                    name="assets_price_display" 
                                    id="assets_price_display"
                                    value="<?= isset($assets['assets_price']) && $assets['assets_price'] > 0 ? number_format($assets['assets_price'], 0, ',', '.') : '' ?>" 
                                    class="form-control money-format" 
                                    placeholder="0"
                                    required>
                                <input type="hidden" name="assets_price" id="assets_price" value="<?= $assets['assets_price'] ?? '' ?>">
                            </div>
                        </div>
                    </div>

                    <!-- 14. Catatan -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Catatan</label>
                            <textarea name="assets_note" class="form-control" rows="2" 
                                      placeholder="Masukan informasi tambahan (garansi, serial number, dll)"><?= htmlspecialchars($assets['assets_note'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- ==================== SECTION 3: DATA KODE ASSSETS ==================== -->
                <div class="section-title">
                    <h5><i class="fas fa-info"></i> Data Kode Assets</h5>
                </div>
                
                <div class="row">
                    <!-- 01. Kategori -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Kategori <span class="text-danger">*</span></label>
                            <select name="kategori_id" id="kategori_id" class="form-control select2" required>
                                <option value="">-- Pilih Kategori --</option>
                                <?php 
                                mysqli_data_seek($kategori, 0);
                                while($k = mysqli_fetch_assoc($kategori)): 
                                    $selected = ($k['kategori_id'] == ($assets['kategori_id'] ?? '')) ? 'selected' : '';
                                ?>
                                    <option value="<?= $k['kategori_id'] ?>" data-kode="<?= $k['kategori_code'] ?>" <?= $selected ?>>
                                        <?= htmlspecialchars($k['kategori_final']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <small><a href="../kategori/tambah.php" target="_blank">+ Tambah Kategori</a></small>
                        </div>
                    </div>

                    <!-- 02. Tanggal Beli -->
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Tanggal Beli Assets <span class="text-danger">*</span></label>
                            <input type="date" name="assets_date" id="assets_date" 
                                   value="<?= ($assets['assets_date'] ?? '') != '0000-00-00' ? ($assets['assets_date'] ?? '') : '' ?>" 
                                   class="form-control" required>
                        </div>
                    </div>

                    <!-- Kode Assets (Manual Input) -->
                    <div class="col-md-5">
                        <div class="form-group">
                            <label>Generate Kode Assets <span class="text-danger">*</span></label>
                            <div class="row">
                                <div class="col-md-3">
                                    <input type="text" id="kode_prefix" class="form-control bg-light" readonly placeholder="XX-0000-">
                                </div>
                                <div class="col-md-2">
                                    <input type="text" id="kode_suffix" class="form-control" maxlength="3" placeholder="000" required>
                                </div>
                                <div class="col-md-7">
                                    <input type="text" id="assets_kode_full" class="form-control bg-light" readonly placeholder="Kode lengkap akan muncul disini">
                                </div>
                            </div>
                            <input type="hidden" name="assets_kode" id="assets_kode" value="<?= $assets['assets_kode'] ?? '' ?>">
                            <small class="text-muted">Format: KODE-TAHUNBULAN-XXX (isi 3 digit manual)</small>
                        </div>
                    </div>
                </div>

                <hr class="sidebar-divider">

                <!-- ==================== SECTION 4: DETAIL PER LOKASI ==================== -->
                <div class="section-title">
                    <h5><i class="fas fa-map-marker-alt"></i> Distribusi per Lokasi</h5>
                </div>

                <div class="table-responsive mb-4">
                    <table class="table table-bordered">
                        <thead class="thead-light">
                            <tr>
                                <th>No</th>
                                <th>Lokasi</th>
                                <th>Qty</th>
                                <th>Kondisi</th>
                                <th width="100">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="lokasiTableBody">
                            <?php 
                            $no = 1;
                            foreach ($all_data as $data): 
                            ?>
                            <tr>
                                <td class="text-center align-middle"><?= $no++ ?> </div>
                                <td>
                                    <input type="hidden" name="primary_id[]" value="<?= $data['primary_id'] ?>">
                                    <select name="lokasi_id[]" class="form-control select2-lokasi" required>
                                        <option value="">-- Pilih Lokasi --</option>
                                        <?php 
                                        mysqli_data_seek($lokasi, 0);
                                        while ($l = mysqli_fetch_assoc($lokasi)): 
                                            $selected = ($l['lokasi_id'] == $data['lokasi_id']) ? 'selected' : '';
                                        ?>
                                            <option value="<?= $l['lokasi_id'] ?>" <?= $selected ?>>
                                                <?= $l['lokasi_name'] ?> - <?= $l['lokasi_lantai'] ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                 </div>
                                <td>
                                    <input type="number" name="primary_qty[]" 
                                        value="<?= $data['primary_qty'] ?>" 
                                        class="form-control qty-input" 
                                        min="1" 
                                        readonly>
                                 </div>
                                <td class="text-center align-middle">
                                    <select name="kondisi_id[]" class="form-control select2-kondisi" required>
                                        <option value="">-- Pilih Kondisi --</option>
                                        <?php 
                                        mysqli_data_seek($kondisi, 0);
                                        while ($k = mysqli_fetch_assoc($kondisi)): 
                                            $selected = ($k['kondisi_id'] == $data['kondisi_id']) ? 'selected' : '';
                                        ?>
                                            <option value="<?= $k['kondisi_id'] ?>" <?= $selected ?>>
                                                <?= $k['kondisi_name'] ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                 </div>
                                <td class="text-center align-middle">
                                    <?php if (count($all_data) > 1): ?>
                                    <button type="button" class="btn btn-sm btn-danger remove-row">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                 </div>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-info">
                                <td colspan="3" class="text-right"><strong>Total Qty:</strong> </div>
                                <td colspan="2">
                                    <strong id="totalQtyDisplay"><?= $total_qty ?> pcs</strong>
                                 </div>
                            </tr>
                        </tfoot>
                    </table>
                    <!-- Tombol Tambah Lokasi -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <button type="button" id="addLokasi" class="btn btn-success btn-sm">
                                <i class="fas fa-plus"></i> Tambah Lokasi Baru
                            </button>
                        </div>
                    </div>
                </div>

                <!-- ==================== UPLOAD GAMBAR ==================== -->
                <div class="row">
                    <div class="col-md-9">
                        <div class="form-group">
                            <label>Upload Gambar Assets</label>
                            <div id="uploadArea" class="upload-box text-center p-4">
                                <div id="uploadContent">
                                    <i class="fas fa-cloud-upload-alt fa-3x mb-3 text-primary"></i>
                                    <p class="mb-1 font-weight-bold">Drag & Drop Gambar Disini</p>
                                    <small class="text-muted">atau klik untuk memilih file</small>
                                </div>
                                <img id="previewImage" class="img-fluid rounded d-none mt-3" style="max-height:220px;">
                                <button type="button" id="removeImage" class="btn btn-sm btn-danger mt-3 d-none">
                                    <i class="fas fa-trash"></i> Hapus Gambar
                                </button>
                                <input type="file" name="primary_image" id="primary_image" accept="image/png, image/jpeg" hidden>
                            </div>
                            <small class="text-muted">Format: JPG / PNG (Max 2MB). Kosongkan jika tidak ingin mengubah.</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <?php 
                        $gambar_pertama = $all_data[0]['primary_image'] ?? '';
                        if (!empty($gambar_pertama)): 
                        ?>
                        <div class="form-group">
                            <label>Gambar Saat Ini</label>
                            <img src="../master/img/assets/<?= $gambar_pertama ?>" 
                                class="img-fluid rounded shadow current-image" 
                                alt="Current Image">
                            <div class="form-check mt-2">
                                <input type="checkbox" name="hapus_gambar" id="hapus_gambar" value="1" class="form-check-input">
                                <label class="form-check-label text-danger" for="hapus_gambar">Hapus gambar saat ini</label>
                            </div>
                            <input type="hidden" name="gambar_lama" value="<?= $gambar_pertama ?>">
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Tombol Submit -->
                <div class="row mt-4">
                    <div class="col-md-12">
                        <button type="submit" name="approve" class="btn btn-success btn-lg">
                            <i class="fas fa-check"></i> Setujui & Simpan
                        </button>
                        <a href="index2.php" class="btn btn-secondary btn-lg">
                            <i class="fas fa-times"></i> Batal
                        </a>
                    </div>
                </div>

            </form>

        </div>
    </div>
</div>

<?php include '../menu/footer.php'; ?>

<script>
$(document).ready(function() {
    // Inisialisasi select2
    $('.select2').select2({ 
        width: '100%',
        minimumResultsForSearch: 1,
        placeholder: '-- Cari atau Pilih --'
    });
    
    $('.select2-lokasi').select2({
        width: '100%',
        minimumResultsForSearch: 1,
        placeholder: '-- Cari Lokasi --',
        allowClear: true
    });

    $('.select2-kondisi').select2({
        width: '100%',
        minimumResultsForSearch: 1,
        placeholder: '-- Pilih Kondisi --'
    });
    
    // Format Rupiah
    $('.money-format').on('input', function() {
        let value = this.value.replace(/[^\d]/g, '');
        if (value) {
            let formatted = parseInt(value).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            this.value = formatted;
            $('#assets_price').val(value);
        } else {
            $('#assets_price').val('');
        }
    });

    // Fungsi untuk update kode asset lengkap
    function updateFullKode() {
        let prefix = $('#kode_prefix').val();
        let suffix = $('#kode_suffix').val();
        if (prefix && suffix) {
            $('#assets_kode_full').val(prefix + suffix);
            $('#assets_kode').val(prefix + suffix);
        } else if (prefix) {
            $('#assets_kode_full').val(prefix + '___');
        } else {
            $('#assets_kode_full').val('');
        }
    }

    // Update kode prefix
    $('#kategori_id').on('change', function() {
        let selectedOption = $(this).find(':selected');
        let kodeKategori = selectedOption.data('kode');
        let tanggal = $('#assets_date').val();
        
        if (!kodeKategori) {
            $('#kode_prefix').val('');
            updateFullKode();
            return;
        }
        
        if (tanggal) {
            let parts = tanggal.split('-');
            let tahun = parts[0].slice(-2);
            let bulan = parts[1];
            $('#kode_prefix').val(kodeKategori + '-' + tahun + bulan + '-');
        } else {
            $('#kode_prefix').val(kodeKategori + '-');
        }
        
        updateFullKode();
    });
    
    $('#assets_date').on('change', function() {
        let kodeKategori = $('#kategori_id option:selected').data('kode');
        let tanggal = $(this).val();
        
        if (!kodeKategori) {
            $('#kode_prefix').val('');
            updateFullKode();
            return;
        }
        
        if (tanggal) {
            let parts = tanggal.split('-');
            let tahun = parts[0].slice(-2);
            let bulan = parts[1];
            $('#kode_prefix').val(kodeKategori + '-' + tahun + bulan + '-');
        } else {
            $('#kode_prefix').val(kodeKategori + '-');
        }
        
        updateFullKode();
    });
    
    $('#kode_suffix').on('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '').slice(0, 3);
        updateFullKode();
    });
    
    // Auto generate nama asset
    function generateAssetName() {
        let typeText = $('#type_id option:selected').text();
        let merkText = $('#merk_id option:selected').text();
        let modelText = $('#assets_model').val();
        
        if (typeText.includes(' - ')) {
            typeText = typeText.split(' - ')[1];
        }
        
        let assetName = '';
        if (typeText && typeText !== '-- Pilih Type --') {
            assetName += typeText;
        }
        if (merkText && merkText !== '-- Pilih Merk --') {
            assetName += (assetName ? ' ' : '') + merkText;
        }
        if (modelText) {
            assetName += (assetName ? ' ' : '') + modelText;
        }
        
        if (assetName) {
            $('#assets_name').val(assetName.toUpperCase());
        }
    }
    
    $('#type_id, #merk_id, #assets_model').on('change keyup', generateAssetName);
    
    <?php if (!empty($assets['assets_kode'])): 
        $kode_parts = explode('-', $assets['assets_kode']);
        $suffix = end($kode_parts);
        $prefix = substr($assets['assets_kode'], 0, -4);
    ?>
        $('#kode_prefix').val('<?= $prefix ?>');
        $('#kode_suffix').val('<?= $suffix ?>');
        $('#assets_kode_full').val('<?= $assets['assets_kode'] ?>');
        $('#assets_kode').val('<?= $assets['assets_kode'] ?>');
    <?php endif; ?>

    function hitungTotalQty() {
        let total = 0;
        $('input[name="primary_qty[]"]').each(function() {
            total += parseInt($(this).val()) || 0;
        });
        $('#totalQtyDisplay').text(total + ' pcs');
    }
    
    $(document).on('input', 'input[name="primary_qty[]"]', hitungTotalQty);
    
    // Terapkan kondisi default ke semua lokasi
    $('#kondisi_default').on('change', function() {
        let kondisiId = $(this).val();
        if (kondisiId) {
            $('select[name="kondisi_id[]"]').val(kondisiId).trigger('change');
        }
    });
    
    // Tambah lokasi baru
    $('#addLokasi').click(function() {
        let currentRows = $('#lokasiTableBody tr').length;
        let rowNumber = currentRows + 1;
        
        let newRow = `
            <tr>
                <td class="text-center align-middle">${rowNumber}</div>
                <td>
                    <input type="hidden" name="primary_id[]" value="new_${rowNumber}">
                    <select name="lokasi_id[]" class="form-control select2-lokasi" required>
                        <option value="">-- Pilih Lokasi --</option>
                        <?= $lokasi_options_html ?>
                    </select>
                 </div>
                <td>
                    <input type="number" name="primary_qty[]" class="form-control qty-input" min="1" value="1" readonly>
                 </div>
                <td class="text-center align-middle">
                    <select name="kondisi_id[]" class="form-control select2-kondisi" required>
                        <option value="">-- Pilih Kondisi --</option>
                        <?= $kondisi_options_html ?>
                    </select>
                 </div>            
                <td class="text-center align-middle">
                    <button type="button" class="btn btn-sm btn-danger remove-row">
                        <i class="fas fa-trash"></i>
                    </button>
                 </div>
            </tr>
        `;
        $('#lokasiTableBody').append(newRow);
        
        $('#lokasiTableBody tr:last-child .select2-lokasi').select2({
            width: '100%',
            minimumResultsForSearch: 1,
            placeholder: '-- Cari Lokasi --',
            allowClear: true
        });
        
        $('#lokasiTableBody tr:last-child .select2-kondisi').select2({
            width: '100%',
            minimumResultsForSearch: 1,
            placeholder: '-- Pilih Kondisi --'
        });
        
        hitungTotalQty();
    });
    
    // Hapus row
    $(document).on('click', '.remove-row', function() {
        if ($('#lokasiTableBody tr').length > 1) {
            let selectToDestroy = $(this).closest('tr').find('.select2-lokasi');
            if (selectToDestroy.hasClass('select2-hidden-accessible')) {
                selectToDestroy.select2('destroy');
            }
            
            let selectKondisiToDestroy = $(this).closest('tr').find('.select2-kondisi');
            if (selectKondisiToDestroy.hasClass('select2-hidden-accessible')) {
                selectKondisiToDestroy.select2('destroy');
            }
            
            $(this).closest('tr').remove();
            
            // Update nomor urut
            $('#lokasiTableBody tr').each(function(index) {
                $(this).find('td:first').text(index + 1);
            });
            
            hitungTotalQty();
        } else {
            alert('Minimal 1 lokasi harus diisi');
        }
    });
    
    // Validasi sebelum submit
    $('#approvalForm').on('submit', function(e) {
        let suffix = $('#kode_suffix').val();
        if (suffix.length !== 3) {
            e.preventDefault();
            alert('Kode Assets harus 3 digit angka');
            return false;
        }
        
        let harga = $('#assets_price').val();
        if (!harga || harga <= 0) {
            e.preventDefault();
            alert('Harga harus diisi dengan nilai yang valid');
            return false;
        }
        
        updateFullKode();
        
        // Validasi lokasi tidak boleh kosong
        let valid = true;
        $('select[name="lokasi_id[]"]').each(function(index) {
            if (!$(this).val()) {
                valid = false;
                alert('Lokasi baris ke-' + (index + 1) + ' harus dipilih');
                return false;
            }
        });
        
        if (!valid) {
            e.preventDefault();
            return false;
        }
        
        // Validasi kondisi tidak boleh kosong
        $('select[name="kondisi_id[]"]').each(function(index) {
            if (!$(this).val()) {
                valid = false;
                alert('Kondisi baris ke-' + (index + 1) + ' harus dipilih');
                return false;
            }
        });
        
        if (!valid) {
            e.preventDefault();
            return false;
        }
    });
    
    // Upload image handling
    const uploadArea = document.getElementById('uploadArea');
    const fileInput = document.getElementById('primary_image');
    const preview = document.getElementById('previewImage');
    const removeBtn = document.getElementById('removeImage');
    const uploadContent = document.getElementById('uploadContent');

    if (uploadArea) {
        uploadArea.addEventListener('click', () => fileInput.click());
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });
        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragover');
        });
        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            handleFile(e.dataTransfer.files[0]);
        });
    }
    
    fileInput.addEventListener('change', () => {
        if (fileInput.files[0]) handleFile(fileInput.files[0]);
    });
    
    function handleFile(file) {
        if (!file) return;
        if (file.size > 2000000) {
            alert("Ukuran maksimal 2MB");
            return;
        }
        const allowed = ['image/jpeg', 'image/png'];
        if (!allowed.includes(file.type)) {
            alert("Format harus JPG atau PNG");
            return;
        }
        const reader = new FileReader();
        reader.onload = function (e) {
            preview.src = e.target.result;
            preview.classList.remove('d-none');
            removeBtn.classList.remove('d-none');
            uploadContent.classList.add('d-none');
        };
        reader.readAsDataURL(file);
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        fileInput.files = dataTransfer.files;
    }
    
    removeBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        fileInput.value = "";
        preview.src = "#";
        preview.classList.add('d-none');
        removeBtn.classList.add('d-none');
        uploadContent.classList.remove('d-none');
    });
});
</script>

</body>
</html>