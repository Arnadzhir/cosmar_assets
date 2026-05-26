<?php
include '../auth/auth.php';
allowRole([1,3]);

include '../config/koneksi.php';

/* =====================
   AMBIL DATA USER LOGIN
===================== */
$user_id = $_SESSION['user_id'];
$user_level = $_SESSION['user_level'];

// Ambil data user yang login
$qUser = mysqli_query($conn, "
    SELECT kar.*, d.dep_name, d.dep_code
    FROM tbl_karyawan kar
    LEFT JOIN tbl_dep d ON kar.dep_id = d.dep_id
    WHERE kar.karyawan_id = '$user_id'
");
$user = mysqli_fetch_assoc($qUser);

if (!$user) {
    $qUserFallback = mysqli_query($conn, "
        SELECT u.*, d.dep_name, d.dep_code
        FROM tbl_user u
        LEFT JOIN tbl_dep d ON u.dep_id = d.dep_id
        WHERE u.user_id = '$user_id'
    ");
    $user = mysqli_fetch_assoc($qUserFallback);
}

// Ambil data untuk dropdown
$merk       = mysqli_query($conn, "SELECT * FROM tbl_merk ORDER BY merk_name ASC");
$type       = mysqli_query($conn, "SELECT * FROM tbl_type ORDER BY type_name ASC");
$supplier   = mysqli_query($conn, "SELECT * FROM tbl_supplier ORDER BY supplier_name ASC");
$produsen   = mysqli_query($conn, "SELECT * FROM tbl_produsen ORDER BY produsen_region ASC");
$kondisi    = mysqli_query($conn, "SELECT * FROM tbl_kondisi ORDER BY kondisi_name ASC");

// Ambil data lokasi untuk dropdown
$qLokasi = mysqli_query($conn, "SELECT * FROM tbl_lokasi ORDER BY lokasi_name ASC");
$lokasi_options = '';
while($r = mysqli_fetch_assoc($qLokasi)) {
    $lokasi_options .= "<option value='{$r['lokasi_id']}'>{$r['lokasi_name']} - Lt.{$r['lokasi_lantai']}</option>";
}

include '../menu/header.php';
include '../menu/sidebar.php';
include '../menu/topbar.php';
?>

<style>
    .lokasi-item {
        margin-bottom: 15px;
        padding: 15px;
        background-color: #f8f9fc;
        border: 1px solid #e3e6f0;
        border-radius: 5px;
        position: relative;
    }
    .lokasi-header {
        margin-bottom: 10px;
        padding-bottom: 10px;
        border-bottom: 1px dashed #d1d3e2;
    }
    .btn-remove-lokasi {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 38px;
        width: 100%;
    }
    .btn-sm-square {
        width: 38px;
        height: 38px;
        padding: 0;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .upload-box {
        cursor: pointer;
        background-color: #f8f9fc;
        transition: all 0.3s;
        border: 2px dashed #d1d3e2;
    }
    .upload-box:hover {
        background-color: #eaeef5;
    }
    .upload-box.dragover {
        background-color: #d4e3fd;
        border-color: #4e73df !important;
    }
    .required-field:after {
        content: " *";
        color: red;
    }
    .select2-container {
        width: 100% !important;
        z-index: 9999;
    }
    .form-group label {
        font-weight: 600;
        font-size: 13px;
    }
    .auto-generate {
        background-color: #f0f2f5;
        font-weight: bold;
        color: #4e73df;
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
</style>

<div class="container-fluid">

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-plus-circle"></i> Request Assets Baru
        </h1>
        <a href="index.php" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="card shadow">
        <div class="card-body">

            <form action="proses.php" method="POST" enctype="multipart/form-data" id="multiForm">

                <input type="hidden" name="karyawan_id" value="<?= $user['karyawan_id'] ?? $user['user_id'] ?>">

                <!-- ==================== SECTION 1: DATA PENGAJU ==================== -->
                <div class="section-title">
                    <h5><i class="fas fa-user-check"></i> Data Pengaju</h5>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="required-field">Departemen</label>
                            <input type="text" id="departemen_name" class="form-control" value="<?= htmlspecialchars($user['dep_name'] ?? '-') ?> (<?= $user['dep_code'] ?? '-' ?>)" readonly>
                            <input type="hidden" name="dep_id" id="dep_id" value="<?= $user['dep_id'] ?? '' ?>">
                            <input type="hidden" name="dep_code" id="dep_code" value="<?= $user['dep_code'] ?? '' ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="required-field">User Pengguna Assets</label>
                            <select name="karyawan_id" id="karyawan_id" class="form-control select2" required>
                                <option value="">-- Pilih Karyawan --</option>
                                <?php
                                // Ambil data karyawan berdasarkan departemen user yang login
                                $dep_id_user = $user['dep_id'] ?? 0;
                                $qKaryawan = mysqli_query($conn, "
                                    SELECT karyawan_id, karyawan_name, karyawan_no 
                                    FROM tbl_karyawan 
                                    WHERE dep_id = '$dep_id_user' 
                                    ORDER BY karyawan_name ASC
                                ");
                                while ($karyawan = mysqli_fetch_assoc($qKaryawan)) {
                                    $selected = ($karyawan['karyawan_id'] == ($user['karyawan_id'] ?? $user['user_id'])) ? 'selected' : '';
                                    echo "<option value='{$karyawan['karyawan_id']}' {$selected}>{$karyawan['karyawan_name']} - {$karyawan['karyawan_no']}</option>";
                                }
                                ?>
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
                                while($r = mysqli_fetch_assoc($type)): ?>
                                    <option value="<?= $r['type_id'] ?>"><?= htmlspecialchars($r['type_name']) ?></option>
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
                                while($r = mysqli_fetch_assoc($merk)): ?>
                                    <option value="<?= $r['merk_id'] ?>"><?= htmlspecialchars($r['merk_name']) ?></option>
                                <?php endwhile; ?>
                            </select>
                            <small><a href="../merk/tambah.php" target="_blank">+ Tambah Merk</a></small>                    
                        </div>
                    </div>

                    <!-- 3. Model / Spesifikasi (assets_model) -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="required-field">Model / Spesifikasi</label>
                            <input type="text" name="assets_model" id="assets_model" class="form-control" placeholder="Contoh: Xenia 1.3L, ThinkPad T490, dll" required>
                        </div>
                    </div>

                    <!-- 4. Nama Asset (Auto Generate - Read Only) -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="required-field">Nama Asset</label>
                            <input type="text" name="assets_name" id="assets_name" class="form-control auto-generate" readonly required>
                            <small class="text-muted">Nama asset akan otomatis tergenerate dari Type, Merk, dan Model</small>
                        </div>
                    </div>

                    <!-- 5. Estimasi Masa Manfaat -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="required-field">Estimasi Masa Manfaat</label>
                            <select name="assets_life" class="form-control" required>
                                <option value="">-- Pilih Tahun --</option>
                                <option value="4">4 Tahun</option>
                                <option value="8">8 Tahun</option>
                                <option value="16">16 Tahun</option>
                                <option value="20">20 Tahun</option>
                            </select>
                        </div>
                    </div>

                    <!-- 6. Detail Spesifikasi (assets_spec) -->
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>Detail Spesifikasi</label>
                            <textarea name="assets_spec" id="assets_spec" class="form-control" rows="3" placeholder="Contoh: Processor Intel i5-1135G7, RAM 8GB, SSD 512GB, WiFi 6, Bluetooth 5.0, dll"></textarea>
                        </div>
                    </div>

                    <!-- 7. Kode Asset Terkait (assets_target) -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Kode Asset Terkait</label>
                            <select name="assets_target" id="assets_target" class="form-control select2">
                                <option value="">-- Pilih Kode Asset --</option>
                                <?php
                                $queryAssets = mysqli_query($conn, "SELECT assets_id, assets_kode, assets_name FROM tbl_assets WHERE assets_kode IS NOT NULL AND assets_kode != '' ORDER BY assets_kode ASC");
                                while ($asset = mysqli_fetch_assoc($queryAssets)) {
                                    echo "<option value='{$asset['assets_kode']}'>{$asset['assets_kode']} - " . htmlspecialchars($asset['assets_name']) . "</option>";
                                }
                                ?>
                            </select>
                            <small class="text-muted">Pilih kode asset yang terkait dengan asset baru ini (opsional)</small>
                        </div>
                    </div>

                    <!-- 8. Kondisi -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="required-field">Kondisi</label>
                            <select name="kondisi_id" id="kondisi_id" class="form-control select2" required>
                                <option value="">-- Pilih Kondisi --</option>
                                <?php 
                                mysqli_data_seek($kondisi, 0);
                                while($r = mysqli_fetch_assoc($kondisi)): ?>
                                    <option value="<?= $r['kondisi_id'] ?>"><?= htmlspecialchars($r['kondisi_name']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>

                    <!-- 9. Kapasitas -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Kapasitas</label>
                            <input type="number" name="assets_cap" class="form-control" placeholder="Isi Angka">
                        </div>
                    </div>

                    <!-- 10. Unit -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Unit</label>
                            <select name="assets_uom" class="form-control select2">
                                <option value="">-- Pilih Unit --</option>
                                <option value="GB">Gigabyte (GB)</option>
                                <option value="TB">Terabyte (TB)</option>
                                <option value="g">gram (g)</option>
                                <option value="kg">kilogram (kg)</option>
                                <option value="L">Liter (L)</option>
                                <option value="ml">Mililiter (ml)</option>
                                <option value="mm">Milimeter (mm)</option>
                                <option value="cm">Centimeter (cm)</option>
                                <option value="m">Meter (m)</option>
                                <option value="cc">Cubic Centimeter (cc)</option>
                                <option value="Pcs">Pieces (Pcs)</option>
                            </select>                             
                        </div>
                    </div>

                    <!-- 11. Supplier -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Supplier</label>
                            <select name="supplier_id" class="form-control select2">
                                <option value="">-- Pilih Supplier --</option>
                                <?php 
                                mysqli_data_seek($supplier, 0);
                                while($r = mysqli_fetch_assoc($supplier)): ?>
                                    <option value="<?= $r['supplier_id'] ?>"><?= htmlspecialchars($r['supplier_name']) ?></option>
                                <?php endwhile; ?>
                            </select>
                            <small><a href="../supplier/tambah.php" target="_blank">+ Tambah Supplier</a></small>
                        </div>
                    </div>

                    <!-- 12. Asal Negara Produsen -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Asal Negara Produsen</label>
                            <select name="produsen_id" class="form-control select2">
                                <option value="">-- Pilih Produsen --</option>
                                <?php 
                                mysqli_data_seek($produsen, 0);
                                while($r = mysqli_fetch_assoc($produsen)): ?>
                                    <option value="<?= $r['produsen_id'] ?>"><?= htmlspecialchars($r['produsen_region']) ?> (<?= $r['produsen_code'] ?>)</option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>

                    <!-- 13. Catatan -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Catatan</label>
                            <input type="text" name="assets_note" class="form-control" placeholder="Masukan informasi tambahan (garansi, serial number, dll)">
                        </div>
                    </div>
                </div>

                <!-- ==================== SECTION 3: LOKASI ==================== -->
                <div class="section-title mt-4">
                    <h5><i class="fas fa-map-marker-alt"></i> Lokasi Asset</h5>
                </div>

                <div id="lokasi_container">
                    <div class="lokasi-item" id="lokasi_1">
                        <div class="lokasi-header">
                            <h6 class="text-primary">Unit #1</h6>
                        </div>
                        <div class="row align-items-end">
                            <div class="col-md-10">
                                <div class="form-group">
                                    <label class="required-field">Lokasi</label>
                                    <select name="lokasi_id[]" class="form-control select2" required>
                                        <option value="">-- Pilih Lokasi --</option>
                                        <?= $lokasi_options ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <div>
                                        <button type="button" class="btn btn-danger btn-sm-square btn-remove-lokasi" onclick="removeLokasi(1)" style="display: none;">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="qty[]" value="1">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <button type="button" id="addLokasi" class="btn btn-success btn-sm">
                            <i class="fas fa-plus"></i> Tambah Unit
                        </button>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Total Unit</label>
                            <input type="number" name="total_unit" id="total_unit" class="form-control" value="1" readonly>
                            <small class="text-info">Jumlah unit asset yang akan diajukan</small>
                        </div>
                    </div>
                </div>

                <!-- ==================== SECTION 4: GAMBAR ==================== -->
                <div class="section-title mt-4">
                    <h5><i class="fas fa-image"></i> Upload Gambar Asset</h5>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <div id="uploadArea" class="upload-box text-center p-4 border rounded">
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
                            <small class="text-muted">Format: JPG / PNG (Max 2MB)</small>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-md-12">
                        <button type="submit" name="create" class="btn btn-primary">
                            <i class="fas fa-save"></i> Ajukan Asset
                        </button>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Batal
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
    let assetCounter = 1;
    let typeName = '';
    let merkName = '';
    
    // Fungsi untuk meng-generate nama asset
    function generateAssetName() {
        let typeText = $('#type_id option:selected').text();
        let merkText = $('#merk_id option:selected').text();
        let modelText = $('#assets_model').val();
        
        // Ambil hanya nama type (tanpa kode)
        if (typeText.includes(' - ')) {
            typeText = typeText.split(' - ')[1];
        }
        
        // Gabungkan menjadi nama asset
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
        } else {
            $('#assets_name').val('');
        }
    }
    
    // Event untuk auto-generate nama asset
    $('#type_id, #merk_id, #assets_model').on('change keyup', function() {
        generateAssetName();
    });
    
    // Inisialisasi generate nama asset pertama kali jika ada nilai
    setTimeout(function() {
        generateAssetName();
    }, 500);
    
    function hitungTotalUnit() {
        let total = $('.lokasi-item').length;
        $('#total_unit').val(total);
    }
    
    $('#addLokasi').click(function(e) {
        e.preventDefault();
        
        assetCounter++;
        
        if (assetCounter > 1) {
            $('.btn-remove-lokasi').show();
        }
        
        let newUnit = `
            <div class="lokasi-item" id="lokasi_${assetCounter}">
                <div class="lokasi-header">
                    <h6 class="text-primary">Unit #${assetCounter}</h6>
                </div>
                <div class="row align-items-end">
                    <div class="col-md-10">
                        <div class="form-group">
                            <label class="required-field">Lokasi</label>
                            <select name="lokasi_id[]" class="form-control select2" required>
                                <option value="">-- Pilih Lokasi --</option>
                                <?= $lokasi_options ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <div>
                                <button type="button" class="btn btn-danger btn-sm-square btn-remove-lokasi" onclick="removeLokasi(${assetCounter})">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <input type="hidden" name="qty[]" value="1">
            </div>
        `;
        
        $('#lokasi_container').append(newUnit);
        
        // Inisialisasi select2 untuk unit baru
        $('#lokasi_' + assetCounter + ' .select2').select2({
            theme: 'bootstrap4',
            width: '100%',
            placeholder: '-- Cari atau Pilih Lokasi --'
        });
        
        hitungTotalUnit();
    });
    
    window.removeLokasi = function(id) {
        if ($('.lokasi-item').length > 1) {
            var selectToDestroy = $('#lokasi_' + id).find('select');
            if (selectToDestroy.hasClass('select2-hidden-accessible')) {
                selectToDestroy.select2('destroy');
            }
            $('#lokasi_' + id).remove();
            
            $('.lokasi-item').each(function(index) {
                let newIndex = index + 1;
                $(this).find('.lokasi-header h6').text('Unit #' + newIndex);
                $(this).attr('id', 'lokasi_' + newIndex);
                $(this).find('.btn-remove-lokasi').attr('onclick', 'removeLokasi(' + newIndex + ')');
            });
            
            if ($('.lokasi-item').length === 1) {
                $('.btn-remove-lokasi').hide();
            }
            
            hitungTotalUnit();
        } else {
            alert('Minimal 1 unit harus diisi');
        }
    };
    
    $('form').on('submit', function(e) {
        let totalUnit = parseInt($('#total_unit').val());
        if (totalUnit < 1) {
            e.preventDefault();
            alert('Minimal 1 unit harus ditambahkan');
            return false;
        }
        
        let valid = true;
        $('select[name="lokasi_id[]"]').each(function(index) {
            if (!$(this).val()) {
                valid = false;
                alert('Semua lokasi harus dipilih (Unit #' + (index + 1) + ')');
                return false;
            }
        });
        
        // Validasi nama asset tidak boleh kosong
        if (!$('#assets_name').val()) {
            e.preventDefault();
            alert('Nama asset harus diisi (Type, Merk, dan Model harus dipilih)');
            return false;
        }
        
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