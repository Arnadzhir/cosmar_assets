<?php
include '../auth/auth.php';
allowRole([3]);

include '../config/koneksi.php';

$user_id = $_SESSION['user_id'];
$user_level = $_SESSION['user_level'];
$dep_id = $_SESSION['dep_id'] ?? 0;

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
</style>

<div class="container-fluid">
    
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-microchip"></i> Ajukan Disposal Sparepart
        </h1>
        <a href="index.php" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="card shadow">
        <div class="card-body">
            <div class="info-card">
                <i class="fas fa-info-circle text-warning"></i> 
                <strong>Informasi:</strong> Anda dapat mengajukan disposal sparepart dengan jumlah yang tidak melebihi stok yang tersedia.
            </div>

            <form action="proses2.php" method="POST" id="disposalForm">
                <input type="hidden" name="tipe" value="sparepart">

                <!-- ================= FORM SPAREPART ================= -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="required-field">Pilih Sparepart</label>
                            <select name="sparepart_id" id="sparepart_id" class="form-control select2" required>
                                <option value="">-- Pilih Sparepart --</option>
                                <?php
                                // Query sparepart yang dimiliki user dan belum diajukan disposal
                                $query = "SELECT 
                                            s.sparepart_id,
                                            s.sparepart_name,
                                            s.sparepart_merk,
                                            s.sparepart_qty,
                                            s.sparepart_price,
                                            a.assets_kode,
                                            a.assets_name
                                          FROM tbl_sparepart s
                                          INNER JOIN tbl_assets a ON s.assets_id = a.assets_id
                                          WHERE s.user_id = $user_id 
                                          AND (s.disposal_status IS NULL OR s.disposal_status = 0)
                                          ORDER BY a.assets_kode ASC, s.sparepart_name ASC";
                                $result = mysqli_query($conn, $query);
                                while ($row = mysqli_fetch_assoc($result)) {
                                    echo "<option value='{$row['sparepart_id']}' 
                                                data-name='{$row['sparepart_name']}'
                                                data-merk='{$row['sparepart_merk']}'
                                                data-qty='{$row['sparepart_qty']}'
                                                data-price='{$row['sparepart_price']}'
                                                data-asset-kode='{$row['assets_kode']}'
                                                data-asset-name='{$row['assets_name']}'>
                                            {$row['assets_kode']} - {$row['sparepart_name']} (Stok: {$row['sparepart_qty']})
                                          </option>";
                                }
                                ?>
                            </select>
                            <small class="text-muted">Pilih sparepart yang akan diajukan disposal</small>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="required-field">Kode Asset</label>
                            <input type="text" id="asset_info" class="form-control readonly-field" readonly>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="required-field">Nama Sparepart</label>
                            <input type="text" id="sparepart_name" class="form-control readonly-field" readonly>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="required-field">Merk</label>
                            <input type="text" id="sparepart_merk" class="form-control readonly-field" readonly>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="required-field">Stok Tersedia</label>
                            <input type="number" id="stok_tersedia" class="form-control readonly-field" readonly>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="required-field">Jumlah yang Didisposal</label>
                            <input type="number" name="disposal_qty" id="disposal_qty" class="form-control qty-input" min="1" required>
                            <small class="text-muted">Masukkan jumlah sparepart yang akan didisposal</small>
                            <div id="qty_warning" class="warning-text" style="display:none;"></div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Sisa Stok Setelah Disposal</label>
                            <input type="number" id="sisa_stok" class="form-control readonly-field" readonly>
                            <small class="text-muted">Sisa stok akan terupdate otomatis</small>
                        </div>
                    </div>
                </div>

                <!-- Alasan Disposal -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label class="required-field">Alasan Disposal</label>
                            <textarea name="alasan" id="alasan" class="form-control" rows="4" placeholder="Isikan alasan pengajuan disposal..." required></textarea>
                            <small class="text-muted">Isikan alasan yang jelas untuk memudahkan proses approval</small>
                        </div>
                    </div>
                </div>

                <!-- Tombol Submit -->
                <div class="row">
                    <div class="col-md-12">
                        <hr>
                        <button type="submit" name="tambah" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Ajukan Disposal
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
    
    // Fungsi untuk menghitung sisa stok
    function hitungSisaStok() {
        var stok = parseInt($('#stok_tersedia').val()) || 0;
        var disposal = parseInt($('#disposal_qty').val()) || 0;
        var sisa = stok - disposal;
        
        $('#sisa_stok').val(sisa);
        
        // Tampilkan warning jika disposal melebihi stok
        if (disposal > stok) {
            $('#qty_warning').html('<i class="fas fa-exclamation-triangle"></i> Jumlah disposal melebihi stok tersedia! Maksimal: ' + stok + ' unit');
            $('#qty_warning').css('color', '#dc3545').show();
            $('#disposal_qty').addClass('is-invalid');
        } else if (disposal <= 0) {
            $('#qty_warning').html('');
            $('#qty_warning').hide();
            $('#disposal_qty').removeClass('is-invalid');
        } else if (disposal > 0 && disposal <= stok) {
            $('#qty_warning').html('<i class="fas fa-info-circle"></i> Sisa stok setelah disposal: ' + sisa + ' unit');
            $('#qty_warning').css('color', '#28a745').show();
            $('#disposal_qty').removeClass('is-invalid');
        }
        
        return disposal <= stok;
    }
    
    // Saat sparepart dipilih, isi data
    $('#sparepart_id').on('change', function() {
        var selected = $(this).find('option:selected');
        var sparepartName = selected.data('name');
        var sparepartMerk = selected.data('merk');
        var sparepartQty = selected.data('qty');
        var assetKode = selected.data('asset-kode');
        var assetName = selected.data('asset-name');
        
        $('#sparepart_name').val(sparepartName || '');
        $('#sparepart_merk').val(sparepartMerk || '');
        $('#stok_tersedia').val(sparepartQty || 0);
        $('#asset_info').val((assetKode || '') + ' - ' + (assetName || ''));
        
        // Reset input disposal qty
        $('#disposal_qty').val('');
        $('#sisa_stok').val('');
        $('#qty_warning').hide();
        $('#disposal_qty').removeClass('is-invalid');
        
        // Set max attribute untuk input
        $('#disposal_qty').attr('max', sparepartQty || 1);
    });
    
    // Saat jumlah disposal diubah
    $('#disposal_qty').on('input', function() {
        hitungSisaStok();
    });
    
    // Validasi sebelum submit
    $('#disposalForm').on('submit', function(e) {
        if (!$('#sparepart_id').val()) {
            e.preventDefault();
            Swal.fire('Error', 'Sparepart harus dipilih!', 'error');
            return false;
        }
        
        var stok = parseInt($('#stok_tersedia').val()) || 0;
        var disposal = parseInt($('#disposal_qty').val()) || 0;
        
        if (disposal <= 0) {
            e.preventDefault();
            Swal.fire('Error', 'Jumlah disposal harus lebih dari 0!', 'error');
            return false;
        }
        
        if (disposal > stok) {
            e.preventDefault();
            Swal.fire('Error', 'Jumlah disposal tidak boleh melebihi stok tersedia!', 'error');
            return false;
        }
        
        var alasan = $('#alasan').val().trim();
        if (!alasan) {
            e.preventDefault();
            Swal.fire('Error', 'Alasan disposal harus diisi!', 'error');
            return false;
        }
    });
});
</script>

</body>
</html>