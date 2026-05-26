<?php
include '../auth/auth.php';
allowRole([1,2,3]);

include '../config/koneksi.php';

$user_id = $_SESSION['user_id'];
$user_level = $_SESSION['user_level'];
$dep_id = $_SESSION['dep_id'] ?? 0;

include '../menu/header.php';
include '../menu/sidebar.php';
include '../menu/topbar.php';
?>

<!-- SweetAlert2 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        flex-wrap: wrap;
        gap: 10px;
    }
    .total-badge {
        background-color: #1cc88a;
        color: white;
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 14px;
        font-weight: 600;
    }
    .card-header-custom {
        background: linear-gradient(135deg, #1cc88a 0%, #13855c 100%);
        color: white;
    }
    .card-header-custom h6 {
        color: white;
        margin: 0;
    }
    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        border: none;
        border-radius: 5px;
    }
    .table {
        min-width: 1400px;
        width: 100%;
        white-space: nowrap;
        margin-bottom: 0;
    }
    .table thead th {
        white-space: nowrap;
        background-color: #f8f9fc;
        vertical-align: middle;
        padding: 12px 10px;
        font-size: 12px;
        font-weight: 600;
        border-bottom: 2px solid #e3e6f0;
    }
    .table tbody td {
        white-space: nowrap;
        padding: 8px 10px;
        vertical-align: middle;
        font-size: 12px;
        border-top: 1px solid #e3e6f0;
    }
    /* Kolom alasan boleh wrap & lebar tetap */
    .table tbody td:nth-child(5) {
        white-space: normal;
        word-break: break-word;
        max-width: 300px;
        min-width: 250px;
    }
    .tools-group {
        display: flex;
        gap: 4px;
        justify-content: center;
    }
    .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.7rem;
        border-radius: 0.2rem;
    }
    .asset-code-link {
        color: #4e73df;
        text-decoration: underline;
        cursor: pointer;
        font-weight: 600;
    }
    .asset-code-link:hover {
        color: #224abe;
    }
    .total-qty {
        font-weight: bold;
        color: #1cc88a;
        font-size: 13px;
    }
    .umur-badge {
        background-color: #f0f0f0;
        padding: 3px 8px;
        border-radius: 15px;
        font-size: 11px;
        display: inline-block;
    }
    .section-title {
        margin: 20px 0 10px 0;
        padding-bottom: 10px;
        border-bottom: 2px solid #1cc88a;
    }
    .section-title h3 {
        font-size: 18px;
        color: #1cc88a;
        font-weight: 600;
        margin: 0;
    }
    .table-responsive::-webkit-scrollbar {
        height: 8px;
    }
    .table-responsive::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }
    .table-responsive::-webkit-scrollbar-thumb {
        background: #1cc88a;
        border-radius: 4px;
    }
</style>

