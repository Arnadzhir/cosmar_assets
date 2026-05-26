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
        background-color: #4e73df;
        color: white;
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 14px;
        font-weight: 600;
    }
    .card-header-custom {
        background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
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
        min-width: 1300px;
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
    .table tbody td:nth-child(6) {
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
        color: #4e73df;
        font-size: 13px;
    }
    .umur-badge {
        background-color: #f0f0f0;
        padding: 3px 8px;
        border-radius: 15px;
        font-size: 11px;
        display: inline-block;
    }
    .custom-scrollbar::-webkit-scrollbar {
        height: 8px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #4e73df;
        border-radius: 4px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: #2e59d9;
    }
    .section-title {
        margin: 20px 0 10px 0;
        padding-bottom: 10px;
        border-bottom: 2px solid #4e73df;
    }
    .section-title h3 {
        font-size: 18px;
        color: #4e73df;
        font-weight: 600;
        margin: 0;
    }
    .btn-add {
        margin-bottom: 15px;
    }
</style>

<div class="container-fluid">

    <!-- HEADER -->
    <div class="page-header">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-clock"></i> Pengajuan Disposal
        </h1>
        <div class="total-badge">
            Total Pengajuan : <span id="total-data">0</span>
        </div>
    </div>

    <!-- ================= CARD 1: ASSET ================= -->
    <div class="section-title">
        <h3><i class="fas fa-building"></i> Disposal Asset</h3>
    </div>
    
    <div class="mb-3 text-right btn-add">
        <?php if (isset($_SESSION['user_level']) && in_array($_SESSION['user_level'], [3])) : ?>
        <a href="tambah.php" class="btn btn-success btn-sm">
            <i class="fas fa-plus"></i> Ajukan Disposal Asset
        </a>
        <?php endif; ?>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-list"></i> Daftar Pengajuan Disposal Asset
            </h6>
        </div>
        <div class="card-body">
            <div class="table-responsive custom-scrollbar">
                <table class="table table-bordered" id="dataTableAsset" width="100%">
                    <thead>
                        <tr>
                            <th width="80">Tools</th>
                            <th width="50">No</th>
                            <th width="150">Kode Asset</th>
                            <th width="200">Nama Asset</th>
                            <th width="80">Qty</th>
                            <th width="250">Alasan Disposal</th>
                            <th width="120">Tanggal Pengajuan</th>
                            <th width="120">Tanggal Pembelian</th>
                            <th width="100">Umur Asset</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $noAsset = 1;
                        $totalAsset = 0;
                        
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
                        
                        $queryAsset = "
                            SELECT
                                p.primary_id AS id,
                                a.assets_kode AS kode,
                                a.assets_name AS nama,
                                a.assets_date AS tanggal_beli,
                                p.primary_qty AS qty,
                                p.disposal_reason AS alasan,
                                p.disposal_date AS tgl_pengajuan,
                                p.karyawan_id
                            FROM tbl_primary p
                            INNER JOIN tbl_assets a ON p.assets_id = a.assets_id
                            WHERE p.disposal_status = 1
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
                                        <a href="edit.php?type=asset&id='.$row['id'].'" class="btn btn-sm btn-warning" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="proses.php?hapus=1&type=asset&id='.$row['id'].'" class="btn btn-sm btn-danger btn-hapus" title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                ';
                                $kode_display = '
                                    <span class="asset-code-link" onclick="copyToClipboard(\''.$row['kode'].'\')">
                                        <i class="fas fa-copy text-muted mr-1"></i> '.$row['kode'].'
                                    </span>
                                ';
                                $umur = hitungUmur($row['tgl_pengajuan'], $row['tanggal_beli']);
                        ?>
                        <tr>
                            <td class="text-center"><?= $tools ?> </div>
                            <td class="text-center"><?= $noAsset++ ?> </div>
                            <td><?= $kode_display ?> </div>
                            <td><?= htmlspecialchars($row['nama']) ?> </div>
                            <td class="text-center total-qty"><?= (int)$row['qty'] ?> unit</div>
                            <td><?= nl2br(htmlspecialchars($row['alasan'])) ?> </div>
                            <td class="text-center"><?= !empty($row['tgl_pengajuan']) ? date('d/m/Y', strtotime($row['tgl_pengajuan'])) : '-' ?></div>
                            <td class="text-center"><?= !empty($row['tanggal_beli']) && $row['tanggal_beli'] != '0000-00-00' ? date('d/m/Y', strtotime($row['tanggal_beli'])) : '-' ?></div>
                            <td class="text-center"><span class="umur-badge"><?= $umur ?></span></div>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted py-5">
                                <i class="fas fa-inbox fa-3x mb-3"></i>
                                <p class="mb-0">Tidak ada pengajuan disposal asset</p>
                             </div>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ================= CARD 2: SPAREPART ================= -->
    <div class="section-title">
        <h3><i class="fas fa-microchip"></i> Disposal Sparepart</h3>
    </div>
    
    <div class="mb-3 text-right btn-add">
        <?php if (isset($_SESSION['user_level']) && in_array($_SESSION['user_level'], [3])) : ?>
        <a href="tambah2.php" class="btn btn-success btn-sm">
            <i class="fas fa-plus"></i> Ajukan Disposal Sparepart
        </a>
        <?php endif; ?>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-list"></i> Daftar Pengajuan Disposal Sparepart
            </h6>
        </div>
        <div class="card-body">
            <div class="table-responsive custom-scrollbar">
                <table class="table table-bordered" id="dataTableSparepart" width="100%">
                    <thead>
                        <tr>
                            <th width="80">Tools</th>
                            <th width="50">No</th>
                            <th width="150">Kode Asset</th>
                            <th width="200">Nama Sparepart</th>
                            <th width="80">Qty</th>
                            <th width="250">Alasan Disposal</th>
                            <th width="120">Tanggal Pengajuan</th>
                            <th width="120">Tanggal Pembelian</th>
                            <th width="100">Umur</th>
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
                                s.disposal_date AS tgl_pengajuan,
                                s.user_id
                            FROM tbl_sparepart s
                            INNER JOIN tbl_assets a ON s.assets_id = a.assets_id
                            WHERE s.disposal_status = 1
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
                                        <a href="edit2.php?type=sparepart&id='.$row['id'].'" class="btn btn-sm btn-warning" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="proses2.php?hapus=1&type=sparepart&id='.$row['id'].'" class="btn btn-sm btn-danger btn-hapus" title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                ';
                                $kode_display = '
                                    <span class="asset-code-link" onclick="copyToClipboard(\''.$row['kode'].'\')">
                                        <i class="fas fa-copy text-muted mr-1"></i> '.$row['kode'].'
                                    </span>
                                ';
                                $umur = hitungUmur($row['tgl_pengajuan'], $row['tanggal_beli']);
                        ?>
                        <tr>
                            <td class="text-center"><?= $tools ?> </div>
                            <td class="text-center"><?= $noSparepart++ ?> </div>
                            <td><?= $kode_display ?> </div>
                            <td><?= htmlspecialchars($row['nama']) ?> </div>
                            <td class="text-center total-qty"><?= (int)$row['qty'] ?> unit</div>
                            <td><?= nl2br(htmlspecialchars($row['alasan'])) ?> </div>
                            <td class="text-center"><?= !empty($row['tgl_pengajuan']) ? date('d/m/Y', strtotime($row['tgl_pengajuan'])) : '-' ?></div>
                            <td class="text-center"><?= !empty($row['tanggal_beli']) && $row['tanggal_beli'] != '0000-00-00' ? date('d/m/Y', strtotime($row['tanggal_beli'])) : '-' ?></div>
                            <td class="text-center"><span class="umur-badge"><?= $umur ?></span></div>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted py-5">
                                <i class="fas fa-inbox fa-3x mb-3"></i>
                                <p class="mb-0">Tidak ada pengajuan disposal sparepart</p>
                             </div>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ================= CARD 3: TOOLS ================= -->
    <div class="section-title">
        <h3><i class="fas fa-tools"></i> Disposal Tools</h3>
    </div>
    
    <div class="mb-3 text-right btn-add">
        <?php if (isset($_SESSION['user_level']) && in_array($_SESSION['user_level'], [3])) : ?>
        <a href="tambah3.php" class="btn btn-success btn-sm">
            <i class="fas fa-plus"></i> Ajukan Disposal Tools
        </a>
        <?php endif; ?>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-list"></i> Daftar Pengajuan Disposal Tools
            </h6>
        </div>
        <div class="card-body">
            <div class="table-responsive custom-scrollbar">
                <table class="table table-bordered" id="dataTableTools" width="100%">
                    <thead>
                        <tr>
                            <th width="80">Tools</th>
                            <th width="50">No</th>
                            <th width="150">Nama Tools</th>
                            <th width="100">Merk</th>
                            <th width="80">Qty</th>
                            <th width="250">Alasan Disposal</th>
                            <th width="120">Tanggal Pengajuan</th>
                            <th width="120">Tanggal Pembelian</th>
                            <th width="100">Umur</th>
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
                                t.disposal_date AS tgl_pengajuan,
                                t.user_id
                            FROM tbl_tools t
                            WHERE t.disposal_status = 1
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
                                        <a href="edit3.php?type=tools&id='.$row['id'].'" class="btn btn-sm btn-warning" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="proses3.php?hapus=1&type=tools&id='.$row['id'].'" class="btn btn-sm btn-danger btn-hapus" title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                ';
                                $umur = hitungUmur($row['tgl_pengajuan'], $row['tanggal_beli']);
                        ?>
                        <tr>
                            <td class="text-center"><?= $tools ?> </div>
                            <td class="text-center"><?= $noTools++ ?> </div>
                            <td><?= htmlspecialchars($row['nama']) ?> </div>
                            <td><?= htmlspecialchars($row['merk'] ?? '-') ?> </div>
                            <td class="text-center total-qty"><?= (int)$row['qty'] ?> unit</div>
                            <td><?= nl2br(htmlspecialchars($row['alasan'])) ?> </div>
                            <td class="text-center"><?= !empty($row['tgl_pengajuan']) ? date('d/m/Y', strtotime($row['tgl_pengajuan'])) : '-' ?></div>
                            <td class="text-center"><?= !empty($row['tanggal_beli']) && $row['tanggal_beli'] != '0000-00-00' ? date('d/m/Y', strtotime($row['tanggal_beli'])) : '-' ?></div>
                            <td class="text-center"><span class="umur-badge"><?= $umur ?></span></div>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted py-5">
                                <i class="fas fa-inbox fa-3x mb-3"></i>
                                <p class="mb-0">Tidak ada pengajuan disposal tools</p>
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
            "zeroRecords": "Tidak ada data",
            "info": "Menampilkan halaman _PAGE_ dari _PAGES_",
            "infoEmpty": "Tidak ada data",
            "infoFiltered": "(difilter dari _MAX_ total data)",
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
            "zeroRecords": "Tidak ada data",
            "info": "Menampilkan halaman _PAGE_ dari _PAGES_",
            "infoEmpty": "Tidak ada data",
            "infoFiltered": "(difilter dari _MAX_ total data)",
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
            "zeroRecords": "Tidak ada data",
            "info": "Menampilkan halaman _PAGE_ dari _PAGES_",
            "infoEmpty": "Tidak ada data",
            "infoFiltered": "(difilter dari _MAX_ total data)",
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

    // SweetAlert konfirmasi hapus
    $(document).on('click', '.btn-hapus', function(e) {
        e.preventDefault();
        let link = $(this).attr('href');
        Swal.fire({
            title: 'Hapus Pengajuan?',
            text: "Data yang dihapus tidak bisa dikembalikan",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e74a3b',
            cancelButtonColor: '#858796',
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) window.location.href = link;
        });
    });

});

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