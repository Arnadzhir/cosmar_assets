<?php
session_start();
if (!isset($_SESSION['login'])) {
    header("Location: ../auth/login.php");
    exit;
}

include '../config/koneksi.php';

// Load autoload dari Composer
require_once '../master/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

// Tambahkan ini untuk mengatasi memory limit
ini_set('memory_limit', '512M');
ini_set('max_execution_time', 300);

// Ambil parameter type
$type = $_GET['type'] ?? '';
$user_level = $_SESSION['user_level'];
$user_id = $_SESSION['user_id'];
$dep_id = $_SESSION['dep_id'] ?? 0;
$is_admin = in_array($user_level, [1, 2]);

// Fungsi untuk membuat file Excel
function createExcel($data, $headers, $sheetName, $filename) {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle(mb_substr($sheetName, 0, 31));
    
    // Header styling
    $headerStyle = [
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4E73DF']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
    ];
    
    // Isi header
    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col . '1', $header);
        $sheet->getStyle($col . '1')->applyFromArray($headerStyle);
        $sheet->getColumnDimension($col)->setAutoSize(true);
        $col++;
    }
    
    // Isi data
    $row = 2;
    foreach ($data as $dataRow) {
        $col = 'A';
        foreach ($dataRow as $value) {
            $sheet->setCellValue($col . $row, $value);
            $sheet->getStyle($col . $row)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $col++;
        }
        $row++;
    }
    
    // Border untuk seluruh data
    $lastColumn = chr(64 + count($headers));
    $lastRow = $row - 1;
    if ($lastRow >= 2) {
        $sheet->getStyle('A1:' . $lastColumn . $lastRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    }
    
    // Set header untuk download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '_' . date('Ymd_His') . '.xlsx"');
    header('Cache-Control: max-age=0');
    
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

// Export berdasarkan type
switch ($type) {
    
    // ==================== PRIMARY ASSETS ====================
    case 'primary':
        $query = "
            SELECT 
                p.primary_id,
                p.primary_qty,
                p.primary_image,
                p.timestamp,
                a.*,
                kat.kategori_name,
                kat.kategori_line,
                kat.kategori_code,
                m.merk_name,
                t.type_name,
                s.supplier_name,
                pr.produsen_code,
                pr.produsen_region,
                kond.kondisi_name,
                l.lokasi_name,
                l.lokasi_lantai,
                kar.karyawan_name,
                d.dep_name,
                d.dep_code
            FROM tbl_primary p
            INNER JOIN tbl_assets a ON p.assets_id = a.assets_id
            LEFT JOIN tbl_kondisi kond ON p.kondisi_id = kond.kondisi_id
            LEFT JOIN tbl_lokasi l ON p.lokasi_id = l.lokasi_id
            LEFT JOIN tbl_kategori kat ON a.kategori_id = kat.kategori_id
            LEFT JOIN tbl_merk m ON a.merk_id = m.merk_id
            LEFT JOIN tbl_type t ON a.type_id = t.type_id
            LEFT JOIN tbl_supplier s ON a.supplier_id = s.supplier_id
            LEFT JOIN tbl_produsen pr ON a.produsen_id = pr.produsen_id
            LEFT JOIN tbl_karyawan kar ON p.karyawan_id = kar.karyawan_id
            LEFT JOIN tbl_dep d ON kar.dep_id = d.dep_id
        ";
        
        if (!$is_admin) {
            $query .= " WHERE d.dep_id = '$dep_id'";
        }
        
        $query .= " ORDER BY p.primary_id ASC";
        
        $result = mysqli_query($conn, $query);
        $data = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $kategori = !empty($row['kategori_name']) ? $row['kategori_name'] . ' - ' . $row['kategori_line'] : '-';
            $produsen = !empty($row['produsen_region']) ? $row['produsen_region'] . ' (' . $row['produsen_code'] . ')' : '-';
            
            $data[] = [
                $row['assets_kode'] ?? '-',
                $row['assets_name'] ?? '-',
                $row['assets_model'] ?? '-',
                $row['assets_life'] ? $row['assets_life'] . ' Tahun' : '-',
                !empty($row['assets_price']) ? 'Rp ' . number_format($row['assets_price'], 0, ',', '.') : '-',
                !empty($row['assets_date']) && $row['assets_date'] != '0000-00-00' ? date('d/m/Y', strtotime($row['assets_date'])) : '-',
                $row['assets_qty'] ?? '-',
                $row['assets_spec'] ?? '-',
                $row['assets_target'] ?? '-',
                $row['assets_cap'] ?? '-',
                $row['assets_uom'] ?? '-',
                $kategori,
                $row['merk_name'] ?? '-',
                $row['type_name'] ?? '-',
                $row['supplier_name'] ?? '-',
                $produsen,
                $row['kondisi_name'] ?? '-',
                $row['lokasi_name'] ?? '-',
                $row['lokasi_lantai'] ?? '-',
                $row['karyawan_name'] ?? '-',
                $row['dep_name'] ?? '-',
                $row['dep_code'] ?? '-',
                date('d/m/Y H:i', strtotime($row['timestamp']))
            ];
        }
        
        $headers = ['Kode Asset', 'Nama Asset', 'Model', 'Estimasi Umur', 'Harga', 'Tanggal Beli', 'Qty', 
                    'Spesifikasi', 'Target', 'Kapasitas', 'UoM', 'Kategori', 'Merk', 'Type', 'Supplier', 
                    'Produsen', 'Kondisi', 'Lokasi', 'Lantai', 'Penanggung Jawab', 'Departemen', 'Kode Dept', 'Timestamp'];
        createExcel($data, $headers, 'Primary Assets', 'primary_assets');
    break;

    // ==================== SPAREPART ASSETS ====================
    case 'sparepart':
        $query = "
            SELECT 
                s.sparepart_id,
                s.sparepart_name,
                s.sparepart_merk,
                s.sparepart_spec,
                s.sparepart_qty,
                s.sparepart_price,
                s.sparepart_date,
                s.sparepart_note,
                s.sparepart_timestamp,
                a.assets_kode,
                a.assets_name,
                kar.karyawan_name,
                d.dep_name,
                d.dep_code
            FROM tbl_sparepart s
            INNER JOIN tbl_assets a ON s.assets_id = a.assets_id
            INNER JOIN tbl_karyawan kar ON s.user_id = kar.karyawan_id
            LEFT JOIN tbl_dep d ON kar.dep_id = d.dep_id
        ";

        if (!$is_admin) {
            $query .= " WHERE d.dep_id = '$dep_id'";
        }

        $query .= " ORDER BY s.sparepart_id DESC";

        $result = mysqli_query($conn, $query);
        $data = [];

        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = [
                $row['sparepart_name'] ?? '-',
                $row['sparepart_merk'] ?? '-',
                $row['sparepart_spec'] ?? '-',
                $row['sparepart_qty'] ?? 0,
                !empty($row['sparepart_price']) ? 'Rp ' . number_format($row['sparepart_price'], 0, ',', '.') : '-',
                !empty($row['sparepart_date']) && $row['sparepart_date'] != '0000-00-00' ? date('d/m/Y', strtotime($row['sparepart_date'])) : '-',
                $row['sparepart_note'] ?? '-',
                $row['assets_kode'] ?? '-',
                $row['assets_name'] ?? '-',
                $row['dep_name'] ?? '-',
                $row['karyawan_name'] ?? '-',
                !empty($row['sparepart_timestamp']) ? date('d/m/Y H:i', strtotime($row['sparepart_timestamp'])) : '-'
            ];
        }

        $headers = [
            'Sparepart Name', 'Merk', 'Spesifikasi', 'Qty', 'Price', 'Tanggal Beli', 'Note',
            'Kode Asset', 'Nama Asset', 'Departemen', 'Penanggung Jawab', 'Timestamp'
        ];

        createExcel($data, $headers, 'Sparepart Assets', 'sparepart_assets');
    break;
    
    // ==================== ASSETS MASTER ====================
    case 'assets':
        $query = "
            SELECT 
                a.assets_kode,
                a.assets_name,
                a.assets_model,
                a.assets_life,
                a.assets_price,
                a.assets_date,
                a.assets_qty,
                a.assets_spec,
                a.assets_target,
                a.assets_cap,
                a.assets_uom,
                a.assets_note,
                kat.kategori_name,
                kat.kategori_line,
                m.merk_name,
                t.type_name,
                s.supplier_name,
                pr.produsen_region,
                pr.produsen_code
            FROM tbl_assets a
            LEFT JOIN tbl_kategori kat ON a.kategori_id = kat.kategori_id
            LEFT JOIN tbl_merk m ON a.merk_id = m.merk_id
            LEFT JOIN tbl_type t ON a.type_id = t.type_id
            LEFT JOIN tbl_supplier s ON a.supplier_id = s.supplier_id
            LEFT JOIN tbl_produsen pr ON a.produsen_id = pr.produsen_id
            WHERE a.assets_kode IS NOT NULL AND a.assets_kode != ''
            ORDER BY a.assets_kode ASC
        ";
        
        $result = mysqli_query($conn, $query);
        $data = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $kategori = !empty($row['kategori_name']) ? $row['kategori_name'] . ' - ' . $row['kategori_line'] : '-';
            $produsen = !empty($row['produsen_region']) ? $row['produsen_region'] . ' (' . $row['produsen_code'] . ')' : '-';
            
            $data[] = [
                $row['assets_kode'] ?? '-',
                $row['assets_name'] ?? '-',
                $row['assets_model'] ?? '-',
                $row['assets_life'] ? $row['assets_life'] . ' Tahun' : '-',
                !empty($row['assets_price']) ? 'Rp ' . number_format($row['assets_price'], 0, ',', '.') : '-',
                !empty($row['assets_date']) && $row['assets_date'] != '0000-00-00' ? date('d/m/Y', strtotime($row['assets_date'])) : '-',
                $row['assets_qty'] ?? '-',
                $row['assets_spec'] ?? '-',
                $row['assets_target'] ?? '-',
                $row['assets_cap'] ?? '-',
                $row['assets_uom'] ?? '-',
                $kategori,
                $row['merk_name'] ?? '-',
                $row['type_name'] ?? '-',
                $row['supplier_name'] ?? '-',
                $produsen,
                $row['assets_note'] ?? '-'
            ];
        }
        
        $headers = ['Kode Asset', 'Nama Asset', 'Model', 'Estimasi Umur', 'Harga', 'Tanggal Beli', 'Total Qty',
                    'Spesifikasi', 'Target', 'Kapasitas', 'UoM', 'Kategori', 'Merk', 'Type', 'Supplier', 
                    'Produsen', 'Catatan'];
        createExcel($data, $headers, 'Master Assets', 'master_assets');
    break;
    
    // ==================== KARYAWAN ====================
    case 'karyawan':
        $query = "
            SELECT 
                kar.karyawan_id,
                kar.karyawan_name,
                kar.karyawan_no,
                kar.karyawan_gender,
                kar.karyawan_level,
                d.dep_name,
                d.dep_code
            FROM tbl_karyawan kar
            LEFT JOIN tbl_dep d ON kar.dep_id = d.dep_id
        ";
        
        if (!$is_admin) {
            $query .= " WHERE kar.dep_id = '$dep_id'";
        }
        
        $query .= " ORDER BY kar.karyawan_name ASC";
        
        $result = mysqli_query($conn, $query);
        $data = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $level_name = '';
            if ($row['karyawan_level'] == 'Manager') $level_name = 'Manager';
            elseif ($row['karyawan_level'] == 'Supervisor') $level_name = 'Supervisor';
            elseif ($row['karyawan_level'] == 'Leader') $level_name = 'Leader';
            elseif ($row['karyawan_level'] == 'Staff') $level_name = 'Staff';
            else $level_name = $row['karyawan_level'] ?? '-';
            
            $data[] = [
                $row['karyawan_id'],
                $row['karyawan_name'],
                $row['karyawan_no'] ?? '-',
                $row['karyawan_gender'] ?? '-',
                $level_name,
                $row['dep_code'] ?? '-',
                $row['dep_name'] ?? '-'
            ];
        }
        
        $headers = ['ID Karyawan', 'Nama Karyawan', 'No. Telepon', 'Jenis Kelamin', 'Level', 'Kode Dept', 'Departemen'];
        createExcel($data, $headers, 'Karyawan', 'karyawan_data');
    break;
    
    // ==================== DEPARTEMEN ====================
    case 'dep':
        $query = "SELECT dep_id, dep_code, dep_name FROM tbl_dep GROUP BY dep_code ORDER BY dep_code";
        $result = mysqli_query($conn, $query);
        $data = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = [
                $row['dep_id'],
                $row['dep_code'],
                $row['dep_name']
            ];
        }
        
        $headers = ['ID', 'Kode Dept', 'Nama Departemen'];
        createExcel($data, $headers, 'Departemen', 'departemen');
    break;
    
    // ==================== KONDISI ====================
    case 'kondisi':
        $query = "SELECT kondisi_id, kondisi_name FROM tbl_kondisi ORDER BY kondisi_id";
        $result = mysqli_query($conn, $query);
        $data = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = [
                $row['kondisi_id'],
                $row['kondisi_name']
            ];
        }
        
        $headers = ['ID', 'Nama Kondisi'];
        createExcel($data, $headers, 'Kondisi', 'kondisi');
    break;
    
    // ==================== TYPE ====================
    case 'type':
        $query = "SELECT type_id, type_name FROM tbl_type ORDER BY type_name";
        $result = mysqli_query($conn, $query);
        $data = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = [
                $row['type_id'],
                $row['type_name']
            ];
        }
        
        $headers = ['ID', 'Nama Type'];
        createExcel($data, $headers, 'Type', 'type');
    break;
    
    // ==================== MERK ====================
    case 'merk':
        $query = "SELECT merk_id, merk_name FROM tbl_merk ORDER BY merk_name";
        $result = mysqli_query($conn, $query);
        $data = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = [
                $row['merk_id'],
                $row['merk_name']
            ];
        }
        
        $headers = ['ID', 'Nama Merk'];
        createExcel($data, $headers, 'Merk', 'merk');
    break;
    
    // ==================== LOKASI ====================
    case 'lokasi':
        $query = "SELECT lokasi_id, lokasi_name, lokasi_lantai FROM tbl_lokasi ORDER BY lokasi_name";
        $result = mysqli_query($conn, $query);
        $data = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = [
                $row['lokasi_id'],
                $row['lokasi_name'],
                $row['lokasi_lantai'] ?? '-'
            ];
        }
        
        $headers = ['ID', 'Nama Lokasi', 'Lantai'];
        createExcel($data, $headers, 'Lokasi', 'lokasi');
    break;
    
    // ==================== KATEGORI ====================
    case 'kategori':
        $query = "SELECT kategori_id, kategori_name, kategori_line, kategori_code FROM tbl_kategori ORDER BY kategori_name";
        $result = mysqli_query($conn, $query);
        $data = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = [
                $row['kategori_id'],
                $row['kategori_name'],
                $row['kategori_line'],
                $row['kategori_code']
            ];
        }
        
        $headers = ['ID', 'Nama Kategori', 'Line', 'Kode'];
        createExcel($data, $headers, 'Kategori', 'kategori');
    break;
    
    // ==================== SUPPLIER ====================
    case 'supplier':
        $query = "SELECT supplier_id, supplier_name, supplier_mail, supplier_no FROM tbl_supplier ORDER BY supplier_name";
        $result = mysqli_query($conn, $query);
        $data = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = [
                $row['supplier_id'],
                $row['supplier_name'],
                $row['supplier_mail'] ?? '-',
                $row['supplier_no'] ?? '-'
            ];
        }
        
        $headers = ['ID', 'Nama Supplier', 'Email', 'Telepon'];
        createExcel($data, $headers, 'Supplier', 'supplier');
    break;
    
    // ==================== PRODUSEN ====================
    case 'produsen':
        $query = "SELECT produsen_id, produsen_code, produsen_region FROM tbl_produsen ORDER BY produsen_region";
        $result = mysqli_query($conn, $query);
        $data = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = [
                $row['produsen_id'],
                $row['produsen_code'],
                $row['produsen_region']
            ];
        }
        
        $headers = ['ID', 'Kode', 'Asal Negara'];
        createExcel($data, $headers, 'Produsen', 'produsen');
    break;
    
    default:
        header("Location: export.php");
        exit;
}

mysqli_close($conn);
?>