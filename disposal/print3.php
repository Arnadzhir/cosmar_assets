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

$tools_id = (int)$_GET['id'];

// PERBAIKAN: Ambil data tools
$query = "
    SELECT 
        t.tools_id,
        t.tools_qty,
        t.disposal_reason,
        t.disposal_date,
        t.tools_image,
        t.tools_name,
        t.tools_merk,
        t.tools_price,
        t.tools_spec,
        t.tools_date as tanggal_beli,
        kar.karyawan_name,
        kar.karyawan_no,
        d.dep_id,
        d.dep_name,
        d.dep_code,
        kond.kondisi_name
    FROM tbl_tools t
    LEFT JOIN tbl_karyawan kar ON t.user_id = kar.karyawan_id
    LEFT JOIN tbl_dep d ON kar.dep_id = d.dep_id
    LEFT JOIN tbl_kondisi kond ON t.kondisi_id = kond.kondisi_id
    WHERE t.tools_id = $tools_id AND t.disposal_status = 2
";

$result = mysqli_query($conn, $query);
$tools = mysqli_fetch_assoc($result);

if (!$tools) {
    echo "Data tidak ditemukan";
    exit;
}

// Ambil gambar untuk lampiran
$images = [];
if (!empty($tools['tools_image'])) {
    $images[] = $tools['tools_image'];
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

// Hitung umur tools
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

// Gunakan tanggal pengajuan disposal dari database
if (!empty($tools['disposal_date'])) {
    $disposal_timestamp = strtotime($tools['disposal_date']);
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

// Hitung umur
$umur = hitungUmur($tools['disposal_date'], $tools['tanggal_beli']);
$total_nilai = ($tools['tools_price'] ?? 0) * ($tools['tools_qty'] ?? 0);

// Buat nomor BA
$ba_number = 'DISPOSAL/TL/' . date('Ymd') . '/' . str_pad($tools_id, 4, '0', STR_PAD_LEFT);

// Lokasi QR Code image (static file)
$qr_code_url = BASE_URL . "master/img/qr-code.png";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Berita Acara Disposal Tools - <?= htmlspecialchars($tools['tools_name']) ?></title>
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

<!-- HALAMAN 1: Berita Acara Disposal Tools -->
<div class="page">
    <!-- Header -->
    <div class="header">
        <img src="<?= BASE_URL ?>master/img/cosmar_logo.png" alt="Logo Perusahaan" class="logo">
        <h1>BERITA ACARA DISPOSAL TOOLS</h1>
        <p style="font-size: 10px; text-align: center;">Nomor: <?= $ba_number ?></p>
    </div>

    <!-- Kalimat Pembuka -->
    <div class="content">
        <p>Pada hari <strong><?= $tanggalFormatted ?></strong>, telah dilakukan proses Disposal (Penghapusan) terhadap tools dengan rincian sebagai berikut:</p>
    </div>

    <!-- 1. DATA TOOLS -->
    <div class="content">
        <h2>1. DATA TOOLS</h2>
        <table>
            <tr>
                <th>Nama Tools</th>
                <td><strong><?= htmlspecialchars($tools['tools_name'] ?? '-') ?></strong></td>
            </tr>
            <tr>
                <th>Merk</th>
                <td><?= htmlspecialchars($tools['tools_merk'] ?? '-') ?></td>
            </tr>
            <?php if (!empty($tools['tools_spec'])): ?>
            <tr>
                <th>Spesifikasi</th>
                <td><?= nl2br(htmlspecialchars($tools['tools_spec'])) ?></td>
            </tr>
            <?php endif; ?>
            <tr>
                <th>Quantity</th>
                <td><?= number_format($tools['tools_qty'] ?? 0) ?> unit</div>
            </tr>
            <tr>
                <th>Harga per Unit</th>
                <td>Rp <?= number_format($tools['tools_price'] ?? 0, 0, ',', '.') ?> </div>
            </tr>
            <tr>
                <th>Total Nilai</th>
                <td>Rp <?= number_format($total_nilai, 0, ',', '.') ?> </div>
            </tr>
            <tr>
                <th>Tanggal Pembelian</th>
                <td><?= !empty($tools['tanggal_beli']) && $tools['tanggal_beli'] != '0000-00-00' ? date('d/m/Y', strtotime($tools['tanggal_beli'])) : '-' ?> </div>
            </tr>
            <tr>
                <th>Kondisi</th>
                <td><?= htmlspecialchars($tools['kondisi_name'] ?? '-') ?> </div>
            </tr>
        </table>
    </div>

    <!-- 2. ALASAN DISPOSAL -->
    <div class="content">
        <h2>2. ALASAN DISPOSAL</h2>
        <table>
            <tr>
                <th>Alasan Penghapusan</th>
                <td><?= nl2br(htmlspecialchars($tools['disposal_reason'] ?? '-')) ?> </div>
            </tr>
            <tr>
                <th>Tanggal Pengajuan</th>
                <td><?= !empty($tools['disposal_date']) ? date('d/m/Y H:i', strtotime($tools['disposal_date'])) : '-' ?> </div>
            </tr>
        <tr>
    </div>

    <!-- 3. PIHAK TERKAIT (menggunakan tbl_karyawan) -->
    <div class="content">
        <h2>3. PIHAK TERKAIT</h2>
        <tr>
            <tr>
                <th>Penanggung Jawab / User</th>
                <td><strong><?= htmlspecialchars($tools['karyawan_name'] ?? '-') ?></strong> </div>
            </tr>
            <tr>
                <th>ID Karyawan</th>
                <td><?= htmlspecialchars($tools['karyawan_no'] ?? '-') ?> </div>
            </tr>
            <tr>
                <th>Departemen</th>
                <td><strong><?= htmlspecialchars($tools['dep_name'] ?? '-') ?> (<?= htmlspecialchars($tools['dep_code'] ?? '-') ?>)</strong> </div>
            </tr>
        </table>
    </div>

    <!-- Informasi Tambahan: Umur Tools -->
    <?php if ($umur != '-'): ?>
    <div class="content">
        <h2>4. INFORMASI TAMBAHAN</h2>
        <table>
            <tr>
                <th>Umur Tools saat Disposal</th>
                <td><?= $umur ?> </div>
            </tr>
        </table>
    </div>
    <?php endif; ?>

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
            <p style="text-align: center;"><strong><?= htmlspecialchars($tools['karyawan_name'] ?? '-') ?></strong></p>
            <p style="text-align: center;"><?= htmlspecialchars($tools['dep_name'] ?? '-') ?></p>
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
        <p style="font-size: 10px;">Berita Acara Disposal Tools - <?= htmlspecialchars($tools['tools_name']) ?></p>
    </div>

    <div class="gallery">
        <?php 
        $no = 1;
        foreach ($images as $img): 
        ?>
        <div class="gallery-item">
            <img src="<?= BASE_URL ?>master/img/tools/<?= htmlspecialchars($img) ?>" alt="Gambar Tools <?= $no ?>" 
                 onerror="this.src='<?= BASE_URL ?>master/img/no-image.png'">
            <p><strong>Gambar <?= $no++ ?></strong></p>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="footer-note">
        <p><em>Halaman ini merupakan lampiran dari Berita Acara Disposal Tools.</em></p>
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