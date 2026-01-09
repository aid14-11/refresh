function prosesExport() {
    // Ambil nilai dari dropdown tanggal_export
    const tgl = document.getElementById('tanggal_export').value;
    
    if (!tgl) {
        alert("Silakan pilih tanggal terlebih dahulu!");
        return;
    }

    // Arahkan ke file export_excel.php dengan parameter tanggal
    window.location.href = `export_excel.php?tanggal=${tgl}`;
}


function updateWaktu() {
    const sekarang = new Date();
    
    // Pengaturan format lokal Indonesia
    const opsi = { 
        day: '2-digit', 
        month: 'long', 
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false
    };

    const formatter = new Intl.DateTimeFormat('id-ID', opsi);
    let hasil = formatter.format(sekarang);
    
    // Mengganti tanda titik (default ID) menjadi titik dua untuk jam
    hasil = hasil.replace(/\./g, ':'); 

    document.getElementById('tanggal-jam').innerHTML = hasil;
}

// Jalankan fungsi setiap 1 detik (1000 milidetik)
setInterval(updateWaktu, 1000);

// Panggil sekali saat halaman pertama kali dibuka
updateWaktu();