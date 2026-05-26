<?php  
session_start();

session_destroy();

header("Location: /cosmar_assets/auth/login.php");

exit;