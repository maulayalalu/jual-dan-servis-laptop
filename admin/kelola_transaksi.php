<?php
session_start();
require_once '../config/koneksi.php';
requireStaff(); // admin, owner, kasir
$basePath = '../'; $pageTitle = 'Kelola Transaksi — A-LINKS';

$page = (int)($_GET['page'] ?? 1);
if ($page < 1) $page = 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$filterStatus = $_GET['status'] ?? '';
if ($filterStatus) {
    $stmt = $koneksi->prepare("SELECT t.*, u.nama AS nama_user FROM transaksi t JOIN users u ON t.id_user=u.id_user WHERE t.status_pembayaran=? ORDER BY t.waktu_transaksi DESC");
    
    $stmt->execute();
    $list = $stmt->get_result(); $stmt->close();
} else {
    $stmt = $koneksi->prepare("SELECT t.*, u.nama AS nama_user FROM transaksi t JOIN users u ON t.id_user=u.id_user ORDER BY t.waktu_transaksi DESC LIMIT ? OFFSET ?");
    $stmt->bind_param('ii', $limit, $offset);
    $stmt->execute();
    $list = $stmt->get_result();
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    verify_csrf();
    $id_trx = (int)$_POST['id_transaksi'];
    $status = $_POST['status_pembayaran'];
    $stmt = $koneksi->prepare("UPDATE transaksi SET status_pembayaran=? WHERE id_transaksi=?");
    $stmt->bind_param('si', $status, $id_trx);
    $stmt->execute(); $stmt->close();
    setFlash('success', 'Status transaksi berhasil diperbarui.');
    redirect('kelola_transaksi.php');
}

$detail_id = $_GET['detail'] ?? 0;
$detailData = null;
$detailItems = [];
if ($detail_id) {
    $stmt = $koneksi->prepare("SELECT t.*, u.nama, u.email, u.alamat, u.no_telp FROM transaksi t JOIN users u ON t.id_user=u.id_user WHERE t.id_transaksi=?");
    $stmt->bind_param('i', $detail_id); $stmt->execute();
    $detailData = $stmt->get_result()->fetch_assoc(); $stmt->close();
    
    if ($detailData) {
        $stmt2 = $koneksi->prepare("SELECT d.*, p.nama_laptop FROM detail_transaksi d JOIN produk p ON d.id_produk=p.id_produk WHERE d.id_transaksi=?");
        $stmt2->bind_param('i', $detail_id); $stmt2->execute();
        $detailItems = $stmt2->get_result(); $stmt2->close();
    }
}

// Total pendapatan
$rTotal = $koneksi->query("SELECT COALESCE(SUM(total_harga),0) AS total FROM transaksi WHERE status_pembayaran='paid'");
$totalPendapatan = $rTotal->fetch_assoc()['total'];

