<?php
include '../auth/auth.php'; 
allowRole([1,2,3]);

include '../config/koneksi.php';
include '../config/base_url.php';

// Ambil user yang login
$user_login_id = $_SESSION['user_id'] ?? 0;
$user_login_level = $_SESSION['user_level'] ?? 0;
$user_login_dep_id = $_SESSION['dep_id'] ?? 0;

// Cek apakah ada parameter
if(!isset($_GET['id']) || !isset($_GET['karyawan_id'])) {
    echo "Parameter tidak lengkap";
    exit();
}

$assets_id = mysqli_real_escape_string($conn, $_GET['id']);
$karyawan_id = mysqli_real_escape_string($conn, $_GET['karyawan_id']);

// CEK APAKAH ASSET INI MILIK DEPARTEMEN YANG SAMA DENGAN USER LOGIN
// Admin dan Operator bisa melihat semua asset
if (in_array($user_login_level, [1, 2])) {
    // Admin/Operator: bisa melihat semua asset
    $user_filter = "";
} else {
    // User biasa: hanya bisa melihat asset dari departemennya sendiri
    $user_filter = " AND d.dep_id = '$user_login_dep_id'";
}

// Ambil data asset dan user (menggunakan tbl_karyawan)
$query = "
    SELECT 
        a.assets_id,
        a.assets_kode,
        a.assets_name,
        a.assets_model,
        a.assets_spec,
        a.assets_target,
        a.assets_cap,
        a.assets_uom,
        a.assets_life,
        a.assets_price,
        a.assets_date,
        a.assets_note,
        a.timestamp,
        
        kat.kategori_id,
        kat.kategori_name,
        kat.kategori_line,
        
        t.type_id,
        t.type_name,
        
        s.supplier_id,
        s.supplier_name,
        
        pr.produsen_id,
        pr.produsen_region,
        pr.produsen_code,
        
        m.merk_id,
        m.merk_name,
        
        kar.karyawan_id,
        kar.karyawan_name,
        kar.karyawan_no,
        
        d.dep_id,
        d.dep_name,
        d.dep_code,

        p.approve_date,
        
        SUM(p.primary_qty) as total_qty
        
    FROM tbl_primary p
    INNER JOIN tbl_assets a ON p.assets_id = a.assets_id
    LEFT JOIN tbl_kategori kat ON a.kategori_id = kat.kategori_id
    LEFT JOIN tbl_type t ON a.type_id = t.type_id
    LEFT JOIN tbl_supplier s ON a.supplier_id = s.supplier_id
    LEFT JOIN tbl_produsen pr ON a.produsen_id = pr.produsen_id
    LEFT JOIN tbl_merk m ON a.merk_id = m.merk_id
    LEFT JOIN tbl_karyawan kar ON p.karyawan_id = kar.karyawan_id
    LEFT JOIN tbl_dep d ON kar.dep_id = d.dep_id
    
    WHERE a.assets_id = '$assets_id'
    AND p.karyawan_id = '$karyawan_id'
    $user_filter
    GROUP BY a.assets_id, p.karyawan_id
";

$result = mysqli_query($conn, $query);
$asset = mysqli_fetch_assoc($result);

if (!$asset) {
    echo "Data tidak ditemukan";
    exit;
}

