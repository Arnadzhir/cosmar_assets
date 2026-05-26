<?php
include '../auth/auth.php';
allowRole([1,2,3]);

include '../config/koneksi.php';

$user_id = $_SESSION['user_id'];
$user_level = $_SESSION['user_level'];
$dep_id = $_SESSION['dep_id'] ?? 0;

// Ambil ID dari URL
$sparepart_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($sparepart_id <= 0) {
    $_SESSION['alert'] = ['type' => 'danger', 'msg' => 'ID tidak valid!'];
    header("Location: index.php");
    exit;
}

// Ambil data sparepart yang diajukan disposal
$query = "SELECT 
            s.sparepart_id,
            s.sparepart_name,
            s.sparepart_merk,
            s.sparepart_qty,
            s.sparepart_price,
            s.sparepart_spec,
            s.disposal_reason,
            s.disposal_date,
            s.disposal_status,
            a.assets_id,
            a.assets_kode,
            a.assets_name,
            a.assets_date,
            kar.karyawan_name,
            d.dep_name,
            d.dep_code
          FROM tbl_sparepart s
          INNER JOIN tbl_assets a ON s.assets_id = a.assets_id
          LEFT JOIN tbl_karyawan kar ON s.user_id = kar.karyawan_id
          LEFT JOIN tbl_dep d ON kar.dep_id = d.dep_id
          WHERE s.sparepart_id = $sparepart_id AND s.disposal_status = 1";

$result = mysqli_query($conn, $query);
$data = mysqli_fetch_assoc($result);

if (!$data) {
    $_SESSION['alert'] = ['type' => 'danger', 'msg' => 'Data tidak ditemukan!'];
    header("Location: index.php");
    exit;
}

// Inisialisasi semua variabel dengan nilai default
$assets_kode = isset($data['assets_kode']) ? htmlspecialchars($data['assets_kode']) : '-';
$assets_name = isset($data['assets_name']) ? htmlspecialchars($data['assets_name']) : '-';
$assets_date = isset($data['assets_date']) ? $data['assets_date'] : '';
$sparepart_name = isset($data['sparepart_name']) ? htmlspecialchars($data['sparepart_name']) : '-';
$sparepart_merk = isset($data['sparepart_merk']) ? htmlspecialchars($data['sparepart_merk']) : '-';
$sparepart_qty = isset($data['sparepart_qty']) ? $data['sparepart_qty'] : 0;
$sparepart_price = isset($data['sparepart_price']) ? $data['sparepart_price'] : 0;
$sparepart_spec = isset($data['sparepart_spec']) ? htmlspecialchars($data['sparepart_spec']) : '';
$disposal_reason = isset($data['disposal_reason']) ? htmlspecialchars($data['disposal_reason']) : '';
$disposal_date = isset($data['disposal_date']) ? $data['disposal_date'] : '';
$karyawan_name = isset($data['karyawan_name']) ? htmlspecialchars($data['karyawan_name']) : '-';
$dep_name = isset($data['dep_name']) ? htmlspecialchars($data['dep_name']) : '-';
$dep_code = isset($data['dep_code']) ? htmlspecialchars($data['dep_code']) : '-';

// Hitung umur
function hitungUmur($tanggal_pengajuan, $tanggal_pembelian) {
    if (empty($tanggal_pengajuan) || empty($tanggal_pembelian) || $tanggal_pengajuan == '0000-00-00' || $tanggal_pembelian == '0000-00-00') {
        return '-';
    }
    $tgl1 = new DateTime($tanggal_pengajuan);
    $tgl2 = new DateTime($tanggal_pembelian);
    $diff = $tgl1->diff($tgl2);
    $hasil = array();
    if ($diff->y > 0) $hasil[] = $diff->y . ' Tahun';
    if ($diff->m > 0) $hasil[] = $diff->m . ' Bulan';
    if ($diff->d > 0) $hasil[] = $diff->d . ' Hari';
    return !empty($hasil) ? implode(' ', $hasil) : '0 Hari';
}

$umur = hitungUmur($disposal_date, $assets_date);
$total_nilai = $sparepart_price * $sparepart_qty;

