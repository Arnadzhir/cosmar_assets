<?php
include '../auth/auth.php';
allowRole([1,2]); // Hanya admin dan operator

include '../config/koneksi.php';

/* =====================
   AMBIL DATA USER LOGIN
===================== */
$user_id    = $_SESSION['user_id'];
$user_level = $_SESSION['user_level'];

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$primary_id = intval($_GET['id']);

// Ambil data primary dan assets terkait
$query = mysqli_query($conn, "SELECT 
        p.primary_id,
        p.primary_qty,
        p.primary_image,
        p.timestamp as primary_timestamp,
        
        a.assets_id,
        a.assets_kode,
        a.assets_name,
        a.assets_life,
        a.assets_spec,
        a.assets_target,
        a.assets_cap,
        a.assets_uom,
        a.assets_price,
        a.assets_date,
        a.assets_qty as total_qty,
        a.assets_note,
        a.timestamp as assets_timestamp,
        a.kategori_id,
        a.merk_id,
        a.type_id,
        a.supplier_id,
        a.produsen_id,
        
        kond.kondisi_id,
        kond.kondisi_name,
        
        kat.kategori_id as kat_id,
        kat.kategori_name,
        kat.kategori_line,
        kat.kategori_code,
        
        t.type_id as t_id,
        t.type_name,
        
        s.supplier_id as s_id,
        s.supplier_name,
        s.supplier_mail,
        s.supplier_no,
        
        pr.produsen_id as pr_id,
        pr.produsen_region,
        pr.produsen_code,
        
        m.merk_id as m_id,
        m.merk_name,
        
        l.lokasi_id,
        l.lokasi_name,
        l.lokasi_lantai,
        
        kar.karyawan_id,
        kar.karyawan_name,
        kar.karyawan_no,
        kar.karyawan_gender,
        kar.karyawan_level as dep_position,
        
        d.dep_id,
        d.dep_name,
        d.dep_code
        
    FROM tbl_primary p
    INNER JOIN tbl_assets a      ON p.assets_id = a.assets_id
    LEFT JOIN tbl_kondisi kond   ON p.kondisi_id = kond.kondisi_id
    LEFT JOIN tbl_kategori kat   ON a.kategori_id = kat.kategori_id
    LEFT JOIN tbl_type t         ON a.type_id = t.type_id
    LEFT JOIN tbl_supplier s     ON a.supplier_id = s.supplier_id
    LEFT JOIN tbl_produsen pr    ON a.produsen_id = pr.produsen_id
    LEFT JOIN tbl_merk m         ON a.merk_id = m.merk_id
    LEFT JOIN tbl_lokasi l       ON p.lokasi_id = l.lokasi_id
    LEFT JOIN tbl_karyawan kar   ON p.karyawan_id = kar.karyawan_id
    LEFT JOIN tbl_dep d          ON kar.dep_id = d.dep_id
    WHERE p.primary_id = $primary_id
");

if (!$query) {
    die("Error query: " . mysqli_error($conn));
}

$data = mysqli_fetch_assoc($query);

if (!$data) {
    $_SESSION['alert'] = [
        'type' => 'danger',
        'msg'  => 'Data tidak ditemukan'
    ];
    header("Location: index.php");
    exit;
}

// Ambil data untuk dropdown
$kondisi   = mysqli_query($conn, "SELECT * FROM tbl_kondisi ORDER BY kondisi_name ASC");
$lokasi    = mysqli_query($conn, "SELECT * FROM tbl_lokasi ORDER BY lokasi_name ASC");
$kategori  = mysqli_query($conn, "SELECT * FROM tbl_kategori ORDER BY kategori_name ASC");
$merk      = mysqli_query($conn, "SELECT * FROM tbl_merk ORDER BY merk_name ASC");
$type      = mysqli_query($conn, "SELECT * FROM tbl_type ORDER BY type_name ASC");
$supplier  = mysqli_query($conn, "SELECT * FROM tbl_supplier ORDER BY supplier_name ASC");
$produsen  = mysqli_query($conn, "SELECT * FROM tbl_produsen ORDER BY produsen_region ASC");

// Ambil data departemen UNIK untuk filter
$departemen = mysqli_query($conn, "
    SELECT DISTINCT d.dep_id, d.dep_code, d.dep_name
    FROM tbl_dep d
    INNER JOIN tbl_karyawan k ON k.dep_id = d.dep_id
    ORDER BY d.dep_name ASC
");

// Backup data untuk menghindari overwrite dari sidebar
$primaryData = $data;
include '../menu/header.php';
include '../menu/sidebar.php';
include '../menu/topbar.php';
$data = $primaryData;
?>

<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-info-circle"></i> Edit Master Assets
        </h1>
        <div>
            <a href="detail.php?id=<?= $primary_id ?>" class="btn btn-info btn-sm">
                <i class="fas fa-eye"></i> Detail
            </a>
            <a href="index.php" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <!-- Status Card -->
    <div class="row">
        <div class="col-md-12">
            <div class="card border-left-primary shadow mb-4">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h5 class="font-weight-bold text-primary">
                                <i class="fas fa-edit"></i> 
                                Mode Edit: <span class="badge badge-primary p-2">Sedang Mengedit</span>
                            </h5>
                            <p class="mb-0">
                                <strong>Kode Assets:</strong> 
                                <span class="badge badge-info p-2" style="font-size: 14px;"><?= $data['assets_kode'] ?></span>
                            </p>
                        </div>
                        <div class="col-md-4 text-right">
                            <p class="mb-0">
                                <i class="fas fa-calendar-alt"></i> 
                                Input: <?= date('d/m/Y H:i', strtotime($data['primary_timestamp'])) ?>
                            </p>
                            <p class="mb-0">
                                <i class="fas fa-cubes"></i> 
                                Total Assets: <?= $data['total_qty'] ?> pcs
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Kolom Kiri: Form Utama -->
        <div class="col-md-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-edit"></i> Form Edit Item
                    </h6>
                </div>
                <div class="card-body">

                    <form action="proses.php" method="POST" enctype="multipart/form-data">

                        <!-- Hidden inputs -->
                        <input type="hidden" name="primary_id" value="<?= $primary_id ?>">
                        <input type="hidden" name="assets_id" value="<?= $data['assets_id'] ?>">

                        <!-- ==================== DEPARTEMEN & USER ==================== -->
                        <?php if (in_array($user_level, [1, 2])) : ?>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Filter Departemen</label>
                                    <select id="dep_code" class="form-control select2">
                                        <option value="">-- Semua Departemen --</option>
                                        <?php 
                                        mysqli_data_seek($departemen, 0);
                                        while($dep = mysqli_fetch_assoc($departemen)): 
                                            $selected = ($dep['dep_code'] == ($data['dep_code'] ?? '')) ? 'selected' : '';
                                        ?>
                                        <option value="<?= htmlspecialchars($dep['dep_code']) ?>" <?= $selected ?>>
                                            <?= htmlspecialchars($dep['dep_code']) ?> - <?= htmlspecialchars($dep['dep_name']) ?>
                                        </option>
                                        <?php endwhile; ?>
                                    </select>
                                    <small class="text-muted">Pilih departemen untuk memfilter nama user</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Penanggung Jawab <span class="text-danger">*</span></label>
                                    <select name="karyawan_id" id="karyawan_id" class="form-control select2" required>
                                        <option value="">-- Pilih User --</option>
                                        <?php
                                        // Load user berdasarkan departemen yang sudah dipilih
                                        if (!empty($data['dep_code'])) {
                                            $qUsers = mysqli_query($conn, "
                                                SELECT k.karyawan_id, k.karyawan_name 
                                                FROM tbl_karyawan k
                                                INNER JOIN tbl_dep d ON k.dep_id = d.dep_id
                                                WHERE d.dep_code = '{$data['dep_code']}'
                                                ORDER BY k.karyawan_name ASC
                                            ");
                                            while($u = mysqli_fetch_assoc($qUsers)) {
                                                $selected = ($u['karyawan_id'] == $data['karyawan_id']) ? 'selected' : '';
                                                echo "<option value='{$u['karyawan_id']}' $selected>{$u['karyawan_name']}</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if ($_SESSION['user_level'] == 3): ?>
                        <input type="hidden" name="karyawan_id" value="<?= $data['karyawan_id'] ?>">
                        <?php endif; ?>
                        
                        <!-- ==================== LOKASI & KONDISI ==================== -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Lokasi <span class="text-danger">*</span></label>
                                    <select name="lokasi_id" class="form-control select2" required>
                                        <option value="">-- Pilih Lokasi --</option>
                                        <?php 
                                        mysqli_data_seek($lokasi, 0);
                                        while($l = mysqli_fetch_assoc($lokasi)): 
                                            $selected = ($l['lokasi_id'] == $data['lokasi_id']) ? 'selected' : '';
                                        ?>
                                        <option value="<?= $l['lokasi_id'] ?>" <?= $selected ?>>
                                            <?= $l['lokasi_name'] ?> - Lt.<?= $l['lokasi_lantai'] ?>
                                        </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Kondisi <span class="text-danger">*</span></label>
                                    <select name="kondisi_id" class="form-control select2" required>
                                        <option value="">-- Pilih Kondisi --</option>
                                        <?php 
                                        mysqli_data_seek($kondisi, 0);
                                        while($k = mysqli_fetch_assoc($kondisi)): 
                                            $selected = ($k['kondisi_id'] == $data['kondisi_id']) ? 'selected' : '';
                                        ?>
                                        <option value="<?= $k['kondisi_id'] ?>" <?= $selected ?>>
                                            <?= $k['kondisi_name'] ?>
                                        </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- ==================== QUANTITY & UPLOAD GAMBAR ==================== -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Quantity <span class="text-danger">*</span></label>
                                    <input type="number" name="primary_qty" class="form-control" 
                                           min="1" value="<?= $data['primary_qty'] ?>" readonly>
                                    <small class="text-muted">
                                        Total assets saat ini: <strong><?= $data['total_qty'] ?></strong> pcs
                                    </small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Upload Gambar (kosongkan jika tidak ingin mengubah)</label>
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
                                    <small class="text-muted">Format: JPG / PNG (Max 2MB)</small>
                                    
                                    <?php if (!empty($data['primary_image'])): ?>
                                    <div class="mt-2">
                                        <small class="text-muted">Gambar saat ini: <?= $data['primary_image'] ?></small>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Informasi Master (Readonly) -->
                        <div class="alert alert-light mt-3">
                            <h6 class="font-weight-bold text-primary">Informasi Master (Tidak dapat diubah):</h6>
                            <div class="row">
                                <div class="col-md-12">
                                    <small>nama Assets:</small><br>
                                    <span><?= $data['assets_name'] ?? '-' ?></span>
                                </div>
                                <div class="col-md-12">
                                    <small>Spesifikasi:</small><br>
                                    <span><?= $data['assets_spec'] ?? '-' ?></span>
                                </div>                                                               
                                <div class="col-md-4">
                                    <small>Kategori:</small><br>
                                    <span><?= ($data['kategori_name'] ?? '-') . ($data['kategori_line'] ? ' - ' . $data['kategori_line'] : '') ?></span>
                                </div>
                                <div class="col-md-4">
                                    <small>Merk:</small><br>
                                    <span><?= $data['merk_name'] ?? '-' ?></span>
                                </div>
                                <div class="col-md-4">
                                    <small>Type:</small><br>
                                    <span><?= $data['type_name'] ?? '-' ?></span>
                                </div>
                                <div class="col-md-4 mt-2">
                                    <small>Harga:</small><br>
                                    <span>Rp <?= number_format($data['assets_price'] ?? 0, 0, ',', '.') ?></span>
                                </div>
                                <div class="col-md-4 mt-2">
                                    <small>Tanggal Beli:</small><br>
                                    <span><?= !empty($data['assets_date']) && $data['assets_date'] != '0000-00-00' ? date('d/m/Y', strtotime($data['assets_date'])) : '-' ?></span>
                                </div>
                                <div class="col-md-4 mt-2">
                                    <small>Masa Manfaat:</small><br>
                                    <span><?= $data['assets_life'] ?? '-' ?> Tahun</span>
                                </div>
                            </div>
                        </div>

                        <!-- Tombol Submit -->
                        <hr>
                        <div class="row">
                            <div class="col-md-12">
                                <button type="submit" name="update" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update
                                </button>
                                <a href="detail.php?id=<?= $primary_id ?>" class="btn btn-info">
                                    <i class="fas fa-times"></i> Batal
                                </a>
                            </div>
                        </div>

                    </form>

                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Preview Gambar & Info -->
        <div class="col-md-4">
            <!-- Preview Gambar -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-image"></i> Preview Gambar
                    </h6>
                </div>
                <div class="card-body text-center">
                    <?php 
                    $img = !empty($data['primary_image']) 
                        ? "../master/img/assets/" . $data['primary_image'] 
                        : "../master/img/no-image.png";
                    ?>
                    <img src="<?= $img ?>" class="img-fluid rounded shadow" style="max-height:200px; margin-bottom:15px;" id="currentImagePreview">
                    
                    <div class="text-left mt-3">
                        <h6 class="font-weight-bold">Informasi Item Ini:</h6>
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td>Lokasi:</div>
                                <td class="font-weight-bold"><?= $data['lokasi_name'] ?> (Lt.<?= $data['lokasi_lantai'] ?>)</div>
                            </tr>
                            <tr>
                                <td>Qty di lokasi ini:</div>
                                <td class="font-weight-bold"><?= $data['primary_qty'] ?> pcs</div>
                            </tr>
                            <tr>
                                <td>Kondisi:</div>
                                <td><span class="badge badge-info"><?= $data['kondisi_name'] ?></span></div>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Informasi User -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-user"></i> Penanggung Jawab Saat Ini
                    </h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <td>Nama:</td>
                            <td><strong><?= $data['karyawan_name'] ?? '-' ?></strong></td>
                        </tr>
                        <tr>
                            <td>No. Telp:</td>
                            <td><?= $data['karyawan_no'] ?? '-' ?></td>
                        </tr>
                        <tr>
                            <td>Departemen:</td>
                            <td><?= $data['dep_name'] ?? '-' ?></td>
                        </tr>
                        <tr>
                            <td>Posisi:</td>
                            <td><?= $data['dep_position'] ?? '-' ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

<?php include '../menu/footer.php'; ?>

<!-- Script untuk autofill dan validasi -->
<script>
$(document).ready(function() {
    // Inisialisasi select2
    $('.select2').select2({ width: '100%' });
    
    // Saat departemen dipilih (menggunakan dep_code)
    $('#dep_code').on('change', function() {
        let depCode = $(this).val();
        let userSelect = $('#karyawan_id'); // Ganti dari #user_id menjadi #karyawan_id
        
        if (depCode) {
            userSelect.empty().append('<option value="">Loading...</option>').trigger('change');
            
            $.ajax({
                url: 'get_users_by_dep_code.php',
                type: 'POST',
                data: { dep_code: depCode },
                dataType: 'html',
                success: function(response) {
                    console.log('Response:', response);
                    if (response && response.trim() !== '') {
                        userSelect.empty().html(response).trigger('change');
                    } else {
                        userSelect.empty().append('<option value="">-- Tidak ada user --</option>').trigger('change');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                    userSelect.empty().append('<option value="">-- Error loading users --</option>').trigger('change');
                }
            });
        } else {
            userSelect.empty().append('<option value="">-- Pilih User --</option>').trigger('change');
        }
    });

    // Upload gambar dengan preview
    $('#primary_image').on('change', function(e) {
        const file = e.target.files[0];
        const preview = $('#previewImage');
        const removeBtn = $('#removeImage');
        const uploadContent = $('#uploadContent');
        const currentPreview = $('#currentImagePreview');
        
        if (file) {
            if (file.size > 2000000) {
                alert('Ukuran gambar maksimal 2MB!');
                this.value = '';
                return;
            }
            
            const allowedTypes = ['image/jpeg', 'image/png'];
            if (!allowedTypes.includes(file.type)) {
                alert('Format harus JPG atau PNG!');
                this.value = '';
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(event) {
                preview.attr('src', event.target.result);
                preview.removeClass('d-none');
                removeBtn.removeClass('d-none');
                uploadContent.addClass('d-none');
                // Update preview juga di kolom kanan
                currentPreview.attr('src', event.target.result);
            };
            reader.readAsDataURL(file);
        }
    });

    $('#removeImage').on('click', function(e) {
        e.stopPropagation();
        $('#primary_image').val('');
        $('#previewImage').attr('src', '#').addClass('d-none');
        $(this).addClass('d-none');
        $('#uploadContent').removeClass('d-none');
        // Kembalikan preview ke gambar awal
        $('#currentImagePreview').attr('src', '<?= !empty($data['primary_image']) ? "../master/img/assets/" . $data['primary_image'] : "../master/img/no-image.png" ?>');
    });
    
    // Drag & drop
    const uploadArea = document.getElementById('uploadArea');
    const fileInput = document.getElementById('primary_image');
    
    if (uploadArea) {
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
            const file = e.dataTransfer.files[0];
            if (file) {
                handleFile(file);
            }
        });
    }
    
    function handleFile(file) {
        if (file.size > 2000000) {
            alert('Ukuran maksimal 2MB');
            return;
        }
        
        const allowed = ['image/jpeg', 'image/png'];
        if (!allowed.includes(file.type)) {
            alert('Format harus JPG atau PNG');
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            $('#previewImage').attr('src', e.target.result).removeClass('d-none');
            $('#removeImage').removeClass('d-none');
            $('#uploadContent').addClass('d-none');
            $('#currentImagePreview').attr('src', e.target.result);
        };
        reader.readAsDataURL(file);
        
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        fileInput.files = dataTransfer.files;
    }
});
</script>

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
        background: #e3e6f0;
        border-color: #2e59d9;
    }
    .select2-container {
        width: 100% !important;
    }
</style>

</body>
</html>