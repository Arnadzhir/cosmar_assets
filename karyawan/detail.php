<?php
include '../auth/auth.php';
allowRole([1]);

include '../config/koneksi.php';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit; 
}

$id = intval($_GET['id']);

// PERBAIKAN: Query dari tbl_karyawan
$q = mysqli_query($conn, "
    SELECT 
        k.*,
        d.dep_code,
        d.dep_name
    FROM tbl_karyawan k
    LEFT JOIN tbl_dep d ON k.dep_id = d.dep_id
    WHERE k.karyawan_id = '$id'
");

$data = mysqli_fetch_assoc($q);

if (!$data) {
    echo "<script>alert('Data tidak ditemukan');window.location='index.php';</script>";
    exit;
}

// Format gender
$gender_text = '';
if ($data['karyawan_gender'] == 'Male') {
    $gender_text = 'Laki-laki';
} elseif ($data['karyawan_gender'] == 'Female') {
    $gender_text = 'Perempuan';
} else {
    $gender_text = '-';
}

// Level badge
$karyawan_level = $data['karyawan_level'] ?? '';
if ($karyawan_level == 'Manager') {
    $level_badge = '<span class="badge badge-danger">Manager</span>';
} elseif ($karyawan_level == 'Supervisor') {
    $level_badge = '<span class="badge badge-warning">Supervisor</span>';
} elseif ($karyawan_level == 'Head') {
    $level_badge = '<span class="badge badge-info">Head</span>';
} elseif ($karyawan_level == 'Leader') {
    $level_badge = '<span class="badge badge-primary">Leader</span>';
} elseif ($karyawan_level == 'Staff') {
    $level_badge = '<span class="badge badge-secondary">Staff</span>';
} else {
    $level_badge = '<span class="badge badge-secondary">' . htmlspecialchars($karyawan_level) . '</span>';
}

$primaryData = $data;
include '../menu/header.php';
include '../menu/sidebar.php';
include '../menu/topbar.php';
$data = $primaryData;
?>

<style>
    .detail-card {
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        overflow: hidden;
    }
    .detail-header {
        background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
        color: white;
        padding: 20px 25px;
    }
    .detail-header h4 {
        margin: 0;
        font-weight: 600;
    }
    .detail-header small {
        opacity: 0.8;
        font-size: 12px;
    }
    .detail-section {
        padding: 20px 25px;
        border-bottom: 1px solid #e3e6f0;
    }
    .detail-section:last-child {
        border-bottom: none;
    }
    .detail-label {
        font-weight: 700;
        color: #4e73df;
        margin-bottom: 15px;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-left: 3px solid #4e73df;
        padding-left: 10px;
    }
    .info-row {
        display: flex;
        margin-bottom: 12px;
        padding-bottom: 8px;
        border-bottom: 1px dashed #f0f0f0;
    }
    .info-label {
        width: 140px;
        font-weight: 600;
        color: #5a5c69;
        font-size: 13px;
    }
    .info-value {
        flex: 1;
        color: #2c3e50;
        font-size: 13px;
    }
    .info-value strong {
        color: #4e73df;
    }
    .profile-avatar {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        background-color: #4e73df;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 15px;
        border: 3px solid white;
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }
    .profile-avatar i {
        font-size: 60px;
        color: white;
    }
    @media (max-width: 768px) {
        .info-row {
            flex-direction: column;
        }
        .info-label {
            width: 100%;
            margin-bottom: 5px;
        }
    }
</style>

<div class="container-fluid">

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 text-gray-800">
            <i class="fas fa-user-circle"></i> Detail Karyawan
        </h1>
        <div>
            <a href="edit.php?id=<?= $id ?>" class="btn btn-warning btn-sm">
                <i class="fas fa-edit"></i> Edit Karyawan
            </a>
            <a href="index.php" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Kolom Foto (Avatar) -->
        <div class="col-md-4">
            <div class="detail-card">
                <div class="detail-header">
                    <h5 class="mb-0">
                        <i class="fas fa-image"></i> Foto Profil
                    </h5>
                </div>
                <div class="detail-section text-center">
                    <div class="profile-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="mt-2">
                        <?= $level_badge ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kolom Informasi Karyawan -->
        <div class="col-md-8">
            <div class="detail-card">
                <div class="detail-header">
                    <h4 class="mb-0">
                        <i class="fas fa-user"></i> <?= htmlspecialchars($data['karyawan_name']) ?>
                    </h4>
                    <small>ID Karyawan: <?= $data['karyawan_id'] ?></small>
                </div>

                <!-- Informasi Umum -->
                <div class="detail-section">
                    <h6 class="detail-label">
                        <i class="fas fa-info-circle"></i> Informasi Umum
                    </h6>
                    <div class="info-row">
                        <div class="info-label">ID Karyawan</div>
                        <div class="info-value"><strong><?= htmlspecialchars($data['karyawan_id']) ?></strong></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Nama Karyawan</div>
                        <div class="info-value"><?= htmlspecialchars($data['karyawan_name']) ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Jenis Kelamin</div>
                        <div class="info-value"><?= $gender_text ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">No. Telepon</div>
                        <div class="info-value">
                            <?php if (!empty($data['karyawan_no'])): ?>
                                <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $data['karyawan_no']) ?>" target="_blank" class="text-success">
                                    <i class="fab fa-whatsapp"></i> <?= htmlspecialchars($data['karyawan_no']) ?>
                                </a>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Informasi Departemen -->
                <div class="detail-section">
                    <h6 class="detail-label">
                        <i class="fas fa-building"></i> Informasi Departemen
                    </h6>
                    <div class="info-row">
                        <div class="info-label">Kode Departemen</div>
                        <div class="info-value"><strong><?= htmlspecialchars($data['dep_code'] ?? '-') ?></strong></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Nama Departemen</div>
                        <div class="info-value"><?= htmlspecialchars($data['dep_name'] ?? '-') ?></div>
                    </div>
                </div>

                <!-- Informasi Level -->
                <div class="detail-section">
                    <h6 class="detail-label">
                        <i class="fas fa-chart-line"></i> Informasi Level
                    </h6>
                    <div class="info-row">
                        <div class="info-label">Level Jabatan</div>
                        <div class="info-value"><?= $level_badge ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<?php include '../menu/footer.php'; ?>

</body>
</html>