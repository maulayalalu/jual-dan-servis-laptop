<?php
require 'config/koneksi.php';

// Check if column exists
$result = $koneksi->query("SHOW COLUMNS FROM hero_slides LIKE 'overlay'");
if ($result->num_rows == 0) {
    $koneksi->query("ALTER TABLE hero_slides ADD COLUMN overlay TINYINT DEFAULT 55");
    echo "Column 'overlay' added successfully.\n";
} else {
    echo "Column 'overlay' already exists.\n";
}

// Check if posisi_gambar size needs to be increased
// It's currently VARCHAR(50). If we store "center 78%", that's fine. 
// Just to be safe, let's make it VARCHAR(100).
$koneksi->query("ALTER TABLE hero_slides MODIFY COLUMN posisi_gambar VARCHAR(100) DEFAULT 'center'");
echo "Column 'posisi_gambar' modified successfully.\n";
?>
