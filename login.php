<?php
session_start();

$username = $_POST['username'];
$password = $_POST['password'];

// Data login sementara
$valid_user = "admin";
$valid_pass = "1234";

if ($username === $valid_user && $password === $valid_pass) {
    $_SESSION['login'] = true;
    header("Location: panel_admin.html");
    exit;
} else {
    // Arahkan kembali ke halaman login dengan parameter error
    header("Location: login_admin.html?error=invalid_credentials"); // Tambahkan parameter lebih spesifik
    exit;
}
?>