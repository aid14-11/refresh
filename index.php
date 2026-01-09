<?php
include "koneksi.php";

$tanggal = $_GET['tanggal'] ?? '';
$tabAktif = $_GET['tab'] ?? 'refresh';

/* =========================
   AMBIL LIST TANGGAL (ringan)
========================= */
$qTanggal = mysqli_query($koneksi, "
    SELECT DATE(created_at) AS tanggal
    FROM tb_log_refresh
    GROUP BY DATE(created_at)
    ORDER BY tanggal DESC
");

/* =========================
 DATA RIWAYAT (HANYA JIKA TAB RIWAYAT)
========================= */
$sql = "SELECT zona, user, created_at FROM tb_log_refresh";
if (!empty($tanggal)) {
    $sql .= " WHERE DATE(created_at) = '$tanggal'";
}
$sql .= " ORDER BY created_at DESC";

$data = mysqli_query($koneksi, $sql);

/* =========================
   TOTAL REFRESH
========================= */
if (!empty($tanggal)) {
    $qTotal = mysqli_query($koneksi, "
        SELECT COUNT(*) AS total
        FROM tb_log_refresh
        WHERE DATE(created_at) = '$tanggal'
    ");
} else {
    $qTotal = mysqli_query($koneksi, "
        SELECT COUNT(*) AS total
        FROM tb_log_refresh
        WHERE DATE(created_at) = CURDATE()
    ");
}
$totalRefresh = mysqli_fetch_assoc($qTotal)['total'];

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="assets/icon.svg">
    <title>Refresh Zona</title>
    <link href="assets/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/temaku.css">
</head>

<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8">

                <div class="card main-card">
                    <div class="card-header card-header-main py-3 d-flex justify-content-between align-items-center">
                        <span><i class="fa-solid fa-sync-alt me-2"></i> REFRESH ZONA</span>
                        <small class="opacity-75">( Utamakan Sholat )</small>
                    </div>

                    <div class="card-body p-0">
                        <ul class="nav nav-tabs nav-fill" id="myTab" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link py-3 <?= ($tabAktif == 'refresh') ? 'active' : ''; ?>" data-bs-toggle="tab" href="#tab-refresh">
                                    <i class="fa-solid fa-paper-plane me-2"></i>Refresh
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link py-3 <?= ($tabAktif == 'riwayat') ? 'active' : ''; ?>" data-bs-toggle="tab" href="#tab-riwayat">
                                    <i class="fa-solid fa-history me-2"></i>Riwayat
                                </a>
                            </li>
                        </ul>
                        <div class="tab-content p-4">
						                  <!-- ================= REFRESH ================= -->
										  
                            <div class="tab-pane fade <?= ($tabAktif == 'refresh') ? 'show active' : ''; ?>" id="tab-refresh">
                                <div class="alert alert-info border-0 bg-light-subtle mb-4">
                                    <small class="text-muted">
                                        <i class="fa-solid fa-circle-info me-1 text-primary"></i>
                                        Gunakan <strong>Key 99</strong> untuk me-refresh <strong>SEMUA ZONA</strong> sekaligus.
                                    </small>
                                </div>

                                <form id="refreshForm" class="row g-3">
                                    <div class="col-sm-8">
                                        <div class="input-group">
                                            <span class="input-group-text bg-white"><i class="fa-solid fa-map-location-dot text-muted"></i></span>
                                            <input type="number" id="zona" name="zona" class="form-control form-control-lg" placeholder="Nomor zona (1-110)" min="1" max="110" required autofocus>
                                        </div>
                                    </div>
                                    <div class="col-sm-4">
                                        <button type="submit" id="btnRefresh" class="btn btn-primary btn-lg w-100 btn-refresh">
                                            <span id="btnText">Refresh</span>
                                            <span id="btnSpinner" class="spinner-border spinner-border-sm d-none"></span>
                                        </button>
                                    </div>
                                </form>
                                <div id="notifBox" class="alert mt-4 d-none"></div>
                            </div>
							           <!-- ================= RIWAYAT ================= -->
									   
                            <div class="tab-pane fade <?= ($tabAktif == 'riwayat') ? 'show active' : ''; ?>" id="tab-riwayat">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="mb-0 fw-bold">Log Aktivitas</h6>
                                    <form method="GET" id="filterForm">
                                        <input type="hidden" name="tab" value="riwayat">
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <select name="tanggal" id="tanggal_export" class="form-select form-select-sm" onchange="this.form.submit()" style="width: auto;">
                                                <option value="">Pilih Tanggal</option>
                                                <?php while ($t = mysqli_fetch_assoc($qTanggal)) : ?>
                                                    <option value="<?= $t['tanggal']; ?>" <?= ($tanggal == $t['tanggal']) ? 'selected' : ''; ?>>
                                                        <?= date('d M Y', strtotime($t['tanggal'])); ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            </select>
                                            <a href="javascript:void(0)" onclick="prosesExport()" title="Export Excel"
                                                style="display: flex; align-items: center; justify-content: center; background-color: #1D6F42; color: white; width: 31px; height: 31px; border-radius: 5px; text-decoration: none; margin-left: 5px;">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                                    <path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z" />
                                                    <path d="M4.5 12a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5zm0-2a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5zm0-2a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5z" />
                                                </svg>
                                            </a>
                                        </div>
                                    </form>
                                </div>
                                <div class="table-container border">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="sticky-thead">
                                            <tr>
                                                <th class="py-3 ps-3">Zona</th>
                                                <th class="py-3">User</th>
                                                <th class="py-3">Waktu</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (mysqli_num_rows($data) > 0) : ?>
                                                <?php while ($row = mysqli_fetch_assoc($data)) : ?>
                                                    <tr>
                                                        <td class="ps-3"><span class="badge bg-secondary-subtle text-secondary border">Zona <?= $row['zona']; ?></span></td>
                                                        <td class="fw-medium"><?= htmlspecialchars($row['user']); ?></td>
                                                        <td class="text-muted small"><?= date('d/m H:i', strtotime($row['created_at'])); ?></td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            <?php else : ?>
                                                <tr>
                                                    <td colspan="3" class="text-center py-5 text-muted">
                                                        <i class="fa-solid fa-folder-open d-block mb-2 fs-2 opacity-25"></i>
                                                        Tidak ada data ditemukan
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php
											// Menghapus data yang lebih tua dari 7 hari
                                if (isset($_GET['pesan']) && $_GET['pesan'] == 'berhasil_dihapus'): ?>
                                    <div id="notifikasi-hapus" style="color: green; margin-bottom: 10px; border: 1px solid green; padding: 5px; border-radius: 5px; background-color: #e8f5e9;">
                                        ✅ Log lama (lebih dari 7 hari) telah berhasil dibersihkan!
                                    </div>

                                    <script>
                                        setTimeout(function() {
                                            var element = document.getElementById("notifikasi-hapus");
                                            if (element) {
                                                element.style.display = "none";
                                            }
                                            // Opsional: Membersihkan URL agar kata '?pesan=berhasil_dihapus' hilang
                                            window.history.replaceState({}, document.title, window.location.pathname);
                                        }, 3000); // 3000 milidetik = 3 detik
                                    </script>
                                <?php endif; ?>
                                           <!-- ================= HAPUS LOG ================= -->
										   
                                <form action="hapus_log.php" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus semua log yang sudah lebih dari 1 minggu?')">
                                    <form action="hapus_manual.php" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus semua log yang sudah lebih dari 1 minggu?')">
                                        <button type="submit" name="hapus_log" style="background-color: #d9534f; color: white; padding: 4px 10px; border: none; border-radius: 4px; cursor: pointer; font-size: 12px;">
                                            Hapus Log > 7 Hari
                                        </button>
                                    </form>
                                </form>
                                <div class="mt-4 text-center">
                                    <span class="badge bg-warning text-dark badge-custom shadow-sm">
                                        <i class="fa-solid fa-chart-line me-1"></i>
                                        Total: <strong><?= $totalRefresh; ?></strong> Refresh <?= !empty($tanggal) ? '(' . date('d/m', strtotime($tanggal)) . ')' : '(Hari Ini)'; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-light border-0 py-3 text-center">
                        <small class="text-muted">© <?= date('Y'); ?> <strong>G305 Gresik</strong> • Final Version</small>
                    </div>
                </div>
            </div>
            <script src="assets/bootstrap/bootstrap.bundle.min.js"></script>
            <script src="assets/support.js"></script>
            <script src="assets/script.js" defer></script>

</body>

</html>