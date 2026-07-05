<?php
session_start();
require_once '../config/koneksi.php';
requireUser();
$basePath = '../';
$pageTitle = 'Riwayat Transaksi & Servis â€” A-LINKS';
$id_user = $_SESSION['id_user'];
$activeTab = $_GET['tab'] ?? 'transaksi';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bayar'])) {
    verify_csrf();
    $id_trx = (int)$_POST['id_transaksi'];
    $koneksi->query("UPDATE transaksi SET status_pembayaran='paid' WHERE id_transaksi=$id_trx AND id_user=$id_user");
    setFlash('success', 'Pembayaran berhasil diverifikasi (Simulasi).');
    redirect('riwayat.php?tab=transaksi');
}

// Ambil Riwayat Transaksi
$rTrx = $koneksi->prepare("SELECT * FROM transaksi WHERE id_user=? ORDER BY waktu_transaksi DESC");
$rTrx->bind_param('i', $id_user); $rTrx->execute();
$riwayatTransaksi = $rTrx->get_result(); $rTrx->close();

// Ambil Riwayat Servis
$rSrv = $koneksi->prepare("SELECT * FROM servis WHERE id_user=? ORDER BY tgl_masuk DESC");
$rSrv->bind_param('i', $id_user); $rSrv->execute();
$riwayatServis = $rSrv->get_result(); $rSrv->close();

include '../includes/header.php';
?>

<div style="min-height:100vh;background:var(--color-cream);padding-top:72px;">
  <div class="container" style="max-width:1100px;padding-top:32px;padding-bottom:80px;">
    
    <div class="page-header">
      <div>
        <h1 class="page-header__title">Riwayat Aktivitas</h1>
        <div class="page-header__sub">Pantau pesanan produk dan status perbaikan laptop Anda</div>
      </div>
    </div>

    <?php renderFlash(); ?>

    <!-- Tabs -->
    <div style="display:flex;gap:0;border-bottom:2px solid var(--color-cream-border);margin-bottom:24px;">
      <a href="?tab=transaksi" style="padding:12px 20px;font-weight:500;font-size:14px;color:<?= $activeTab==='transaksi' ? 'var(--color-navy)' : 'var(--color-pewter)' ?>;border-bottom:2px solid <?= $activeTab==='transaksi' ? 'var(--color-navy)' : 'transparent' ?>;margin-bottom:-2px;transition:all 0.3s;">
        ðŸ›’ Pesanan Produk
      </a>
      <a href="?tab=servis" style="padding:12px 20px;font-weight:500;font-size:14px;color:<?= $activeTab==='servis' ? 'var(--color-navy)' : 'var(--color-pewter)' ?>;border-bottom:2px solid <?= $activeTab==='servis' ? 'var(--color-navy)' : 'transparent' ?>;margin-bottom:-2px;transition:all 0.3s;">
        ðŸ”§ Layanan Servis
      </a>
    </div>

    <?php if ($activeTab === 'transaksi'): ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Order ID</th><th>Tanggal</th><th>Total Harga</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                <?php if ($riwayatTransaksi->num_rows > 0): while ($t = $riwayatTransaksi->fetch_assoc()): ?>
                    <tr>
                        <td><code style="font-size:12px;color:var(--color-pewter);background:var(--color-cream);padding:2px 6px;border-radius:4px;"><?= htmlspecialchars($t['order_id']) ?></code></td>
                        <td style="color:var(--color-carbon);font-size:14px;"><?= date('d M Y H:i', strtotime($t['waktu_transaksi'])) ?></td>
                        <td style="color:var(--color-navy);font-weight:700;"><?= formatRupiah($t['total_harga']) ?></td>
                        <td><?php $m=['paid'=>['green','Lunas'],'unpaid'=>['amber','Menunggu Pembayaran'],'failed'=>['red','Dibatalkan'],'pending_verify'=>['blue','Menunggu Verifikasi']]; [$c,$l]=$m[$t['status_pembayaran']]??['gray',$t['status_pembayaran']]; ?><span class="badge badge--<?= $c ?>"><?= $l ?></span></td>
                        <td>
                            <div style="display:flex;flex-direction:column;gap:6px;align-items:flex-start;">
                            <?php if ($t['status_pembayaran'] === 'unpaid'): ?>
                                <div style="display:flex;gap:6px;">
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="id_transaksi" value="<?= $t['id_transaksi'] ?>">
                                    <button type="submit" name="bayar" class="btn btn--primary btn--sm" onclick="return confirm('Lakukan simulasi pembayaran untuk pesanan ini?');">Bayar (Simulasi)</button>
                                </form>
                                <a href="upload_bukti.php?id=<?= $t['id_transaksi'] ?>" class="btn btn--secondary btn--sm">Upload Bukti</a>
                                </div>
                            <?php elseif ($t['status_pembayaran'] === 'pending_verify'): ?>
                                <span style="font-size:12px;color:var(--color-pewter);">â³ Sedang diverifikasi admin</span>
                            <?php endif; ?>
                            <a href="cetak_nota.php?id=<?= $t['id_transaksi'] ?>&print=1" target="_blank" class="btn btn--secondary btn--sm" style="font-size:11px;padding:4px 8px;">Cetak Nota (PDF)</a>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; else: ?>
                    <tr><td colspan="5"><div class="empty-state"><p>Anda belum memiliki riwayat pesanan produk.</p></div></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <?php if ($activeTab === 'servis'): ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>ID Servis</th><th>Laptop</th><th>Keluhan</th><th>Tanggal Masuk</th><th>Estimasi Biaya</th><th>Status</th></tr></thead>
                <tbody>
                <?php if ($riwayatServis->num_rows > 0): while ($s = $riwayatServis->fetch_assoc()): ?>
                    <tr>
                        <td><span style="font-size:12px;color:var(--color-pewter);">#<?= $s['id_servis'] ?></span></td>
                        <td style="font-weight:500;color:var(--color-carbon);"><?= htmlspecialchars($s['tipe_laptop']) ?></td>
                        <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:13px;"><?= htmlspecialchars($s['keluhan']) ?></td>
                        <td style="font-size:13px;color:var(--color-pewter);"><?= date('d M Y', strtotime($s['tgl_masuk'])) ?></td>
                        <td style="color:var(--color-navy);font-weight:600;"><?= $s['biaya'] > 0 ? formatRupiah($s['biaya']) : '<span style="color:var(--color-taupe-light);font-weight:400;">Menunggu Pengecekan</span>' ?></td>
                        <td><?php $sm=['pending'=>['gray','Pending'],'proses'=>['amber','Diproses'],'selesai'=>['green','Selesai'],'diambil'=>['blue','Diambil']]; [$sc,$sl]=$sm[$s['status']]??['gray',$s['status']]; ?><span class="badge badge--<?= $sc ?>"><?= $sl ?></span></td>
                    </tr>
                <?php endwhile; else: ?>
                    <tr><td colspan="6"><div class="empty-state"><p>Anda belum memiliki riwayat pengajuan servis.</p></div></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

  </div>
</div>

<?php include '../includes/footer.php'; ?>
