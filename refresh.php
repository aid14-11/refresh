<?php
header("Content-Type: application/json");
include 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Method tidak diizinkan'
    ]);
    exit;
}

$zona = isset($_POST['zona']) ? intval($_POST['zona']) : 0;
$user = $_SERVER['REMOTE_ADDR'];

if ($zona <= 0) {
    echo json_encode([
        'success' => false,
        'message' => "Pastikan dulu zona nya!"
    ]);
    exit;
}

if ($zona != 99 && ($zona < 1 || $zona > 110)) {
    echo json_encode([
        'success' => false,
        'message' => "Zona tidak valid."
    ]);
    exit;
}

if ($zona == 99) {
    $query = "SELECT DISTINCT ip_address FROM tb_ip_server_pick";
} else {
    $query = "SELECT DISTINCT ip_address FROM tb_ip_server_pick WHERE zona = $zona";
}

$result = mysqli_query($koneksi, $query);

$ipList = [];
while ($row = mysqli_fetch_assoc($result)) {
    $ipList[] = trim($row['ip_address']);
}

if (empty($ipList)) {
    echo json_encode([
        'success' => false,
        'message' => "Tidak ada IP ditemukan."
    ]);
    exit;
}

$notifDetail = ($zona == 99)
    ? "[ SEMUA ZONA ]\n"
    : "[ Zona $zona ]\n";

$successCount = 0;
$failCount = 0;

foreach ($ipList as $ip) {

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
        $failCount++;
        $notifDetail .= "❌ $ip - Listener tidak merespon\n";
        continue;
    }

    $listenerData = json_decode($listenerResponse, true);
    if (is_array($listenerData) && isset($listenerData['success']) && !$listenerData['success']) {
        $failCount++;
        $notifDetail .= "❌ $ip - Listener error: " . ($listenerData['message'] ?? 'Unknown') . "\n";
        continue;
    }

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
        $successCount++;
        $notifDetail .= "✅ $ip - Refresh sukses\n";
    } else {
        $failCount++;
        $notifDetail .= "❌ $ip - Refresh gagal (HTTP $refreshHttpCode)\n";
    }
}

if ($successCount > 0) {
    $zonaLog = ($zona == 99) ? 99 : $zona;
    $zonaEscaped = mysqli_real_escape_string($koneksi, $zonaLog);
    $userEscaped = mysqli_real_escape_string($koneksi, $user);

    mysqli_query($koneksi, "
        INSERT INTO tb_log_refresh (zona, user, created_at)
        VALUES ('$zonaEscaped', '$userEscaped', NOW())
    ");
}

$notifDetail .= "\n[Sukses: $successCount | Gagal: $failCount]\n[Selesai]";

$overallSuccess = ($zona == 99)
    ? ($successCount > 0)
    : ($successCount > 0 && $failCount === 0);

echo json_encode([
    'success' => $overallSuccess,
    'message' => $notifDetail
]);
