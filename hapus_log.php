<?php
include "koneksi.php"; // Sesuaikan dengan file koneksi Anda

if (isset($_POST['hapus_log'])) {
    // Menghapus data yang lebih tua dari 7 hari
    $query = "DELETE FROM tb_log_refresh WHERE created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)";

    if ($koneksi->query($query)) {
        // Kembali ke halaman utama dengan pesan sukses
        header("Location: index.php?pesan=berhasil_dihapus");
    } else {
        echo "Gagal menghapus: " . $koneksi->error;
    }
}
