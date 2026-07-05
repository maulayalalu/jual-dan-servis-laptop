<?php
require_once 'config/koneksi.php';

$queries = [
    // Tabel keranjang (DB Cart - Tahap 3)
    "CREATE TABLE IF NOT EXISTS keranjang (
        id_keranjang INT AUTO_INCREMENT PRIMARY KEY,
        id_user INT NOT NULL,
        id_produk INT NOT NULL,
        qty INT DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_cart (id_user, id_produk),
        FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE CASCADE,
        FOREIGN KEY (id_produk) REFERENCES produk(id_produk) ON DELETE CASCADE
    ) ENGINE=InnoDB",

    // Tabel ulasan produk
    "CREATE TABLE IF NOT EXISTS ulasan (
        id_ulasan INT AUTO_INCREMENT PRIMARY KEY,
        id_produk INT NOT NULL,
        id_user INT NOT NULL,
        rating TINYINT NOT NULL DEFAULT 5,
        komentar TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_ulasan (id_produk, id_user),
        FOREIGN KEY (id_produk) REFERENCES produk(id_produk) ON DELETE CASCADE,
        FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE CASCADE
    ) ENGINE=InnoDB",

    // Tabel reset password
    "CREATE TABLE IF NOT EXISTS password_resets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(150) NOT NULL,
        token VARCHAR(100) NOT NULL,
        expires_at DATETIME NOT NULL,
        used TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB",

    // Tabel kategori produk
    "CREATE TABLE IF NOT EXISTS kategori (
        id_kategori INT AUTO_INCREMENT PRIMARY KEY,
        nama_kategori VARCHAR(100) NOT NULL,
        slug VARCHAR(100) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB",

    // Tabel voucher/diskon
    "CREATE TABLE IF NOT EXISTS voucher (
        id_voucher INT AUTO_INCREMENT PRIMARY KEY,
        kode VARCHAR(50) NOT NULL UNIQUE,
        diskon_persen INT DEFAULT 0,
        diskon_nominal INT DEFAULT 0,
        min_belanja INT DEFAULT 0,
        max_penggunaan INT DEFAULT 1,
        total_digunakan INT DEFAULT 0,
        aktif TINYINT(1) DEFAULT 1,
        expires_at DATE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB",

    // Kolom bukti_pembayaran di tabel transaksi
    "ALTER TABLE transaksi ADD COLUMN IF NOT EXISTS bukti_pembayaran VARCHAR(255) DEFAULT NULL",

    // Kolom id_kategori di tabel produk
    "ALTER TABLE produk ADD COLUMN IF NOT EXISTS id_kategori INT DEFAULT NULL",
    "ALTER TABLE produk ADD COLUMN IF NOT EXISTS rata_rating DECIMAL(3,2) DEFAULT 0",

    // Kolom no_telp di users (jika belum ada)
    "ALTER TABLE produk ADD COLUMN IF NOT EXISTS harga_coret INT DEFAULT NULL COMMENT 'Harga asli sebelum diskon'",

    // Insert kategori default
    "INSERT IGNORE INTO kategori (nama_kategori, slug) VALUES
        ('Gaming', 'gaming'),
        ('Bisnis & Office', 'bisnis'),
        ('Pelajar & Mahasiswa', 'pelajar'),
        ('Desain & Kreator', 'desain'),
        ('Ultrabook', 'ultrabook')",
];

foreach ($queries as $q) {
    if ($koneksi->query($q)) {
        echo "âœ“ OK: " . substr($q, 0, 60) . "...\n";
    } else {
        echo "âœ— Skip/Error: " . $koneksi->error . "\n";
    }
}

echo "\nâœ… Database schema update selesai!\n";