<div class="container-fluid">

    <!-- HEADER -->
    <div class="page-header">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-check-circle"></i> Disposal Asset (Disetujui)
        </h1>
        <div class="total-badge">
            Total Disposal : <span id="total-data">0</span>
        </div>
    </div>

    <!-- BUTTON -->
    <div class="mb-3 text-right">
        <a href="index.php" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Kembali ke Pengajuan
        </a>
        <a href="index2.php" class="btn btn-secondary btn-sm">
            <i class="fas fa-sync-alt"></i> Refresh
        </a>
    </div>

    <!-- ================= SECTION 1: ASSET ================= -->
    <div class="section-title">
        <h3><i class="fas fa-building"></i> Disposal Asset</h3>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-list"></i> Disposal Asset (Sudah Disetujui)
            </h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTableAsset" width="100%">
                    <thead>
                        <tr>
                            <th width="80">Tools</th>
                            <th width="50">No</th>
                            <th width="150">Kode Asset</th>
                            <th width="220">Nama Asset</th>
                            <th width="100">Qty</th>
                            <th width="300">Alasan Disposal</th>
                            <th width="150">Tanggal Disposal</th>
                            <th width="150">Tanggal Pembelian</th>
                            <th width="120">Umur Asset</th>
                            <th width="150">Penanggung Jawab</th>
                        </td>
                    </thead>
                    <tbody>
                        <?php
                        $noAsset = 1;
                        $totalAsset = 0;
                        
                        // Fungsi untuk menghitung selisih tanggal
                        function hitungUmur($tanggal_disposal, $tanggal_pembelian) {
                            if (empty($tanggal_disposal) || empty($tanggal_pembelian) || $tanggal_disposal == '0000-00-00' || $tanggal_pembelian == '0000-00-00') {
                                return '-';
                            }
                            $tgl1 = new DateTime($tanggal_disposal);
                            $tgl2 = new DateTime($tanggal_pembelian);
                            $diff = $tgl1->diff($tgl2);
                            $hasil = array();
                            if ($diff->y > 0) $hasil[] = $diff->y . ' Tahun';
                            if ($diff->m > 0) $hasil[] = $diff->m . ' Bulan';
                            if ($diff->d > 0) $hasil[] = $diff->d . ' Hari';
                            return !empty($hasil) ? implode(' ', $hasil) : '0 Hari';
                        }
                        
                        $queryAsset = "SELECT
                                p.primary_id AS id,
                                a.assets_kode AS kode,
                                a.assets_name AS nama,
                                a.assets_date AS tanggal_beli,
                                p.primary_qty AS qty,
                                p.disposal_reason AS alasan,
                                p.disposal_date AS tgl_disposal,
                                kar.karyawan_name,
                                d.dep_name
                            FROM tbl_primary p
                            INNER JOIN tbl_assets a ON p.assets_id = a.assets_id
                            LEFT JOIN tbl_karyawan kar ON p.karyawan_id = kar.karyawan_id
                            LEFT JOIN tbl_dep d ON kar.dep_id = d.dep_id
                            WHERE p.disposal_status = 2 OR p.kondisi_id = 70006
                        ";
                        if ($user_level == 3) {
                            $queryAsset .= " AND p.karyawan_id = '$user_id'";
                        }
                        $queryAsset .= " ORDER BY p.disposal_date DESC";
                        $resultAsset = mysqli_query($conn, $queryAsset);
                        if ($resultAsset && mysqli_num_rows($resultAsset) > 0):
                            $totalAsset = mysqli_num_rows($resultAsset);
                            while ($row = mysqli_fetch_assoc($resultAsset)):
                                $tools = '
                                    <div class="tools-group">
                                        <a href="print.php?type=asset&id='.$row['id'].'" class="btn btn-sm btn-info" title="Cetak" target="_blank">
                                            <i class="fas fa-print"></i>
                                        </a>
                                    </div>
                                ';
                                $kode_display = '
                                    <span class="asset-code-link" onclick="copyToClipboard(\''.$row['kode'].'\')" title="Klik untuk copy kode">
                                        <i class="fas fa-copy text-muted mr-1"></i> '.$row['kode'].'
                                    </span>
                                ';
                                $umur = hitungUmur($row['tgl_disposal'], $row['tanggal_beli']);
                                $penanggung_jawab = !empty($row['karyawan_name']) ? $row['karyawan_name'] . ' (' . ($row['dep_name'] ?? '-') . ')' : '-';
                        ?>
                        <tr>
                            <td class="text-center"><?= $tools ?> </div>
                            <td class="text-center"><?= $noAsset++ ?> </div>
                            <td><?= $kode_display ?> </div>
                            <td><?= htmlspecialchars($row['nama']) ?> </div>
                            <td class="text-center total-qty"><?= (int)$row['qty'] ?> unit</div>
                            <td><?= nl2br(htmlspecialchars($row['alasan'])) ?> </div>
                            <td class="text-center"><?= !empty($row['tgl_disposal']) ? date('d/m/Y', strtotime($row['tgl_disposal'])) : '-' ?></div>
                            <td class="text-center"><?= !empty($row['tanggal_beli']) && $row['tanggal_beli'] != '0000-00-00' ? date('d/m/Y', strtotime($row['tanggal_beli'])) : '-' ?></div>
                            <td class="text-center"><span class="umur-badge"><?= $umur ?></span></div>
                            <td class="text-center"><?= htmlspecialchars($penanggung_jawab) ?></div>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr>
                            <td colspan="10" class="text-center text-muted py-5">
                                <i class="fas fa-inbox fa-3x mb-3"></i>
                                <p class="mb-0">Tidak ada data disposal asset</p>
                             </div>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ================= SECTION 2: SPAREPART ================= -->
    <div class="section-title">
        <h3><i class="fas fa-microchip"></i> Disposal Sparepart</h3>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-list"></i> Disposal Sparepart (Sudah Disetujui)
            </h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTableSparepart" width="100%">
                    <thead>
                        <tr>
                            <th width="80">Tools</th>
                            <th width="50">No</th>
                            <th width="150">Kode Asset</th>
                            <th width="220">Nama Sparepart</th>
                            <th width="100">Qty</th>
                            <th width="300">Alasan Disposal</th>
                            <th width="150">Tanggal Disposal</th>
                            <th width="150">Tanggal Pembelian</th>
                            <th width="120">Umur Asset</th>
                            <th width="150">Penanggung Jawab</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $noSparepart = 1;
                        $totalSparepart = 0;
                        
                        $querySparepart = "
                            SELECT
                                s.sparepart_id AS id,
                                a.assets_kode AS kode,
                                a.assets_date AS tanggal_beli,
                                s.sparepart_name AS nama,
                                s.sparepart_qty AS qty,
                                s.disposal_reason AS alasan,
                                s.disposal_date AS tgl_disposal,
                                kar.karyawan_name,
                                d.dep_name
                            FROM tbl_sparepart s
                            INNER JOIN tbl_assets a ON s.assets_id = a.assets_id
                            LEFT JOIN tbl_karyawan kar ON s.user_id = kar.karyawan_id
                            LEFT JOIN tbl_dep d ON kar.dep_id = d.dep_id
                            WHERE s.disposal_status = 2
                        ";
                        if ($user_level == 3) {
                            $querySparepart .= " AND s.user_id = '$user_id'";
                        }
                        $querySparepart .= " ORDER BY s.disposal_date DESC";
                        $resultSparepart = mysqli_query($conn, $querySparepart);
                        if ($resultSparepart && mysqli_num_rows($resultSparepart) > 0):
                            $totalSparepart = mysqli_num_rows($resultSparepart);
                            while ($row = mysqli_fetch_assoc($resultSparepart)):
                                $tools = '
                                    <div class="tools-group">
                                        <a href="print2.php?type=sparepart&id='.$row['id'].'" class="btn btn-sm btn-info" title="Cetak" target="_blank">
                                            <i class="fas fa-print"></i>
                                        </a>
                                    </div>
                                ';
                                $kode_display = '
                                    <span class="asset-code-link" onclick="copyToClipboard(\''.$row['kode'].'\')" title="Klik untuk copy kode">
                                        <i class="fas fa-copy text-muted mr-1"></i> '.$row['kode'].'
                                    </span>
                                ';
                                $umur = hitungUmur($row['tgl_disposal'], $row['tanggal_beli']);
                                $penanggung_jawab = !empty($row['karyawan_name']) ? $row['karyawan_name'] . ' (' . ($row['dep_name'] ?? '-') . ')' : '-';
                        ?>
                        <tr>
                            <td class="text-center"><?= $tools ?> </div>
                            <td class="text-center"><?= $noSparepart++ ?> </div>
                            <td><?= $kode_display ?> </div>
                            <td><?= htmlspecialchars($row['nama']) ?> </div>
                            <td class="text-center total-qty"><?= (int)$row['qty'] ?> unit</div>
                            <td><?= nl2br(htmlspecialchars($row['alasan'])) ?> </div>
                            <td class="text-center"><?= !empty($row['tgl_disposal']) ? date('d/m/Y', strtotime($row['tgl_disposal'])) : '-' ?></div>
                            <td class="text-center"><?= !empty($row['tanggal_beli']) && $row['tanggal_beli'] != '0000-00-00' ? date('d/m/Y', strtotime($row['tanggal_beli'])) : '-' ?></div>
                            <td class="text-center"><span class="umur-badge"><?= $umur ?></span></div>
                            <td class="text-center"><?= htmlspecialchars($penanggung_jawab) ?></div>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr>
                            <td colspan="10" class="text-center text-muted py-5">
                                <i class="fas fa-inbox fa-3x mb-3"></i>
                                <p class="mb-0">Tidak ada data disposal sparepart</p>
                             </div>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ================= SECTION 3: TOOLS ================= -->
    <div class="section-title">
        <h3><i class="fas fa-tools"></i> Disposal Tools</h3>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-list"></i> Disposal Tools (Sudah Disetujui)
            </h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTableTools" width="100%">
                    <thead>
                        <tr>
                            <th width="80">Tools</th>
                            <th width="50">No</th>
                            <th width="200">Nama Tools</th>
                            <th width="150">Merk</th>
                            <th width="100">Qty</th>
                            <th width="300">Alasan Disposal</th>
                            <th width="150">Tanggal Disposal</th>
                            <th width="150">Tanggal Pembelian</th>
                            <th width="120">Umur Tools</th>
                            <th width="150">Penanggung Jawab</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $noTools = 1;
                        $totalTools = 0;
                        
                        $queryTools = "
                            SELECT
                                t.tools_id AS id,
                                t.tools_name AS nama,
                                t.tools_merk AS merk,
                                t.tools_date AS tanggal_beli,
                                t.tools_qty AS qty,
                                t.disposal_reason AS alasan,
                                t.disposal_date AS tgl_disposal,
                                kar.karyawan_name,
                                d.dep_name
                            FROM tbl_tools t
                            LEFT JOIN tbl_karyawan kar ON t.user_id = kar.karyawan_id
                            LEFT JOIN tbl_dep d ON kar.dep_id = d.dep_id
                            WHERE t.disposal_status = 2
                        ";
                        if ($user_level == 3) {
                            $queryTools .= " AND t.user_id = '$user_id'";
                        }
                        $queryTools .= " ORDER BY t.disposal_date DESC";
                        $resultTools = mysqli_query($conn, $queryTools);
                        if ($resultTools && mysqli_num_rows($resultTools) > 0):
                            $totalTools = mysqli_num_rows($resultTools);
                            while ($row = mysqli_fetch_assoc($resultTools)):
                                $tools = '
                                    <div class="tools-group">
                                        <a href="print3.php?type=tools&id='.$row['id'].'" class="btn btn-sm btn-info" title="Cetak" target="_blank">
                                            <i class="fas fa-print"></i>
                                        </a>
                                    </div>
                                ';
                                $umur = hitungUmur($row['tgl_disposal'], $row['tanggal_beli']);
                                $penanggung_jawab = !empty($row['karyawan_name']) ? $row['karyawan_name'] . ' (' . ($row['dep_name'] ?? '-') . ')' : '-';
                        ?>
                        <tr>
                            <td class="text-center"><?= $tools ?> </div>
                            <td class="text-center"><?= $noTools++ ?> </div>
                            <td><?= htmlspecialchars($row['nama']) ?> </div>
                            <td><?= htmlspecialchars($row['merk'] ?? '-') ?> </div>
                            <td class="text-center total-qty"><?= (int)$row['qty'] ?> unit</div>
                            <td><?= nl2br(htmlspecialchars($row['alasan'])) ?> </div>
                            <td class="text-center"><?= !empty($row['tgl_disposal']) ? date('d/m/Y', strtotime($row['tgl_disposal'])) : '-' ?></div>
                            <td class="text-center"><?= !empty($row['tanggal_beli']) && $row['tanggal_beli'] != '0000-00-00' ? date('d/m/Y', strtotime($row['tanggal_beli'])) : '-' ?></div>
                            <td class="text-center"><span class="umur-badge"><?= $umur ?></span></div>
                            <td class="text-center"><?= htmlspecialchars($penanggung_jawab) ?></div>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr>
                            <td colspan="10" class="text-center text-muted py-5">
                                <i class="fas fa-inbox fa-3x mb-3"></i>
                                <p class="mb-0">Tidak ada data disposal tools</p>
                             </div>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<?php include '../menu/footer.php'; ?>

