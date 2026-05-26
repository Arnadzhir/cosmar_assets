<?php
include '../auth/auth.php';
allowRole([1]);

include '../config/koneksi.php';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id = intval($_GET['id']);

$q = mysqli_query($conn, "
    SELECT 
        u.*,
        d.dep_code,
        d.dep_name
    FROM tbl_user u
    LEFT JOIN tbl_dep d ON u.dep_id = d.dep_id
    WHERE u.user_id = '$id'
");

$data = mysqli_fetch_assoc($q);

if (!$data) {
    echo "<script>alert('Data tidak ditemukan');window.location='index.php';</script>";
    exit;
}

// Format level user menjadi teks
$level_text = '';
if ($data['user_level'] == 1) {
    $level_text = 'Administrator';
} elseif ($data['user_level'] == 2) {
    $level_text = 'Operator';
} elseif ($data['user_level'] == 3) {
    $level_text = 'User';
} else {
    $level_text = 'Unknown';
}

// Format gender
$gender_text = '';
if ($data['user_gender'] == 'Male') {
    $gender_text = 'Laki-laki';
} elseif ($data['user_gender'] == 'Female') {
    $gender_text = 'Perempuan';
} else {
    $gender_text = '-';
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
    .profile-image {
        width: 200px;
        height: 200px;
        object-fit: cover;
        border-radius: 10px;
        border: 1px solid #ddd;
        margin-bottom: 15px;
    }
    .level-badge {
        display: inline-block;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    .level-admin {
        background-color: #e74a3b;
        color: white;
    }
    .level-operator {
        background-color: #f6c23e;
        color: #212529;
    }
    .level-user {
        background-color: #1cc88a;
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
            <i class="fas fa-user-circle"></i> Detail User Akses
        </h1>
        <div>
            <a href="edit.php?id=<?= $id ?>" class="btn btn-warning btn-sm">
                <i class="fas fa-edit"></i> Edit User
            </a>
            <a href="index.php" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Kolom Foto -->
        <div class="col-md-4">
            <div class="detail-card">
                <div class="detail-header">
                    <h5 class="mb-0">
                        <i class="fas fa-image"></i> Foto Profil
                    </h5>
                </div>
                <div class="detail-section text-center">
                    <?php
                    $img = !empty($data['user_image']) 
                        ? "../master/img/user/" . $data['user_image'] 
                        : "../master/img/no-image.png";
                    ?>
                    <img src="<?= $img ?>" class="profile-image" alt="Foto Profil"
                         onerror="this.src='../master/img/no-image.png'">
                    <div class="mt-2">
                        <span class="level-badge <?= 
                            $data['user_level'] == 1 ? 'level-admin' : 
                            ($data['user_level'] == 2 ? 'level-operator' : 'level-user') 
                        ?>">
                            <i class="fas <?= 
                                $data['user_level'] == 1 ? 'fa-crown' : 
                                ($data['user_level'] == 2 ? 'fa-tools' : 'fa-user') 
                            ?>"></i> <?= $level_text ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kolom Informasi User -->
        <div class="col-md-8">
            <div class="detail-card">
                <div class="detail-header">
                    <h4 class="mb-0">
                        <i class="fas fa-user"></i> <?= htmlspecialchars($data['user_name']) ?>
                    </h4>
                    <small>User ID: <?= $data['user_id'] ?></small>
                </div>

                <!-- Informasi Umum -->
                <div class="detail-section">
                    <h6 class="detail-label">
                        <i class="fas fa-info-circle"></i> Informasi Umum
                    </h6>
                    <div class="info-row">
                        <div class="info-label">User ID</div>
                        <div class="info-value"><strong><?= htmlspecialchars($data['user_id']) ?></strong></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Nama User</div>
                        <div class="info-value"><?= htmlspecialchars($data['user_name']) ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Email</div>
                        <div class="info-value">
                            <a href="mailto:<?= $data['user_mail'] ?>" class="text-primary">
                                <i class="fas fa-envelope"></i> <?= htmlspecialchars($data['user_mail']) ?>
                            </a>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Jenis Kelamin</div>
                        <div class="info-value"><?= $gender_text ?></div>
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

                <!-- Informasi Hak Akses -->
                <div class="detail-section">
                    <h6 class="detail-label">
                        <i class="fas fa-lock"></i> Informasi Hak Akses
                    </h6>
                    <div class="info-row">
                        <div class="info-label">Level Akses</div>
                        <div class="info-value">
                            <span class="level-badge <?= 
                                $data['user_level'] == 1 ? 'level-admin' : 
                                ($data['user_level'] == 2 ? 'level-operator' : 'level-user') 
                            ?>">
                                <?= $level_text ?>
                            </span>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Keterangan</div>
                        <div class="info-value">
                            <?php if ($data['user_level'] == 1): ?>
                                <i class="fas fa-check-circle text-success"></i> Akses penuh ke semua fitur sistem
                            <?php elseif ($data['user_level'] == 2): ?>
                                <i class="fas fa-check-circle text-success"></i> Dapat mengelola data, namun tidak dapat mengubah pengaturan sistem
                            <?php else: ?>
                                <i class="fas fa-check-circle text-success"></i> Hanya dapat melihat data milik sendiri
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<?php include '../menu/footer.php'; ?>

</body>
</html>