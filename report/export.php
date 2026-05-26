<?php
include '../auth/auth.php';
allowRole([1,2,3]);

include '../config/koneksi.php';

/* =====================
   AMBIL DATA USER LOGIN
===================== */
$user_id    = $_SESSION['user_id'];
$user_level = $_SESSION['user_level'];
$dep_id     = $_SESSION['dep_id'] ?? 0;
$dep_code   = $_SESSION['dep_code'] ?? '';
$dep_name   = $_SESSION['dep_name'] ?? '';

// Cek apakah user bisa export semua data atau hanya departemennya
$is_admin = in_array($user_level, [1, 2]);

include '../menu/header.php';
include '../menu/sidebar.php';
include '../menu/topbar.php';
?>

<!-- SweetAlert2 CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    .export-card {
        transition: all 0.3s;
        cursor: pointer;
        border: none;
        border-radius: 10px;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        margin-bottom: 20px;
    }
    .export-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 0.5rem 2rem 0 rgba(58, 59, 69, 0.2);
    }
    .export-card .card-body {
        padding: 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .export-card .card-icon {
        font-size: 40px;
        width: 60px;
        height: 60px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
    }
    .export-card .card-info {
        flex: 1;
        margin-left: 15px;
    }
    .export-card .card-title {
        font-size: 16px;
        font-weight: 600;
        margin-bottom: 5px;
    }
    .export-card .card-text {
        font-size: 12px;
        color: #6c757d;
        margin-bottom: 0;
    }
    .btn-export {
        background-color: #28a745;
        border-color: #28a745;
        color: white;
        transition: all 0.3s;
        padding: 8px 20px;
        border-radius: 20px;
        font-size: 13px;
    }
    .btn-export:hover {
        background-color: #218838;
        border-color: #1e7e34;
        transform: translateY(-2px);
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        color: white;
    }
    .page-header {
        margin-bottom: 25px;
    }
    .badge-role {
        background-color: #4e73df;
        color: white;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
    }
    .icon-primary { background-color: #e8f0fe; color: #4e73df; }
    .icon-success { background-color: #d4edda; color: #28a745; }
    .icon-info { background-color: #d1ecf1; color: #17a2b8; }
    .icon-warning { background-color: #fff3cd; color: #ffc107; }
    .icon-danger { background-color: #f8d7da; color: #dc3545; }
    .icon-dark { background-color: #e2e3e5; color: #343a40; }
    .icon-secondary { background-color: #e9ecef; color: #6c757d; }
    .dep-info {
        font-size: 13px;
        margin-top: 5px;
        color: #6c757d;
    }
</style>

<div class="container-fluid">

    <!-- Page Heading -->
    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-file-excel"></i> Export Data
            </h1>
            <p class="text-muted mt-2">Export data ke format Excel (.xlsx)</p>
        </div>
        <div class="badge-role">
            <i class="fas fa-user-shield"></i> 
            <?php if ($is_admin): ?>
                Admin/Operator - Dapat export semua data
            <?php else: ?>
                User - Hanya data departemen <?= htmlspecialchars($dep_code ?? '') ?> - <?= htmlspecialchars($dep_name ?? '') ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Informasi Tambahan untuk User Level 3 -->
    <?php if (!$is_admin): ?>
    <div class="alert alert-info mb-4">
        <i class="fas fa-info-circle"></i> 
        <strong>Informasi:</strong> Sebagai user, Anda hanya dapat mengekspor data yang terkait dengan departemen Anda (<?= htmlspecialchars($dep_code ?? '') ?> - <?= htmlspecialchars($dep_name ?? '') ?>).
    </div>
    <?php endif; ?>

    <div class="row">
        <!-- Card 1: Primary Assets -->
        <div class="col-md-6">
            <div class="card export-card">
                <div class="card-body">
                    <div class="card-icon icon-primary">
                        <i class="fas fa-database"></i>
                    </div>
                    <div class="card-info">
                        <h5 class="card-title">Primary Assets</h5>
                        <p class="card-text">Export data asset per unit (lokasi, kondisi, penanggung jawab)</p>
                    </div>
                    <button class="btn btn-export btn-sm" onclick="exportData('primary')">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
            </div>
        </div>

        <!-- Card 2: Master Assets -->
        <div class="col-md-6">
            <div class="card export-card">
                <div class="card-body">
                    <div class="card-icon icon-success">
                        <i class="fas fa-boxes"></i>
                    </div>
                    <div class="card-info">
                        <h5 class="card-title">Master Assets</h5>
                        <p class="card-text">Export data master asset (nama, spesifikasi, harga)</p>
                    </div>
                    <button class="btn btn-export btn-sm" onclick="exportData('assets')">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
            </div>
        </div>

        <!-- Card 3: Sparepart Assets -->
        <div class="col-md-6">
            <div class="card export-card">
                <div class="card-body">
                    <div class="card-icon icon-success">
                        <i class="fas fa-screwdriver"></i>
                    </div>
                    <div class="card-info">
                        <h5 class="card-title">Sparepart Assets</h5>
                        <p class="card-text">Export data sparepart asset</p>
                    </div>
                    <button class="btn btn-export btn-sm" onclick="exportData('sparepart')">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
            </div>
        </div>

        <!-- Card 4: Karyawan -->
        <div class="col-md-6">
            <div class="card export-card">
                <div class="card-body">
                    <div class="card-icon icon-info">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="card-info">
                        <h5 class="card-title">Karyawan</h5>
                        <p class="card-text">Export data karyawan (nama, ID, departemen, jenis kelamin)</p>
                    </div>
                    <button class="btn btn-export btn-sm" onclick="exportData('karyawan')">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
            </div>
        </div>

        <!-- Card 5: Departemen -->
        <div class="col-md-6">
            <div class="card export-card">
                <div class="card-body">
                    <div class="card-icon icon-warning">
                        <i class="fas fa-building"></i>
                    </div>
                    <div class="card-info">
                        <h5 class="card-title">Departemen</h5>
                        <p class="card-text">Export data departemen (kode, nama)</p>
                    </div>
                    <button class="btn btn-export btn-sm" onclick="exportData('dep')">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
            </div>
        </div>

        <!-- Card 6: Kondisi -->
        <div class="col-md-6">
            <div class="card export-card">
                <div class="card-body">
                    <div class="card-icon icon-danger">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <div class="card-info">
                        <h5 class="card-title">Kondisi</h5>
                        <p class="card-text">Export data kondisi asset</p>
                    </div>
                    <button class="btn btn-export btn-sm" onclick="exportData('kondisi')">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
            </div>
        </div>

        <!-- Card 7: Type -->
        <div class="col-md-6">
            <div class="card export-card">
                <div class="card-body">
                    <div class="card-icon icon-secondary">
                        <i class="fas fa-tag"></i>
                    </div>
                    <div class="card-info">
                        <h5 class="card-title">Type</h5>
                        <p class="card-text">Export data type asset</p>
                    </div>
                    <button class="btn btn-export btn-sm" onclick="exportData('type')">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
            </div>
        </div>

        <!-- Card 8: Merk -->
        <div class="col-md-6">
            <div class="card export-card">
                <div class="card-body">
                    <div class="card-icon icon-dark">
                        <i class="fas fa-trademark"></i>
                    </div>
                    <div class="card-info">
                        <h5 class="card-title">Merk</h5>
                        <p class="card-text">Export data merk asset</p>
                    </div>
                    <button class="btn btn-export btn-sm" onclick="exportData('merk')">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
            </div>
        </div>

        <!-- Card 9: Lokasi -->
        <div class="col-md-6">
            <div class="card export-card">
                <div class="card-body">
                    <div class="card-icon icon-primary">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="card-info">
                        <h5 class="card-title">Lokasi</h5>
                        <p class="card-text">Export data lokasi asset</p>
                    </div>
                    <button class="btn btn-export btn-sm" onclick="exportData('lokasi')">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
            </div>
        </div>

        <!-- Card 10: Kategori -->
        <div class="col-md-6">
            <div class="card export-card">
                <div class="card-body">
                    <div class="card-icon icon-success">
                        <i class="fas fa-folder"></i>
                    </div>
                    <div class="card-info">
                        <h5 class="card-title">Kategori</h5>
                        <p class="card-text">Export data kategori asset</p>
                    </div>
                    <button class="btn btn-export btn-sm" onclick="exportData('kategori')">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
            </div>
        </div>

        <!-- Card 11: Supplier -->
        <div class="col-md-6">
            <div class="card export-card">
                <div class="card-body">
                    <div class="card-icon icon-info">
                        <i class="fas fa-truck"></i>
                    </div>
                    <div class="card-info">
                        <h5 class="card-title">Supplier</h5>
                        <p class="card-text">Export data supplier</p>
                    </div>
                    <button class="btn btn-export btn-sm" onclick="exportData('supplier')">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
            </div>
        </div>

        <!-- Card 12: Produsen -->
        <div class="col-md-6">
            <div class="card export-card">
                <div class="card-body">
                    <div class="card-icon icon-secondary">
                        <i class="fas fa-industry"></i>
                    </div>
                    <div class="card-info">
                        <h5 class="card-title">Produsen</h5>
                        <p class="card-text">Export data produsen (asal negara)</p>
                    </div>
                    <button class="btn btn-export btn-sm" onclick="exportData('produsen')">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../menu/footer.php'; ?>

<script>
function exportData(type) {    
    // Redirect ke file export_proses.php
    window.location.href = 'export_proses.php?type=' + type;
}
</script>

</body>
</html>