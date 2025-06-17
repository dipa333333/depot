<?php
session_start();
header('Content-Type: application/json');

// Check if request method is POST (FormData always sends POST)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Metode request tidak diizinkan.']);
    exit;
}

// Path to your XML file
$xmlPath = __DIR__ . '/data/menu.xml'; // Sesuaikan jika lokasi berbeda

if (!file_exists($xmlPath)) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'File XML tidak ditemukan.']);
    exit;
}

$xml = simplexml_load_file($xmlPath);
$found = false;

// Validate input
$required = ['id', 'nama', 'deskripsi', 'harga', 'kategori', 'status'];
foreach ($required as $field) {
    if (!isset($_POST[$field])) { // Use !isset for robustness
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => "Field '$field' kosong!"]);
        exit;
    }
}

$id = $_POST['id'];
$nama = $_POST['nama'];
$deskripsi = $_POST['deskripsi'];
$harga = $_POST['harga'];
$kategori = $_POST['kategori'];
$status = $_POST['status'];
$current_gambar = isset($_POST['current_gambar']) ? $_POST['current_gambar'] : ''; // Ambil nama gambar lama jika ada

// Find the menu item by ID
foreach ($xml->menu as $menu) {
    if ((string)$menu->id === $id) {
        $menu->nama      = $nama;
        $menu->deskripsi = $deskripsi;
        $menu->harga     = $harga;
        $menu->kategori  = $kategori;
        $menu->status    = $status;

        // --- Handle image upload for edit ---
        $gambar_nama_file = (string)$menu->gambar; // Default to existing image

        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == UPLOAD_ERR_OK) {
            $gambar_tmp_name = $_FILES['gambar']['tmp_name'];
            $extension = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
            $new_gambar_name = uniqid('menu_') . '.' . $extension; // Generate unique name for new image
            
            $target_dir = __DIR__ . '/assets/img/menu/'; // Sesuaikan
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            $target_file = $target_dir . $new_gambar_name;

            // Delete old image if it's not the default one and a new image is uploaded
            if ($gambar_nama_file !== 'default.jpg' && file_exists($target_dir . $gambar_nama_file)) {
                unlink($target_dir . $gambar_nama_file);
            }

            if (!move_uploaded_file($gambar_tmp_name, $target_file)) {
                http_response_code(500);
                echo json_encode(['status' => 'error', 'message' => 'Gagal mengunggah file gambar baru.']);
                exit();
            }
            $menu->gambar = $new_gambar_name; // Update image name in XML
        }
        // If no new image is uploaded AND current_gambar was sent (meaning user didn't clear image field)
        // Ensure the old image filename is retained if no new file provided
        else if (isset($_POST['gambar_unchanged']) && $_POST['gambar_unchanged'] === 'true') {
            // Do nothing, the $menu->gambar already has the old value
        }
        // If 'gambar' field was empty in FormData (user cleared image input)
        else if (!isset($_FILES['gambar']) && !isset($_POST['gambar']) && $gambar_nama_file !== 'default.jpg') {
             // If user cleared the image input, revert to default.jpg and delete old file
             if (file_exists($target_dir . $gambar_nama_file)) {
                 unlink($target_dir . $gambar_nama_file);
             }
             $menu->gambar = 'default.jpg';
        }
        // If 'gambar' was sent as a string (i.e., user did not touch the file input, but we need to ensure the existing filename is used)
        else if (isset($_POST['gambar'])) {
             $menu->gambar = $_POST['gambar']; // Use the filename sent from JS (could be original filename if no new file was selected)
        }


        $found = true;
        break;
    }
}

if (!$found) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Menu tidak ditemukan.']);
    exit;
}

// Save changes to XML file
$dom = new DOMDocument('1.0', 'UTF-8');
$dom->preserveWhiteSpace = false;
$dom->formatOutput = true;
$dom->loadXML($xml->asXML());
if ($dom->save($xmlPath) === false) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan perubahan ke file XML.']);
    exit();
}

echo json_encode(['status' => 'success', 'message' => 'Menu berhasil diperbarui!']);

?>