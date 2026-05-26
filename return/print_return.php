<?php
session_start();
if (!isset($_SESSION['login'])) {
    header("Location: ../auth/login.php");
    exit;
}

include '../config/koneksi.php';
include '../config/base_url.php';

// Cek apakah ada data POST dari form
if (!isset($_POST['primary_ids']) || empty($_POST['primary_ids'])) {
    echo "<script>
        alert('Tidak ada data untuk dicetak');
        window.location.href = 'index3.php';
    </script>";
    exit;
}

// ======================
// CETAK SATUAN - AMBIL DATA PERTAMA SAJA
// ======================
$primary_ids = $_POST['primary_ids'];
$primary_id_single = intval($primary_ids[0]); // Ambil data pertama saja

// ======================
// AMBIL DATA ASSET
// ======================
$qAsset = mysqli_query($conn, "
    SELECT 
        a.*,
        kat.kategori_name,
        kat.kategori_line,
        t.type_name,
        s.supplier_name,
        pr.produsen_region,
        pr.produsen_code,
        m.merk_name
    FROM tbl_primary p
    INNER JOIN tbl_assets a ON p.assets_id = a.assets_id
    LEFT JOIN tbl_kategori kat ON a.kategori_id = kat.kategori_id
    LEFT JOIN tbl_type t ON a.type_id = t.type_id
    LEFT JOIN tbl_supplier s ON a.supplier_id = s.supplier_id
    LEFT JOIN tbl_produsen pr ON a.produsen_id = pr.produsen_id
    LEFT JOIN tbl_merk m ON a.merk_id = m.merk_id
    WHERE p.primary_id = $primary_id_single
    AND p.return_status = 2
");

if (mysqli_num_rows($qAsset) == 0) {
    echo "<script>
        alert('Data asset tidak ditemukan');
        window.location.href = 'index3.php';
    </script>";
    exit;
}

$asset = mysqli_fetch_assoc($qAsset);

// ======================
// AMBIL DATA KARYAWAN
// ======================
$qUser = mysqli_query($conn, "
    SELECT 
        kar.karyawan_name,
        kar.karyawan_no,
        d.dep_name,
        d.dep_code
    FROM tbl_primary p
    LEFT JOIN tbl_karyawan kar ON p.karyawan_id = kar.karyawan_id
    LEFT JOIN tbl_dep d ON kar.dep_id = d.dep_id
    WHERE p.primary_id = $primary_id_single
");

$userData = mysqli_fetch_assoc($qUser);

// ======================
// AMBIL DATA UNIT (PRIMARY) UNTUK ASSET INI
// ======================
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
    WHERE p.primary_id = $primary_id_single
    ORDER BY p.primary_id ASC
");

// Hitung total unit
$total_unit = mysqli_num_rows($qUnits);

// ======================
// AMBIL GAMBAR UNTUK LAMPIRAN (JIKA ADA)
// ======================
$qImages = mysqli_query($conn, "
    SELECT primary_image 
    FROM tbl_primary 
    WHERE primary_id = $primary_id_single 
    AND primary_image IS NOT NULL 
    AND primary_image != ''
");

// ======================
// FUNGSI FORMAT TANGGAL
// ======================
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
$ba_number = 'BA/RET/' . date('Ymd') . '/' . $primary_id_single;

// Lokasi QR Code image (static file)
$qr_code_url = BASE_URL . "master/img/qr-code.png";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Berita Acara Pengembalian Asset - <?= $asset['assets_kode'] ?></title>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= BASE_URL ?>master/img/assets/logo.png">
    <style>
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
        }
    </style>
</head>
<body>

<!-- HALAMAN 1: Berita Acara Pengembalian -->
<div class="page">
    <div class="header">
        <img src="<?= BASE_URL ?>master/img/cosmar_logo.png" alt="Logo Perusahaan" class="logo">
        <h1>BERITA ACARA PENGEMBALIAN ASSET</h1>
        <p style="font-size: 10px;">Nomor: <?= $ba_number ?></p>
    </div>

    <div class="content">
        <p style="text-align: justify;">
            Pada hari <strong><?= $tanggalFormatted ?></strong>, saya yang bertanda tangan di bawah ini:
        </p>
        
        <table class="info-table">
            <tr>
                <td style="width: 80px;">Nama</td>
                <td style="width: 10px;">:</td>
                <td><strong><?= htmlspecialchars($userData['karyawan_name'] ?? '-') ?></strong></td>
            </tr>
             <tr>
                <td>ID Karyawan</td>
                <td>:</td>
                <td><strong><?= htmlspecialchars($userData['karyawan_no'] ?? '-') ?></strong></td>
             </tr>
             <tr>
                <td>Departemen</td>
                <td>:</td>
                <td><strong><?= htmlspecialchars($userData['dep_name'] ?? '-') ?> (<?= $userData['dep_code'] ?? '-' ?>)</strong></td>
             </tr>
          </table>

        <p style="text-align: justify; margin-top: 8px;">
            <strong>Menyatakan bahwa</strong> telah mengembalikan asset berikut kepada perusahaan:
        </p>
    </div>

    <!-- Data Asset -->
    <div class="content">
        <h2>DATA ASSET</h2>
        <table>
            <tr>
                <th>Kode Asset</th>
                <td><strong><?= $asset['assets_kode'] ?></strong></td>
            </tr>
            <tr>
                <th>Nama Asset</th>
                <td><?= htmlspecialchars($asset['assets_name']) ?></td>
            </tr>
            <?php if (!empty($asset['assets_model'])): ?>
            <tr>
                <th>Model</th>
                <td><?= htmlspecialchars($asset['assets_model']) ?></td>
            </tr>
            <?php endif; ?>
            <tr>
                <th>Kategori</th>
                <td><?= !empty($asset['kategori_name']) ? $asset['kategori_name'] . ' - ' . $asset['kategori_line'] : '-' ?></td>
            </tr>
            <tr>
                <th>Type</th>
                <td><?= $asset['type_name'] ?? '-' ?></td>
            </tr>
            <tr>
                <th>Merk</th>
                <td><?= $asset['merk_name'] ?? '-' ?></td>
            </tr>
            <tr>
                <th>Spesifikasi</th>
                <td><?= !empty($asset['assets_spec']) ? nl2br(htmlspecialchars($asset['assets_spec'])) : '-' ?></td>
            </tr>
            <tr>
                <th>Kapasitas</th>
                <td><?= !empty($asset['assets_cap']) ? $asset['assets_cap'] . ' ' . ($asset['assets_uom'] ?? '') : '-' ?></td>
            </tr>
        </table>
    </div>

    <!-- Detail Unit -->
    <div class="content">
        <h2>DETAIL UNIT</h2>
        <table>
            <thead>
                <tr>
                    <th style="width: 40px;">No</th>
                    <th>Lokasi</th>
                    <th>Kondisi</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                mysqli_data_seek($qUnits, 0);
                while ($unit = mysqli_fetch_assoc($qUnits)): 
                    $lokasi = $unit['lokasi_name'] ?? '-';
                    if (!empty($unit['lokasi_lantai'])) {
                        $lokasi .= ' (Lt.' . $unit['lokasi_lantai'] . ')';
                    }
                ?>
                <tr>
                    <td class="text-center"><?= $no++ ?></td>
                    <td><?= $lokasi ?></td>
                    <td><?= $unit['kondisi_name'] ?? '-' ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="2" class="text-right">Total Unit:</th>
                    <th class="text-center"><?= $total_unit ?> unit</th>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="content">
        <p style="text-align: justify;">
            Demikian Berita Acara Pengembalian ini dibuat agar dapat dipergunakan sebagaimana mestinya.
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
            <p><strong><?= htmlspecialchars($userData['karyawan_name'] ?? '-') ?></strong></p>
            <p><?= htmlspecialchars($userData['dep_name'] ?? '-') ?></p>
            <div class="auto-generated-note">
                <em>This document is auto generated system</em>
            </div>
        </div>
        <div class="clearfix"></div>
    </div>

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
        <p style="font-size: 10px;">Berita Acara Pengembalian - <?= $asset['assets_kode'] ?></p>
    </div>

    <div class="gallery">
        <?php 
        $no = 1;
        while ($img = mysqli_fetch_assoc($qImages)): 
        ?>
        <div class="gallery-item">
            <img src="<?= BASE_URL ?>master/img/assets/<?= $img['primary_image'] ?>" alt="Gambar Unit <?= $no ?>" 
                 onerror="this.src='<?= BASE_URL ?>master/img/no-image.png'">
            <p><strong>Gambar <?= $no++ ?></strong></p>
        </div>
        <?php endwhile; ?>
    </div>

    <div class="footer-note">
        <p><em>Halaman ini merupakan lampiran dari Berita Acara Pengembalian.</em></p>
    </div>
</div>
<?php endif; ?>

<!-- Tombol Print -->
<div class="no-print" style="position: fixed; bottom: 20px; right: 20px; z-index: 9999;">
    <button onclick="window.print()" style="padding: 8px 15px; background: #4e73df; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 12px;">
        <i class="fas fa-print"></i> Cetak / Simpan PDF
    </button>
    <button onclick="window.close()" style="padding: 8px 15px; background: #858796; color: white; border: none; border-radius: 5px; cursor: pointer; margin-left: 10px; font-size: 12px;">
        <i class="fas fa-times"></i> Tutup
    </button>
</div>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<script>
window.onload = function() {
    // window.print();
};
</script>

</body>
</html>