<?php
session_start();
header('Content-Type: application/json');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Metode request tidak diizinkan.']);
    exit;
}

// --- AMBIL DATA DARI JSON BODY ---
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// --- DEBUGGING: Cek data yang diterima ---
// error_log("Raw input for delete_menu: " . $input);
// error_log("Decoded data for delete_menu: " . print_r($data, true));
// error_log("JSON Error for delete_menu: " . json_last_error_msg());

if (json_last_error() !== JSON_ERROR_NONE || !isset($data['id']) || $data['id'] === '') {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => "Field 'id' kosong atau tidak valid!"]);
    exit;
}

$menuIdToDelete = (string)$data['id']; // Pastikan ID yang dicari adalah string
$xmlPath = __DIR__ . '/data/menu.xml';

if (!file_exists($xmlPath)) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'File menu.xml tidak ditemukan di: ' . $xmlPath]);
    exit;
}
if (!is_writable($xmlPath)) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'File menu.xml tidak bisa ditulis. Periksa izin folder/file "data".']);
    exit;
}

try {
    $xml = simplexml_load_file($xmlPath);
    if ($xml === false) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Gagal memuat XML. Periksa format XML.']);
        exit;
    }

    $found = false;
    $i = 0;
    foreach ($xml->menu as $menu) {
        if ((string)$menu->id === $menuIdToDelete) { // <-- PERUBAHAN: Bandingkan ID sebagai elemen anak
            unset($xml->menu[$i]); // Hapus elemen
            $found = true;
            break;
        }
        $i++;
    }

    if (!$found) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Menu dengan ID ' . $menuIdToDelete . ' tidak ditemukan.']);
        exit;
    }

    // Simpan perubahan ke file XML dengan lock
    if (file_put_contents($xmlPath, $xml->asXML(), LOCK_EX) === false) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan perubahan setelah penghapusan ke menu.xml.']);
        exit;
    }

    echo json_encode(['status' => 'success', 'message' => 'Menu berhasil dihapus.']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Kesalahan server saat menghapus: ' . $e->getMessage()]);
}
?>