<?php
include '../config/koneksi.php';

$assets_id = isset($_POST['assets_id']) ? (int)$_POST['assets_id'] : 0;

if ($assets_id <= 0) {
    echo '<div class="alert alert-danger">ID Asset tidak valid</div>';
    exit();
}

$query = "
    SELECT 
        p.primary_id,
        p.primary_qty,
        p.primary_image,
        p.timestamp,
        l.lokasi_name,
        l.lokasi_lantai,
        kond.kondisi_name,
        kar.karyawan_name,
        d.dep_code,
        d.dep_name,
        d.dep_id
    FROM tbl_primary p
    LEFT JOIN tbl_lokasi l ON p.lokasi_id = l.lokasi_id
    LEFT JOIN tbl_kondisi kond ON p.kondisi_id = kond.kondisi_id
    LEFT JOIN tbl_karyawan kar ON p.karyawan_id = kar.karyawan_id
    LEFT JOIN tbl_dep d ON kar.dep_id = d.dep_id
    WHERE p.assets_id = $assets_id
    ORDER BY p.primary_id ASC
";

$result = mysqli_query($conn, $query);

if (!$result) {
    echo '<div class="alert alert-danger">Error query: ' . mysqli_error($conn) . '</div>';
    exit();
}

if (mysqli_num_rows($result) == 0) {
    echo '<div class="alert alert-info">Belum ada primary asset untuk asset ini</div>';
    exit();
}
?>

<div class="table-responsive">
    <table class="table table-bordered table-sm">
        <thead class="bg-light">
            <tr>
                <th>No</th>
                <th>Qty</th>
                <th>Lokasi</th>
                <th>Kondisi</th>
                <th>Penanggung Jawab</th>
                <th>Departemen</th>
                <th>Tanggal Input</th>
                <th>Gambar</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $no = 1;
            $total_qty = 0;
            while ($row = mysqli_fetch_assoc($result)):
                $total_qty += $row['primary_qty'];
                $lokasi = $row['lokasi_name'] ?? '-';
                if (!empty($row['lokasi_lantai'])) {
                    $lokasi .= ' (Lt.' . $row['lokasi_lantai'] . ')';
                }
                
                // Format tanggal
                $tanggal = !empty($row['timestamp']) 
                    ? date('d/m/Y H:i', strtotime($row['timestamp'])) 
                    : '-';
            ?>
            <tr>
                <td class="text-center"><?= $no++ ?></td>
                <td class="text-center"><?= number_format($row['primary_qty']) ?> unit</td>
                <td><?= htmlspecialchars($lokasi) ?></td>
                <td><?= htmlspecialchars($row['kondisi_name'] ?? '-') ?></td>
                <td><?= htmlspecialchars($row['karyawan_name'] ?? '-') ?></td>
                <td>
                    <?php if (!empty($row['dep_code'])): ?>
                        <?= htmlspecialchars($row['dep_code']) ?> - <?= htmlspecialchars($row['dep_name'] ?? '-') ?>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
                <td class="text-center"><?= $tanggal ?></td>
                <td class="text-center">
                    <?php if (!empty($row['primary_image'])): ?>
                        <a href="../master/img/assets/<?= $row['primary_image'] ?>" target="_blank">
                            <img src="../master/img/assets/<?= $row['primary_image'] ?>" 
                                style="width:40px;height:40px;object-fit:cover;border-radius:4px;border:1px solid #ddd;"
                                onerror="this.src='../master/img/no-image.png'">
                        </a>
                    <?php else: ?>
                        <span class="badge badge-secondary">No Image</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
        <tfoot class="bg-light">
            <tr>
                <th colspan="1" class="text-right">Total Primary Qty:</th>
                <th class="text-center"><?= number_format($total_qty) ?> unit</th>
                <th colspan="6"></th>
            </tr>
        </tfoot>
    </table>
</div>

<script>
// Optional: Tambahkan tooltip untuk gambar
$(document).ready(function() {
    $('img[onerror]').each(function() {
        $(this).on('error', function() {
            $(this).attr('src', '../master/img/no-image.png');
        });
    });
});
</script>