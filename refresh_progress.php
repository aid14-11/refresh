<?php
header("Content-Type: application/json");
include 'koneksi.php';

$user = $_SERVER['REMOTE_ADDR'];

$q = mysqli_query($koneksi, "
    SELECT * FROM tb_refresh_progress
    WHERE user='$user'
    ORDER BY id DESC LIMIT 1
");

if (!$row = mysqli_fetch_assoc($q)) {
    echo json_encode(['active' => false]);
    exit;
}

echo json_encode([
    'active' => true,
    'zona'   => $row['zona'],
    'total'  => $row['total'],
    'success' => $row['success'],
    'fail'   => $row['fail'],
    'last_ip' => $row['last_ip'],
    'status' => $row['status']
]);
