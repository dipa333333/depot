<?php
session_start();

// Set response header to JSON
header('Content-Type: application/json');

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Metode request tidak diizinkan.']);
    exit();
}

// Path to your XML file
$xmlPath = __DIR__ . '/data/menu.xml'; // Sesuaikan jika lokasi berbeda

// Load XML file
if (!file_exists($xmlPath)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'File menu.xml tidak ditemukan.']);
    exit();
}
$xml = simplexml_load_file($xmlPath);

// Validate POST fields
$required_post_fields = ['nama', 'deskripsi', 'harga', 'kategori', 'status'];
foreach ($required_post_fields as $field) {
    if (!isset($_POST[$field]) || empty($_POST[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Field '$field' tidak boleh kosong!"]);
        exit();
    }
}

$nama = $_POST['nama'];
$deskripsi = $_POST['deskripsi'];
$harga = $_POST['harga'];
$kategori = $_POST['kategori'];
$status = $_POST['status'];

$gambar_nama_file = 'default.jpg'; // Default image if no upload

// Handle image upload
if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == UPLOAD_ERR_OK) {
    $gambar_tmp_name = $_FILES['gambar']['tmp_name'];
    // Generate unique file name to prevent conflicts
    $extension = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
    $gambar_nama_file = uniqid('menu_') . '.' . $extension;
    
    // Target directory for images (ensure this directory exists and is writable)
    $target_dir = __DIR__ . '/assets/img/menu/'; // Sesuaikan dengan folder penyimpanan gambar
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true); // Create directory if it doesn't exist
    }
    $target_file = $target_dir . $gambar_nama_file;

    if (!move_uploaded_file($gambar_tmp_name, $target_file)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal mengunggah file gambar.']);
        exit();
    }
}

// Generate new ID
$newId = 1;
if ($xml->menu) {
    foreach ($xml->menu as $menu) {
        if ((int)$menu->id >= $newId) {
            $newId = (int)$menu->id + 1;
        }
    }
}

// Add new menu item to XML
$newMenu = $xml->addChild('menu');
$newMenu->addChild('id', $newId);
$newMenu->addChild('nama', $nama);
$newMenu->addChild('deskripsi', $deskripsi);
$newMenu->addChild('harga', $harga);
$newMenu->addChild('kategori', $kategori);
$newMenu->addChild('status', $status);
$newMenu->addChild('gambar', $gambar_nama_file); // Save unique image filename

// Save changes to XML file
$dom = new DOMDocument('1.0', 'UTF-8');
$dom->preserveWhiteSpace = false;
$dom->formatOutput = true;
$dom->loadXML($xml->asXML());
if ($dom->save($xmlPath) === false) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gagal menyimpan data ke file XML.']);
    exit();
}

// Success response
echo json_encode([
    'success' => true,
    'message' => 'Menu berhasil ditambahkan!',
    'id' => $newId
]);

?>