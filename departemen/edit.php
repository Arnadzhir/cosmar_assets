<?php
include '../auth/auth.php';
allowRole([1,2]);

include '../config/koneksi.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    $_SESSION['alert'] = ['type' => 'danger', 'msg' => 'ID departemen tidak valid!'];
    header("Location: index.php");
    exit;
}

// PERBAIKAN: Query hanya mengambil kolom yang ada di database
$data = mysqli_query($conn, "SELECT dep_id, dep_code, dep_name FROM tbl_dep WHERE dep_id = '$id'");
$row = mysqli_fetch_assoc($data);

if (!$row) {
    $_SESSION['alert'] = ['type' => 'danger', 'msg' => 'Data departemen tidak ditemukan!'];
    header("Location: index.php");
    exit;
}

include '../menu/header.php';
include '../menu/sidebar.php';
include '../menu/topbar.php';
?>

<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<style>
    .required-field:after {
        content: " *";
        color: red;
    }
    .select2-container {
        width: 100% !important;
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
            <i class="fas fa-edit"></i> Edit Departemen
        </h1>
        <a href="index.php" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="card shadow">
        <div class="card-body">

            <form action="proses.php" method="POST">
                
                <input type="hidden" name="dep_id" value="<?= $row['dep_id'] ?>">

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="required-field">Kode Departemen</label>
                            <select name="dep_code" id="dep_code" class="form-control select2" required>
                                <option value="">-- Pilih Kode --</option>
                                <option value="ENG" <?= $row['dep_code'] == 'ENG' ? 'selected' : '' ?>>ENG - Engineering</option>
                                <option value="FAT" <?= $row['dep_code'] == 'FAT' ? 'selected' : '' ?>>FAT - Finance & Accounting</option>
                                <option value="HRGA" <?= $row['dep_code'] == 'HRGA' ? 'selected' : '' ?>>HRGA - Human Resources & General Affairs</option>
                                <option value="IT" <?= $row['dep_code'] == 'IT' ? 'selected' : '' ?>>IT - Information Technology</option>
                                <option value="MKT" <?= $row['dep_code'] == 'MKT' ? 'selected' : '' ?>>MKT - Sales & Marketing</option>
                                <option value="PRC" <?= $row['dep_code'] == 'PRC' ? 'selected' : '' ?>>PRC - Purchasing & Packaging Development</option>
                                <option value="PPIC" <?= $row['dep_code'] == 'PPIC' ? 'selected' : '' ?>>PPIC - Production Planning & Inventory Control</option>
                                <option value="PROD" <?= $row['dep_code'] == 'PROD' ? 'selected' : '' ?>>PROD - Production</option>
                                <option value="QAQC" <?= $row['dep_code'] == 'QAQC' ? 'selected' : '' ?>>QAQC - Quality Assurance & Quality Control</option>
                                <option value="RND" <?= $row['dep_code'] == 'RND' ? 'selected' : '' ?>>RND - Research & Development</option>
                                <option value="WH" <?= $row['dep_code'] == 'WH' ? 'selected' : '' ?>>WH - Warehouse</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="required-field">Nama Departemen</label>
                            <input type="text" name="dep_name" id="dep_name" class="form-control readonly-field" 
                                   value="<?= htmlspecialchars($row['dep_name']) ?>" readonly>
                            <small class="text-muted">Nama akan otomatis terisi berdasarkan kode yang dipilih</small>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <button type="submit" name="edit" class="btn btn-primary">
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

<script>
$(document).ready(function() {
    // Inisialisasi Select2
    $('.select2').select2({
        theme: 'bootstrap4',
        width: '100%',
        placeholder: '-- Pilih Kode --',
        allowClear: true
    });
    
    // Mapping kode ke nama departemen
    var depNameMap = {
        'ENG': 'Engineering',
        'FAT': 'Finance & Accounting',
        'HRGA': 'Human Resources & General Affairs',
        'IT': 'Information Technology',
        'MKT': 'Sales & Marketing',
        'PRC': 'Purchasing & Packaging Development',
        'PPIC': 'Production Planning & Inventory Control',
        'PROD': 'Production',
        'QAQC': 'Quality Assurance & Quality Control',
        'RND': 'Research & Development',
        'WH': 'Warehouse'
    };
    
    // Auto fill nama departemen berdasarkan kode yang dipilih
    $('#dep_code').on('change', function() {
        var selectedVal = $(this).val();
        
        if (selectedVal && depNameMap[selectedVal]) {
            $('#dep_name').val(depNameMap[selectedVal]);
        } else {
            var selectedText = $(this).find('option:selected').text();
            if (selectedText && selectedText.includes('-')) {
                var depName = selectedText.split('-')[1].trim();
                $('#dep_name').val(depName);
            } else {
                $('#dep_name').val('');
            }
        }
    });
    
    // Simpan nilai awal sebelum perubahan (opsional)
    var originalDepCode = $('#dep_code').val();
});
</script>

</body>
</html>