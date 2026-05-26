<?php
include '../auth/auth.php';
allowRole([1]);
include '../config/koneksi.php';

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
            <i class="fas fa-user-plus"></i> Tambah Data Karyawan
        </h1>
        <a href="index.php" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="card shadow">
        <div class="card-body">

            <form action="proses.php" method="POST" enctype="multipart/form-data">

                <!-- ID Karyawan -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="required-field">ID Karyawan</label>
                            <input type="text" 
                                name="karyawan_id" 
                                class="form-control"
                                placeholder="Contoh: 918057"
                                pattern="[0-9]+"
                                maxlength="15"
                                oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                required>
                            <small class="text-muted">ID unik untuk karyawan</small>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="required-field">Nama Karyawan</label>
                            <input type="text" name="karyawan_name" class="form-control" required>
                        </div>
                    </div>
                </div>

                <!-- Jenis Kelamin & No Telepon -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="required-field">Jenis Kelamin</label>
                            <select name="karyawan_gender" class="form-control select2" required>
                                <option value="">-- Pilih Jenis Kelamin --</option>
                                <option value="Male">Laki-laki</option>
                                <option value="Female">Perempuan</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="required-field">No. Telepon</label>
                            <input type="text"
                                name="karyawan_no"
                                class="form-control"
                                placeholder="Contoh: 08123456789"
                                pattern="[0-9]+"
                                maxlength="15"
                                oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                required>
                        </div>
                    </div>
                </div>

                <!-- Level & Departemen -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="required-field">Level</label>
                            <select name="karyawan_level" class="form-control select2" required>
                                <option value="">-- Pilih Level --</option>
                                <option value="Manager">Manager</option>
                                <option value="Supervisor">Supervisor</option>
                                <option value="Head">Head</option>
                                <option value="Leader">Leader</option>
                                <option value="Staff">Staff</option>
                            </select>
                            <small class="text-muted">Level jabatan karyawan</small>
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
                                    echo "<option value='{$dep['dep_id']}'>{$dep['dep_code']} - {$dep['dep_name']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>

                <hr>

                <div class="form-group">
                    <button type="submit" name="tambah" class="btn btn-primary">
                        <i class="fas fa-save"></i> Simpan Karyawan
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
        placeholder: '-- Pilih --',
        allowClear: true
    });
});
</script>

</body>
</html>