if ($filterStatus) {
    $stmtC = $koneksi->prepare("SELECT COUNT(*) AS total FROM transaksi WHERE status_pembayaran=?");
    $stmtC->bind_param('s', $filterStatus);
    $stmtC->execute();
    $totalData = $stmtC->get_result()->fetch_assoc()['total'];
    $stmtC->close();
} else {
    $totalData = $koneksi->query("SELECT COUNT(*) AS total FROM transaksi")->fetch_assoc()['total'];
}
$totalPages = ceil($totalData / $limit);
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
    <div><h1 class="page-header__title">Kelola Transaksi</h1>
    <div class="page-header__sub">Riwayat semua pesanan dan status pembayaran pelanggan</div></div>
    <div style="background:var(--color-white);padding:10px 20px;border-radius:8px;text-align:center;">
      <div style="font-size:11px;color:var(--color-pewter);text-transform:uppercase;letter-spacing:1px;">Total Pendapatan</div>
      <div style="font-size:18px;font-weight:600;color:var(--color-blue);"><?= formatRupiah($totalPendapatan) ?></div>
    </div>
  </div>
  <?php renderFlash(); ?>

  <!-- Filter tabs -->
  <div style="display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap;">
    <?php foreach ([''=> 'Semua','paid'=>'Lunas','unpaid'=>'Menunggu','failed'=>'Gagal'] as $val => $lbl): ?>
    <a href="kelola_transaksi.php<?= $val ? '?status='.$val : '' ?>"
       class="btn btn--sm <?= $filterStatus===$val ? 'btn--primary' : 'btn--secondary' ?>"
       id="tabTrx-<?= $val ?: 'all' ?>"><?= $lbl ?></a>
    <?php endforeach; ?>
  </div>

  <?php if ($detailData): ?>
  <div class="card" style="margin-bottom:24px;padding:24px;border-left:4px solid var(--color-blue);">
    <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:16px;">
        <div>
            <h3 style="margin-bottom:4px;font-size:16px;color:var(--color-carbon);">Detail Pesanan <?= htmlspecialchars($detailData['order_id']) ?></h3>
            <div style="font-size:13px;color:var(--color-pewter);">Oleh: <?= htmlspecialchars($detailData['nama']) ?> (<?= htmlspecialchars($detailData['email']) ?>)</div>
            <div style="font-size:13px;color:var(--color-pewter);margin-top:4px;">Alamat: <?= htmlspecialchars($detailData['alamat'] ?? '-') ?></div>
        </div>
        <a href="kelola_transaksi.php" class="btn btn--secondary btn--sm">Tutup</a>
    </div>
    
    <div style="margin-bottom:16px;">
        <table style="font-size:13px;">
            <tr style="background:var(--color-cream);"><th>Produk</th><th>Qty</th><th>Subtotal</th></tr>
            <?php while ($item = $detailItems->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($item['nama_laptop']) ?></td>
                <td><?= $item['jumlah'] ?></td>
                <td><?= formatRupiah($item['harga_satuan'] * $item['jumlah']) ?></td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>

    <form method="POST" style="display:flex;align-items:center;gap:12px;background:var(--color-cream);border:1px solid var(--color-cream-border);padding:12px;border-radius:6px;">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="update_status" value="1">
        <input type="hidden" name="id_transaksi" value="<?= $detailData['id_transaksi'] ?>">
        <div style="font-weight:500;font-size:14px;">Update Status:</div>
        <select name="status_pembayaran" class="form-control form-select" style="width:200px;padding:6px 12px;height:auto;">
            <option value="unpaid" <?= $detailData['status_pembayaran'] === 'unpaid' ? 'selected' : '' ?>>Menunggu</option>
            <option value="paid" <?= $detailData['status_pembayaran'] === 'paid' ? 'selected' : '' ?>>Lunas</option>
            <option value="failed" <?= $detailData['status_pembayaran'] === 'failed' ? 'selected' : '' ?>>Gagal / Batal</option>
        </select>
        <button type="submit" class="btn btn--primary btn--sm">Simpan</button>
    </form>
  </div>
  <?php endif; ?>

  <div class="table-wrap">
    <div class="table-toolbar">
      <div class="table-toolbar__title">Daftar Transaksi</div>
      <div class="search-input"><svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="var(--color-silver-fog)" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
        <input type="text" id="tableSearch" placeholder="Cari transaksi..."/></div>
    </div>
    <div style="overflow-x: auto; width: 100%;">
      <table style="min-width: 950px;">
        <thead><tr><th>Order ID</th><th>Pelanggan</th><th>Total</th><th>Pembayaran</th><th>Status</th><th>Waktu</th><th>Detail</th></tr></thead>
        <tbody>
        <?php if ($list->num_rows > 0): while ($t = $list->fetch_assoc()): ?>
        <tr id="rowTrx-<?= $t['id_transaksi'] ?>">
          <td><code style="font-size:11px;background:var(--color-cream);padding:2px 6px;border-radius:3px;border:1px solid var(--color-cream-border);"><?= htmlspecialchars($t['order_id'] ?? '-') ?></code></td>
          <td style="font-weight:500;color:var(--color-carbon);"><?= htmlspecialchars($t['nama_user']) ?></td>
          <td style="color:var(--color-navy);font-weight:700;"><?= formatRupiah($t['total_harga']) ?></td>
          <td style="font-size:13px;color:var(--color-pewter);"><?= htmlspecialchars($t['tipe_pembayaran'] ?? '—') ?></td>
          <td><?php $m=['paid'=>['green','Lunas'],'unpaid'=>['amber','Menunggu'],'failed'=>['red','Gagal']]; [$c,$l]=$m[$t['status_pembayaran']]??['gray',$t['status_pembayaran']]; ?><span class="badge badge--<?= $c ?>"><?= $l ?></span></td>
          <td style="font-size:12px;color:var(--color-pewter);"><?= date('d M Y H:i', strtotime($t['waktu_transaksi'])) ?></td>
          <td><a href="?detail=<?= $t['id_transaksi'] ?>" class="btn btn--secondary btn--sm" id="btnDetailTrx-<?= $t['id_transaksi'] ?>">Lihat</a></td>
        </tr>
        <?php endwhile; else: ?>
        <tr><td colspan="7"><div class="empty-state"><p>Belum ada transaksi.</p></div></td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
      <?php if ($totalPages > 1): ?>
      <div style="display:flex;gap:8px;justify-content:center;margin-top:20px;">
          <?php for ($i=1; $i<=$totalPages; $i++): ?>
          <a href="?page=<?= $i ?><?= $filterStatus ? '&status='.$filterStatus : '' ?>" class="btn btn--sm <?= $i === $page ? 'btn--primary' : 'btn--secondary' ?>"><?= $i ?></a>
          <?php endfor; ?>
      </div>
      <?php endif; ?>
  </div>
</main>
</div>
<script src="../assets/js/main.js"></script>
</body></html>
