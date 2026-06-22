<?php
require_once 'config/koneksi.php';

$sql = "CREATE TABLE IF NOT EXISTS hero_slides (
    id_slide INT AUTO_INCREMENT PRIMARY KEY,
    gambar VARCHAR(255) NOT NULL,
    posisi_gambar VARCHAR(50) DEFAULT 'center',
    badge_text VARCHAR(100),
    judul VARCHAR(100),
    deskripsi TEXT,
    btn1_text VARCHAR(50),
    btn1_url VARCHAR(255),
    btn2_text VARCHAR(50),
    btn2_url VARCHAR(255),
    urutan INT DEFAULT 0
)";
$koneksi->query($sql);

// Insert default data if empty
$res = $koneksi->query("SELECT COUNT(*) as c FROM hero_slides");
if ($res->fetch_assoc()['c'] == 0) {
    $koneksi->query("INSERT INTO hero_slides (gambar, posisi_gambar, badge_text, judul, deskripsi, btn1_text, btn1_url, btn2_text, btn2_url, urutan) VALUES 
    ('https://images.unsplash.com/photo-1593642632632-9b7a7e2e3c7e?w=1600&q=85', 'center', '0% Cicilan Tersedia', 'Laptop Impianmu Ada di Sini', 'Koleksi terlengkap untuk gaming, kerja & belajar', 'Lihat Katalog', 'user/katalog.php', 'Request Servis', 'user/request_servis.php', 1),
    ('https://images.unsplash.com/photo-1588702547923-7093a6c3ba33?w=1600&q=85', 'center', 'Servis Profesional', 'Laptop Rusak? Kami Perbaiki', 'Teknisi berpengalaman, garansi pengerjaan 30 hari', 'Ajukan Servis', 'user/request_servis.php', 'Pelajari Lebih', '#servis-info', 2),
    ('https://images.unsplash.com/photo-1484788984921-03950022c38b?w=1600&q=85', 'center', 'Member Eksklusif', 'Daftar & Dapatkan Penawaran', 'Akses harga spesial & tracking servis real-time', 'Daftar Gratis', 'register.php', 'Sudah Punya Akun', 'login.php', 3)");
}
echo "Hero table updated!";
