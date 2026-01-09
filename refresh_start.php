<?php
header("Content-Type: application/json");
include 'koneksi.php';

$zona = intval($_POST['zona'] ?? 0);
$user = $_SERVER['REMOTE_ADDR'];

if ($zona <= 0) {
    echo json_encode(['success' => false, 'message' => 'Zona tidak valid']);
    exit;
}

$query = ($zona == 99)
    ? "SELECT DISTINCT ip_address FROM tb_ip_server_pick"
    : "SELECT DISTINCT ip_address FROM tb_ip_server_pick WHERE zona=$zona";

$res = mysqli_query($koneksi, $query);
$ipList = [];
while ($r = mysqli_fetch_assoc($res)) {
    $ipList[] = $r['ip_address'];
}

$total = count($ipList);
if ($total === 0) {
    echo json_encode(['success' => false, 'message' => 'Tidak ada IP']);
    exit;
}

mysqli_query($koneksi, "DELETE FROM tb_refresh_progress WHERE user='$user'");

mysqli_query($koneksi, "
    INSERT INTO tb_refresh_progress (user,zona,total,success,fail,status)
    VALUES ('$user','$zona','$total',0,0,'running')
");

echo json_encode(['success' => true, 'message' => 'Proses dimulai']);
flush();

// ==========================
// PROSES REFRESH SEBENARNYA
// ==========================
$success = 0;
$fail = 0;

foreach ($ipList as $ip) {

    // LISTENER CHECK
    $listenerUrl = "http://$ip:5000/listener-check";
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $listenerUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_USERAGENT => "PHP-Refresh-Agent"
    ]);
    $listenerResponse = curl_exec($ch);
    $listenerHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($listenerHttpCode !== 200 || !$listenerResponse) {
        $fail++;
        mysqli_query($koneksi, "
            UPDATE tb_refresh_progress
            SET fail='$fail', last_ip='$ip'
            WHERE user='$user'
        ");
        continue;
    }

    $listenerData = json_decode($listenerResponse, true);
    if (is_array($listenerData) && isset($listenerData['success']) && !$listenerData['success']) {
        $fail++;
        mysqli_query($koneksi, "
            UPDATE tb_refresh_progress
            SET fail='$fail', last_ip='$ip'
            WHERE user='$user'
        ");
        continue;
    }

    // REFRESH
    $refreshUrl = "http://$ip:5000/refresh";
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $refreshUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 40,
        CURLOPT_USERAGENT => "PHP-Refresh-Agent"
    ]);
    curl_exec($ch);
    $refreshHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($refreshHttpCode === 200) {
        $success++;
    } else {
        $fail++;
    }

    mysqli_query($koneksi, "
        UPDATE tb_refresh_progress
        SET success='$success', fail='$fail', last_ip='$ip'
        WHERE user='$user'
    ");
}

mysqli_query($koneksi, "
    UPDATE tb_refresh_progress
    SET status='done'
    WHERE user='$user'
");

mysqli_query($koneksi, "
    INSERT INTO tb_log_refresh (zona,user,created_at)
    VALUES ('$zona','$user',NOW())
");
