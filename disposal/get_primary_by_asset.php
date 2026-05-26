<?php
include '../config/koneksi.php';

$assets_id = isset($_POST['assets_id']) ? (int)$_POST['assets_id'] : 0;
$user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;

$response = ['success' => false, 'data' => []];

if ($assets_id > 0 && $user_id > 0) {
    $query = "SELECT 
                p.primary_id,
                p.primary_qty,
                l.lokasi_name,
                l.lokasi_lantai
              FROM tbl_primary p
              LEFT JOIN tbl_lokasi l ON p.lokasi_id = l.lokasi_id
              WHERE p.assets_id = $assets_id 
              AND p.karyawan_id = $user_id
              AND (p.disposal_status IS NULL OR p.disposal_status = 0)
              ORDER BY l.lokasi_name ASC";
    
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $response['success'] = true;
        while ($row = mysqli_fetch_assoc($result)) {
            $response['data'][] = $row;
        }
    }
}

header('Content-Type: application/json');
echo json_encode($response);
?>