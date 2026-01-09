const formRefresh = document.getElementById("refreshForm");
const btnRefresh  = document.getElementById("btnRefresh");
const btnText     = document.getElementById("btnText");
const btnSpinner  = document.getElementById("btnSpinner");
const notifBox    = document.getElementById("notifBox");
const zonaInput   = document.getElementById("zona");

let pollingTimer = null;
let notifTimer = null;


formRefresh?.addEventListener("submit", (e) => {
    e.preventDefault();

    const zona = zonaInput.value.trim();
    if (!zona) return;

    startLoading();

    if (zona === "99") {
        startPolling(zona);
    } else {
        startNormalRefresh(zona);
    }
});

async function startNormalRefresh(zona) {
    try {
        const fd = new FormData();
        fd.append("zona", zona);

        const res = await fetch("refresh.php", { method: "POST", body: fd });
        const data = await res.json();

        if (data.success) {
            showNotification(`Zona ${zona} selesai di-refresh!`, "success");
        } else {
            showNotification(`Zona ${zona} gagal di-refresh!`, "danger");
        }
    } catch {
        showNotification("Koneksi ke server gagal!", "danger");
    } finally {
        stopLoading();
    }
}

function startLoading() {
    if (notifTimer) {
        clearTimeout(notifTimer);
        notifTimer = null;
    }

    notifBox.className = "alert alert-info mt-3";
    notifBox.textContent = "Mohon tunggu, permintaan sedang diproses.";
    notifBox.classList.remove("d-none");

    btnRefresh.disabled = true;
    btnSpinner.classList.remove("d-none");
    btnText.textContent = "Memproses...";
}


function stopLoading() {
    btnRefresh.disabled = false;
    btnSpinner.classList.add("d-none");
    btnText.textContent = "Refresh";
    zonaInput.value = "";
    zonaInput.focus();
}


function showNotification(message, type = "info", duration = 5000) {
    // 1. Bersihkan timer lama jika ada agar tidak bentrok
    if (notifTimer) {
        clearTimeout(notifTimer);
        notifTimer = null;
    }

    // 2. Set tampilan
    notifBox.className = `alert alert-${type} mt-3`;
    notifBox.style.whiteSpace = "pre-line"; // Agar \n berfungsi
    notifBox.textContent = message;
    notifBox.classList.remove("d-none");

    // 3. Jalankan timer sembunyi otomatis
    if (duration > 0) {
        notifTimer = setTimeout(() => {
            notifBox.classList.add("d-none");
            notifTimer = null;
        }, duration);
    }
}
//  Fungsi Refresh ALL Zona
function startPolling(zona) {
    showNotification("Semua Zona akan Di Refresh...!", "info", 0); // 0 = jangan sembunyi dulu

    fetch("refresh_start.php", {
        method: "POST",
        body: new URLSearchParams({ zona })
    }).catch(() => showNotification("Gagal memulai proses server", "danger"));

    pollingTimer = setInterval(async () => {
        try {
            const res = await fetch("refresh_progress.php");
            const data = await res.json();

            if (!data.active) return;

            // Update isi pesan tanpa menutup notif (durasi 0)
            const progressMsg = `[ Refresh ALL Zona Start... ]\n` +
                                `${data.success}/${data.total} Selesai\n` +
                                (data.last_ip ? `IP: ${data.last_ip}` : "");
            
            showNotification(progressMsg, "info", 0);

            if (data.status === "done") {
                clearInterval(pollingTimer);
                stopLoading();
                // Selesai! Beri durasi 5 detik sebelum hilang
                showNotification(progressMsg + "\n\nSELESAI!", "success", 5000);
            }
        } catch {
            clearInterval(pollingTimer);
            stopLoading();
            showNotification("Gagal mengambil progress dari server", "danger");
        }
    }, 1000);
}