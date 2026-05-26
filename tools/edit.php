<?php
include '../auth/auth.php';
allowRole([1,2]);

include '../config/koneksi.php';

$user_id    = $_SESSION['user_id'];
$user_level = $_SESSION['user_level'];
$dep_id     = $_SESSION['dep_id'] ?? 0;
$dep_code   = $_SESSION['dep_code'] ?? '';
$dep_name   = $_SESSION['dep_name'] ?? '';
$is_admin   = in_array($user_level, [1,2]);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    echo "<script>alert('ID tidak valid');window.location='index.php';</script>";
    exit;
}

// PERBAIKAN: Query dari tbl_tools
$query = "
    SELECT 
        t.*,
        kar.karyawan_name,
        kar.karyawan_id,
        d.dep_code,
        d.dep_name,
        d.dep_id,
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
    echo "<script>alert('Data tidak ditemukan');window.location='index.php';</script>";
    exit;
}

include '../menu/header.php';
include '../menu/sidebar.php';
include '../menu/topbar.php';
?>

<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

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
    .required-field:after {
        content: " *";
        color: red;
    }
    .preview-image {
        max-width: 200px;
        max-height: 200px;
        object-fit: cover;
        border-radius: 8px;
        border: 1px solid #ddd;
    }
    .auto-generate {
        background-color: #f0f2f5;
        font-weight: bold;
        color: #4e73df;
    }
    
    /* PERBAIKAN SELECT2 TULISAN KE BAWAH & TOMBOL X DI KANAN */
    .select2-container {
        width: 100% !important;
    }
    .select2-container--bootstrap4 .select2-selection--single {
        height: calc(1.5em + 0.75rem + 2px) !important; 
        display: flex !important;
        align-items: center !important; 
        position: relative !important;
    }
    
    .select2-container--bootstrap4 .select2-selection--single .select2-selection__rendered,
    .select2-container--bootstrap4 .select2-selection--single .select2-selection__placeholder {
        line-height: normal !important;
        padding-top: 0 !important;
        padding-bottom: 0 !important;
        margin-top: 0 !important;
        padding-right: 50px !important;
    }

    .select2-container--bootstrap4 .select2-selection__clear {
        position: absolute !important;
        top: 50% !important;
        transform: translateY(-50%) !important;
        right: 30px !important;
        z-index: 10 !important;
        background: transparent !important;
        width: 20px;
        text-align: center;
    }

    .select2-container--bootstrap4 .select2-selection--single .select2-selection__arrow {
        position: absolute !important;
        top: 50% !important;
        transform: translateY(-50%) !important;
        right: 8px !important;
    }
</style>

