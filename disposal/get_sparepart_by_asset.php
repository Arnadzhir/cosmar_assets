<?php
include '../config/koneksi.php';

$assets_id = (int)$_POST['assets_id'];
$user_id = (int)$_POST['user_id'];

$query = "SELECT sparepart_id, sparepart_name, sparepart_qty 
          FROM tbl_sparepart 
          WHERE assets_id = $assets_id AND user_id = $user_id AND disposal_status IS NULL";
$result = mysqli_query($conn, $query);

$options = '<option value="">-- Pilih Sparepart --</option>';
while ($row = mysqli_fetch_assoc($result)) {
    $options .= "<option value='{$row['sparepart_id']}'>{$row['sparepart_name']} (Qty: {$row['sparepart_qty']})</option>";
}
echo $options;
?>