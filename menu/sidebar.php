<?php
$current_uri  = $_SERVER['REQUEST_URI'];
$current_page = basename($_SERVER['PHP_SELF']);

/* GROUP MENU */
$is_primary   = strpos($current_uri, '/primary/') !== false;
$is_handover  = strpos($current_uri, '/handover/') !== false;
$is_return    = strpos($current_uri, '/return/') !== false;
$is_assets    = strpos($current_uri, '/assets/') !== false;
$is_sparepart = strpos($current_uri, '/sparepart/') !== false;
$is_tools     = strpos($current_uri, '/tools/') !== false;
$is_disposal  = strpos($current_uri, '/disposal/') !== false;
$is_kategori  = strpos($current_uri, '/kategori/') !== false;
$is_type      = strpos($current_uri, '/type/') !== false;
$is_kondisi   = strpos($current_uri, '/kondisi/') !== false;
$is_lokasi    = strpos($current_uri, '/lokasi/') !== false;
$is_merk      = strpos($current_uri, '/merk/') !== false;
$is_produsen  = strpos($current_uri, '/produsen/') !== false;
$is_supplier  = strpos($current_uri, '/supplier/') !== false;
$is_dep       = strpos($current_uri, '/departemen/') !== false;
$is_user      = strpos($current_uri, '/user/') !== false;
$is_karyawan  = strpos($current_uri, '/karyawan/') !== false;
$is_report    = strpos($current_uri, '/report/') !== false;
$is_audit     = strpos($current_uri, '/audit/') !== false;
$is_maint     = strpos($current_uri, '/maintenance/') !== false;

/* DASHBOARD */
$is_dashboard = (
    $current_page == 'index.php' &&
    !$is_primary &&
    !$is_handover &&
    !$is_return &&
    !$is_assets &&
    !$is_sparepart &&
    !$is_kategori &&
    !$is_type &&
    !$is_kondisi &&
    !$is_lokasi &&
    !$is_merk &&
    !$is_produsen &&
    !$is_supplier &&
    !$is_dep &&
    !$is_karyawan &&
    !$is_user
);

