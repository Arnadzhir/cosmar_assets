<?php
include '../auth/auth.php';
allowRole([1,2,3]);

include '../config/koneksi.php';
include '../config/base_url.php';

// Cek apakah ada karyawan_id
if(!isset($_GET['karyawan_id']) || empty($_GET['karyawan_id'])) {
    echo "ID karyawan tidak ditemukan";
    exit();
}

$karyawan_id = mysqli_real_escape_string($conn, $_GET['karyawan_id']);

// PERBAIKAN: Ambil data karyawan dari tbl_karyawan
$qUser = mysqli_query($conn, "
    SELECT kar.*, d.dep_name, d.dep_code
    FROM tbl_karyawan kar
    LEFT JOIN tbl_dep d ON kar.dep_id = d.dep_id
    WHERE kar.karyawan_id = '$karyawan_id'
");

if (mysqli_num_rows($qUser) == 0) {
    echo "Data karyawan tidak ditemukan";
    exit;
}

$user = mysqli_fetch_assoc($qUser);

// PERBAIKAN: Ambil semua asset milik karyawan tersebut
$qAssets = mysqli_query($conn, "
    SELECT 
        p.primary_id,
        p.primary_qty,
        p.primary_image,
        p.timestamp as primary_timestamp,
        
        a.assets_id,
        a.assets_kode,
        a.assets_name,
        a.assets_model,
        a.assets_spec,
        a.assets_target,
        a.assets_cap,
        a.assets_uom,
        a.assets_price,
        a.assets_date,
        a.assets_life,
        
        kond.kondisi_name,
        
        kat.kategori_name,
        kat.kategori_line,
        
        t.type_name,
        m.merk_name,
        
        l.lokasi_name,
        l.lokasi_lantai
        
    FROM tbl_primary p
    INNER JOIN tbl_assets a ON p.assets_id = a.assets_id
    LEFT JOIN tbl_kondisi kond ON p.kondisi_id = kond.kondisi_id
    LEFT JOIN tbl_kategori kat ON a.kategori_id = kat.kategori_id
    LEFT JOIN tbl_type t ON a.type_id = t.type_id
    LEFT JOIN tbl_merk m ON a.merk_id = m.merk_id
    LEFT JOIN tbl_lokasi l ON p.lokasi_id = l.lokasi_id
    WHERE p.karyawan_id = '$karyawan_id'
    ORDER BY a.assets_name ASC, p.primary_id ASC
");

$total_assets = mysqli_num_rows($qAssets);

// Fungsi untuk mengubah angka bulan menjadi nama bulan dalam bahasa Indonesia
function bulanIndonesia($bulan) {
    $bulanArr = [
        '01' => 'Januari',
        '02' => 'Februari',
        '03' => 'Maret',
        '04' => 'April',
        '05' => 'Mei',
        '06' => 'Juni',
        '07' => 'Juli',
        '08' => 'Agustus',
        '09' => 'September',
        '10' => 'Oktober',
        '11' => 'November',
        '12' => 'Desember'
    ];
    return $bulanArr[$bulan] ?? $bulan;
}

// Fungsi untuk mengubah angka hari menjadi nama hari dalam bahasa Indonesia
function hariIndonesia($hari) {
    $hariArr = [
        'Sunday' => 'Minggu',
        'Monday' => 'Senin',
        'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu',
        'Thursday' => 'Kamis',
        'Friday' => 'Jumat',
        'Saturday' => 'Sabtu'
    ];
    return $hariArr[$hari] ?? $hari;
}

// Format tanggal hari ini
$hariIni = date('l');
$tanggalIni = date('d');
$bulanIni = date('m');
$tahunIni = date('Y');

$tanggalFormatted = hariIndonesia($hariIni) . ', tanggal ' . $tanggalIni . ' ' . bulanIndonesia($bulanIni) . ' ' . $tahunIni;
$tanggalsaja = $tanggalIni . ' ' . bulanIndonesia($bulanIni) . ' ' . $tahunIni;

// Buat nomor BA
$ba_number = 'BA/RET/' . date('Ymd') . '/' . $karyawan_id;

// QR Code content (data yang akan di encode ke QR Code)
$qr_content = "Berita Acara Pengembalian Asset\n" .
              "Nomor: " . $ba_number . "\n" .
              "Tanggal: " . $tanggalFormatted . "\n" .
              "Pengembali: " . ($user['karyawan_name'] ?? '-') . "\n" .
              "ID Karyawan: " . ($user['karyawan_no'] ?? '-') . "\n" .
              "Departemen: " . ($user['dep_name'] ?? '-') . "\n" .
              "Total Asset: " . $total_assets . " unit\n" .
              "Dicetak: " . date('d/m/Y H:i:s');

// Lokasi QR Code image (static file)
$qr_code_url = BASE_URL . "master/img/qr-code.png";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Berita Acara Pengembalian - <?= htmlspecialchars($user['karyawan_name'] ?? '-') ?></title>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= BASE_URL ?>master/img/assets/logo.png">
    <style>
        /* Style untuk print A4 */
        body {
            font-family: Arial, Helvetica, sans-serif;
            margin: 0;
            padding: 5px;
            background: #fff;
            color: #000;
            font-size: 11px;
            line-height: 1.3;
        }
        .page {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            background: white;
            padding: 15px;
            box-sizing: border-box;
            page-break-after: always;
        }
        .page:last-child {
            page-break-after: auto;
        }
        .header {
            text-align: center;
            margin-bottom: 15px;
        }
        .logo {
            max-width: 150px;
            max-height: 150px;
            margin-bottom: 10px;
        }
        h1 {
            font-size: 16px;
            margin: 5px 0;
            text-transform: uppercase;
            text-decoration: underline;
        }
        h2 {
            font-size: 14px;
            margin: 15px 0 10px 0;
            background-color: #f0f0f0;
            padding: 5px;
        }
        .content {
            margin: 15px 0;
        }
        p {
            margin: 5px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 10px;
        }
        th, td {
            border: 1px solid #000;
            padding: 6px;
            text-align: left;
            vertical-align: middle;
        }
        th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .signature {
            margin-top: 50px;
            width: 100%;
            display: flex;
            justify-content: space-between;
        }
        .signature-left, .signature-right {
            width: 45%;
            text-align: center;
        }
        .signature-left p, .signature-right p {
            margin: 5px 0;
        }
        .signature-line {
            margin-top: 40px;
            margin-bottom: 5px;
            border-bottom: 1px solid #000;
            width: 80%;
            margin-left: auto;
            margin-right: auto;
        }
        .info-table {
            width: 70%;
            border: none;
            margin: 5px 0;
        }
        .info-table td {
            border: none;
            padding: 3px;
        }
        .footer-note {
            margin-top: 30px;
            font-style: italic;
            text-align: center;
            font-size: 9px;
        }
        .checkbox-col {
            text-align: center;
        }
        .checkbox-col input[type="checkbox"] {
            width: 16px;
            height: 16px;
            cursor: default;
        }
        .qr-code {
            width: 70px;
            height: 70px;
            margin: 0 auto 10px auto;
        }
        .qr-code img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        .auto-generated-note {
            font-size: 8px;
            color: #666;
            text-align: center;
            margin-top: 5px;
        }
        @media print {
            body {
                padding: 0;
            }
            .page {
                padding: 15px;
                box-shadow: none;
            }
            .no-print {
                display: none;
            }
            .qr-code img {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>

<!-- HALAMAN 1: Berita Acara Pengembalian -->
<div class="page">
    <!-- Logo -->
    <div class="header">
        <img src="<?= BASE_URL ?>master/img/cosmar_logo.png" alt="Logo Perusahaan" class="logo">
        <h1>BERITA ACARA PENGEMBALIAN ASSET</h1>
        <p style="font-size: 10px;">Nomor: <?= $ba_number ?></p>
    </div>

    <!-- Kalimat Pembuka -->
    <div class="content">
        <p style="text-align: justify;">
            Pada hari <strong><?= $tanggalFormatted ?></strong>, saya yang bertanda tangan di bawah ini:
        </p>
        
        <!-- PERBAIKAN: Data Pengembali (Karyawan) -->
        <table class="info-table">
            <tr>
                <td style="width: 100px;">Nama</td>
                <td style="width: 10px;">:</td>
                <td><strong><?= htmlspecialchars($user['karyawan_name'] ?? '-') ?></strong></td>
            </tr>
            <tr>
                <td>ID Karyawan</td>
                <td>:</td>
                <td><strong><?= htmlspecialchars($user['karyawan_no'] ?? '-') ?></strong></td>
            </tr>
            <tr>
                <td>Departemen</td>
                <td>:</td>
                <td><strong><?= htmlspecialchars($user['dep_name'] ?? '-') ?> (<?= $user['dep_code'] ?? '-' ?>)</strong></td>
            </tr>
        </table>

        <p style="text-align: justify; margin-top: 10px;">
            <strong>Menyatakan bahwa</strong> telah mengembalikan asset-asset berikut kepada perusahaan:
        </p>
    </div>

    <!-- Daftar Asset -->
    <div class="content">
        <h2>DAFTAR ASSET YANG DIKEMBALIKAN</h2>
        
        <?php if ($total_assets > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th style="width: 30px;">No</th>
                        <th>Kode Asset</th>
                        <th>Nama Asset</th>
                        <th>Model</th>
                        <th>Kondisi</th>
                        <th>Estimasi Manfaat</th>
                        <th>Tanggal Pembelian</th>
                        <th style="width: 60px;">IT Cek</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    mysqli_data_seek($qAssets, 0);
                    while ($row = mysqli_fetch_assoc($qAssets)): 
                        
                        // Format estimasi manfaat
                        $estimasi = !empty($row['assets_life']) ? $row['assets_life'] . ' Tahun' : '-';
                        
                        // Format tanggal pembelian
                        $tanggal_beli = (!empty($row['assets_date']) && $row['assets_date'] != '0000-00-00') 
                            ? date('d/m/Y', strtotime($row['assets_date'])) 
                            : '-';
                        
                        // Nama asset dengan model
                        $asset_name = htmlspecialchars($row['assets_name']);
                        if (!empty($row['assets_model'])) {
                            $asset_name .= '<br><small class="text-muted">' . htmlspecialchars($row['assets_model']) . '</small>';
                        }
                    ?>
                    <tr>
                        <td class="text-center"><?= $no++ ?></td>
                        <td><strong><?= $row['assets_kode'] ?></strong></td>
                        <td><?= $asset_name ?></td>
                        <td><?= $row['assets_model'] ?? '-' ?></td>
                        <td><?= $row['kondisi_name'] ?? '-' ?></td>
                        <td class="text-center"><?= $estimasi ?></td>
                        <td class="text-center"><?= $tanggal_beli ?></td>
                        <td class="checkbox-col">
                            <input type="checkbox" disabled>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            
            <p style="margin-top: 10px;">
                <strong>Total Asset:</strong> <?= $total_assets ?> unit
            </p>
        <?php else: ?>
            <p class="text-center">Tidak ada asset yang dikembalikan.</p>
        <?php endif; ?>
    </div>

    <!-- Kalimat Penutup -->
    <div class="content">
        <p style="text-align: justify;">
            Demikian Berita Acara Pengembalian ini dibuat dengan sebenar-benarnya untuk dapat dipergunakan sebagaimana mestinya.
        </p>
    </div>

    <!-- PERBAIKAN: Tanda Tangan dengan QR Code -->
    <div class="signature">
        <div class="signature-left">
            <p><strong>Yang Menerima,</strong></p>
            <p>(Perusahaan)</p>
            <div class="signature-line"></div>
            <p><strong>( _____________________ )</strong></p>
            <p>HRGA / GA</p>
        </div>
        <div class="signature-right">
            <p><strong>Yang Mengembalikan,</strong></p>
            <p>(Pengembali)</p>
            <div class="qr-code">
                <img src="<?= $qr_code_url ?>" alt="QR Code">
            </div>
            <div class="signature-line"></div>
            <p><strong><?= htmlspecialchars($user['karyawan_name'] ?? '-') ?></strong></p>
            <p><?= htmlspecialchars($user['dep_name'] ?? 'Karyawan') ?></p>
            <div class="auto-generated-note">
                <em>This document is auto generated system</em>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer-note">
        <p><em>Dokumen ini sah tanpa tanda tangan basah karena dilindungi QR Code.</em></p>
        <p><em>Dicetak pada: <?= date('d/m/Y H:i:s') ?></em></p>
    </div>
</div>

<!-- HALAMAN 2: Lampiran Gambar (Jika Ada) -->
<?php 
// Reset pointer query untuk mengambil data gambar
mysqli_data_seek($qAssets, 0);
$has_images = false;
$images = [];
while ($row = mysqli_fetch_assoc($qAssets)) {
    if (!empty($row['primary_image'])) {
        $has_images = true;
        $images[] = $row;
    }
}

if ($has_images): 
?>
<div class="page" style="page-break-before: always;">
    <div class="header">
        <h1>LAMPIRAN GAMBAR</h1>
        <p style="font-size: 10px;">Berita Acara Pengembalian - <?= htmlspecialchars($user['karyawan_name'] ?? '-') ?></p>
    </div>

    <div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 10px; margin-top: 20px;">
        <?php 
        $no = 1;
        foreach ($images as $row): 
        ?>
        <div style="width: 45%; margin-bottom: 20px; text-align: center; border: 1px solid #ddd; padding: 10px; border-radius: 5px; page-break-inside: avoid;">
            <img src="<?= BASE_URL ?>master/img/assets/<?= $row['primary_image'] ?>" alt="Gambar Asset" style="max-width: 100%; max-height: 150px; object-fit: contain;" 
                 onerror="this.src='<?= BASE_URL ?>master/img/no-image.png'">
            <p style="margin: 5px 0; font-size: 10px;"><strong><?= $row['assets_kode'] ?></strong> - <?= htmlspecialchars($row['assets_name']) ?></p>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="footer-note">
        <p><em>Halaman ini merupakan lampiran dari Berita Acara Pengembalian.</em></p>
    </div>
</div>
<?php endif; ?>

<!-- Tombol Print (Hanya muncul di layar, tidak saat print) -->
<div class="no-print" style="position: fixed; bottom: 20px; right: 20px; z-index: 9999;">
    <button onclick="window.print()" style="padding: 8px 15px; background: #4e73df; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 12px; margin-right: 10px;">
        <i class="fas fa-print"></i> Cetak / Simpan PDF
    </button>
    <button onclick="window.close()" style="padding: 8px 15px; background: #858796; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 12px;">
        <i class="fas fa-times"></i> Tutup
    </button>
</div>

<!-- Font Awesome untuk icon (hanya untuk tampilan layar) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<script>
// Otomatis membuka dialog print saat halaman dimuat
window.onload = function() {
    // Uncomment baris di bawah jika ingin otomatis membuka dialog print
    // window.print();
};
</script>

</body>
</html>