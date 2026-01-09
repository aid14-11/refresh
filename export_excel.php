<?php
// Load autoload dari composer
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// 1. Koneksi ke Database (Sesuaikan detailnya)
$host = "localhost";
$user = "root";
$pass = "";
$db   = "support"; // Ganti dengan nama database Anda

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// 2. Inisialisasi Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// 3. Membuat Header Tabel (Baris 1)
$sheet->setCellValue('A1', 'Zona');
$sheet->setCellValue('B1', 'User / IP Address');
$sheet->setCellValue('C1', 'Waktu');

// Opsional: Menebalkan Header
$sheet->getStyle('A1:C1')->getFont()->setBold(true);

// Definisi style border tipis untuk semua sisi
$styleArray = [
    'borders' => [
        'allBorders' => [
            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
            'color' => ['argb' => '000000'],
        ],
    ],
];

// --- EDIT BAGIAN INI ---

// 1. Tangkap parameter tanggal dari URL
$filter_tgl = isset($_GET['tanggal']) ? $_GET['tanggal'] : '';

// 2. Susun Query SQL berdasarkan filter
if (!empty($filter_tgl)) {
    // Jika ada tanggal, filter hanya tanggal tersebut
    // Menggunakan DATE(created_at) karena kolom Anda bertipe timestamp
    $query = "SELECT zona, user, created_at FROM tb_log_refresh 
              WHERE DATE(created_at) = '$filter_tgl' 
              ORDER BY created_at DESC";
} else {
    // Jika tidak ada filter, ambil semua atau batasi (opsional)
    $query = "SELECT zona, user, created_at FROM tb_log_refresh ORDER BY created_at DESC";
}

$result = $conn->query($query);

// --- AKHIR BAGIAN EDIT ---

$rowNum = 2; // Mulai mengisi data dari baris ke-2
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $sheet->setCellValue('A' . $rowNum, $row['zona']);
        $sheet->setCellValue('B' . $rowNum, $row['user']);
        $sheet->setCellValue('C' . $rowNum, $row['created_at']);
        $rowNum++;
    }
}

// Menghitung baris terakhir (dikurangi 1 karena loop berhenti setelah data habis)
$lastRow = $rowNum - 1;

// Terapkan border dari cell A1 sampai C(baris terakhir)
$sheet->getStyle('A1:C' . $lastRow)->applyFromArray($styleArray);

// 5. Atur Ukuran Kolom Otomatis (Agar tidak terpotong)
foreach (range('A', 'C') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}
if (ob_get_contents()) ob_end_clean();
// 6. Header untuk Download Langsung
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Log_Aktivitas_' . date('Y-m-d') . '.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
// Mengatur agar kolom A, B, dan C otomatis lebar sesuai isi
foreach (range('A', 'C') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Mengatur agar semua teks di header (Baris 1) rata tengah
$sheet->getStyle('A1:C1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

// Memberikan warna latar belakang pada header (Abu-abu muda)
$sheet->getStyle('A1:C1')->getFill()
    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
    ->getStartColor()->setARGB('F2F2F2');

// Contoh jika kolom C adalah waktu
$sheet->getStyle('C2:C' . ($rowNum - 1))
    ->getNumberFormat()
    ->setFormatCode('DD/MM HH:MM');

$writer->save('php://output');
exit;
