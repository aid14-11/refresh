<?php
header("Content-Type: application/json");
include "koneksi.php";

$result = mysqli_query($koneksi, "
    SELECT zona, user, created_at 
    FROM tb_log_refresh 
    ORDER BY created_at DESC 
    LIMIT 20
");

$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
}

echo json_encode($data);
