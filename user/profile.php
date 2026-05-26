<?php
session_start();
if (!isset($_SESSION['login'])) {
    header("Location: ../auth/login.php");
    exit;
}

include '../config/koneksi.php';
include '../menu/header.php';
include '../menu/sidebar.php';
include '../menu/topbar.php';

// Ambil data user yang sedang login
$user_id = $_SESSION['user_id'];

// PERBAIKAN: Hanya ambil kolom yang ada di database
$query = "
    SELECT 
        u.*,
        d.dep_code,
        d.dep_name
    FROM tbl_user u
    LEFT JOIN tbl_dep d ON u.dep_id = d.dep_id
    WHERE u.user_id = '$user_id'
";

$result = mysqli_query($conn, $query);
$dataUser = mysqli_fetch_assoc($result);

if (!$dataUser) {
    header("Location: ../dashboard.php?error=Data user tidak ditemukan");
    exit;
}

// Format level user menjadi teks
$level_name = '';
if ($dataUser['user_level'] == 1) $level_name = 'Administrator';
elseif ($dataUser['user_level'] == 2) $level_name = 'Operator';
else $level_name = 'User';

// Format gender
$gender_text = '';
if ($dataUser['user_gender'] == 'Male') $gender_text = 'Laki-laki';
elseif ($dataUser['user_gender'] == 'Female') $gender_text = 'Perempuan';
else $gender_text = '-';
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
    .preview-image {
        max-width: 200px;
        max-height: 200px;
        object-fit: cover;
        border-radius: 8px;
        border: 1px solid #ddd;
    }
    .profile-header {
        background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
        color: white;
    }
    .profile-avatar {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid white;
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }
    .profile-avatar-placeholder {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        background-color: #6c757d;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 15px;
        border: 3px solid white;
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }
    .info-card {
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        padding: 20px;
        margin-bottom: 20px;
    }
    .info-label {
        font-weight: 600;
        color: #4e73df;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 5px;
    }
    .info-value {
        font-size: 14px;
        color: #5a5c69;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 1px solid #e3e6f0;
    }
    .info-value:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }
    .current-image {
        max-width: 150px;
        max-height: 150px;
        object-fit: cover;
        border-radius: 8px;
        border: 1px solid #ddd;
        margin-bottom: 10px;
    }
    .level-badge {
        display: inline-block;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
    }
    .level-admin {
        background-color: #e74a3b;
        color: white;
    }
    .level-operator {
        background-color: #f6c23e;
        color: #212529;
    }
    .level-user {
        background-color: #1cc88a;
        color: white;
    }
    .readonly-field {
        background-color: #e9ecef;
        cursor: not-allowed;
    }
</style>