// Format tanggal
$tanggal_beli = (!empty($assets_date) && $assets_date != '0000-00-00') ? date('d/m/Y', strtotime($assets_date)) : '-';
$tanggal_pengajuan = (!empty($disposal_date) && $disposal_date != '0000-00-00') ? date('d/m/Y H:i', strtotime($disposal_date)) : '-';

include '../menu/header.php';
include '../menu/sidebar.php';
include '../menu/topbar.php';
?>

<!-- SweetAlert2 CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<style>
    .select2-container {
        width: 100% !important;
    }
    .required-field:after {
        content: " *";
        color: red;
    }
    .info-card {
        background: #e8f0fe;
        border-left: 4px solid #ffc107;
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 8px;
    }
    .readonly-field {
        background-color: #e9ecef;
        cursor: not-allowed;
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
    .required-field:after {
        content: " *";
        color: red;
    }
    .info-card {
        background: #e8f0fe;
        border-left: 4px solid #ffc107;
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 8px;
    }
    .info-card-warning {
        background: #fff3cd;
        border-left: 4px solid #ffc107;
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 8px;
    }
    .readonly-field {
        background-color: #e9ecef;
        cursor: not-allowed;
    }
    .status-badge {
        display: inline-block;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    .status-pending {
        background-color: #ffc107;
        color: #212529;
    }
    .status-approved {
        background-color: #28a745;
        color: white;
    }
    .status-rejected {
        background-color: #dc3545;
        color: white;
    }
    .umur-badge {
        background-color: #f0f0f0;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        display: inline-block;
    }
</style>

<div class="container-fluid">
    
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-edit"></i> Edit Pengajuan Disposal Sparepart
        </h1>
        <a href="index.php" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <!-- Informasi Status -->
    <div class="info-card-warning">
        <i class="fas fa-info-circle text-warning"></i> 
        <strong>Status Pengajuan:</strong> 
        <span class="status-badge status-pending">Menunggu Approval</span>
        <br>
        <small>Pengajuan ini masih menunggu persetujuan dari admin/operator.</small>
    </div>

    <div class="card shadow">
        <div class="card-body">

            <form action="proses2.php" method="POST" id="disposalForm">
                <input type="hidden" name="edit" value="1">
                <input type="hidden" name="type" value="sparepart">
                <input type="hidden" name="id" value="<?= $sparepart_id ?>">

                <!-- Informasi Asset -->
                <div class="row">
                    <div class="col-md-12">
                        <h5 class="text-primary mb-3"><i class="fas fa-box"></i> Informasi Asset</h5>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Kode Asset</label>
                            <input type="text" class="form-control readonly-field" 
                                   value="<?= $assets_kode ?>" readonly>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Nama Asset</label>
                            <input type="text" class="form-control readonly-field" 
                                   value="<?= $assets_name ?>" readonly>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Tanggal Pembelian Asset</label>
                            <input type="text" class="form-control readonly-field" 
                                   value="<?= $tanggal_beli ?>" readonly>
                        </div>
                    </div>
                </div>

                <!-- Informasi Sparepart -->
                <div class="row mt-3">
                    <div class="col-md-12">
                        <h5 class="text-primary mb-3"><i class="fas fa-microchip"></i> Informasi Sparepart</h5>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Nama Sparepart</label>
                            <input type="text" class="form-control readonly-field" 
                                   value="<?= $sparepart_name ?>" readonly>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Merk</label>
                            <input type="text" class="form-control readonly-field" 
                                   value="<?= $sparepart_merk ?>" readonly>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Quantity</label>
                            <input type="text" class="form-control readonly-field" 
                                   value="<?= number_format($sparepart_qty) ?> unit" readonly>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Harga per Unit</label>
                            <input type="text" class="form-control readonly-field" 
                                   value="Rp <?= number_format($sparepart_price, 0, ',', '.') ?>" readonly>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Total Nilai</label>
                            <input type="text" class="form-control readonly-field" 
                                   value="Rp <?= number_format($total_nilai, 0, ',', '.') ?>" readonly>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Umur Asset</label>
                            <input type="text" class="form-control readonly-field" 
                                   value="<?= $umur ?>" readonly>
                        </div>
                    </div>
                </div>

                <?php if (!empty($sparepart_spec)): ?>
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>Spesifikasi</label>
                            <textarea class="form-control readonly-field" rows="3" readonly><?= $sparepart_spec ?></textarea>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Informasi Pengaju -->
                <div class="row mt-3">
                    <div class="col-md-12">
                        <h5 class="text-primary mb-3"><i class="fas fa-user"></i> Informasi Pengaju</h5>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Penanggung Jawab</label>
                            <input type="text" class="form-control readonly-field" 
                                   value="<?= $karyawan_name ?>" readonly>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Departemen</label>
                            <input type="text" class="form-control readonly-field" 
                                   value="<?= $dep_code ?> - <?= $dep_name ?>" readonly>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Tanggal Pengajuan</label>
                            <input type="text" class="form-control readonly-field" 
                                   value="<?= $tanggal_pengajuan ?>" readonly>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Jumlah yang Diajukan</label>
                            <input type="text" class="form-control readonly-field" 
                                   value="<?= number_format($sparepart_qty) ?> unit" readonly>
                        </div>
                    </div>
                </div>

                <!-- Alasan Disposal -->
                <div class="row mt-3">
                    <div class="col-md-12">
                        <h5 class="text-primary mb-3"><i class="fas fa-comment"></i> Alasan Disposal</h5>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <?php if ($user_level == 3): ?>
                                <textarea name="alasan" id="alasan" class="form-control" rows="4" 
                                          placeholder="Edit alasan disposal jika diperlukan"><?= $disposal_reason ?></textarea>
                                <small class="text-muted">Edit alasan disposal jika diperlukan.</small>
                            <?php else: ?>
                                <textarea class="form-control readonly-field" rows="4" readonly><?= $disposal_reason ?></textarea>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Status Approve (Hanya untuk Admin/Operator) -->
                <?php if (in_array($user_level, [1, 2])): ?>
                <div class="row mt-3">
                    <div class="col-md-12">
                        <h5 class="text-primary mb-3"><i class="fas fa-check-circle"></i> Approval</h5>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label class="required-field">Status Approve</label>
                            <select name="approve_status" id="approve_status" class="form-control select2" required>
                                <option value="">-- Pilih Status --</option>
                                <option value="approve">✅ Setujui (Disposal)</option>
                                <option value="reject">❌ Tolak (Kembalikan)</option>
                            </select>
                            <small class="text-muted">
                                Pilih "Setujui" untuk mengkonfirmasi disposal, atau "Tolak" untuk membatalkan pengajuan.
                            </small>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Tombol Submit -->
                <div class="row mt-4">
                    <div class="col-md-12">
                        <hr>
                        <?php if ($user_level == 3): ?>
                        <button type="submit" name="edit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Simpan Perubahan
                        </button>
                        <?php else: ?>
                        <button type="submit" name="edit" class="btn btn-success">
                            <i class="fas fa-check"></i> Proses
                        </button>
                        <?php endif; ?>
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
    
    <?php if ($user_level == 3): ?>
    // Validasi untuk user (hanya edit alasan)
    $('#disposalForm').on('submit', function(e) {
        var alasan = $('#alasan').val().trim();
        if (!alasan) {
            e.preventDefault();
            Swal.fire('Error', 'Alasan disposal harus diisi!', 'error');
            return false;
        }
    });
    <?php else: ?>
    // Validasi untuk admin/operator
    $('#disposalForm').on('submit', function(e) {
        var approveStatus = $('#approve_status').val();
        if (!approveStatus) {
            e.preventDefault();
            Swal.fire('Error', 'Status approve harus dipilih!', 'error');
            return false;
        }
        
        if (approveStatus == 'approve') {
            Swal.fire({
                title: 'Setujui Disposal?',
                text: "Sparepart akan dihapus dari sistem. Tindakan ini tidak dapat dibatalkan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#dc3545',
                confirmButtonText: 'Ya, Setujui!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#disposalForm').off('submit').submit();
                }
            });
            return false;
        } else if (approveStatus == 'reject') {
            Swal.fire({
                title: 'Tolak Pengajuan?',
                text: "Pengajuan disposal akan dibatalkan dan sparepart akan tetap tersedia.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#858796',
                confirmButtonText: 'Ya, Tolak!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#disposalForm').off('submit').submit();
                }
            });
            return false;
        }
    });
    <?php endif; ?>
});
</script>

</body>
</html>