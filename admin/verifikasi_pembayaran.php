<?php
session_start();
require_once '../config/koneksi.php';
requireStaff(); // admin, owner, kasir
$basePath = '../'; $pageTitle = 'Verifikasi Pembayaran — A-LINKS';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    $id_trx = (int)$_POST['id_transaksi'];
    if ($action === 'approve') {
        $stmt = $koneksi->prepare("UPDATE transaksi SET status_pembayaran='paid' WHERE id_transaksi=?");
        $stmt->bind_param('i', $id_trx); $stmt->execute(); $stmt->close();
        setFlash('success', 'Pembayaran berhasil diverifikasi!');
    } elseif ($action === 'reject') {
        $stmt = $koneksi->prepare("UPDATE transaksi SET status_pembayaran='unpaid', bukti_pembayaran=NULL WHERE id_transaksi=?");
        $stmt->bind_param('i', $id_trx); $stmt->execute(); $stmt->close();
        setFlash('warning', 'Bukti pembayaran ditolak. User dapat mengunggah ulang.');
    }
    redirect('verifikasi_pembayaran.php');
}

$list = $koneksi->query("SELECT t.*, u.nama, u.email FROM transaksi t JOIN users u ON t.id_user=u.id_user WHERE t.status_pembayaran='pending_verify' ORDER BY t.waktu_transaksi DESC");
$total = $list->num_rows;
?>
<!DOCTYPE html><html lang="id"><head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title><?= $pageTitle ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="../assets/css/style.css?v=<?= time() ?>"/>
</head><body>
<div class="app-layout">
<?php include '../includes/sidebar_admin.php'; ?>
<main class="main-content">
  <div class="page-header">
    <div><h1 class="page-header__title">Verifikasi Pembayaran</h1>
    <div class="page-header__sub">Periksa bukti transfer dan konfirmasi status pesanan</div></div>
    <?php if ($total > 0): ?>
    <span class="badge badge--amber" style="font-size:14px;padding:8px 16px;"><?= $total ?> Menunggu Verifikasi</span>
    <?php endif; ?>
  </div>
  <?php renderFlash(); ?>

  <?php if ($total > 0): while($t = $list->fetch_assoc()): ?>
  <div class="card" style="padding:24px;margin-bottom:20px;border-left:4px solid var(--color-taupe);">
    <div style="display:grid;grid-template-columns:1fr 280px;gap:24px;align-items:start;">
      <div>
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;">
          <div style="width:40px;height:40px;border-radius:50%;background:var(--color-taupe);color:white;display:grid;place-items:center;font-weight:600;box-shadow:var(--shadow-taupe);"><?= strtoupper(substr($t['nama'],0,1)) ?></div>
          <div>
            <div style="font-weight:600;color:var(--color-carbon);"><?= htmlspecialchars($t['nama']) ?></div>
            <div style="font-size:13px;color:var(--color-pewter);"><?= htmlspecialchars($t['email']) ?></div>
          </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;font-size:14px;background:var(--color-cream);border:1px solid var(--color-cream-border);border-radius:8px;padding:16px;">
          <div><span style="color:var(--color-pewter);">Order ID:</span><br><code style="font-size:12px;background:var(--color-white);padding:2px 6px;border-radius:3px;border:1px solid var(--color-cream-border);"><?= htmlspecialchars($t['order_id']) ?></code></div>
          <div><span style="color:var(--color-pewter);">Total:</span><br><span style="font-weight:700;color:var(--color-navy);"><?= formatRupiah($t['total_harga']) ?></span></div>
          <div><span style="color:var(--color-pewter);">Metode:</span><br><?= htmlspecialchars($t['tipe_pembayaran'] ?? '—') ?></div>
          <div><span style="color:var(--color-pewter);">Waktu Pesan:</span><br><?= date('d M Y H:i', strtotime($t['waktu_transaksi'])) ?></div>
        </div>
        <div style="display:flex;gap:8px;margin-top:16px;">
          <form method="POST" style="display:inline;">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="action" value="approve">
            <input type="hidden" name="id_transaksi" value="<?= $t['id_transaksi'] ?>">
            <button type="submit" class="btn btn--primary" onclick="return confirm('Konfirmasi pembayaran ini sebagai LUNAS?');">âœ“ Terima — Tandai Lunas</button>
          </form>
          <form method="POST" style="display:inline;">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="action" value="reject">
            <input type="hidden" name="id_transaksi" value="<?= $t['id_transaksi'] ?>">
            <button type="submit" class="btn btn--danger" onclick="return confirm('Tolak bukti pembayaran ini?');">âœ— Tolak</button>
          </form>
        </div>
      </div>
      <!-- Bukti Image -->
      <div>
        <?php if ($t['bukti_pembayaran']): ?>
        <div style="font-size:13px;font-weight:500;color:var(--color-pewter);margin-bottom:8px;">Bukti Transfer:</div>
        <a href="../<?= htmlspecialchars($t['bukti_pembayaran']) ?>" target="_blank">
          <img src="../<?= htmlspecialchars($t['bukti_pembayaran']) ?>" 
               alt="Bukti Transfer" 
               style="width:100%;border-radius:8px;border:1px solid var(--color-cream-border);cursor:zoom-in;"
               onerror="this.parentElement.innerHTML='<div class=\'empty-state\'><p>Gagal memuat gambar</p></div>'">
        </a>
        <div style="font-size:12px;color:var(--color-pewter);margin-top:4px;text-align:center;">Klik untuk perbesar</div>
        <?php else: ?>
        <div class="empty-state"><p>Tidak ada bukti.</p></div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php endwhile; else: ?>
  <div class="card" style="padding:60px;text-align:center;">
    <div class="empty-state"><p>🎉 Tidak ada pembayaran yang menunggu verifikasi.</p></div>
  </div>
  <?php endif; ?>

</main>
</div>
<script src="../assets/js/main.js"></script>
</body></html>
