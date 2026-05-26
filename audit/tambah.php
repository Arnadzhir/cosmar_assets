<?php
include '../auth/auth.php';
allowRole([1,2,3]);

include '../config/koneksi.php';

/* =====================
   AMBIL DATA USER LOGIN
===================== */
$user_id    = $_SESSION['user_id'] ?? 0;
$user_level = $_SESSION['user_level'] ?? 0;
$dep_id     = $_SESSION['dep_id'] ?? 0;

$is_admin = in_array($user_level, [1, 2]);

include '../menu/header.php';
include '../menu/sidebar.php';
include '../menu/topbar.php';
?>

<!-- SweetAlert2 CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

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
    .select2-container {
        width: 100% !important;
    }
    .detail-item {
        background: #f8f9fc;
        border: 1px solid #e3e6f0;
        border-radius: 10px;
        padding: 15px;
        margin-bottom: 15px;
        position: relative;
    }
    .detail-item .remove-item {
        position: absolute;
        top: -10px;
        right: -10px;
        border-radius: 50%;
        width: 30px;
        height: 30px;
        padding: 0;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .btn-add-item {
        margin-top: 10px;
    }
    .image-preview {
        max-width: 80px;
        max-height: 80px;
        object-fit: cover;
        border-radius: 8px;
        margin-top: 10px;
        border: 1px solid #ddd;
    }
</style>

<div class="container-fluid">

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-plus-circle"></i> Tambah Audit Asset
        </h1>
        <a href="index.php" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="card shadow">
        <div class="card-body">

            <form action="proses.php" method="POST" enctype="multipart/form-data" id="auditForm">
                
                <!-- Informasi Asset -->
                <div class="row">
                    <div class="col-md-12">
                        <h5 class="text-primary mb-3"><i class="fas fa-box"></i> Informasi Asset</h5>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="required-field">Kode Asset</label>
                            <select name="assets_id" id="assets_id" class="form-control select2" required>
                                <option value="">-- Pilih Kode Asset --</option>
                                <?php
                                $qAsset = mysqli_query($conn, "
                                    SELECT assets_id, assets_kode, assets_name, assets_qty
                                    FROM tbl_assets 
                                    WHERE assets_kode IS NOT NULL AND assets_kode != ''
                                    ORDER BY assets_kode ASC
                                ");
                                while ($asset = mysqli_fetch_assoc($qAsset)) {
                                    echo "<option value='{$asset['assets_id']}' data-name='{$asset['assets_name']}' data-qty='{$asset['assets_qty']}'>{$asset['assets_kode']} - {$asset['assets_name']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="required-field">Nama Asset</label>
                            <input type="text" id="assets_name" class="form-control" readonly>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="required-field">Qty Asset (Sistem)</label>
                            <input type="text" id="assets_qty" class="form-control" readonly>
                        </div>
                    </div>
                </div>

                <hr>

                <!-- Informasi Penanggung Jawab & Auditor -->
                <div class="row">
                    <div class="col-md-12">
                        <h5 class="text-primary mb-3"><i class="fas fa-user"></i> Informasi Penanggung Jawab & Auditor</h5>
                    </div>
                    
                    <?php if ($is_admin): ?>
                    <div class="col-md-4">
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
                                    echo "<option value='{$dep['dep_code']}'>{$dep['dep_code']} - {$dep['dep_name']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="required-field">Penanggung Jawab</label>
                            <select name="karyawan_id" id="karyawan_id" class="form-control select2" required>
                                <option value="">-- Pilih Penanggung Jawab --</option>
                            </select>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Departemen</label>
                            <input type="text" class="form-control" value="<?= $_SESSION['dep_name'] ?? '-' ?> (<?= $_SESSION['dep_code'] ?? '-' ?>)" readonly>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="required-field">Penanggung Jawab</label>
                            <select name="karyawan_id" id="karyawan_id" class="form-control select2" required>
                                <option value="">-- Pilih Penanggung Jawab --</option>
                                <?php
                                $qKaryawan = mysqli_query($conn, "
                                    SELECT karyawan_id, karyawan_name 
                                    FROM tbl_karyawan 
                                    WHERE dep_id = '$dep_id' 
                                    ORDER BY karyawan_name
                                ");
                                while ($kar = mysqli_fetch_assoc($qKaryawan)) {
                                    $selected = ($kar['karyawan_id'] == $user_id) ? 'selected' : '';
                                    echo "<option value='{$kar['karyawan_id']}' {$selected}>{$kar['karyawan_name']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="required-field">Auditor</label>
                            <select name="auditor" id="auditor" class="form-control select2" required>
                                <option value="">-- Pilih Auditor --</option>
                                <option value="<?= $_SESSION['user_name'] ?>"><?= $_SESSION['user_name'] ?></option>
                                <option value="Internal Audit">Internal Audit</option>
                                <option value="External Audit">External Audit</option>
                            </select>
                        </div>
                    </div>
                </div>

                <hr>

                <!-- Detail Audit (Multiple Insert) -->
                <div class="row">
                    <div class="col-md-12">
                        <h5 class="text-primary mb-3"><i class="fas fa-list"></i> Detail Audit Asset</h5>
                        <p class="text-muted small">Isi detail audit untuk setiap lokasi asset. Gambar akan diupload per item.</p>
                    </div>
                </div>

                <div id="detail-container">
                    <div class="detail-item" data-index="0">
                        <button type="button" class="btn btn-sm btn-danger remove-item" style="display: none;">
                            <i class="fas fa-trash"></i>
                        </button>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="required-field">Lokasi Asset</label>
                                    <select name="lokasi_id[]" class="form-control select2-lokasi" required>
                                        <option value="">-- Pilih Lokasi --</option>
                                        <?php
                                        $qLokasi = mysqli_query($conn, "
                                            SELECT lokasi_id, lokasi_name, lokasi_lantai
                                            FROM tbl_lokasi 
                                            ORDER BY lokasi_name ASC
                                        ");
                                        while ($lok = mysqli_fetch_assoc($qLokasi)) {
                                            echo "<option value='{$lok['lokasi_id']}'>{$lok['lokasi_name']} (Lt.{$lok['lokasi_lantai']})</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="required-field">Kondisi Asset</label>
                                    <select name="kondisi_id[]" class="form-control select2-kondisi" required>
                                        <option value="">-- Pilih Kondisi --</option>
                                        <?php
                                        $qKondisi = mysqli_query($conn, "
                                            SELECT kondisi_id, kondisi_name
                                            FROM tbl_kondisi 
                                            ORDER BY kondisi_name ASC
                                        ");
                                        while ($kon = mysqli_fetch_assoc($qKondisi)) {
                                            echo "<option value='{$kon['kondisi_id']}'>{$kon['kondisi_name']}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label class="required-field">Qty Audit</label>
                                    <input type="number" name="audit_qty[]" class="form-control qty-audit" min="1" value="1" required>
                                </div>
                            </div>
                            
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label class="required-field">Upload Gambar</label>
                                    <input type="file" name="audit_image[]" class="form-control-file upload-image" accept="image/png, image/jpeg, image/jpg" required>
                                    <div class="image-preview-container mt-2"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <button type="button" id="btn-add-item" class="btn btn-sm btn-primary btn-add-item">
                            <i class="fas fa-plus"></i> Tambah Lokasi Lain
                        </button>
                    </div>
                </div>

                <hr>

                <!-- Informasi Note -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>Catatan Audit</label>
                            <textarea name="audit_note" class="form-control" rows="3" placeholder="Masukkan catatan audit jika ada..."></textarea>
                        </div>
                    </div>
                </div>

                <hr>

                <!-- Tombol Submit -->
                <div class="row mt-4">
                    <div class="col-md-12">
                        <button type="submit" name="tambah" class="btn btn-primary">
                            <i class="fas fa-save"></i> Simpan Audit
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
        width: '100%',
        placeholder: '-- Pilih --',
        allowClear: true
    });
    
    // Auto fill nama asset dan qty saat kode asset dipilih
    $('#assets_id').on('change', function() {
        var selected = $(this).find('option:selected');
        var assetsName = selected.data('name');
        var assetsQty = selected.data('qty');
        $('#assets_name').val(assetsName);
        $('#assets_qty').val(assetsQty);
        
        // Set qty awal untuk setiap detail item
        $('.qty-audit').val(assetsQty);
    });
    
    <?php if ($is_admin): ?>
    // Dropdown penanggung jawab berdasarkan departemen (untuk admin)
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
    
    // Fungsi untuk menambah item detail
    var itemCount = 1;
    
    $('#btn-add-item').on('click', function() {
        var newItem = $('.detail-item:first').clone();
        var newIndex = itemCount;
        
        // Reset nilai form
        newItem.find('select[name="lokasi_id[]"]').val('').trigger('change');
        newItem.find('select[name="kondisi_id[]"]').val('').trigger('change');
        newItem.find('input[name="audit_qty[]"]').val($('#assets_qty').val() || 1);
        newItem.find('input[name="audit_image[]"]').val('');
        newItem.find('.image-preview-container').empty();
        
        // Tampilkan tombol remove
        newItem.find('.remove-item').show();
        
        // Update data-index
        newItem.attr('data-index', newIndex);
        
        // Append ke container
        $('#detail-container').append(newItem);
        
        // Re-inisialisasi select2 untuk item baru
        newItem.find('.select2-lokasi').select2({
            width: '100%',
            placeholder: '-- Pilih Lokasi --',
            allowClear: true
        });
        newItem.find('.select2-kondisi').select2({
            width: '100%',
            placeholder: '-- Pilih Kondisi --',
            allowClear: true
        });
        
        itemCount++;
    });
    
    // Hapus item detail
    $(document).on('click', '.remove-item', function() {
        var itemCount = $('.detail-item').length;
        if (itemCount > 1) {
            $(this).closest('.detail-item').remove();
        } else {
            Swal.fire('Info', 'Minimal 1 detail item harus diisi', 'info');
        }
    });
    
    // Preview gambar untuk setiap upload
    $(document).on('change', '.upload-image', function(e) {
        var file = e.target.files[0];
        var container = $(this).closest('.col-md-2').find('.image-preview-container');
        
        if (file) {
            if (file.size > 2000000) {
                alert("Ukuran gambar maksimal 2MB!");
                this.value = "";
                container.empty();
                return;
            }
            
            var allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
            if (!allowedTypes.includes(file.type)) {
                alert("Format harus JPG atau PNG!");
                this.value = "";
                container.empty();
                return;
            }
            
            var reader = new FileReader();
            reader.onload = function(event) {
                container.html('<img src="' + event.target.result + '" class="image-preview">');
            };
            reader.readAsDataURL(file);
        } else {
            container.empty();
        }
    });
    
    // Validasi sebelum submit
    $('#auditForm').on('submit', function(e) {
        var assetsId = $('#assets_id').val();
        if (!assetsId) {
            e.preventDefault();
            Swal.fire('Error', 'Kode asset harus dipilih!', 'error');
            return false;
        }
        
        var karyawanId = $('#karyawan_id').val();
        if (!karyawanId) {
            e.preventDefault();
            Swal.fire('Error', 'Penanggung jawab harus dipilih!', 'error');
            return false;
        }
        
        var auditor = $('#auditor').val();
        if (!auditor) {
            e.preventDefault();
            Swal.fire('Error', 'Auditor harus dipilih!', 'error');
            return false;
        }
        
        var isValid = true;
        var errorMessage = '';
        
        $('.detail-item').each(function(index) {
            var lokasi = $(this).find('select[name="lokasi_id[]"]').val();
            var kondisi = $(this).find('select[name="kondisi_id[]"]').val();
            var qty = $(this).find('input[name="audit_qty[]"]').val();
            var image = $(this).find('input[name="audit_image[]"]').val();
            
            if (!lokasi) {
                isValid = false;
                errorMessage = 'Lokasi pada item ' + (index + 1) + ' harus dipilih!';
                return false;
            }
            if (!kondisi) {
                isValid = false;
                errorMessage = 'Kondisi pada item ' + (index + 1) + ' harus dipilih!';
                return false;
            }
            if (!qty || qty < 1) {
                isValid = false;
                errorMessage = 'Qty pada item ' + (index + 1) + ' minimal 1!';
                return false;
            }
            if (!image) {
                isValid = false;
                errorMessage = 'Gambar pada item ' + (index + 1) + ' wajib diupload!';
                return false;
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            Swal.fire('Error', errorMessage, 'error');
            return false;
        }
    });
});
</script>

</body>
</html>