<?php
require_once 'config/koneksi.php';
$res = $koneksi->query("SHOW COLUMNS FROM produk LIKE 'garansi'");
if ($res->num_rows == 0) {
    $koneksi->query("ALTER TABLE produk ADD COLUMN garansi VARCHAR(100) DEFAULT 'Garansi Resmi 1 Tahun' AFTER harga");
    echo "Added garansi column.\n";
} else {
    echo "Column garansi already exists.\n";
}
