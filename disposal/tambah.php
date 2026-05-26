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
            <i class="fas fa-trash-alt"></i> Ajukan Disposal Asset
        </h1>
        <a href="index.php" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="card shadow">
        <div class="card-body">
            <div class="info-card">
                <i class="fas fa-info-circle text-warning"></i> 
                <strong>Informasi:</strong> Anda hanya dapat mengajukan disposal untuk asset yang sudah melewati masa manfaat (minimal 75% dari estimasi).
            </div>

            <form action="proses.php" method="POST" id="disposalForm">
                <input type="hidden" name="tipe" value="asset">

                <!-- ================= FORM ASSET ================= -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="required-field">Pilih Kode Asset</label>
                            <select name="assets_id" id="assets_id" class="form-control select2" required>
                                <option value="">-- Pilih Kode Asset --</option>
                                <?php
                                // Query asset yang dimiliki user dan belum diajukan disposal
                                $query = "SELECT DISTINCT 
                                            a.assets_id,
                                            a.assets_kode,
                                            a.assets_name,
                                            a.assets_date,
                                            a.assets_life
                                          FROM tbl_primary p
                                          INNER JOIN tbl_assets a ON p.assets_id = a.assets_id
                                          WHERE p.karyawan_id = $user_id 
                                          AND (p.disposal_status IS NULL OR p.disposal_status = 0)
                                          ORDER BY a.assets_kode ASC";
                                $result = mysqli_query($conn, $query);
                                while ($row = mysqli_fetch_assoc($result)) {
                                    echo "<option value='{$row['assets_id']}' 
                                                data-name='{$row['assets_name']}'
                                                data-date='{$row['assets_date']}'
                                                data-life='{$row['assets_life']}'>
                                            {$row['assets_kode']} - {$row['assets_name']}
                                          </option>";
                                }
                                ?>
                            </select>
                            <small class="text-muted">Pilih kode asset yang akan diajukan disposal</small>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="required-field">Nama Asset</label>
                            <input type="text" id="assets_name" class="form-control readonly-field" readonly>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="required-field">Pilih Lokasi Asset</label>
                            <select name="primary_id" id="primary_id" class="form-control select2" required disabled>
                                <option value="">-- Pilih Lokasi Asset --</option>
                            </select>
                            <small class="text-muted">Pilih unit asset berdasarkan lokasi</small>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="required-field">Quantity</label>
                            <input type="number" name="qty" id="qty" class="form-control readonly-field" readonly>
                            <small class="text-muted">Quantity akan terisi otomatis</small>
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
    
    // Fungsi untuk menghitung persentase umur asset
    function hitungPersentaseUmur(tanggalBeli, masaManfaat) {
        if (!tanggalBeli || !masaManfaat || masaManfaat <= 0) {
            return 0;
        }
        
        var tanggalBeliDate = new Date(tanggalBeli);
        var tanggalSekarang = new Date();
        
        // Hitung selisih dalam tahun
        var umurTahun = (tanggalSekarang - tanggalBeliDate) / (1000 * 60 * 60 * 24 * 365.25);
        
        // Hitung persentase umur
        var persentase = (umurTahun / masaManfaat) * 100;
        
        return persentase;
    }
    
    // Fungsi untuk memformat tanggal ke Indonesian
    function formatTanggalIndonesia(tanggal) {
        if (!tanggal) return '';
        var months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 
                      'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        var date = new Date(tanggal);
        return date.getDate() + ' ' + months[date.getMonth()] + ' ' + date.getFullYear();
    }
    
    // Saat kode asset dipilih, ambil nama asset dan validasi umur
    $('#assets_id').on('change', function() {
        var selected = $(this).find('option:selected');
        var assetsName = selected.data('name');
        var assetsDate = selected.data('date');
        var assetsLife = selected.data('life');
        
        $('#assets_name').val(assetsName);
        
        // Validasi umur asset (minimal 75% dari masa manfaat)
        if (assetsDate && assetsLife && assetsLife > 0) {
            var persentase = hitungPersentaseUmur(assetsDate, assetsLife);
            var tanggalBeliFormatted = formatTanggalIndonesia(assetsDate);
            
            if (persentase < 75) {
                var tahunBerlalu = Math.floor((persentase * assetsLife) / 100);
                var bulanBerlalu = Math.floor(((persentase * assetsLife) % 1) * 12);
                var sisaTahun = assetsLife - tahunBerlalu;
                var sisaBulan = 12 - bulanBerlalu;
                
                var sisaText = '';
                if (sisaTahun > 0) {
                    sisaText += sisaTahun + ' tahun';
                }
                if (sisaBulan > 0) {
                    sisaText += (sisaText ? ' ' : '') + sisaBulan + ' bulan';
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Asset Belum Dapat Didaftarkan',
                    html: '<strong>' + selected.text() + '</strong><br><br>' +
                          '📅 Tanggal Beli: <strong>' + tanggalBeliFormatted + '</strong><br>' +
                          '⏱️ Estimasi Masa Manfaat: <strong>' + assetsLife + ' tahun</strong><br>' +
                          '📊 Umur Asset Saat Ini: <strong>' + Math.floor(persentase) + '%</strong><br><br>' +
                          'Asset baru mencapai <strong>' + Math.floor(persentase) + '%</strong> dari masa manfaat.<br>' +
                          'Minimal <strong>75%</strong> untuk dapat diajukan disposal.<br><br>' +
                          '⏳ Sisa masa manfaat: <strong>' + sisaText + '</strong><br>',
                    confirmButtonText: 'OK'
                }).then(function() {
                    $('#assets_id').val('').trigger('change');
                });
                
                // Reset dropdown lokasi
                $('#primary_id').empty().append('<option value="">-- Pilih Lokasi Asset --</option>');
                $('#primary_id').prop('disabled', true);
                $('#qty').val('');
                return;
            }
        }
        
        // Reset dropdown lokasi dan qty
        $('#primary_id').empty().append('<option value="">-- Pilih Lokasi Asset --</option>');
        $('#primary_id').prop('disabled', true);
        $('#qty').val('');
        
        var assetsId = $(this).val();
        
        if (assetsId) {
            // Load lokasi berdasarkan assets_id yang dipilih
            $('#primary_id').prop('disabled', false);
            
            $.ajax({
                url: 'get_primary_by_asset.php',
                type: 'POST',
                data: { assets_id: assetsId, user_id: <?= $user_id ?> },
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.data.length > 0) {
                        $('#primary_id').empty().append('<option value="">-- Pilih Lokasi Asset --</option>');
                        $.each(response.data, function(index, item) {
                            var lokasi = item.lokasi_name;
                            if (item.lokasi_lantai) {
                                lokasi += ' (Lt.' + item.lokasi_lantai + ')';
                            }
                            $('#primary_id').append('<option value="' + item.primary_id + '" data-qty="' + item.primary_qty + '">' + lokasi + ' (Qty: ' + item.primary_qty + ' unit)</option>');
                        });
                        $('#primary_id').trigger('change');
                    } else {
                        $('#primary_id').empty().append('<option value="">-- Tidak ada unit asset --</option>');
                        $('#primary_id').prop('disabled', true);
                        Swal.fire('Info', 'Tidak ada unit asset yang tersedia untuk asset ini', 'info');
                    }
                },
                error: function() {
                    $('#primary_id').empty().append('<option value="">-- Error loading data --</option>');
                    Swal.fire('Error', 'Gagal memuat data lokasi', 'error');
                }
            });
        }
    });
    
    // Saat lokasi dipilih, set qty
    $('#primary_id').on('change', function() {
        var selected = $(this).find('option:selected');
        var qty = selected.data('qty');
        $('#qty').val(qty || '');
    });
    
    // Validasi sebelum submit
    $('#disposalForm').on('submit', function(e) {
        if (!$('#assets_id').val()) {
            e.preventDefault();
            Swal.fire('Error', 'Kode asset harus dipilih!', 'error');
            return false;
        }
        
        if (!$('#primary_id').val()) {
            e.preventDefault();
            Swal.fire('Error', 'Lokasi asset harus dipilih!', 'error');
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