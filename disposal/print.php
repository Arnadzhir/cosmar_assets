<?php
include '../auth/auth.php';
allowRole([1,2,3]);

include '../config/koneksi.php';
include '../config/base_url.php';

// Cek apakah ada parameter
if(!isset($_GET['id'])) {
    echo "Parameter tidak lengkap";
    exit();
}

$primary_id = (int)$_GET['id'];

// PERBAIKAN: Ambil data asset menggunakan tbl_karyawan
$query = "
    SELECT 
        p.primary_id,
        p.primary_qty,
        p.disposal_reason,
        p.disposal_date,
        p.primary_image,
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
        kat.kategori_name,
        kat.kategori_line,
        t.type_name,
        m.merk_name,
        kar.karyawan_name,
        kar.karyawan_no,
        d.dep_id,
        d.dep_name,
        d.dep_code,
        kond.kondisi_name,
        l.lokasi_name,
        l.lokasi_lantai
    FROM tbl_primary p
    INNER JOIN tbl_assets a ON p.assets_id = a.assets_id
    LEFT JOIN tbl_kategori kat ON a.kategori_id = kat.kategori_id
    LEFT JOIN tbl_type t ON a.type_id = t.type_id
    LEFT JOIN tbl_merk m ON a.merk_id = m.merk_id
    LEFT JOIN tbl_karyawan kar ON p.karyawan_id = kar.karyawan_id
    LEFT JOIN tbl_dep d ON kar.dep_id = d.dep_id
    LEFT JOIN tbl_kondisi kond ON p.kondisi_id = kond.kondisi_id
    LEFT JOIN tbl_lokasi l ON p.lokasi_id = l.lokasi_id
    WHERE p.primary_id = $primary_id AND p.disposal_status = 2
";

$result = mysqli_query($conn, $query);
$asset = mysqli_fetch_assoc($result);

if (!$asset) {
    echo "Data tidak ditemukan";
    exit;
}

// Ambil gambar untuk lampiran
$images = [];
if (!empty($asset['primary_image'])) {
    $images[] = $asset['primary_image'];
}

// Fungsi untuk mengubah angka bulan menjadi nama bulan dalam bahasa Indonesia
function bulanIndonesia($bulan) {
    $bulanArr = [
        '01' => 'Januari', '02' => 'Februari', '03' => 'Maret',
        '04' => 'April', '05' => 'Mei', '06' => 'Juni',
        '07' => 'Juli', '08' => 'Agustus', '09' => 'September',
        '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
    ];
    return $bulanArr[$bulan] ?? $bulan;
}

// Fungsi untuk mengubah angka hari menjadi nama hari dalam bahasa Indonesia
function hariIndonesia($hari) {
    $hariArr = [
        'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat',
        'Saturday' => 'Sabtu'
    ];
    return $hariArr[$hari] ?? $hari;
}

// Gunakan tanggal pengajuan disposal dari database
if (!empty($asset['disposal_date'])) {
    $disposal_timestamp = strtotime($asset['disposal_date']);
    $hariDisposal = date('l', $disposal_timestamp);
    $tanggalDisposal = date('d', $disposal_timestamp);
    $bulanDisposal = date('m', $disposal_timestamp);
    $tahunDisposal = date('Y', $disposal_timestamp);
    
    $tanggalFormatted = hariIndonesia($hariDisposal) . ', tanggal ' . $tanggalDisposal . ' ' . bulanIndonesia($bulanDisposal) . ' ' . $tahunDisposal;
    $tanggalsaja = $tanggalDisposal . ' ' . bulanIndonesia($bulanDisposal) . ' ' . $tahunDisposal;
} else {
    $hariIni = date('l');
    $tanggalIni = date('d');
    $bulanIni = date('m');
    $tahunIni = date('Y');
    
    $tanggalFormatted = hariIndonesia($hariIni) . ', tanggal ' . $tanggalIni . ' ' . bulanIndonesia($bulanIni) . ' ' . $tahunIni;
    $tanggalsaja = $tanggalIni . ' ' . bulanIndonesia($bulanIni) . ' ' . $tahunIni;
}

// Buat nomor BA
$ba_number = 'DISPOSAL/' . date('Ymd') . '/' . str_pad($primary_id, 4, '0', STR_PAD_LEFT);

