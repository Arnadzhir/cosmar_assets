<?php
session_start();
require_once '../config/koneksi.php'; // sesuaikan nama file koneksi

// Cegah akses langsung
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit;
}

// Ambil & sanitasi input
$user_id       = mysqli_real_escape_string($conn, $_POST['user_id']);
$user_password = $_POST['user_password'];

// Validasi kosong
if (empty($user_id) || empty($user_password)) {
    $_SESSION['login_error'] = "ID Karyawan dan Password wajib diisi.";
    header("Location: login.php");
    exit;
}

// Query user
$sql = "
    SELECT 
        u.user_id,
        u.user_password,
        u.user_name,
        d.dep_id,
        u.user_mail,
        u.user_gender,
        u.user_image,
        u.user_level,
        d.dep_code,
        d.dep_name
    FROM tbl_user u
    LEFT JOIN tbl_dep d ON d.dep_id = u.dep_id
    WHERE u.user_id = '$user_id'
    LIMIT 1
";

$query = mysqli_query($conn, $sql);

if (mysqli_num_rows($query) === 1) {

    $user = mysqli_fetch_assoc($query);

    // Verifikasi password hash
    if (password_verify($user_password, $user['user_password'])) {

        // Regenerasi session ID (security)
        session_regenerate_id(true);

        // Simpan session
        $_SESSION['login']      = true;
        $_SESSION['user_id']    = $user['user_id'];
        $_SESSION['user_name']  = $user['user_name'];
        $_SESSION['user_level'] = $user['user_level'];
        $_SESSION['dep_id']     = $user['dep_id'];
        $_SESSION['dep_code']   = $user['dep_code'];
        $_SESSION['dep_name']   = $user['dep_name'];
        $_SESSION['user_image'] = $user['user_image'];

        // Redirect berdasarkan level
        switch ($user['user_level']) {
            case 1: // Administrator
                header("Location: ../index.php");
                break;

            case 2: // Operator
                header("Location: ../index.php");
                break;

            case 3: // User
                header("Location: ../index.php");
                break;

            default:
                $_SESSION['login_error'] = "Level user tidak dikenali.";
                header("Location: login.php");
        }
        exit;

    } else {
        $_SESSION['login_error'] = "Password salah.";
        header("Location: login.php");
        exit;
    }

} else {
    $_SESSION['login_error'] = "ID Karyawan tidak terdaftar.";
    header("Location: login.php");
    exit;
}
