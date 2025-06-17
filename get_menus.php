<?php
/**
 * get_menus.php
 * ----------------
 * Mengambil semua menu dari data/menu.xml
 * dan mengembalikannya dalam format JSON.
 */

// --- Pengaturan header ――
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *'); // CORS sederhana kalau dashboard dan PHP beda origin

ini_set('display_errors', 1); // Aktifkan error reporting untuk debugging
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$xmlPath = __DIR__ . '/data/menu.xml';   // arahkan ke lokasi file xml

// Validasi keberadaan file
if (!file_exists($xmlPath)) {
    http_response_code(404);
    echo json_encode(['error' => 'File menu.xml tidak ditemukan di: ' . $xmlPath]);
    exit;
}
if (!is_readable($xmlPath)) {
    http_response_code(500);
    echo json_encode(['error' => 'File menu.xml tidak bisa dibaca. Periksa izin.']);
    exit;
}

// Load XML
try {
    $menusXml = simplexml_load_file($xmlPath);

    if ($menusXml === false) {
        http_response_code(500);
        echo json_encode(['error' => 'Gagal memuat XML. Periksa format XML.']);
        exit;
    }

    // Konversi ke array PHP
    $menuArray = [];
    foreach ($menusXml->menu as $m) {
        $menuArray[] = [
            'id' => (string)$m->id, // <-- PERUBAHAN: Mengambil ID sebagai elemen anak
            'nama' => (string)$m->nama,
            'deskripsi' => (string)$m->deskripsi,
            'harga' => (string)$m->harga,
            'kategori' => (string)$m->kategori,
            'status' => (string)$m->status,
            'gambar' => (string)$m->gambar
        ];
    }

    echo json_encode(['status' => 'success', 'menus' => $menuArray]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Kesalahan server saat parsing XML: ' . $e->getMessage()]);
}
?>