<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-user-circle"></i> Profil Saya
        </h1>
        <div>
            <span class="badge badge-primary p-2">
                <i class="fas fa-user"></i> <?= htmlspecialchars($dataUser['user_name']) ?>
            </span>
        </div>
    </div>

    <div class="row">
        <!-- Kolom Kiri - Foto Profil -->
        <div class="col-md-4">
            <div class="info-card text-center">
                <?php if (!empty($dataUser['user_image'])): ?>
                    <img src="../master/img/user/<?= htmlspecialchars($dataUser['user_image']) ?>" 
                         class="profile-avatar mb-3"
                         onerror="this.src='../master/img/no-image.png'">
                <?php else: ?>
                    <div class="profile-avatar-placeholder">
                        <i class="fas fa-user fa-4x text-white"></i>
                    </div>
                <?php endif; ?>
                
                <h5 class="mt-2"><?= htmlspecialchars($dataUser['user_name']) ?></h5>
                <p class="mb-2">
                    <span class="level-badge <?= 
                        $dataUser['user_level'] == 1 ? 'level-admin' : 
                        ($dataUser['user_level'] == 2 ? 'level-operator' : 'level-user') 
                    ?>">
                        <i class="fas <?= 
                            $dataUser['user_level'] == 1 ? 'fa-crown' : 
                            ($dataUser['user_level'] == 2 ? 'fa-tools' : 'fa-user') 
                        ?>"></i> <?= $level_name ?>
                    </span>
                </p>
                
                <hr>
                
                <div class="text-left">
                    <div class="info-label">ID Karyawan</div>
                    <div class="info-value"><?= htmlspecialchars($dataUser['user_id']) ?></div>
                    
                    <div class="info-label">Email</div>
                    <div class="info-value"><?= htmlspecialchars($dataUser['user_mail']) ?></div>
                    
                    <div class="info-label">Jenis Kelamin</div>
                    <div class="info-value"><?= $gender_text ?></div>
                    
                    <div class="info-label">Departemen</div>
                    <div class="info-value"><?= htmlspecialchars($dataUser['dep_name'] ?? '-') ?> (<?= htmlspecialchars($dataUser['dep_code'] ?? '-') ?>)</div>
                    
                    <div class="info-label">Hak Akses</div>
                    <div class="info-value">
                        <span class="level-badge <?= 
                            $dataUser['user_level'] == 1 ? 'level-admin' : 
                            ($dataUser['user_level'] == 2 ? 'level-operator' : 'level-user') 
                        ?>">
                            <?= $level_name ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan - Form Edit -->
        <div class="col-md-8">
            <div class="info-card">
                <h5 class="text-primary mb-4">
                    <i class="fas fa-edit"></i> Edit Profil
                </h5>
                
                <form action="proses.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="user_id" value="<?= $dataUser['user_id'] ?>">
                    <input type="hidden" name="profile_update" value="1">
                    
                    <!-- Nama Lengkap (Readonly - tidak bisa diubah) -->
                    <div class="form-group">
                        <label>Nama Lengkap</label>
                        <input type="text" class="form-control readonly-field" 
                               value="<?= htmlspecialchars($dataUser['user_name']) ?>" 
                               readonly>
                    </div>

                    <!-- Email (Readonly - tidak bisa diubah) -->
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" class="form-control readonly-field" 
                               value="<?= htmlspecialchars($dataUser['user_mail']) ?>" 
                               readonly>
                    </div>

                    <!-- Jenis Kelamin (Readonly - tidak bisa diubah) -->
                    <div class="form-group">
                        <label>Jenis Kelamin</label>
                        <input type="text" class="form-control readonly-field" 
                               value="<?= $gender_text ?>" 
                               readonly>
                    </div>

                    <!-- Departemen (Readonly - tidak bisa diubah) -->
                    <div class="form-group">
                        <label>Departemen</label>
                        <input type="text" class="form-control readonly-field" 
                               value="<?= htmlspecialchars($dataUser['dep_name'] ?? '-') ?> (<?= htmlspecialchars($dataUser['dep_code'] ?? '-') ?>)" 
                               readonly>
                    </div>

                    <!-- Hak Akses (Readonly - tidak bisa diubah) -->
                    <div class="form-group">
                        <label>Hak Akses</label>
                        <input type="text" class="form-control readonly-field" 
                               value="<?= $level_name ?>" 
                               readonly>
                    </div>

                    <!-- Password (Bisa diubah - opsional) -->
                    <div class="form-group">
                        <label>Password Baru <small class="text-muted">(Kosongkan jika tidak diubah)</small></label>
                        <div class="input-group">
                            <input type="password" 
                                   name="user_password" 
                                   id="password"
                                   minlength="8"
                                   class="form-control"
                                   placeholder="Masukkan password baru">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-outline-secondary" onclick="togglePassword()">
                                    <i class="fas fa-eye" id="eyeIcon"></i>
                                </button>
                            </div>
                        </div>
                        <small class="text-muted">Minimal 8 karakter. Kosongkan jika tidak ingin mengubah password.</small>
                    </div>

                    <!-- Upload Foto Profile -->
                    <div class="form-group">
                        <label>Foto Profile</label>
                        
                        <?php if (!empty($dataUser['user_image'])): ?>
                            <div class="mb-3">
                                <label>Gambar Saat Ini:</label><br>
                                <img src="../master/img/user/<?= htmlspecialchars($dataUser['user_image']) ?>" 
                                     class="current-image"
                                     onerror="this.src='../master/img/no-image.png'">
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

                        <small class="text-muted">Format: JPG / PNG (Max 2MB). Kosongkan jika tidak ingin mengubah gambar.</small>
                    </div>

                    <hr>

                    <button type="submit" name="update_profile" class="btn btn-primary">
                        <i class="fas fa-save"></i> Simpan Perubahan
                    </button>
                    <a href="../index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>

                </form>
            </div>
        </div>
    </div>

</div>

<?php include '../menu/footer.php'; ?>

<script>
// Password Toggle
function togglePassword() {
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
}

// Upload Gambar
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
</script>

</body>
</html>