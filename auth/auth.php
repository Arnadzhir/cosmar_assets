<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include __DIR__ . '/../config/koneksi.php';

/* =========================
   CEK LOGIN
========================= */
if (!isset($_SESSION['login'])) {
    header("Location: ../auth/login.php");
    exit;
}

/* =========================
   AMBIL DATA USER LOGIN
========================= */
$user_id    = $_SESSION['user_id'];
$user_level = $_SESSION['user_level'];

/* =========================
   FUNCTION CEK ROLE
========================= */
function allowRole($roles = []) {
    global $user_level;

    if (!in_array($user_level, $roles)) {
        echo "<script>
                alert('Anda tidak memiliki akses ke halaman ini');
                window.location='../index.php';
              </script>";
        exit;
    }
}

/* =========================
   FUNCTION FILTER DATA
========================= */
function ownershipFilter($column = 'p.user_id') {
    global $user_level, $user_id;

    // Admin & Operator bebas
    if ($user_level == 1 || $user_level == 2) {
        return "";
    }

    // User biasa hanya data miliknya
    return " AND $column = '$user_id' ";
}
