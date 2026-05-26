<?php
include '../auth/auth.php';
allowRole([1]);
include '../config/koneksi.php';

include '../menu/header.php';
include '../menu/sidebar.php';
include '../menu/topbar.php';

/* =========================
   AMBIL DATA USER
========================= */
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    $_SESSION['alert'] = ['type' => 'danger', 'msg' => 'ID user tidak valid!'];
    header("Location: index.php");
    exit;
}

$qUser = mysqli_query($conn, "
    SELECT u.*, d.dep_code, d.dep_name
    FROM tbl_user u
    LEFT JOIN tbl_dep d ON u.dep_id = d.dep_id
    WHERE u.user_id = '$id'
");

$dataUser = mysqli_fetch_assoc($qUser);

if (!$dataUser) {
    $_SESSION['alert'] = ['type' => 'danger', 'msg' => 'Data user tidak ditemukan!'];
    header("Location: index.php");
    exit;
}
?>

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
    .preview-image {
        max-width: 200px;
        max-height: 200px;
        object-fit: cover;
        border-radius: 8px;
        border: 1px solid #ddd;
    }
    .current-image {
        max-width: 150px;
        max-height: 150px;
        object-fit: cover;
        border-radius: 8px;
        border: 1px solid #ddd;
    }
    .select2-container {
        width: 100% !important;
    }
    .required-field:after {
        content: " *";
        color: red;
    }
    .readonly-field {
        background-color: #e9ecef;
        cursor: not-allowed;
    }

    /* PERBAIKAN SELECT2 TULISAN KE BAWAH & TOMBOL X DI KANAN */
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
            <i class="fas fa-user-edit"></i> Edit User Akses
        </h1>
        <a href="index.php" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="card shadow">
        <div class="card-body">

            <form action="proses.php" method="POST" enctype="multipart/form-data">

                <input type="hidden" name="user_id" value="<?= $dataUser['user_id']; ?>">

                <!-- User ID (readonly) -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="required-field">User ID (ID Karyawan)</label>
                            <input type="text" class="form-control readonly-field" 
                                   value="<?= htmlspecialchars($dataUser['user_id']); ?>" readonly>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Password (Kosongkan jika tidak diubah)</label>
                            <div class="input-group">
                                <input type="password" 
                                    name="user_password" 
                                    id="password"
                                    minlength="8"
                                    class="form-control"
                                    placeholder="Masukkan password baru jika ingin mengubah">
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-outline-secondary" onclick="togglePassword()">
                                        <i class="fas fa-eye" id="eyeIcon"></i>
                                    </button>
                                </div>
                            </div>
                            <small class="text-muted">Minimal 8 karakter. Kosongkan jika tidak ingin mengubah password.</small>
                        </div>
                    </div>
                </div>

                <!-- Nama User -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="required-field">Nama User</label>
                            <input type="text" 
                                name="user_name" 
                                class="form-control" 
                                value="<?= htmlspecialchars($dataUser['user_name']); ?>" 
                                required>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="required-field">Email</label>
                            <input type="email" 
                                name="user_mail" 
                                class="form-control" 
                                value="<?= htmlspecialchars($dataUser['user_mail']); ?>" 
                                required>
                        </div>
                    </div>
                </div>

                <!-- Gender -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="required-field">Gender</label>
                            <select name="user_gender" class="form-control select2" required>
                                <option value="">-- Pilih Gender --</option>
                                <option value="Male" <?= $dataUser['user_gender'] == 'Male' ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?= $dataUser['user_gender'] == 'Female' ? 'selected' : ''; ?>>Female</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="required-field">Departemen</label>
                            <select name="dep_id" id="dep_id" class="form-control select2" required>
                                <option value="">-- Pilih Departemen --</option>
                                <?php
                                $qDep = mysqli_query($conn, "
                                    SELECT MIN(dep_id) as dep_id, dep_code, MIN(dep_name) as dep_name
                                    FROM tbl_dep 
                                    GROUP BY dep_code 
                                    ORDER BY dep_code
                                ");
                                while ($dep = mysqli_fetch_assoc($qDep)) {
                                    $selected = ($dep['dep_id'] == $dataUser['dep_id']) ? 'selected' : '';
                                    echo "<option value='{$dep['dep_id']}' {$selected}>{$dep['dep_code']} - {$dep['dep_name']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Hak Akses (User Level) -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label class="required-field">Hak Akses (Level)</label>
                            <select name="user_level" class="form-control select2" required>
                                <option value="">-- Pilih Level --</option>
                                <option value="1" <?= $dataUser['user_level'] == 1 ? 'selected' : ''; ?>>Administrator</option>
                                <option value="2" <?= $dataUser['user_level'] == 2 ? 'selected' : ''; ?>>Operator</option>
                                <option value="3" <?= $dataUser['user_level'] == 3 ? 'selected' : ''; ?>>User</option>
                            </select>
                            <small class="text-muted">
                                <strong>Administrator:</strong> Akses penuh ke semua fitur<br>
                                <strong>Operator:</strong> Dapat mengelola data namun tidak bisa mengubah pengaturan sistem<br>
                                <strong>User:</strong> Hanya dapat melihat data milik sendiri
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Upload Gambar -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>Upload Gambar User</label>

                            <?php if (!empty($dataUser['user_image'])): ?>
                                <div class="mb-3">
                                    <label>Gambar Saat Ini:</label><br>
                                    <img src="../master/img/user/<?= htmlspecialchars($dataUser['user_image']) ?>" 
                                        class="current-image">
                                    <div class="form-check mt-2">
                                        <input type="checkbox" name="hapus_gambar" id="hapus_gambar" value="1" class="form-check-input">
                                        <label class="form-check-label text-danger" for="hapus_gambar">Hapus gambar saat ini</label>
                                    </div>
                                    <input type="hidden" name="gambar_lama" value="<?= htmlspecialchars($dataUser['user_image']) ?>">
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

                                <input type="file"
                                    name="user_image"
                                    id="user_image"
                                    accept="image/png, image/jpeg, image/jpg"
                                    hidden>
                            </div>

                            <small class="text-muted">
                                Format: JPG / PNG (Max 2MB). Kosongkan jika tidak ingin mengubah gambar.
                            </small>
                        </div>
                    </div>
                </div>

                <hr>

                <div class="form-group">
                    <button type="submit" name="update" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update User
                    </button>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Batal
                    </a>
                </div>

            </form>

        </div>
    </div>

</div>

<?php include '../menu/footer.php'; ?>

<script>
$(document).ready(function() {
    $('.select2').select2({
        theme: 'bootstrap4',
        width: '100%',
        placeholder: '-- Pilih --',
        allowClear: true
    });

    window.togglePassword = function() {
        const pass = document.getElementById("password");
        const icon = document.getElementById("eyeIcon");
        if (pass.type === "password") {
            pass.type = "text";
            icon.classList.remove("fa-eye");
            icon.classList.add("fa-eye-slash");
        } else {
            pass.type = "password";
            icon.classList.remove("fa-eye-slash");
            icon.classList.add("fa-eye");
        }
    };

    // Upload gambar handling
    const fileInput = document.getElementById('user_image');
    const preview = document.getElementById('previewImage');
    const removeBtn = document.getElementById('removeImage');
    const uploadContent = document.getElementById('uploadContent');
    const uploadArea = document.getElementById('uploadArea');

    function handleFile(file) {
        if (!file) return;
        
        if (file.size > 2000000) {
            alert("Ukuran maksimal 2MB");
            return;
        }
        
        const allowed = ['image/jpeg', 'image/png', 'image/jpg'];
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
        
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        fileInput.files = dataTransfer.files;
    }

    if (fileInput) {
        fileInput.addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                handleFile(e.target.files[0]);
            }
        });
    }

    if (removeBtn) {
        removeBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            fileInput.value = "";
            preview.src = "#";
            preview.classList.add('d-none');
            removeBtn.classList.add('d-none');
            uploadContent.classList.remove('d-none');
        });
    }

    if (uploadArea && fileInput) {
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
            if (e.dataTransfer.files && e.dataTransfer.files[0]) {
                handleFile(e.dataTransfer.files[0]);
            }
        });
    }
});
</script>

</body>
</html>