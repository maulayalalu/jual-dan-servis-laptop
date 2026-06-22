<?php
session_start();
require_once 'config/koneksi.php';
$basePath = '';
$pageTitle = 'Halaman Tidak Ditemukan — A-LINKS';
http_response_code(404);
include 'includes/header.php';
?>

<div style="min-height:100vh;background:var(--color-light-ash);display:flex;align-items:center;justify-content:center;padding:20px;padding-top:72px;">
  <div style="text-align:center;max-width:480px;">
    <div style="font-size:120px;font-weight:800;color:var(--color-blue);line-height:1;letter-spacing:-4px;margin-bottom:0;">404</div>
    <h1 style="font-size:28px;font-weight:600;color:var(--color-carbon);margin:8px 0 16px;">Halaman Tidak Ditemukan</h1>
    <p style="color:var(--color-pewter);font-size:15px;line-height:1.7;margin-bottom:32px;">
      Maaf, halaman yang Anda cari tidak ada, sudah dihapus, atau alamatnya salah.
    </p>
    <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
      <a href="index.php" class="btn btn--primary">← Kembali ke Beranda</a>
      <a href="user/katalog.php" class="btn btn--secondary">Lihat Katalog</a>
    </div>
    <div style="margin-top:48px;display:flex;gap:24px;justify-content:center;">
      <a href="user/request_servis.php" style="font-size:13px;color:var(--color-pewter);text-decoration:none;">Request Servis</a>
      <a href="login.php" style="font-size:13px;color:var(--color-pewter);text-decoration:none;">Login</a>
      <a href="register.php" style="font-size:13px;color:var(--color-pewter);text-decoration:none;">Daftar</a>
    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