?>

        <!-- Sidebar -->
        <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

            <!-- Sidebar - Brand -->
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="<?= BASE_URL ?>index.php">
                <div class="sidebar-brand-icon">
                    <img src="<?= BASE_URL ?>master/img/putih.png" alt="Logo" style="width: 40px; height: 40px; object-fit: contain;">
                </div>
                <div class="sidebar-brand-text mx-3">Cosmar Assets <sup>v2</sup></div>
            </a>

            <!-- Divider -->
            <hr class="sidebar-divider my-0">

            <!-- Nav Item - Dashboard -->
            <li class="nav-item <?= $is_dashboard ? 'active' : '' ?>">
                <a class="nav-link" href="<?= BASE_URL ?>index.php">
                    <i class="fas fa-fw fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <!-- Divider -->
            <hr class="sidebar-divider">

            <!-- Heading -->
            <div class="sidebar-heading">
                Data Assets
            </div>

            
            <!-- Nav Item - Master Assets -->
            <li class="nav-item <?= ($is_assets || $is_primary) ? 'active' : '' ?>">
                <a class="nav-link <?= ($is_assets || $is_primary) ? '' : 'collapsed' ?>" href="#" data-toggle="collapse" data-target="#collapseTwo"
                    aria-expanded="<?= ($is_assets || $is_primary) ? 'true' : 'false' ?>" aria-controls="collapseTwo">
                    <i class="fas fa-fw fa-database"></i>
                    <span>Control Assets</span>
                </a>
                <div id="collapseTwo" class="collapse <?= ($is_assets || $is_primary) ? 'show' : '' ?>" 
                    aria-labelledby="headingTwo" 
                    data-parent="#accordionSidebar">

                    <div class="bg-white py-2 collapse-inner rounded">
                        <h6 class="collapse-header">Control Assets:</h6>
                        <a class="collapse-item" href="<?= BASE_URL ?>primary/index.php">Primary Assets</a>
                        <?php
                            require_once __DIR__ . '/../config/koneksi.php';
                            $query = mysqli_query($conn, "SELECT COUNT(*) AS total
                                FROM (SELECT 
                                        a.assets_id,
                                        a.assets_qty,
                                        COALESCE(SUM(p.primary_qty), 0) AS qty_primary
                                    FROM tbl_assets a
                                    LEFT JOIN tbl_primary p ON a.assets_id = p.assets_id
                                    WHERE a.assets_kode IS NOT NULL 
                                    AND a.assets_kode != ''
                                    GROUP BY a.assets_id
                                    HAVING a.assets_qty != qty_primary
                                ) AS mismatch
                            ");
                            
                            $data = mysqli_fetch_assoc($query);
                            $total_validasi = $data['total'];
                        ?>                        
                        <?php if (isset($_SESSION['user_level']) && in_array($_SESSION['user_level'], [1, 2])) : ?>
                        <a class="collapse-item" href="<?= BASE_URL ?>primary/index2.php">
                            Validasi Assets
                            <?php if ($total_validasi > 0): ?>
                                <span class="badge badge-danger"><?= $total_validasi ?></span>
                            <?php endif; ?>                            
                        </a>
                        <?php
                            require_once __DIR__ . '/../config/koneksi.php';
                            $query = mysqli_query($conn, "SELECT COUNT(*) AS total
                            FROM tbl_primary p
                            INNER JOIN tbl_assets a ON p.assets_id = a.assets_id
                            LEFT JOIN tbl_lokasi l ON p.lokasi_id = l.lokasi_id
                            LEFT JOIN tbl_kondisi k ON p.kondisi_id = k.kondisi_id
                            WHERE p.karyawan_id IS NULL
                            OR (p.primary_image IS NULL OR p.primary_image = '')");
                            $data = mysqli_fetch_assoc($query);
                            $total_unassigned = $data['total'];
                        ?>
                        <a class="collapse-item" href="<?= BASE_URL ?>primary/index3.php">
                            Unassigned Assets
                            <?php if ($total_unassigned > 0): ?>
                                <span class="badge badge-danger"><?= $total_unassigned ?></span>
                            <?php endif; ?>
                        </a>
                        <?php endif; ?>
                        <a class="collapse-item" href="<?= BASE_URL ?>assets/index.php">Master Assets</a>
                    </div>
                </div>
            </li>

            <!-- Nav Item - Handover Assets -->
            <li class="nav-item <?= ($is_handover) ? 'active' : '' ?>">
                <a class="nav-link <?= ($is_handover) ? '' : 'collapsed' ?>" href="#" data-toggle="collapse" data-target="#collapseHand"
                    aria-expanded="<?= ($is_handover) ? 'true' : 'false' ?>" aria-controls="collapseHand">
                    <i class="fas fa-fw fa-arrow-circle-up"></i>
                    <?php if (isset($_SESSION['user_level']) && in_array($_SESSION['user_level'], [1, 2])) : ?>
                    <span>Handover Assets</span>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['user_level']) && in_array($_SESSION['user_level'], [3])) : ?>
                    <span>Request Assets</span>
                    <?php endif; ?>
                </a>
                <div id="collapseHand" class="collapse <?= ($is_handover) ? 'show' : '' ?>" 
                    aria-labelledby="headingHand" 
                    data-parent="#accordionSidebar">

                    <div class="bg-white py-2 collapse-inner rounded">
                        <?php if (isset($_SESSION['user_level']) && in_array($_SESSION['user_level'], [3])) : ?>
                        <a class="collapse-item" href="<?= BASE_URL ?>handover/index.php">Draft Assets</a>
                        <?php endif; ?>
                        <?php
                            require_once __DIR__ . '/../config/koneksi.php';
                            $query = mysqli_query($conn, "SELECT COUNT(*) AS total 
                                                        FROM tbl_assets 
                                                        WHERE kategori_id IS NULL");
                            $data = mysqli_fetch_assoc($query);
                            $total_pending = $data['total'];
                        ?>
                        <?php if (isset($_SESSION['user_level']) && in_array($_SESSION['user_level'], [1, 2])) : ?>
                        <a class="collapse-item" href="<?= BASE_URL ?>handover/index2.php">
                            Approval Assets 
                            <?php if ($total_pending > 0): ?>
                                <span class="badge badge-danger"><?= $total_pending ?></span>
                            <?php endif; ?>
                        </a>     
                        <?php endif; ?>                   
                        <a class="collapse-item" href="<?= BASE_URL ?>handover/index3.php">Handover Print</a>
                    </div>
                </div>
            </li>

            <!-- Nav Item - Sparepart -->
            <li class="nav-item <?= ($is_sparepart) ? 'active' : '' ?>">
                <a class="nav-link <?= ($is_sparepart) ? '' : 'collapsed' ?>" href="#" data-toggle="collapse" data-target="#collapseSpare"
                    aria-expanded="<?= ($is_sparepart) ? 'true' : 'false' ?>" aria-controls="collapseSpare">
                    <i class="fas fa-fw fa-plug"></i>
                    <span>Sparepart</span>
                </a>
                <div id="collapseSpare" class="collapse <?= ($is_sparepart) ? 'show' : '' ?>" 
                    aria-labelledby="headingSpare" 
                    data-parent="#accordionSidebar">

                    <div class="bg-white py-2 collapse-inner rounded">
                        <a class="collapse-item" href="<?= BASE_URL ?>sparepart/index.php">Data Sparepart</a>
                        <a class="collapse-item" href="<?= BASE_URL ?>sparepart/index2.php">Sparepart Assets</a>
                    </div>
                </div>
            </li>

            <!-- Nav Item - Tools -->
            <li class="nav-item <?= $is_tools ? 'active' : '' ?>">
                <a class="nav-link" href="<?= BASE_URL ?>tools/index.php">
                    <i class="fas fa-fw fa-screwdriver"></i>
                    <span>Equipment Tools</span>
                </a>
            </li>

            <!-- Nav Item - Return Assets -->
            <li class="nav-item <?= ($is_return) ? 'active' : '' ?>">
                <a class="nav-link <?= ($is_return) ? '' : 'collapsed' ?>" href="#" data-toggle="collapse" data-target="#collapseReturn"
                    aria-expanded="<?= ($is_return) ? 'true' : 'false' ?>" aria-controls="collapseReturn">
                    <i class="fas fa-fw fa-arrow-circle-down"></i>
                    <span>Return Assets</span>
                </a>
                <div id="collapseReturn" class="collapse <?= ($is_return) ? 'show' : '' ?>" 
                    aria-labelledby="headingReturn" 
                    data-parent="#accordionSidebar">

                    <div class="bg-white py-2 collapse-inner rounded">
                        <a class="collapse-item" href="<?= BASE_URL ?>return/index.php">Return Assets</a>
                        <?php
                            require_once __DIR__ . '/../config/koneksi.php';
                            $query2 = mysqli_query($conn, "SELECT COUNT(*) AS total 
                                                        FROM tbl_primary 
                                                        WHERE return_status = 1");
                            $data2 = mysqli_fetch_assoc($query2);
                            $total_pending2 = $data2['total'];
                        ?>
                        <?php if (isset($_SESSION['user_level']) && in_array($_SESSION['user_level'], [1, 2])) : ?>
                        <a class="collapse-item" href="<?= BASE_URL ?>return/index2.php">
                            Approval Assets 
                            <?php if ($total_pending2 > 0): ?>
                                <span class="badge badge-danger"><?= $total_pending2 ?></span>
                            <?php endif; ?>
                        </a>     
                        <?php endif; ?>                          
                        <a class="collapse-item" href="<?= BASE_URL ?>return/index3.php">Return Print</a>
                    </div>
                </div>
            </li>

            <!-- Nav Item - Disposal -->
            <li class="nav-item <?= ($is_disposal) ? 'active' : '' ?>">
                <a class="nav-link <?= ($is_disposal) ? '' : 'collapsed' ?>" 
                href="#" data-toggle="collapse" data-target="#collapseDisposal"
                aria-expanded="<?= ($is_disposal) ? 'true' : 'false' ?>" aria-controls="collapseDisposal">
                    <i class="fas fa-fw fa-trash-alt"></i>
                    <span>Disposal</span>
                </a>
                <div id="collapseDisposal" class="collapse <?= ($is_disposal) ? 'show' : '' ?>"
                    aria-labelledby="headingDisposal" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <h6 class="collapse-header">Disposal Base On:</h6>
                        <a class="collapse-item" href="<?= BASE_URL ?>disposal/index.php">Pengajuan Disposal</a>
                        <a class="collapse-item" href="<?= BASE_URL ?>disposal/index2.php">Disposal Approve</a>
                    </div>
                </div>
            </li>

            <!-- Nav Item - Report -->
            <li class="nav-item <?= ($is_report) ? 'active' : '' ?>">
                <a class="nav-link <?= ($is_report) ? '' : 'collapsed' ?>" 
                href="#" data-toggle="collapse" data-target="#collapseReport"
                aria-expanded="<?= ($is_report) ? 'true' : 'false' ?>" aria-controls="collapseReport">
                    <i class="fas fa-fw fa-chart-pie"></i>
                    <span>Report</span>
                </a>
                <div id="collapseReport"
                class="collapse <?= ($is_report) ? 'show' : '' ?>"
                aria-labelledby="headingReport"
                data-parent="#accordionSidebar">

                    <div class="bg-white py-2 collapse-inner rounded">
                        <h6 class="collapse-header">Report Assets Base on:</h6>
                        <?php if (isset($_SESSION['user_level']) && in_array($_SESSION['user_level'], [1, 2])) : ?>
                        <a class="collapse-item" href="<?= BASE_URL ?>report/export.php">Export</a>
                        <a class="collapse-item" href="<?= BASE_URL ?>report/user.php">User</a>
                        <?php endif; ?>
                        <a class="collapse-item" href="<?= BASE_URL ?>report/dep.php">Departemen</a>
                        <a class="collapse-item" href="<?= BASE_URL ?>report/kondisi.php">Kondisi</a>
                        <a class="collapse-item" href="<?= BASE_URL ?>report/type.php">Type</a>
                        <a class="collapse-item" href="<?= BASE_URL ?>report/merk.php">Merk</a>
                        <a class="collapse-item" href="<?= BASE_URL ?>report/lokasi.php">Lokasi</a>
                        <a class="collapse-item" href="<?= BASE_URL ?>report/kategori.php">Kategori</a>
                    </div>
                </div>
            </li>

            <?php if (isset($_SESSION['user_level']) && in_array($_SESSION['user_level'], [1, 2])) : ?>
            <!-- Nav Item - Audit -->
            <li class="nav-item <?= ($is_audit) ? 'active' : '' ?>">
                <a class="nav-link <?= ($is_audit) ? '' : 'collapsed' ?>" 
                href="#" data-toggle="collapse" data-target="#collapseAudit"
                aria-expanded="<?= ($is_audit) ? 'true' : 'false' ?>" aria-controls="collapseAudit">
                    <i class="fas fa-fw fa-search"></i>
                    <span>Audit</span>
                </a>
                <div id="collapseAudit"
                class="collapse <?= ($is_audit) ? 'show' : '' ?>"
                aria-labelledby="headingAudit"
                data-parent="#accordionSidebar">

                    <div class="bg-white py-2 collapse-inner rounded">
                        <h6 class="collapse-header">Audit Assets Base on:</h6>
                        <a class="collapse-item" href="<?= BASE_URL ?>audit/index.php">Outstanding Audit</a>
                        <a class="collapse-item" href="<?= BASE_URL ?>audit/index2.php">Completed Audit</a>
                        <a class="collapse-item" href="<?= BASE_URL ?>audit/index3.php">Progress Audit</a>
                    </div>
                </div>
            </li>
            <?php endif; ?>

            <!-- Nav Item - Utilities Collapse Menu -->
            <li class="nav-item <?= ($is_kategori || $is_type || $is_kondisi || $is_lokasi || $is_merk || $is_produsen || $is_supplier) ? 'active' : '' ?>">
                <a class="nav-link <?= ($is_kategori || $is_type || $is_kondisi || $is_lokasi || $is_merk || $is_produsen || $is_supplier) ? '' : 'collapsed' ?>"
                href="#" data-toggle="collapse" data-target="#collapseUtilities"
                aria-expanded="<?= ($is_kategori || $is_type || $is_kondisi || $is_lokasi || $is_merk || $is_produsen || $is_supplier) ? 'true' : 'false' ?>"
                aria-controls="collapseUtilities">
                    <i class="fas fa-fw fa-cog"></i>
                    <span>Utilities</span>
                </a>

                <div id="collapseUtilities"
                    class="collapse <?= ($is_kategori || $is_type || $is_kondisi || $is_lokasi || $is_merk || $is_produsen || $is_supplier) ? 'show' : '' ?>"
                    aria-labelledby="headingUtilities"
                    data-parent="#accordionSidebar">

                    <div class="bg-white py-2 collapse-inner rounded">
                        <h6 class="collapse-header">Data Utilities :</h6>
                        <a class="collapse-item" href="<?= BASE_URL ?>kategori/index.php">Kategori</a>
                        <a class="collapse-item" href="<?= BASE_URL ?>type/index.php">Type</a>
                        <a class="collapse-item" href="<?= BASE_URL ?>kondisi/index.php">Kondisi</a>
                        <a class="collapse-item" href="<?= BASE_URL ?>lokasi/index.php">Lokasi</a>
                        <a class="collapse-item" href="<?= BASE_URL ?>merk/index.php">Merk</a>
                        <a class="collapse-item" href="<?= BASE_URL ?>produsen/index.php">Produsen</a>
                        <a class="collapse-item" href="<?= BASE_URL ?>supplier/index.php">Supplier</a>
                    </div>
                </div>
            </li>

            <!-- Divider -->
            <hr class="sidebar-divider">

            <?php if (isset($_SESSION['user_level']) && in_array($_SESSION['user_level'], [1, 2])) : ?>
            <!-- Heading -->
            <div class="sidebar-heading">
                Data Karyawan
            </div>

            <!-- Nav Item - Pages Collapse Menu -->
            <li class="nav-item <?= ($is_dep || $is_user || $is_karyawan) ? 'active' : '' ?>">
                <a class="nav-link <?= ($is_dep || $is_user || $is_karyawan) ? '' : 'collapsed' ?>" href="#" data-toggle="collapse" data-target="#collapsePages"
                    aria-expanded="<?= ($is_dep || $is_user || $is_karyawan) ? 'true' : 'false' ?>" aria-controls="collapsePages">
                    <i class="fas fa-fw fa-users"></i>
                    <span>Data Karyawan</span>
                </a>
                <div id="collapsePages" class="collapse <?= ($is_dep || $is_user || $is_karyawan) ? 'show' : '' ?>" aria-labelledby="headingPages" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <a class="collapse-item" href="<?= BASE_URL ?>departemen/index.php">Departemen</a>
                        <a class="collapse-item" href="<?= BASE_URL ?>user/index.php">User</a>
                        <a class="collapse-item" href="<?= BASE_URL ?>karyawan/index.php">Karyawan</a>
                    </div>
                </div>
            </li>

            <!-- Divider --> 
            <hr class="sidebar-divider d-none d-md-block">

            <?php endif; ?>

            <!-- Sidebar Toggler (Sidebar) -->
            <div class="text-center d-none d-md-inline">
                <button class="rounded-circle border-0" id="sidebarToggle"></button>
            </div>

        </ul>
        <!-- End of Sidebar -->
