<?php
/**
 * db_add_roles.php â€” Migration: tambah role owner & kasir
 * âš ï¸  Jalankan SEKALI, lalu hapus file ini demi keamanan.
 */
session_start();
require_once 'config/koneksi.php';

$messages = [];
$errors   = [];

// â”€â”€ 1. Ubah enum kolom role â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
$alterSQL = "ALTER TABLE `users` MODIFY `role` ENUM('admin','owner','kasir','user') NOT NULL DEFAULT 'user'";
if ($koneksi->query($alterSQL)) {
    $messages[] = 'âœ… Kolom <code>role</code> berhasil diubah menjadi 4 nilai: admin, owner, kasir, user';
} else {
    $errors[] = 'âŒ Gagal alter tabel users: ' . $koneksi->error;
}

// â”€â”€ 2. Tambah demo user: Owner â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
$ownerEmail = 'owner@alinks.id';
$chk = $koneksi->prepare("SELECT id_user FROM users WHERE email=? LIMIT 1");
$chk->bind_param('s', $ownerEmail);
$chk->execute();
$chk->store_result();
if ($chk->num_rows === 0) {
    $ownerHash = password_hash('owner123', PASSWORD_DEFAULT);
    $ins = $koneksi->prepare("INSERT INTO users (nama, email, password, role, no_telp, alamat) VALUES (?, ?, ?, 'owner', ?, ?)");
    $nama    = 'Owner Demo';
    $telp    = '081200000001';
    $alamat  = 'Kantor Pusat A-LINKS';
    $ins->bind_param('sssss', $nama, $ownerEmail, $ownerHash, $telp, $alamat);
    if ($ins->execute()) {
        $messages[] = 'âœ… Demo user <strong>Owner</strong> ditambahkan â€” <code>owner@alinks.id</code> / <code>owner123</code>';
    } else {
        $errors[] = 'âŒ Gagal insert user owner: ' . $ins->error;
    }
    $ins->close();
} else {
    $messages[] = 'âš ï¸ User owner@alinks.id sudah ada, dilewati.';
}
$chk->close();

// â”€â”€ 3. Tambah demo user: Kasir â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
$kasirEmail = 'kasir@alinks.id';
$chk2 = $koneksi->prepare("SELECT id_user FROM users WHERE email=? LIMIT 1");
$chk2->bind_param('s', $kasirEmail);
$chk2->execute();
$chk2->store_result();
if ($chk2->num_rows === 0) {
    $kasirHash = password_hash('kasir123', PASSWORD_DEFAULT);
    $ins2 = $koneksi->prepare("INSERT INTO users (nama, email, password, role, no_telp, alamat) VALUES (?, ?, ?, 'kasir', ?, ?)");
    $nama2   = 'Kasir Demo';
    $telp2   = '081200000002';
    $alamat2 = 'Kasir A-LINKS';
    $ins2->bind_param('sssss', $nama2, $kasirEmail, $kasirHash, $telp2, $alamat2);
    if ($ins2->execute()) {
        $messages[] = 'âœ… Demo user <strong>Kasir</strong> ditambahkan â€” <code>kasir@alinks.id</code> / <code>kasir123</code>';
    } else {
        $errors[] = 'âŒ Gagal insert user kasir: ' . $ins2->error;
    }
    $ins2->close();
} else {
    $messages[] = 'âš ï¸ User kasir@alinks.id sudah ada, dilewati.';
}
$chk2->close();

$koneksi->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Migrasi Role â€” A-LINKS</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet"/>
  <style>
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:'Inter',sans-serif;background:#f3f4f6;min-height:100vh;display:flex;align-items:center;justify-content:center;}
    .card{background:white;border-radius:16px;padding:40px;max-width:600px;width:100%;box-shadow:0 4px 24px rgba(0,0,0,.08);}
    h1{font-size:20px;font-weight:600;margin-bottom:8px;color:#111827;}
    .sub{font-size:14px;color:#6b7280;margin-bottom:28px;}
    .msg{padding:12px 16px;border-radius:8px;font-size:14px;margin-bottom:10px;}
    .msg.ok{background:#ecfdf5;color:#065f46;border:1px solid #6ee7b7;}
    .msg.err{background:#fef2f2;color:#991b1b;border:1px solid #fca5a5;}
    .note{margin-top:24px;padding:16px;background:#fffbeb;border:1px solid #fbbf24;border-radius:8px;font-size:13px;color:#92400e;}
    .btn{display:inline-block;margin-top:20px;padding:10px 20px;background:#1c64f2;color:white;text-decoration:none;border-radius:8px;font-weight:500;font-size:14px;}
  </style>
</head>
<body>
<div class="card">
  <h1>ðŸ”§ Migrasi Role â€” A-LINKS</h1>
  <p class="sub">Script penambahan role <strong>owner</strong> dan <strong>kasir</strong> ke database.</p>

  <?php foreach ($messages as $m): ?>
  <div class="msg ok"><?= $m ?></div>
  <?php endforeach; ?>

  <?php foreach ($errors as $e): ?>
  <div class="msg err"><?= $e ?></div>
  <?php endforeach; ?>

  <?php if (empty($errors)): ?>
  <div class="note">
    âš ï¸ <strong>Penting:</strong> Migrasi selesai. Hapus file <code>db_add_roles.php</code> ini setelah selesai untuk keamanan server.
  </div>
  <?php endif; ?>

  <a href="login.php" class="btn">â† Kembali ke Login</a>
</div>
</body>
</html>
