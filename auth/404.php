<?php
// auth/404.php - Custom 404 Page

// Mulai session jika belum
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ambil informasi URL yang diakses
$request_uri = $_SERVER['REQUEST_URI'];
$request_method = $_SERVER['REQUEST_METHOD'];
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
$ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';

// Tentukan path untuk log file
$log_dir = __DIR__ . '/../logs';
$log_file = $log_dir . '/404_errors.log';

// Buat folder logs jika belum ada
if (!file_exists($log_dir)) {
    mkdir($log_dir, 0777, true);
}

// Log 404 error ke file
$log_entry = date('Y-m-d H:i:s') . " | IP: $ip_address | URL: $request_uri | Method: $request_method | UA: $user_agent\n";
error_log($log_entry, 3, $log_file);

// Deteksi modul dari URL untuk menentukan judul yang sesuai
$module_titles = [
    'maintenance' => 'Manajemen Maintenance',
    'laporan' => 'Laporan & Statistik',
    'pengaturan' => 'Pengaturan Sistem',
    'setting' => 'Pengaturan Sistem',
    'user/admin' => 'Manajemen User Admin',
    'sparepart/laporan' => 'Laporan Sparepart',
    'api' => 'API Endpoint',
    'ajax' => 'Ajax Handler',
    'export' => 'Export Data',
    'import' => 'Import Data',
    'print' => 'Print Data'
];

$page_title = 'Halaman Tidak Ditemukan';
$page_desc = 'Halaman yang Anda cari tidak tersedia atau sedang dalam pengembangan.';
$page_type = 'default';
$progress = 0;

// Coba deteksi dari URL
foreach ($module_titles as $key => $title) {
    if (strpos($request_uri, $key) !== false) {
        $page_title = $title;
        $page_desc = "Modul {$title} sedang dalam tahap pengembangan.";
        $page_type = explode('/', $key)[0];
        $progress = 30;
        break;
    }
}

// Jika tidak terdeteksi, cek ekstensi file
$path_info = pathinfo($request_uri);
$extension = $path_info['extension'] ?? '';

if (in_array($extension, ['php', 'html', 'htm'])) {
    $page_desc = 'Halaman yang Anda tuju belum tersedia. Tim kami sedang mengembangkannya.';
}

// Tentukan BASE_URL jika belum didefinisikan
if (!defined('BASE_URL')) {
    $base_url = '/cosmar_assets/';
} else {
    $base_url = BASE_URL;
}

include '../menu/header.php';
include '../menu/sidebar.php';
include '../menu/topbar.php';
?>

<style>
    .error-container {
        min-height: 70vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 40px 20px;
    }
    .error-card {
        text-align: center;
        padding: 50px 40px;
        background: white;
        border-radius: 20px;
        box-shadow: 0 20px 35px rgba(0,0,0,0.1);
        max-width: 600px;
        width: 100%;
        margin: 0 auto;
    }
    .error-code {
        font-size: 120px;
        font-weight: 800;
        color: #4e73df;
        line-height: 1;
        margin-bottom: 10px;
        text-shadow: 5px 5px 0 rgba(78, 115, 223, 0.1);
    }
    .error-icon {
        font-size: 60px;
        color: #f6c23e;
        margin-bottom: 20px;
    }
    .error-title {
        font-size: 28px;
        font-weight: 700;
        color: #5a5c69;
        margin-bottom: 15px;
    }
    .error-subtitle {
        font-size: 16px;
        color: #858796;
        margin-bottom: 30px;
        line-height: 1.6;
    }
    .error-divider {
        margin: 30px 0;
        border-top: 1px solid #e3e6f0;
    }
    .info-list {
        text-align: left;
        background: #f8f9fc;
        border-radius: 10px;
        padding: 15px 20px;
        margin: 20px 0;
    }
    .info-list h6 {
        color: #4e73df;
        margin-bottom: 15px;
        font-weight: 600;
    }
    .info-list ul {
        list-style: none;
        padding-left: 0;
        margin-bottom: 0;
    }
    .info-list li {
        padding: 8px 0;
        color: #5a5c69;
        font-size: 13px;
    }
    .info-list li i {
        color: #4e73df;
        width: 25px;
        margin-right: 10px;
    }
    .btn-group-custom {
        margin-top: 20px;
    }
    .btn-custom {
        padding: 10px 25px;
        border-radius: 50px;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
        margin: 5px;
    }
    .btn-primary-custom {
        background: #4e73df;
        color: white;
        border: none;
    }
    .btn-primary-custom:hover {
        background: #224abe;
        transform: translateY(-2px);
        color: white;
    }
    .btn-secondary-custom {
        background: #5a5c69;
        color: white;
        border: none;
    }
    .btn-secondary-custom:hover {
        background: #3a3c45;
        transform: translateY(-2px);
        color: white;
    }
    .btn-outline-custom {
        background: transparent;
        color: #4e73df;
        border: 1px solid #4e73df;
    }
    .btn-outline-custom:hover {
        background: #4e73df;
        color: white;
        transform: translateY(-2px);
    }
    .suggestion-box {
        margin-top: 30px;
        padding-top: 20px;
        border-top: 1px solid #e3e6f0;
    }
    .suggestion-box h6 {
        color: #5a5c69;
        font-size: 14px;
    }
    .suggestion-links {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 15px;
        margin-top: 15px;
    }
    .suggestion-links a {
        color: #4e73df;
        text-decoration: none;
        font-size: 14px;
    }
    .suggestion-links a:hover {
        text-decoration: underline;
    }
    @media (max-width: 768px) {
        .error-card {
            padding: 30px 20px;
        }
        .error-code {
            font-size: 80px;
        }
        .btn-custom {
            padding: 8px 20px;
            font-size: 14px;
        }
    }
</style>

<div class="container-fluid">
    <div class="error-container">
        <div class="error-card">
            <div class="error-code">
                404
            </div>
            
            <div class="error-icon">
                <i class="fas fa-map-signs"></i>
            </div>
            
            <h2 class="error-title">
                <?= htmlspecialchars($page_title) ?>
            </h2>
            
            <p class="error-subtitle">
                <?= htmlspecialchars($page_desc) ?>
            </p>

            <div class="error-divider"></div>

            <div class="btn-group-custom">
                <a href="<?= $base_url ?>index.php" class="btn-custom btn-primary-custom">
                    <i class="fas fa-home"></i> Ke Dashboard
                </a>
                <a href="javascript:history.back()" class="btn-custom btn-secondary-custom">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
                <a href="javascript:location.reload()" class="btn-custom btn-outline-custom">
                    <i class="fas fa-sync-alt"></i> Refresh
                </a>
            </div>

            <div class="mt-4">
                <small class="text-muted">
                    <i class="fas fa-envelope"></i> Informasi lebih lanjut : 
                    <a href="mailto:it@ptcosmar.com">it@ptcosmar.com</a>
                </small>
            </div>
        </div>
    </div>
</div>

<?php include '../menu/footer.php'; ?>

<script>
$(document).ready(function() {
    // Animasi untuk error code
    $('.error-code').css({
        'animation': 'fadeInDown 0.5s ease'
    });
    
    // Track 404 ke Google Analytics (opsional)
    if (typeof gtag !== 'undefined') {
        gtag('event', '404', {
            'page_path': window.location.pathname,
            'page_title': document.title
        });
    }
});
</script>

<style>
@keyframes fadeInDown {
    from {
        opacity: 0;
        transform: translateY(-30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>

</body>
</html>