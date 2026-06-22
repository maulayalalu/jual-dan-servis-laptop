<?php
require_once 'config/koneksi.php';

// Check and Add username
$res = $koneksi->query("SHOW COLUMNS FROM users LIKE 'username'");
if ($res->num_rows == 0) {
    $koneksi->query("ALTER TABLE users ADD COLUMN username VARCHAR(50) DEFAULT NULL AFTER nama");
    echo "Added username column.\n";
}

// Check and Add created_at
$res2 = $koneksi->query("SHOW COLUMNS FROM users LIKE 'created_at'");
if ($res2->num_rows == 0) {
    $koneksi->query("ALTER TABLE users ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
    echo "Added created_at column.\n";
}

// Generate username for existing users
$res3 = $koneksi->query("SELECT id_user, nama FROM users WHERE username IS NULL");
if ($res3) {
    while ($row = $res3->fetch_assoc()) {
        $parts = explode(' ', $row['nama']);
        $uname = preg_replace('/[^a-zA-Z0-9]/', '', ucfirst($parts[0]));
        $koneksi->query("UPDATE users SET username = '$uname' WHERE id_user = {$row['id_user']}");
    }
    echo "Updated existing users.\n";
}

echo "Database migration complete!";
