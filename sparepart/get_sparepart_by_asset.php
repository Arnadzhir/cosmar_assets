<?php
include '../config/koneksi.php';

$assets_id = isset($_POST['assets_id']) ? (int)$_POST['assets_id'] : 0;

if ($assets_id <= 0) {
    echo '<div class="alert alert-danger">ID Asset tidak valid</div>';
    exit();
}

// PERBAIKAN: Query menggunakan tbl_karyawan
$query = "
    SELECT 
        s.*,
        kar.karyawan_name,
        d.dep_code,
        d.dep_name
    FROM tbl_sparepart s
    INNER JOIN tbl_karyawan kar ON s.user_id = kar.karyawan_id
    LEFT JOIN tbl_dep d ON kar.dep_id = d.dep_id
    WHERE s.assets_id = $assets_id
    ORDER BY s.sparepart_id DESC
";

$result = mysqli_query($conn, $query);

if (!$result) {
    echo '<div class="alert alert-danger">Error query: ' . mysqli_error($conn) . '</div>';
    exit();
}

if (mysqli_num_rows($result) == 0) {
    echo '<div class="alert alert-info">Tidak ada sparepart untuk asset ini</div>';
    exit();
}
?>

<div class="table-responsive">
    <table class="table table-bordered table-sm">
        <thead class="bg-light">
            <tr>
                <th>No</th>
                <th>Nama Sparepart</th>
                <th>Merk</th>
                <th>Qty</th>
                <th>Harga Satuan</th>
                <th>Total</th>
                <th>Tanggal</th>
                <th>Spesifikasi</th>
                <th>Penanggung Jawab</th>
                <th>Departemen</th>
                <th>Catatan</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $no = 1;
            $grand_total = 0;
            while ($row = mysqli_fetch_assoc($result)):
                $total = $row['sparepart_price'] * $row['sparepart_qty'];
                $grand_total += $total;
                
                // Format tanggal
                $tanggal = (!empty($row['sparepart_date']) && $row['sparepart_date'] != '0000-00-00') 
                    ? date('d/m/Y', strtotime($row['sparepart_date'])) 
                    : '-';
                
                // Format harga
                $harga = !empty($row['sparepart_price']) ? 'Rp ' . number_format($row['sparepart_price'], 0, ',', '.') : 'Rp 0';
                $total_format = 'Rp ' . number_format($total, 0, ',', '.');
            ?>
            <tr>
                <td class="text-center"><?= $no++ ?> </div>
                <td><?= htmlspecialchars($row['sparepart_name'] ?? '-') ?> </div>
                <td><?= htmlspecialchars($row['sparepart_merk'] ?? '-') ?> </div>
                <td class="text-center"><?= number_format($row['sparepart_qty']) ?> </div>
                <td class="text-right"><?= $harga ?> </div>
                <td class="text-right"><?= $total_format ?> </div>
                <td class="text-center"><?= $tanggal ?> </div>
                <td><?= htmlspecialchars($row['sparepart_spec'] ?? '-') ?> </div>
                <td><?= htmlspecialchars($row['karyawan_name'] ?? '-') ?> </div>
                <td><?= ($row['dep_code'] ?? '-') ?> - <?= ($row['dep_name'] ?? '-') ?> </div>
                <td><?= htmlspecialchars($row['sparepart_note'] ?? '-') ?> </div>
            </tr>
            <?php endwhile; ?>
        </tbody>
        <tfoot class="bg-light">
            <tr>
                <th colspan="5" class="text-right">Grand Total:</th>
                <th class="text-right">Rp <?= number_format($grand_total, 0, ',', '.') ?></th>
                <th colspan="5"></th>
            </tr>
        </tfoot>
    </table>
</div>