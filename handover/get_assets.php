<?php
include '../config/koneksi.php';

if (isset($_POST['assets_id'])) {

    $id = intval($_POST['assets_id']);

    $q = mysqli_query($conn, "
        SELECT assets_name,
               assets_life,
               assets_qty,
               assets_price,
               assets_date,
               assets_note
        FROM tbl_assets
        WHERE assets_id = '$id'
        LIMIT 1
    ");

    if (mysqli_num_rows($q) > 0) {
        $data = mysqli_fetch_assoc($q);
        echo json_encode($data);
    } else {
        echo json_encode(null);
    }
}