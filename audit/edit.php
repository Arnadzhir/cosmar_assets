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

// Cek apakah ada ID yang akan diedit
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['alert'] = ['type' => 'danger', 'msg' => 'ID audit tidak ditemukan!'];
    header("Location: index.php");
    exit;
}

$edit_id = (int)$_GET['id'];

// PERBAIKAN: Ambil data audit yang akan diedit menggunakan tbl_karyawan
$qEdit = mysqli_query($conn, "
    SELECT a.*, 
           ast.assets_kode, 
           ast.assets_name, 
           ast.assets_qty as master_qty,
           ast.assets_spec,
           ast.assets_target,
           ast.assets_cap,
           ast.assets_uom,
           ast.assets_price,
           ast.assets_date,
           ast.assets_life,
           kar.karyawan_name,
           kar.karyawan_id as asset_karyawan_id,
           d.dep_code,
           d.dep_name,
           d.dep_id
    FROM tbl_audit a
    LEFT JOIN tbl_assets ast ON a.assets_id = ast.assets_id
    LEFT JOIN tbl_karyawan kar ON a.user_id = kar.karyawan_id
    LEFT JOIN tbl_dep d ON kar.dep_id = d.dep_id
    WHERE a.audit_id = '$edit_id'
");
$data_audit = mysqli_fetch_assoc($qEdit);

if (!$data_audit) {
    $_SESSION['alert'] = ['type' => 'danger', 'msg' => 'Data audit tidak ditemukan!'];
    header("Location: index.php");
    exit;
}

// Cek apakah audit sudah disinkronkan (status = 2)
if ($data_audit['status'] == 2) {
    $_SESSION['alert'] = ['type' => 'warning', 'msg' => 'Data audit sudah disinkronkan ke primary assets, tidak dapat diedit!'];
    header("Location: index.php");
    exit;
}

// Ambil data departemen untuk dropdown
$qDep = mysqli_query($conn, "
    SELECT DISTINCT dep_code, MIN(dep_name) as dep_name
    FROM tbl_dep 
    GROUP BY dep_code 
    ORDER BY dep_code
");

// Ambil data lokasi dan kondisi untuk dropdown
$qLokasi = mysqli_query($conn, "SELECT lokasi_id, lokasi_name, lokasi_lantai FROM tbl_lokasi ORDER BY lokasi_name ASC");
$qKondisi = mysqli_query($conn, "SELECT kondisi_id, kondisi_name FROM tbl_kondisi ORDER BY kondisi_name ASC");

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
    .current-image {
        max-width: 150px;
        max-height: 150px;
        object-fit: cover;
        border-radius: 8px;
        border: 1px solid #ddd;
        margin-bottom: 10px;
    }
    .info-asset {
        background: #fff;
        border: 1px solid #e3e6f0;
        border-radius: 10px;
        padding: 15px;
        margin-bottom: 20px;
    }
    .info-asset-title {
        font-size: 14px;
        font-weight: 600;
        color: #4e73df;
        margin-bottom: 15px;
        border-left: 3px solid #4e73df;
        padding-left: 10px;
    }
    .info-asset-row {
        display: flex;
        margin-bottom: 8px;
        font-size: 12px;
    }
    .info-asset-label {
        width: 120px;
        font-weight: 600;
        color: #5a5c69;
    }
    .info-asset-value {
        flex: 1;
        color: #2c3e50;
    }
    .readonly-input {
        background-color: #e9ecef;
        cursor: not-allowed;
    }
</style>

<div class="container-fluid">

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-edit"></i> Edit Audit Asset
        </h1>
        <a href="index.php" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Kembali ke Dashboard Audit
        </a>
    </div>

    <!-- Card Informasi Asset -->
    <div class="info-asset">
        <div class="info-asset-title">
            <i class="fas fa-box"></i> Informasi Asset Master
        </div>
        <div class="row">
            <div class="col-md-4">
                <div class="info-asset-row">
                    <div class="info-asset-label">Kode Asset:</div>
                    <div class="info-asset-value"><strong><?= htmlspecialchars($data_audit['assets_kode'] ?? '-') ?></strong></div>
                </div>
                <div class="info-asset-row">
                    <div class="info-asset-label">Nama Asset:</div>
                    <div class="info-asset-value"><?= htmlspecialchars($data_audit['assets_name'] ?? '-') ?></div>
                </div>
                <div class="info-asset-row">
                    <div class="info-asset-label">Qty Sistem:</div>
                    <div class="info-asset-value"><?= number_format($data_audit['master_qty'] ?? 0) ?> unit</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-asset-row">
                    <div class="info-asset-label">Spesifikasi:</div>
                    <div class="info-asset-value"><?= !empty($data_audit['assets_spec']) ? nl2br(htmlspecialchars($data_audit['assets_spec'])) : '-' ?></div>
                </div>
                <div class="info-asset-row">
                    <div class="info-asset-label">Peruntukan:</div>
                    <div class="info-asset-value"><?= !empty($data_audit['assets_target']) ? htmlspecialchars($data_audit['assets_target']) : '-' ?></div>
                </div>
                <div class="info-asset-row">
                    <div class="info-asset-label">Kapasitas:</div>
                    <div class="info-asset-value"><?= !empty($data_audit['assets_cap']) ? $data_audit['assets_cap'] . ' ' . ($data_audit['assets_uom'] ?? '') : '-' ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-asset-row">
                    <div class="info-asset-label">Harga:</div>
                    <div class="info-asset-value"><?= !empty($data_audit['assets_price']) ? 'Rp ' . number_format($data_audit['assets_price'], 0, ',', '.') : '-' ?></div>
                </div>
                <div class="info-asset-row">
                    <div class="info-asset-label">Tanggal Beli:</div>
                    <div class="info-asset-value"><?= !empty($data_audit['assets_date']) && $data_audit['assets_date'] != '0000-00-00' ? date('d/m/Y', strtotime($data_audit['assets_date'])) : '-' ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow">
        <div class="card-body">

            <form action="proses.php" method="POST" enctype="multipart/form-data" id="auditForm">
                
                <input type="hidden" name="audit_id" value="<?= $edit_id ?>">
                <input type="hidden" name="assets_id" value="<?= $data_audit['assets_id'] ?>">

                <!-- Informasi Asset -->
                <div class="row">
                    <div class="col-md-12">
                        <h5 class="text-primary mb-3"><i class="fas fa-box"></i> Informasi Asset</h5>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="required-field">Kode Asset</label>
                            <input type="text" class="form-control readonly-input" readonly value="<?= htmlspecialchars($data_audit['assets_kode'] ?? '-') ?>">
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="required-field">Nama Asset</label>
                            <input type="text" class="form-control readonly-input" readonly value="<?= htmlspecialchars($data_audit['assets_name'] ?? '-') ?>">
                        </div>
                    </div>
                </div>

                <hr>

                <!-- PERBAIKAN: Informasi Penanggung Jawab & Auditor -->
                <div class="row">
                    <div class="col-md-12">
                        <h5 class="text-primary mb-3"><i class="fas fa-user"></i> Informasi Penanggung Jawab & Auditor</h5>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="required-field">Departemen</label>
                            <select name="dep_code" id="dep_code" class="form-control select2" required>
                                <option value="">-- Pilih Departemen --</option>
                                <?php
                                mysqli_data_seek($qDep, 0);
                                while ($dep = mysqli_fetch_assoc($qDep)) {
                                    $selected = ($dep['dep_code'] == $data_audit['dep_code']) ? 'selected' : '';
                                    echo "<option value='{$dep['dep_code']}' {$selected}>{$dep['dep_code']} - {$dep['dep_name']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="required-field">Penanggung Jawab</label>
                            <select name="karyawan_id" id="karyawan_id" class="form-control select2" required>
                                <option value="<?= $data_audit['asset_karyawan_id'] ?>" selected><?= htmlspecialchars($data_audit['karyawan_name'] ?? '-') ?></option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="required-field">Auditor</label>
                            <select name="auditor" id="auditor" class="form-control select2" required>
                                <option value="">-- Pilih Auditor --</option>
                                <?php
                                // Ambil user dengan level 1 (Admin) dan 2 (Operator) dari tbl_user
                                $qAuditor = mysqli_query($conn, "
                                    SELECT user_id, user_name 
                                    FROM tbl_user 
                                    WHERE user_level IN (1, 2)
                                    ORDER BY user_name ASC
                                ");
                                
                                while ($auditor = mysqli_fetch_assoc($qAuditor)) {
                                    $selected = ($data_audit['auditor'] == $auditor['user_name']) ? 'selected' : '';
                                    echo "<option value='{$auditor['user_name']}' {$selected}>{$auditor['user_name']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>

                <hr>

                <!-- Detail Audit (Bisa Diedit) -->
                <div class="row">
                    <div class="col-md-12">
                        <h5 class="text-primary mb-3"><i class="fas fa-clipboard-list"></i> Detail Audit Asset</h5>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="required-field">Lokasi Asset</label>
                            <select name="lokasi_id" id="lokasi_id" class="form-control select2" required>
                                <option value="">-- Pilih Lokasi --</option>
                                <?php
                                mysqli_data_seek($qLokasi, 0);
                                while ($lok = mysqli_fetch_assoc($qLokasi)) {
                                    $selected = ($data_audit['lokasi_id'] == $lok['lokasi_id']) ? 'selected' : '';
                                    echo "<option value='{$lok['lokasi_id']}' $selected>{$lok['lokasi_name']} (Lt.{$lok['lokasi_lantai']})</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="required-field">Kondisi Asset</label>
                            <select name="kondisi_id" id="kondisi_id" class="form-control select2" required>
                                <option value="">-- Pilih Kondisi --</option>
                                <?php
                                mysqli_data_seek($qKondisi, 0);
                                while ($kon = mysqli_fetch_assoc($qKondisi)) {
                                    $selected = ($data_audit['kondisi_id'] == $kon['kondisi_id']) ? 'selected' : '';
                                    echo "<option value='{$kon['kondisi_id']}' $selected>{$kon['kondisi_name']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="required-field">Qty Temuan</label>
                            <input type="number" name="audit_qty" id="audit_qty" class="form-control" min="1" value="<?= $data_audit['audit_qty'] ?>" required>
                        </div>
                    </div>
                </div>

                <hr>
                    
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <?php if (!empty($data_audit['audit_image'])): ?>
                            <div class="mb-2">
                                <label>Gambar Saat Ini:</label><br>
                                <img src="../master/img/audit/<?= htmlspecialchars($data_audit['audit_image']) ?>" class="current-image">
                                <div class="form-check mt-2">
                                    <input type="checkbox" name="hapus_gambar" id="hapus_gambar" value="1" class="form-check-input">
                                    <label class="form-check-label text-danger" for="hapus_gambar">Hapus gambar saat ini</label>
                                </div>
                                <input type="hidden" name="gambar_lama" value="<?= htmlspecialchars($data_audit['audit_image']) ?>">
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Upload Bukti Foto Baru</label>
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
                                <input type="file" name="audit_image" id="audit_image" accept="image/png, image/jpeg, image/jpg" hidden>
                            </div>
                            <small class="text-muted">Format: JPG / PNG (Max 2MB) - Kosongkan jika tidak ingin mengubah gambar</small>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="required-field">Status Audit</label>
                            <select name="status" id="status" class="form-control select2" required>
                                <option value="1" <?= ($data_audit['status'] == 1) ? 'selected' : '' ?>>Pending</option>
                                <option value="2" <?= ($data_audit['status'] == 2) ? 'selected' : '' ?>>Done</option>
                            </select>
                            <small class="text-muted">Pending = Masih perlu diedit, Done = Selesai dan siap diproses</small>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <div class="form-group">
                            <label>Catatan Audit</label>
                            <textarea name="audit_note" class="form-control" rows="3" placeholder="Masukkan catatan tambahan (opsional)"><?= htmlspecialchars($data_audit['audit_note'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>

                <hr>

                <!-- Tombol Submit -->
                <div class="row mt-4">
                    <div class="col-md-12">
                        <button type="submit" name="edit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Audit
                        </button>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Batal
                        </a>
                    </div>
                </div>

            </form>

        </div>
    </div>

    <!-- Riwayat Audit untuk Asset yang Sama -->
    <div class="card shadow mt-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-history"></i> Riwayat Audit Asset Ini
            </h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Auditor</th>
                            <th>Lokasi</th>
                            <th>Kondisi</th>
                            <th>Qty</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $qRiwayat = mysqli_query($conn, "
                            SELECT a.*, kond.kondisi_name, l.lokasi_name, l.lokasi_lantai
                            FROM tbl_audit a
                            LEFT JOIN tbl_kondisi kond ON a.kondisi_id = kond.kondisi_id
                            LEFT JOIN tbl_lokasi l ON a.lokasi_id = l.lokasi_id
                            WHERE a.assets_id = '{$data_audit['assets_id']}'
                            ORDER BY a.timestamp DESC
                            LIMIT 10
                        ");
                        while ($riwayat = mysqli_fetch_assoc($qRiwayat)):
                            $status_text = '';
                            if ($riwayat['audit_status'] == 0) $status_text = 'Belum';
                            elseif ($riwayat['audit_status'] == 1) $status_text = 'Kurang';
                            elseif ($riwayat['audit_status'] == 2) $status_text = 'Lebih';
                            else $status_text = 'Selesai';
                            
                            $lokasi = $riwayat['lokasi_name'] ?? '-';
                            if (!empty($riwayat['lokasi_lantai'])) {
                                $lokasi .= ' (' . $riwayat['lokasi_lantai'] . ')';
                            }
                        ?>
                        <tr>
                            <td class="text-center"><?= date('d/m/Y H:i', strtotime($riwayat['timestamp'])) ?> </div>
                            <td class="text-center"><?= htmlspecialchars($riwayat['auditor']) ?> </div>
                            <td class="text-center"><?= htmlspecialchars($lokasi) ?> </div>
                            <td class="text-center"><?= htmlspecialchars($riwayat['kondisi_name'] ?? '-') ?> </div>
                            <td class="text-center"><?= $riwayat['audit_qty'] ?> unit</div>
                            <td class="text-center"><?= $status_text ?> </div>
                            <td class="text-center">
                                <?php if ($riwayat['audit_id'] != $edit_id): ?>
                                <a href="edit.php?id=<?= $riwayat['audit_id'] ?>" class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <?php else: ?>
                                <span class="badge badge-primary">Sedang Diedit</span>
                                <?php endif; ?>
                             </div>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
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
    
    // PERBAIKAN: Dropdown penanggung jawab berdasarkan departemen
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
    
    // Upload gambar handling
    const fileInput = document.getElementById('audit_image');
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
            preview.classList.remove('d-none');
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
    
    // Validasi sebelum submit
    $('#auditForm').on('submit', function(e) {
        var depCode = $('#dep_code').val();
        if (!depCode) {
            e.preventDefault();
            Swal.fire('Error', 'Departemen harus dipilih!', 'error');
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
        
        var lokasiId = $('#lokasi_id').val();
        if (!lokasiId) {
            e.preventDefault();
            Swal.fire('Error', 'Lokasi asset harus dipilih!', 'error');
            return false;
        }
        
        var kondisiId = $('#kondisi_id').val();
        if (!kondisiId) {
            e.preventDefault();
            Swal.fire('Error', 'Kondisi asset harus dipilih!', 'error');
            return false;
        }
        
        var auditQty = $('#audit_qty').val();
        if (!auditQty || auditQty < 1) {
            e.preventDefault();
            Swal.fire('Error', 'Qty temuan minimal 1!', 'error');
            return false;
        }
    });
});
</script>

</body>
</html>