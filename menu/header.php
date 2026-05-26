<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
 
// Proteksi halaman
if (!isset($_SESSION['login'])) {
    header("Location: auth/login.php");
    exit;
}

$user_name  = $_SESSION['user_name'] ?? 'User';
$user_level = $_SESSION['user_level'] ?? 0;
?>

<?php include_once __DIR__ . '/../config/base_url.php'; ?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Cosmar Assets</title>

    <!-- Custom fonts for this template-->
    <link href="<?= BASE_URL ?>master/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="<?= BASE_URL ?>master/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= BASE_URL ?>master/img/assets/logo.png">
    
    <!-- DataTables CSS -->
    <link href="<?= BASE_URL ?>master/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">

    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <!-- Agar style sesuai SB Admin -->
    <style>
    .select2-container .select2-selection--single {
        height: 38px !important;
    }
    .select2-selection__rendered {
        line-height: 38px !important;
    }
    .select2-selection__arrow {
        height: 38px !important;
    }
    </style>

    <style>

    /* Samakan tinggi dengan form-control */
    .select2-container--bootstrap4 .select2-selection {
        height: calc(2.25rem + 2px) !important;
        border: 1px solid #d1d3e2 !important;
        border-radius: .35rem !important;
    }

    /* Teks di dalam */
    .select2-container--bootstrap4 .select2-selection__rendered {
        line-height: 2.25rem !important;
        padding-left: .75rem !important;
    }

    /* Icon panah */
    .select2-container--bootstrap4 .select2-selection__arrow {
        height: calc(2.25rem + 2px) !important;
    }

    /* Focus effect biar sama seperti input */
    .select2-container--bootstrap4.select2-container--focus .select2-selection {
        border-color: #bac8f3 !important;
        box-shadow: 0 0 0 0.2rem rgba(78,115,223,.25) !important;
    }

    </style>

    <style>
    .upload-box {
        border: 2px dashed #d1d3e2;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.3s ease;
        background: #f8f9fc;
    }

    .upload-box:hover {
        background: #eaecf4;
        border-color: #4e73df;
    }

    .upload-box.dragover {
        background: #d4e3ff;
        border-color: #4e73df;
    }
    </style>

    <style>
    .img-preview:hover {
        transform: scale(1.8);
        transition: 0.2s;
        z-index: 999;
        position: relative;
    }
    </style>

</head>

<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">