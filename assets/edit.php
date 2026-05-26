<?php
include '../auth/auth.php';
allowRole([1]);

include '../config/koneksi.php';

$id = intval($_GET['id']);

$data = mysqli_query($conn, "
    SELECT * 
    FROM tbl_assets 
    WHERE assets_id = $id
");

$row = mysqli_fetch_assoc($data);

if (!$row) {
    header("Location: index.php");
    exit;
}

// Ambil data untuk dropdown
$kategori = mysqli_query($conn, "SELECT * FROM tbl_kategori ORDER BY kategori_name ASC");
$merk     = mysqli_query($conn, "SELECT * FROM tbl_merk ORDER BY merk_name ASC");
$type     = mysqli_query($conn, "SELECT * FROM tbl_type ORDER BY type_name ASC");
$supplier = mysqli_query($conn, "SELECT * FROM tbl_supplier ORDER BY supplier_name ASC");
$produsen = mysqli_query($conn, "SELECT * FROM tbl_produsen ORDER BY produsen_region ASC");

// Ambil data assets untuk dropdown assets_target (kode assets terkait)
$assets_target_list = mysqli_query($conn, "
    SELECT assets_id, assets_kode, assets_name 
    FROM tbl_assets 
    WHERE assets_kode IS NOT NULL AND assets_kode != '' 
    AND assets_id != $id
    ORDER BY assets_kode ASC
");

include '../menu/header.php';
include '../menu/sidebar.php';
include '../menu/topbar.php';
?>

<style>
    .required-field:after {
        content: " *";
        color: red;
    }
    
    .select2-container {
        width: 100% !important;
    }

    /* PERBAIKAN SELECT2 TULISAN KE BAWAH */
    .select2-container--bootstrap4 .select2-selection--single {
        height: calc(1.5em + 0.75rem + 2px) !important; 
        display: flex !important;
        align-items: center !important; 
        position: relative !important; /* Penting sebagai patokan posisi absolut */
    }
    
    .select2-container--bootstrap4 .select2-selection--single .select2-selection__rendered,
    .select2-container--bootstrap4 .select2-selection--single .select2-selection__placeholder {
        line-height: normal !important;
        padding-top: 0 !important;
        padding-bottom: 0 !important;
        margin-top: 0 !important;
        padding-right: 50px !important; /* Jarak aman agar teks tidak menabrak tombol X dan panah */
    }

    /* Memposisikan tombol X (clear) tepat di kanan (sebelum panah) */
    .select2-container--bootstrap4 .select2-selection__clear {
        position: absolute !important;
        top: 50% !important;
        transform: translateY(-50%) !important;
        right: 30px !important; /* Jarak dari ujung kanan */
        z-index: 10 !important;
        background: transparent !important;
        width: 20px;
        text-align: center;
    }

    /* Memposisikan panah dropdown di ujung kanan */
    .select2-container--bootstrap4 .select2-selection--single .select2-selection__arrow {
        position: absolute !important;
        top: 50% !important;
        transform: translateY(-50%) !important;
        right: 8px !important;
    }
</style>

<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap4-theme@1.0.0/dist/select2-bootstrap4.min.css" rel="stylesheet" />

<div class="container-fluid">

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-edit"></i> Edit Sistem Assets
        </h1>
        <a href="index.php" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="card shadow">
        <div class="card-body">

            <form action="proses.php" method="POST">

                <input type="hidden" name="assets_id" value="<?= $row['assets_id'] ?>">

                <!-- Kode Assets (Readonly) -->
                <div class="form-group">
                    <label class="required-field">Kode Assets</label>
                    <input type="text" name="assets_kode" class="form-control" value="<?= $row['assets_kode'] ?>" readonly>
                </div>

                <!-- Nama Assets -->
                <div class="form-group">
                    <label class="required-field">Nama Assets</label>
                    <input type="text" name="assets_name" class="form-control" value="<?= htmlspecialchars($row['assets_name']) ?>" required>
                </div>

                <!-- Estimasi Masa Manfaat -->
                <div class="form-group">
                    <label class="required-field">Estimasi Masa Manfaat</label>
                    <select name="assets_life" class="form-control" required>
                        <option value="">-- Pilih Tahun --</option>
                        <option value="4" <?= $row['assets_life'] == '4' ? 'selected' : '' ?>>4 Tahun</option>
                        <option value="8" <?= $row['assets_life'] == '8' ? 'selected' : '' ?>>8 Tahun</option>
                        <option value="16" <?= $row['assets_life'] == '16' ? 'selected' : '' ?>>16 Tahun</option>
                        <option value="20" <?= $row['assets_life'] == '20' ? 'selected' : '' ?>>20 Tahun</option>
                    </select>
                </div>

                <!-- Kategori -->
                <div class="form-group">
                    <label class="required-field">Kategori</label>
                    <select name="kategori_id" id="kategori_id" class="form-control select2" required>
                        <option value="">-- Pilih Kategori --</option>
                        <?php 
                        mysqli_data_seek($kategori, 0);
                        while($k = mysqli_fetch_assoc($kategori)): 
                            $selected = ($k['kategori_id'] == $row['kategori_id']) ? 'selected' : '';
                        ?>
                            <option value="<?= $k['kategori_id'] ?>" <?= $selected ?>>
                                <?= htmlspecialchars($k['kategori_name']) ?> - <?= htmlspecialchars($k['kategori_line']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <small><a href="../kategori/tambah.php" target="_blank">+ Tambah Kategori</a></small>
                </div>

                <!-- Merk -->
                <div class="form-group">
                    <label class="required-field">Merk</label>
                    <select name="merk_id" id="merk_id" class="form-control select2" required>
                        <option value="">-- Pilih Merk --</option>
                        <?php 
                        mysqli_data_seek($merk, 0);
                        while($m = mysqli_fetch_assoc($merk)): 
                            $selected = ($m['merk_id'] == $row['merk_id']) ? 'selected' : '';
                        ?>
                            <option value="<?= $m['merk_id'] ?>" <?= $selected ?>><?= htmlspecialchars($m['merk_name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                    <small><a href="../merk/tambah.php" target="_blank">+ Tambah Merk</a></small>
                </div>

                <!-- Type -->
                <div class="form-group">
                    <label class="required-field">Type</label>
                    <select name="type_id" id="type_id" class="form-control select2" required>
                        <option value="">-- Pilih Type --</option>
                        <?php 
                        mysqli_data_seek($type, 0);
                        while($t = mysqli_fetch_assoc($type)): 
                            $selected = ($t['type_id'] == $row['type_id']) ? 'selected' : '';
                        ?>
                            <option value="<?= $t['type_id'] ?>" <?= $selected ?>><?= htmlspecialchars($t['type_name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                    <small><a href="../type/tambah.php" target="_blank">+ Tambah Type</a></small>
                </div>

                <!-- Spesifikasi -->
                <div class="form-group">
                    <label>Spesifikasi</label>
                    <textarea name="assets_spec" class="form-control" rows="3"><?= htmlspecialchars($row['assets_spec']) ?></textarea>
                </div>

                <!-- Peruntukan Assets (assets_target) -->
                <div class="form-group">
                    <label>Kode Assets Terkait (Peruntukan)</label>
                    <select name="assets_target" id="assets_target" class="form-control select2">
                        <option value="">-- Pilih Kode Assets --</option>
                        <?php 
                        mysqli_data_seek($assets_target_list, 0);
                        while($a = mysqli_fetch_assoc($assets_target_list)): 
                            $selected = ($a['assets_kode'] == $row['assets_target']) ? 'selected' : '';
                        ?>
                            <option value="<?= $a['assets_kode'] ?>" <?= $selected ?>>
                                <?= $a['assets_kode'] ?> - <?= htmlspecialchars($a['assets_name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <small class="text-muted">Pilih kode asset yang terkait dengan asset ini (opsional)</small>
                </div>

                <!-- Kapasitas dan Unit -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Kapasitas</label>
                            <input type="number" name="assets_cap" class="form-control" value="<?= $row['assets_cap'] ?>" placeholder="Isi Angka">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Unit</label>
                            <select name="assets_uom" class="form-control select2">
                                <option value="">-- Pilih Unit --</option>
                                <option value="GB" <?= ($row['assets_uom'] ?? '') == 'GB' ? 'selected' : '' ?>>Gigabyte (GB)</option>
                                <option value="TB" <?= ($row['assets_uom'] ?? '') == 'TB' ? 'selected' : '' ?>>Terabyte (TB)</option>
                                <option value="g" <?= ($row['assets_uom'] ?? '') == 'g' ? 'selected' : '' ?>>gram (g)</option>
                                <option value="kg" <?= ($row['assets_uom'] ?? '') == 'kg' ? 'selected' : '' ?>>kilogram (kg)</option>
                                <option value="L" <?= ($row['assets_uom'] ?? '') == 'L' ? 'selected' : '' ?>>Liter (L)</option>
                                <option value="ml" <?= ($row['assets_uom'] ?? '') == 'ml' ? 'selected' : '' ?>>Mililiter (ml)</option>
                                <option value="mm" <?= ($row['assets_uom'] ?? '') == 'mm' ? 'selected' : '' ?>>Milimeter (mm)</option>
                                <option value="cm" <?= ($row['assets_uom'] ?? '') == 'cm' ? 'selected' : '' ?>>Centimeter (cm)</option>
                                <option value="m" <?= ($row['assets_uom'] ?? '') == 'm' ? 'selected' : '' ?>>Meter (m)</option>
                                <option value="cc" <?= ($row['assets_uom'] ?? '') == 'cc' ? 'selected' : '' ?>>Cubic Centimeter (cc)</option>
                                <option value="Pcs" <?= ($row['assets_uom'] ?? '') == 'Pcs' ? 'selected' : '' ?>>Pieces (Pcs)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Supplier -->
                <div class="form-group">
                    <label>Supplier</label>
                    <select name="supplier_id" class="form-control select2">
                        <option value="">-- Pilih Supplier --</option>
                        <?php 
                        mysqli_data_seek($supplier, 0);
                        while($s = mysqli_fetch_assoc($supplier)): 
                            $selected = ($s['supplier_id'] == $row['supplier_id']) ? 'selected' : '';
                        ?>
                            <option value="<?= $s['supplier_id'] ?>" <?= $selected ?>><?= htmlspecialchars($s['supplier_name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                    <small><a href="../supplier/tambah.php" target="_blank">+ Tambah Supplier</a></small>
                </div>

                <!-- Produsen -->
                <div class="form-group">
                    <label>Asal Negara Produsen</label>
                    <select name="produsen_id" class="form-control select2">
                        <option value="">-- Pilih Produsen --</option>
                        <?php 
                        mysqli_data_seek($produsen, 0);
                        while($p = mysqli_fetch_assoc($produsen)): 
                            $selected = ($p['produsen_id'] == $row['produsen_id']) ? 'selected' : '';
                        ?>
                            <option value="<?= $p['produsen_id'] ?>" <?= $selected ?>>
                                <?= htmlspecialchars($p['produsen_region']) ?> (<?= $p['produsen_code'] ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- Harga -->
                <div class="form-group">
                    <label>Harga</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Rp</span>
                        </div>
                        <input type="text" name="assets_price_display" id="assets_price_display" class="form-control money-format" 
                               value="<?= !empty($row['assets_price']) ? number_format($row['assets_price'], 0, ',', '.') : '' ?>" placeholder="0">
                        <input type="hidden" name="assets_price" id="assets_price" value="<?= $row['assets_price'] ?>">
                    </div>
                </div>

                <!-- Tanggal Beli -->
                <div class="form-group">
                    <label>Tanggal Beli</label>
                    <input type="date" name="assets_date" class="form-control" value="<?= $row['assets_date'] != '0000-00-00' ? $row['assets_date'] : '' ?>">
                </div>

                <!-- Qty (Readonly) -->
                <div class="form-group">
                    <label>Total Qty (Sistem)</label>
                    <input type="number" name="assets_qty" class="form-control" value="<?= $row['assets_qty'] ?>" readonly>
                    <small class="text-muted">Qty akan terupdate otomatis dari data primary</small>
                </div>

                <!-- Catatan -->
                <div class="form-group">
                    <label>Catatan</label>
                    <textarea name="assets_note" class="form-control" rows="2"><?= htmlspecialchars($row['assets_note']) ?></textarea>
                </div>

                <hr>

                <div class="form-group">
                    <button type="submit" name="edit" class="btn btn-warning">
                        <i class="fas fa-save"></i> Update
                    </button>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                </div>

            </form>

        </div>
    </div>

</div>

<?php include '../menu/footer.php'; ?>

<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    // Inisialisasi Select2
    $('.select2').select2({
        theme: 'bootstrap4',
        width: '100%',
        placeholder: '-- Pilih --',
        allowClear: true
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
});
</script>

</body>
</html>