// Ambil detail unit (primary) untuk asset ini
$qUnits = mysqli_query($conn, "
    SELECT 
        p.primary_id,
        p.primary_qty,
        p.primary_image,
        kond.kondisi_name,
        l.lokasi_name,
        l.lokasi_lantai
    FROM tbl_primary p
    LEFT JOIN tbl_kondisi kond ON p.kondisi_id = kond.kondisi_id
    LEFT JOIN tbl_lokasi l ON p.lokasi_id = l.lokasi_id
    WHERE p.assets_id = '$assets_id'
    AND p.karyawan_id = '$karyawan_id'
    $user_filter
    ORDER BY p.primary_id ASC
");

// Hitung total unit
$total_unit = mysqli_num_rows($qUnits);

// Ambil semua gambar untuk lampiran
$qImages = mysqli_query($conn, "
    SELECT primary_image 
    FROM tbl_primary 
    WHERE assets_id = '$assets_id' 
    AND karyawan_id = '$karyawan_id'
    AND primary_image IS NOT NULL 
    AND primary_image != ''
    ORDER BY primary_id ASC
");

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

// Approve_date
if (!empty($asset['approve_date'])) {
    $approve_timestamp = strtotime($asset['approve_date']);
    $hariApprove = date('l', $approve_timestamp);
    $tanggalApprove = date('d', $approve_timestamp);
    $bulanApprove = date('m', $approve_timestamp);
    $tahunApprove = date('Y', $approve_timestamp);
    
    $tanggalFormatted = hariIndonesia($hariApprove) . ', tanggal ' . $tanggalApprove . ' ' . bulanIndonesia($bulanApprove) . ' ' . $tahunApprove;
    $tanggalsaja = $tanggalApprove . ' ' . bulanIndonesia($bulanApprove) . ' ' . $tahunApprove;
} else {
    // Fallback jika belum approve (seharusnya tidak terjadi karena hanya asset approved yang bisa print)
    $hariIni = date('l');
    $tanggalIni = date('d');
    $bulanIni = date('m');
    $tahunIni = date('Y');
    
    $tanggalFormatted = hariIndonesia($hariIni) . ', tanggal ' . $tanggalIni . ' ' . bulanIndonesia($bulanIni) . ' ' . $tahunIni;
    $tanggalsaja = $tanggalIni . ' ' . bulanIndonesia($bulanIni) . ' ' . $tahunIni;
}

// Buat nomor BA
$ba_number = 'BA/' . date('Ymd') . '/' . $assets_id . '/' . $karyawan_id;

// Buat QR Code content
$qr_content = "Berita Acara Serah Terima Asset\n" .
              "Nomor: " . $ba_number . "\n" .
              "Tanggal: " . $tanggalFormatted . "\n" .
              "Asset: " . $asset['assets_kode'] . " - " . $asset['assets_name'] . "\n" .
              "Penerima: " . ($asset['karyawan_name'] ?? '-') . "\n" .
              "Departemen: " . ($asset['dep_name'] ?? '-') . "\n" .
              "Total Unit: " . $asset['total_qty'] . " unit\n" .
              "Dicetak: " . date('d/m/Y H:i:s');

// Lokasi QR Code image (static file)
$qr_code_url = BASE_URL . "master/img/qr-code.png";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Berita Acara Serah Terima - <?= $asset['assets_kode'] ?></title>
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
            margin: 10px 0 5px 0;
            background-color: #f0f0f0;
            padding: 5px;
        }
        .content {
            margin: 10px 0;
        }
        p {
            margin: 5px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 8px 0;
            font-size: 10px;
        }
        th, td {
            border: 1px solid #000;
            padding: 5px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background-color: #f0f0f0;
            font-weight: bold;
            width: 150px;
        }
        .text-center {
            text-align: center;
        }
        .signature {
            margin-top: 30px;
            width: 100%;
        }
        .signature-right {
            float: right;
            width: 280px;
            text-align: center;
        }
        .signature-left {
            float: left;
            width: 280px;
            text-align: center;
        }
        .signature-line {
            margin-top: 40px;
            margin-bottom: 5px;
            border-bottom: 1px solid #000;
            width: 200px;
            margin-left: auto;
            margin-right: auto;
        }
        .clearfix {
            clear: both;
        }
        .page-break {
            page-break-before: always;
        }
        .gallery {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 8px;
            margin-top: 15px;
        }
        .gallery-item {
            width: 45%;
            margin-bottom: 15px;
            text-align: center;
            border: 1px solid #ddd;
            padding: 8px;
            border-radius: 5px;
            page-break-inside: avoid;
        }
        .gallery-item img {
            max-width: 100%;
            max-height: 150px;
            object-fit: contain;
        }
        .gallery-item p {
            margin: 3px 0;
            font-size: 10px;
        }
        .footer-note {
            margin-top: 15px;
            font-style: italic;
            text-align: center;
            font-size: 9px;
        }
        .info-table {
            width: 60%;
            border: none;
            margin: 5px 0;
        }
        .info-table td {
            border: none;
            padding: 3px;
        }
        .qr-code {
            width: 80px;
            height: 80px;
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
        .signature-container {
            margin-top: 20px;
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

<!-- HALAMAN 1: Berita Acara -->
<div class="page">
    <!-- Logo -->
    <div class="header">
        <img src="<?= BASE_URL ?>master/img/cosmar_logo.png" alt="Logo Perusahaan" class="logo">
        <h1>BERITA ACARA SERAH TERIMA ASSET</h1>
        <p style="font-size: 10px;">Nomor: <?= $ba_number ?></p>
    </div>

    <!-- Kalimat Pembuka -->
    <div class="content">
        <p style="text-align: justify;">
            Pada hari <strong><?= $tanggalFormatted ?></strong>, saya yang bertanda tangan di bawah ini:
        </p>
        
        <!-- Data Penandatangan -->
        <table class="info-table">
            <tr>
                <td style="width: 80px;">Nama</div>
                <td style="width: 10px;">:</div>
                <td><strong><?= htmlspecialchars($asset['karyawan_name'] ?? '-') ?></strong></div>
            </tr>
            <tr>
                <td>ID Karyawan</div>
                <td>:</div>
                <td><strong><?= htmlspecialchars($asset['karyawan_no'] ?? '-') ?></strong></div>
            </tr>
            <tr>
                <td>Departemen</div>
                <td>:</div>
                <td><strong><?= htmlspecialchars($asset['dep_name'] ?? '-') ?> (<?= $asset['dep_code'] ?? '-' ?>)</strong></div>
            </tr>
        </table>

        <p style="text-align: justify; margin-top: 8px;">
            <strong>Menyatakan bahwa</strong> barang yang tertera di bawah ini diterima dalam keadaan sudah terpasang / terinstall sehingga bisa berfungsi dengan baik.
        </p>
    </div>

    <!-- Data Asset -->
    <div class="content">
        <h2>1. DATA ASSET</h2>
        <table>
            <tr>
                <th>Kode Asset</th>
                <td><strong><?= $asset['assets_kode'] ?></strong></div>
            </tr>
            <tr>
                <th>Nama Asset</th>
                <td><?= htmlspecialchars($asset['assets_name']) ?></div>
            </tr>
            <?php if (!empty($asset['assets_model'])): ?>
            <tr>
                <th>Model</th>
                <td><?= htmlspecialchars($asset['assets_model']) ?></div>
            </tr>
            <?php endif; ?>
            <tr>
                <th>Kategori</th>
                <td><?= !empty($asset['kategori_name']) ? $asset['kategori_name'] . ' - ' . $asset['kategori_line'] : '-' ?></div>
            </tr>
            <tr>
                <th>Type</th>
                <td><?= $asset['type_name'] ?? '-' ?></div>
            </tr>
            <tr>
                <th>Merk</th>
                <td><?= $asset['merk_name'] ?? '-' ?></div>
            </tr>
            <tr>
                <th>Spesifikasi</th>
                <td><?= !empty($asset['assets_spec']) ? nl2br(htmlspecialchars($asset['assets_spec'])) : '-' ?></div>
            </tr>
            <tr>
                <th>Peruntukan</th>
                <td><?= !empty($asset['assets_target']) ? htmlspecialchars($asset['assets_target']) : '-' ?></div>
            <tr>
            <tr>
                <th>Kapasitas</th>
                <td><?= !empty($asset['assets_cap']) ? $asset['assets_cap'] . ' ' . ($asset['assets_uom'] ?? '') : '-' ?></div>
            </tr>
            <tr>
                <th>Masa Manfaat</th>
                <td><?= !empty($asset['assets_life']) ? $asset['assets_life'] . ' Tahun' : '-' ?></div>
            </tr>
            <tr>
                <th>Supplier</th>
                <td><?= $asset['supplier_name'] ?? '-' ?></div>
            </tr>
            <tr>
                <th>Produsen</th>
                <td><?= !empty($asset['produsen_region']) ? $asset['produsen_region'] . ' (' . $asset['produsen_code'] . ')' : '-' ?></div>
            </tr>
            <tr>
                <th>Harga</th>
                <td><?= !empty($asset['assets_price']) ? 'Rp ' . number_format($asset['assets_price'], 0, ',', '.') : '-' ?></div>
            </tr>
            <tr>
                <th>Tanggal Beli</th>
                <td><?= !empty($asset['assets_date']) ? date('d/m/Y', strtotime($asset['assets_date'])) : '-' ?></div>
            </tr>
            <tr>
                <th>Total Unit</th>
                <td><strong><?= $asset['total_qty'] ?> unit</strong></div>
            </tr>
        </table>
    </div>

    <!-- Detail Unit -->
    <div class="content">
        <h2>2. DETAIL UNIT</h2>
        <table>
            <thead>
                <tr>
                    <th class="text-center" style="width: 20px;">No</th>
                    <th class="text-center">Kondisi</th>
                    <th class="text-center">Lokasi</th>
                    <th class="text-center" style="width: 20px;">Qty</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                mysqli_data_seek($qUnits, 0);
                while ($unit = mysqli_fetch_assoc($qUnits)): 
                ?>
                <tr>
                    <td class="text-center"><?= $no++ ?></div>
                    <td><?= $unit['kondisi_name'] ?? '-' ?></div>
                    <td><?= $unit['lokasi_name'] ?? '-' ?> <?= !empty($unit['lokasi_lantai']) ? '(' . $unit['lokasi_lantai'] . ')' : '' ?></div>
                    <td class="text-center"><?= $unit['primary_qty'] ?? '-' ?></div>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Kalimat Penutup -->
    <div class="content">
        <p style="text-align: justify;">
            Demikian Berita Acara ini dibuat agar dapat dipergunakan sebagaimana mestinya.
        </p>
    </div>

    <!-- Tanda Tangan dengan QR Code -->
    <div class="signature-container">

        <div class="signature-right">
            <p>Tangerang Selatan, <?= $tanggalsaja ?></p>
            <div class="qr-code">
                <img src="<?= $qr_code_url ?>" alt="QR Code">
            </div>
            <div class="signature-line"></div>
            <p><strong><?= htmlspecialchars($asset['karyawan_name'] ?? '-') ?></strong></p>
            <p><?= htmlspecialchars($asset['dep_name'] ?? '-') ?></p>
            <div class="auto-generated-note">
                <em>This document is auto generated system</em>
            </div>
        </div>
        <div class="clearfix"></div>
    </div>

    <!-- Footer -->
    <div class="footer-note">
        <p><em>Dokumen ini sah tanpa tanda tangan basah karena dilindungi QR Code.</em></p>
        <p><em>Dicetak pada: <?= date('d/m/Y H:i:s') ?></em></p>
    </div>
</div>

<!-- HALAMAN 2: Lampiran Gambar (Jika Ada) -->
<?php if (mysqli_num_rows($qImages) > 0): ?>
<div class="page page-break">
    <div class="header">
        <h1>LAMPIRAN GAMBAR</h1>
        <p style="font-size: 10px;">Berita Acara Serah Terima - <?= $asset['assets_kode'] ?> - <?= htmlspecialchars($asset['karyawan_name'] ?? '-') ?></p>
    </div>

    <div class="gallery">
        <?php 
        $no = 1;
        while ($img = mysqli_fetch_assoc($qImages)): 
        ?>
        <div class="gallery-item">
            <img src="<?= BASE_URL ?>master/img/assets/<?= $img['primary_image'] ?>" alt="Gambar Unit <?= $no ?>">
            <p><strong>Gambar <?= $no++ ?></strong></p>
        </div>
        <?php endwhile; ?>
    </div>

    <div class="footer-note">
        <p><em>Halaman ini merupakan lampiran dari Berita Acara Serah Terima.</em></p>
    </div>
</div>
<?php endif; ?>

<!-- Tombol Print (Hanya muncul di layar) -->
<div class="no-print" style="position: fixed; bottom: 20px; right: 20px; z-index: 9999;">
    <button onclick="window.print()" style="padding: 8px 15px; background: #4e73df; color: white; border: none; border-radius: 5px; cursor: pointer; margin-right: 10px;">
        <i class="fas fa-print"></i> Cetak / Simpan PDF
    </button>
    <button onclick="window.close()" style="padding: 8px 15px; background: #858796; color: white; border: none; border-radius: 5px; cursor: pointer;">
        <i class="fas fa-times"></i> Tutup
    </button>
</div>

<!-- Font Awesome untuk icon -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<script>
// Otomatis membuka dialog print saat halaman dimuat (opsional, uncomment jika ingin)
// window.onload = function() {
//     window.print();
// };
</script>

</body>
</html>