// Lokasi QR Code image (static file)
$qr_code_url = BASE_URL . "master/img/qr-code.png";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Berita Acara Disposal Asset - <?= htmlspecialchars($asset['assets_kode']) ?></title>
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
            text-align: justify;
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
            vertical-align: top;
        }
        th {
            background-color: #f0f0f0;
            font-weight: bold;
            width: 140px;
            text-align: left;
        }
        td {
            text-align: left;
        }
        .text-center {
            text-align: center;
        }
        .signature {
            margin-top: 30px;
            width: 100%;
        }
        .signature-left {
            float: left;
            width: 250px;
            text-align: center;
        }
        .signature-right {
            float: right;
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
        .footer-note {
            margin-top: 15px;
            font-style: italic;
            text-align: center;
            font-size: 9px;
        }
        .gallery {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 15px;
            margin-top: 15px;
        }
        .gallery-item {
            width: 45%;
            text-align: center;
            border: 1px solid #ddd;
            padding: 10px;
            border-radius: 5px;
            page-break-inside: avoid;
        }
        .gallery-item img {
            max-width: 100%;
            max-height: 200px;
            object-fit: contain;
        }
        .gallery-item p {
            margin: 5px 0 0 0;
            font-size: 10px;
        }
        .page-break {
            page-break-before: always;
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

<!-- HALAMAN 1: Berita Acara Disposal -->
<div class="page">
    <!-- Header -->
    <div class="header">
        <img src="<?= BASE_URL ?>master/img/cosmar_logo.png" alt="Logo Perusahaan" class="logo">
        <h1>BERITA ACARA DISPOSAL ASSET</h1>
        <p style="font-size: 10px; text-align: center;">Nomor: <?= $ba_number ?></p>
    </div>

    <!-- Kalimat Pembuka -->
    <div class="content">
        <p>Pada hari <strong><?= $tanggalFormatted ?></strong>, telah dilakukan proses Disposal (Penghapusan) terhadap asset dengan rincian sebagai berikut:</p>
    </div>

    <!-- Data Asset -->
    <div class="content">
        <h2>1. DATA ASSET</h2>
        <table>
            <tr><th>Kode Asset</th><td><strong><?= htmlspecialchars($asset['assets_kode']) ?></strong></div></tr>
            <tr><th>Nama Asset</th><td><?= htmlspecialchars($asset['assets_name']) ?></div></tr>
            <?php if (!empty($asset['assets_model'])): ?>
            <tr><th>Model</th><td><?= htmlspecialchars($asset['assets_model']) ?></div></tr>
            <?php endif; ?>
            <tr><th>Kategori</th><td><?= !empty($asset['kategori_name']) ? htmlspecialchars($asset['kategori_name'] . ' - ' . $asset['kategori_line']) : '-' ?></div></tr>
            <tr><th>Type</th><td><?= htmlspecialchars($asset['type_name'] ?? '-') ?></div></tr>
            <tr><th>Merk</th><td><?= htmlspecialchars($asset['merk_name'] ?? '-') ?></div></tr>
            <?php if (!empty($asset['assets_spec'])): ?>
            <tr><th>Spesifikasi</th><td><?= nl2br(htmlspecialchars($asset['assets_spec'])) ?></div></tr>
            <?php endif; ?>
            <?php if (!empty($asset['assets_target'])): ?>
            <tr><th>Peruntukan</th><td><?= htmlspecialchars($asset['assets_target']) ?></div></tr>
            <?php endif; ?>
            <?php if (!empty($asset['assets_cap'])): ?>
            <tr><th>Kapasitas</th><td><?= htmlspecialchars($asset['assets_cap']) . ' ' . htmlspecialchars($asset['assets_uom']) ?></div></tr>
            <?php endif; ?>
            <tr><th>Masa Manfaat</th><td><?= $asset['assets_life'] ?> Tahun</div></tr>
            <tr><th>Tanggal Perolehan</th><td><?= !empty($asset['assets_date']) && $asset['assets_date'] != '0000-00-00' ? date('d/m/Y', strtotime($asset['assets_date'])) : '-' ?></div></tr>
            <tr><th>Harga Perolehan</th><td>Rp <?= number_format($asset['assets_price'], 0, ',', '.') ?></div></tr>
        </table>
    </div>

    <!-- Detail Unit yang Dihapus -->
    <div class="content">
        <h2>2. DETAIL UNIT YANG DIHAPUS</h2>
        <table>
            <thead>
                <tr><th class="text-center">No</th><th class="text-center">Kondisi</th><th class="text-center">Lokasi</th><th class="text-center">Qty</th></tr>
            </thead>
            <tbody>
                <tr><td class="text-center">1</div><td class="text-center"><?= htmlspecialchars($asset['kondisi_name'] ?? '-') ?></div><td class="text-center"><?= htmlspecialchars($asset['lokasi_name'] ?? '-') ?><?= !empty($asset['lokasi_lantai']) ? ' (Lt.' . $asset['lokasi_lantai'] . ')' : '' ?></div><td class="text-center"><?= $asset['primary_qty'] ?> unit</div></tr>
            </tbody>
        </table>
    </div>

    <!-- Alasan Disposal -->
    <div class="content">
        <h2>3. ALASAN DISPOSAL</h2>
        <table>
            <tr><th>Alasan Penghapusan</th><td><?= nl2br(htmlspecialchars($asset['disposal_reason'])) ?></div></tr>
            <tr><th>Tanggal Pengajuan</th><td><?= !empty($asset['disposal_date']) ? date('d/m/Y H:i', strtotime($asset['disposal_date'])) : '-' ?></div></tr>
        </table>
    </div>

    <!-- PERBAIKAN: Pihak Terkait (menggunakan tbl_karyawan) -->
    <div class="content">
        <h2>4. PIHAK TERKAIT</h2>
        <table>
            <tr><th>Penanggung Jawab / User</th><td><strong><?= htmlspecialchars($asset['karyawan_name'] ?? '-') ?></strong></div></tr>
            <tr><th>ID Karyawan</th><td><?= htmlspecialchars($asset['karyawan_no'] ?? '-') ?></div></tr>
            <tr><th>Departemen</th><td><strong><?= htmlspecialchars($asset['dep_name'] ?? '-') ?> (<?= htmlspecialchars($asset['dep_code'] ?? '-') ?>)</strong></div></tr>
        </table>
    </div>

    <!-- Penutup -->
    <div class="content">
        <p>Demikian Berita Acara Disposal ini dibuat agar dapat dipergunakan sebagaimana mestinya.</p>
    </div>

    <!-- Tanda Tangan dengan QR Code -->
    <div class="signature">
        <!-- Bagian Penerima (Perusahaan) - Kiri -->
        <div class="signature-left">
            <p>Mengetahui,</p>
            <p>Penerima</p>
            <div class="signature-line"></div>
            <p><strong>(.....................................)</strong></p>
        </div>

        <!-- Bagian Pengaju - Kanan -->
        <div class="signature-right">
            <p>Tangerang Selatan, <?= $tanggalsaja ?></p>
            <div class="qr-code">
                <img src="<?= $qr_code_url ?>" alt="QR Code">
            </div>
            <div class="signature-line"></div>
            <p><center><strong><?= htmlspecialchars($asset['karyawan_name'] ?? '-') ?></strong></center></p>
            <p><center><?= htmlspecialchars($asset['dep_name'] ?? '-') ?></center></p>
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
<?php if (!empty($images)): ?>
<div class="page page-break">
    <div class="header">
        <h1>LAMPIRAN GAMBAR</h1>
        <p style="font-size: 10px;">Berita Acara Disposal Asset - <?= htmlspecialchars($asset['assets_kode']) ?></p>
    </div>

    <div class="gallery">
        <?php 
        $no = 1;
        foreach ($images as $img): 
        ?>
        <div class="gallery-item">
            <img src="<?= BASE_URL ?>master/img/assets/<?= $img ?>" alt="Gambar Unit <?= $no ?>" 
                 onerror="this.src='<?= BASE_URL ?>master/img/no-image.png'">
            <p><strong>Gambar <?= $no++ ?></strong></p>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="footer-note">
        <p><em>Halaman ini merupakan lampiran dari Berita Acara Disposal Asset.</em></p>
    </div>
</div>
<?php endif; ?>

<!-- Tombol Print -->
<div class="no-print" style="position: fixed; bottom: 20px; right: 20px; z-index: 9999;">
    <button onclick="window.print()" style="padding: 8px 15px; background: #4e73df; color: white; border: none; border-radius: 5px; cursor: pointer; margin-right: 10px;">
        <i class="fas fa-print"></i> Cetak / Simpan PDF
    </button>
    <button onclick="window.close()" style="padding: 8px 15px; background: #858796; color: white; border: none; border-radius: 5px; cursor: pointer;">
        <i class="fas fa-times"></i> Tutup
    </button>
</div>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

</body>
</html>