<script>
$(document).ready(function() {

    // DataTable Asset
    if ($.fn.DataTable.isDataTable('#dataTableAsset')) {
        $('#dataTableAsset').DataTable().destroy();
    }
    $('#dataTableAsset').DataTable({
        "pageLength": 25,
        "lengthMenu": [10, 25, 50, 100],
        "order": [[1, "asc"]],
        "scrollX": true,
        "scrollCollapse": true,
        "language": {
            "lengthMenu": "Tampilkan _MENU_ data per halaman",
            "zeroRecords": "Tidak ada数据",
            "info": "Menampilkan halaman _PAGE_ dari _PAGES_",
            "infoEmpty": "Tidak ada数据",
            "infoFiltered": "(difilter dari _MAX_ total数据)",
            "search": "Cari:",
            "paginate": {
                "first": "Pertama",
                "last": "Terakhir",
                "next": "Selanjutnya",
                "previous": "Sebelumnya"
            }
        },
        "columnDefs": [
            { "orderable": false, "targets": [0] }
        ]
    });

    // DataTable Sparepart
    if ($.fn.DataTable.isDataTable('#dataTableSparepart')) {
        $('#dataTableSparepart').DataTable().destroy();
    }
    $('#dataTableSparepart').DataTable({
        "pageLength": 25,
        "lengthMenu": [10, 25, 50, 100],
        "order": [[1, "asc"]],
        "scrollX": true,
        "scrollCollapse": true,
        "language": {
            "lengthMenu": "Tampilkan _MENU_ data per halaman",
            "zeroRecords": "Tidak ada数据",
            "info": "Menampilkan halaman _PAGE_ dari _PAGES_",
            "infoEmpty": "Tidak ada数据",
            "infoFiltered": "(difilter dari _MAX_ total数据)",
            "search": "Cari:",
            "paginate": {
                "first": "Pertama",
                "last": "Terakhir",
                "next": "Selanjutnya",
                "previous": "Sebelumnya"
            }
        },
        "columnDefs": [
            { "orderable": false, "targets": [0] }
        ]
    });

    // DataTable Tools
    if ($.fn.DataTable.isDataTable('#dataTableTools')) {
        $('#dataTableTools').DataTable().destroy();
    }
    $('#dataTableTools').DataTable({
        "pageLength": 25,
        "lengthMenu": [10, 25, 50, 100],
        "order": [[1, "asc"]],
        "scrollX": true,
        "scrollCollapse": true,
        "language": {
            "lengthMenu": "Tampilkan _MENU_ data per halaman",
            "zeroRecords": "Tidak ada数据",
            "info": "Menampilkan halaman _PAGE_ dari _PAGES_",
            "infoEmpty": "Tidak ada数据",
            "infoFiltered": "(difilter dari _MAX_ total数据)",
            "search": "Cari:",
            "paginate": {
                "first": "Pertama",
                "last": "Terakhir",
                "next": "Selanjutnya",
                "previous": "Sebelumnya"
            }
        },
        "columnDefs": [
            { "orderable": false, "targets": [0] }
        ]
    });

    // Update total badge
    let totalAsset = <?= $totalAsset ?>;
    let totalSparepart = <?= $totalSparepart ?>;
    let totalTools = <?= $totalTools ?>;
    $('#total-data').text(totalAsset + totalSparepart + totalTools);

});

// Copy kode ke clipboard
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        Swal.fire({
            icon: 'success',
            title: 'Tersalin!',
            text: 'Kode ' + text + ' berhasil disalin',
            timer: 1500,
            showConfirmButton: false
        });
    }).catch(function() {
        var textarea = document.createElement('textarea');
        textarea.value = text;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        alert('Kode berhasil disalin');
    });
}
</script>

</body>
</html>