<div class="container-fluid">

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-edit"></i> Edit Tools
        </h1>
        <a href="index.php" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="card shadow">
        <div class="card-body">

            <form action="proses.php" method="POST" enctype="multipart/form-data" id="toolsForm">

                <input type="hidden" name="tools_id" value="<?= $row['tools_id'] ?>">

                <!-- Informasi Penanggung Jawab -->
                <div class="row">
                    <div class="col-md-12">
                        <h5 class="text-primary mb-3"><i class="fas fa-user"></i> Informasi Penanggung Jawab</h5>
                    </div>
                    
                    <?php if ($is_admin): ?>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="required-field">Departemen</label>
                            <select name="dep_code" id="dep_code" class="form-control select2">
                                <option value="">-- Pilih Departemen --</option>
                                <?php
                                $qDep = mysqli_query($conn, "
                                    SELECT DISTINCT dep_code, MIN(dep_name) as dep_name
                                    FROM tbl_dep 
                                    GROUP BY dep_code 
                                    ORDER BY dep_code
                                ");
                                while ($dep = mysqli_fetch_assoc($qDep)) {
                                    $selected = ($dep['dep_code'] == $row['dep_code']) ? 'selected' : '';
                                    echo "<option value='{$dep['dep_code']}' {$selected}>{$dep['dep_code']} - {$dep['dep_name']}</option>";
                                }
                                ?>
                            </select>                            
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="required-field">Penanggung Jawab</label>
                            <select name="karyawan_id" id="karyawan_id" class="form-control select2" required>
                                <option value="<?= $row['karyawan_id'] ?>" selected>
                                    <?= htmlspecialchars($row['karyawan_name'] ?? '-') ?>
                                </option>
                            </select>
                        </div>
                    </div>
                    <?php else: ?>
                    <input type="hidden" name="karyawan_id" value="<?= $user_id ?>">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Penanggung Jawab</label>
                            <input type="text" class="form-control" value="<?= $_SESSION['user_name'] ?>" readonly>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Departemen</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($dep_name ?? '-') ?> (<?= htmlspecialchars($dep_code ?? '-') ?>)" readonly>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <hr class="sidebar-divider">

                <!-- Informasi Tools -->
                <div class="row">
                    <div class="col-md-12">
                        <h5 class="text-primary mb-3"><i class="fas fa-tools"></i> Informasi Tools</h5>
                    </div>

                    <!-- Nama Tools -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="required-field">Nama Tools</label>
                            <input type="text" name="tools_name" id="tools_name" class="form-control" 
                                   value="<?= htmlspecialchars($row['tools_name'] ?? '') ?>" required>
                        </div>
                    </div>

                    <!-- Merk -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="required-field">Merk</label>
                            <select name="tools_merk" id="tools_merk" class="form-control select2" required>
                                <option value="">-- Pilih Merk --</option>
                                <?php
                                $qMerk = mysqli_query($conn, "SELECT merk_id, merk_name FROM tbl_merk ORDER BY merk_name ASC");
                                while ($merk = mysqli_fetch_assoc($qMerk)) {
                                    $selected = ($merk['merk_name'] == $row['tools_merk']) ? 'selected' : '';
                                    echo "<option value='{$merk['merk_name']}' {$selected}>{$merk['merk_name']}</option>";
                                }
                                ?>
                            </select>
                            <small><a href="../merk/tambah.php" target="_blank">+ Tambah Merk</a></small>
                        </div>
                    </div>

                    <!-- Spesifikasi -->
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>Spesifikasi</label>
                            <textarea name="tools_spec" id="tools_spec" class="form-control" rows="3" 
                                placeholder="Masukkan spesifikasi tools"><?= htmlspecialchars($row['tools_spec'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <!-- Quantity -->
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="required-field">Quantity</label>
                            <input type="number" name="tools_qty" id="tools_qty" class="form-control" 
                                   min="1" value="<?= $row['tools_qty'] ?? 1 ?>" required>
                        </div>
                    </div>

                    <!-- Harga -->
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="required-field">Harga Per Pcs</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">Rp</span>
                                </div>
                                <input type="text" id="tools_price_display" 
                                    class="form-control money-format"
                                    value="<?= number_format($row['tools_price'] ?? 0, 0, ',', '.') ?>">
                                <input type="hidden" name="tools_price" id="tools_price"
                                    value="<?= $row['tools_price'] ?? 0 ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Tanggal Pembelian -->
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="required-field">Tanggal Pembelian</label>
                            <input type="date" name="tools_date" id="tools_date" class="form-control" 
                                   value="<?= $row['tools_date'] ?? '' ?>" required>
                        </div>
                    </div>

                    <!-- Kondisi -->
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="required-field">Kondisi</label>
                            <select name="kondisi_id" id="kondisi_id" class="form-control select2" required>
                                <option value="">-- Pilih Kondisi --</option>
                                <?php
                                $qKondisi = mysqli_query($conn, "SELECT kondisi_id, kondisi_name FROM tbl_kondisi ORDER BY kondisi_name ASC");
                                while ($kond = mysqli_fetch_assoc($qKondisi)) {
                                    $selected = ($kond['kondisi_id'] == $row['kondisi_id']) ? 'selected' : '';
                                    echo "<option value='{$kond['kondisi_id']}' {$selected}>{$kond['kondisi_name']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <!-- Catatan -->
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>Catatan</label>
                            <textarea name="tools_note" id="tools_note" class="form-control" rows="2" 
                                placeholder="Catatan tambahan"><?= htmlspecialchars($row['tools_note'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>

                <hr class="sidebar-divider">

                <!-- Upload Gambar -->
                <div class="row">
                    <div class="col-md-12">
                        <h5 class="text-primary mb-3"><i class="fas fa-image"></i> Upload Gambar</h5>
                        <div class="form-group">
                            <?php if (!empty($row['tools_image'])): ?>
                                <div class="mb-3">
                                    <label>Gambar Saat Ini:</label><br>
                                    <img src="../master/img/tools/<?= htmlspecialchars($row['tools_image']) ?>" 
                                         class="current-image">
                                    <div class="form-check mt-2">
                                        <input type="checkbox" name="hapus_gambar" id="hapus_gambar" value="1" class="form-check-input">
                                        <label class="form-check-label text-danger" for="hapus_gambar">Hapus gambar saat ini</label>
                                    </div>
                                    <input type="hidden" name="gambar_lama" value="<?= htmlspecialchars($row['tools_image']) ?>">
                                </div>
                            <?php endif; ?>                            
                            <div id="uploadArea" class="upload-box text-center p-4">
                                <div id="uploadContent">
                                    <i class="fas fa-cloud-upload-alt fa-3x mb-3 text-primary"></i>
                                    <p class="mb-1 font-weight-bold">Drag & Drop Gambar Disini</p>
                                    <small class="text-muted">atau klik untuk memilih file</small>
                                </div>
                                <img id="previewImage" class="preview-image d-none mt-3">
                                <button type="button" id="removeImage" class="btn btn-sm btn-danger mt-3 d-none">
                                    <i class="fas fa-trash"></i> Hapus Gambar
                                </button>
                                <input type="file" name="tools_image" id="tools_image" accept="image/png, image/jpeg, image/jpg" hidden>
                            </div>
                            <small class="text-muted">Format: JPG / PNG (Max 2MB). Kosongkan jika tidak ingin mengubah gambar.</small>
                        </div>
                    </div>
                </div>

                <!-- Tombol Submit -->
                <div class="row mt-4">
                    <div class="col-md-12">
                        <button type="submit" name="edit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Tools
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
    // Inisialisasi Select2
    $('.select2').select2({
        theme: 'bootstrap4',
        width: '100%',
        placeholder: '-- Pilih --',
        allowClear: true
    });

    <?php if ($is_admin): ?>
    // Filter karyawan berdasarkan departemen
    $('#dep_code').on('change', function() {
        var depCode = $(this).val();
        var userSelect = $('#karyawan_id');
        
        if (depCode) {
            userSelect.empty().append('<option value="">Loading...</option>').trigger('change');
            
            $.ajax({
                url: '../primary/get_users_by_dep_code.php',
                type: 'POST',
                data: { dep_code: depCode },
                dataType: 'html',
                success: function(response) {
                    if (response && response.trim() !== '') {
                        userSelect.html(response).trigger('change');
                    } else {
                        userSelect.html('<option value="">-- Tidak ada penanggung jawab --</option>').trigger('change');
                    }
                },
                error: function() {
                    userSelect.html('<option value="">-- Error loading users --</option>').trigger('change');
                }
            });
        } else {
            userSelect.html('<option value="">-- Pilih Penanggung Jawab --</option>').trigger('change');
        }
    });
    <?php endif; ?>

    // Format Rupiah
    $('.money-format').on('input', function() {
        let value = this.value.replace(/[^\d]/g, '');
        this.value = value.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        $('#tools_price').val(value);
    });

    // Validasi sebelum submit
    $('#toolsForm').on('submit', function(e) {
        if (!$('#karyawan_id').val()) {
            e.preventDefault();
            Swal.fire('Error', 'Penanggung jawab wajib dipilih!', 'error');
            return false;
        }
        
        if (!$('#tools_name').val()) {
            e.preventDefault();
            Swal.fire('Error', 'Nama tools wajib diisi!', 'error');
            return false;
        }
        
        if (!$('#tools_merk').val()) {
            e.preventDefault();
            Swal.fire('Error', 'Merk tools wajib dipilih!', 'error');
            return false;
        }
        
        if (!$('#kondisi_id').val()) {
            e.preventDefault();
            Swal.fire('Error', 'Kondisi tools wajib dipilih!', 'error');
            return false;
        }
        
        var price = $('#tools_price').val();
        if (!price || price <= 0) {
            e.preventDefault();
            Swal.fire('Error', 'Harga harus diisi dengan nilai valid!', 'error');
            return false;
        }
    });
});
</script>

<!-- Script untuk preview gambar -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const fileInput = document.getElementById('tools_image');
        const preview = document.getElementById('previewImage');
        const removeBtn = document.getElementById('removeImage');
        const uploadContent = document.getElementById('uploadContent');
        const uploadArea = document.getElementById('uploadArea');

        function handleFile(file) {
            if (!file) return;

            if (file.size > 2000000) {
                alert("Max 2MB");
                return;
            }

            const allowed = ['image/jpeg','image/png','image/jpg'];
            if (!allowed.includes(file.type)) {
                alert("Format harus JPG/PNG");
                return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.classList.remove('d-none');
                removeBtn.classList.remove('d-none');
                uploadContent.classList.add('d-none');
            };
            reader.readAsDataURL(file);
        }

        if (fileInput) {
            fileInput.addEventListener('change', e => handleFile(e.target.files[0]));
        }

        if (removeBtn) {
            removeBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                fileInput.value = "";
                preview.classList.add('d-none');
                removeBtn.classList.add('d-none');
                uploadContent.classList.remove('d-none');
            });
        }

        if (uploadArea && fileInput) {
            uploadArea.addEventListener('click', () => fileInput.click());

            uploadArea.addEventListener('dragover', e => {
                e.preventDefault();
                uploadArea.classList.add('dragover');
            });

            uploadArea.addEventListener('dragleave', () => {
                uploadArea.classList.remove('dragover');
            });

            uploadArea.addEventListener('drop', e => {
                e.preventDefault();
                uploadArea.classList.remove('dragover');
                handleFile(e.dataTransfer.files[0]);
            });
        }
    });
</script>

</body>
</html>