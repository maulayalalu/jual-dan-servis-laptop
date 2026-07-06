<?php
session_start();
require_once '../config/koneksi.php';
requireOwner();
$basePath  = '../';
$pageTitle = 'Owner Dashboard — A-LINKS';

// KPI Stats
$bulanIni = date('Y-m');
$row = $koneksi->query("SELECT COALESCE(SUM(total_harga),0) AS rev, COUNT(*) AS cnt FROM transaksi WHERE status_pembayaran='paid' AND DATE_FORMAT(waktu_transaksi,'%Y-%m')='$bulanIni'")->fetch_assoc();
$stats['rev_bulan'] = $row['rev'];
$stats['trx_bulan'] = $row['cnt'];
$stats['rev_total'] = $koneksi->query("SELECT COALESCE(SUM(total_harga),0) AS total FROM transaksi WHERE status_pembayaran='paid'")->fetch_assoc()['total'];
$stats['produk']    = $koneksi->query("SELECT COUNT(*) AS c FROM produk WHERE is_deleted=0")->fetch_assoc()['c'];
$stats['users']     = $koneksi->query("SELECT COUNT(*) AS c FROM users WHERE role='user'")->fetch_assoc()['c'];
$stats['servis']    = $koneksi->query("SELECT COUNT(*) AS c FROM servis WHERE status IN('pending','proses')")->fetch_assoc()['c'];
$stats['pending']   = $koneksi->query("SELECT COUNT(*) AS c FROM transaksi WHERE status_pembayaran='pending_verify'")->fetch_assoc()['c'];

$rTop = $koneksi->query("SELECT p.nama_laptop, SUM(dt.jumlah) AS terjual, SUM(dt.jumlah * dt.harga_satuan) AS pendapatan FROM detail_transaksi dt JOIN produk p ON dt.id_produk=p.id_produk JOIN transaksi t ON dt.id_transaksi=t.id_transaksi WHERE t.status_pembayaran='paid' GROUP BY p.id_produk ORDER BY terjual DESC LIMIT 5");
$rTrx = $koneksi->query("SELECT t.*, u.nama AS nama_user FROM transaksi t JOIN users u ON t.id_user=u.id_user ORDER BY t.waktu_transaksi DESC LIMIT 8");

$chartMonths = []; $chartRevs = [];
for ($i=5;$i>=0;$i--) {
    $ym = date('Y-m', strtotime("-$i months"));
    $label = date('M Y', strtotime("-$i months"));
    $rev = $koneksi->query("SELECT COALESCE(SUM(total_harga),0) AS r FROM transaksi WHERE status_pembayaran='paid' AND DATE_FORMAT(waktu_transaksi,'%Y-%m')='$ym'")->fetch_assoc()['r'];
    $chartMonths[] = $label; $chartRevs[] = (int)$rev;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title><?= $pageTitle ?></title>
<meta name="description" content="Dashboard Owner A-LINKS — pantau KPI bisnis dan performa toko."/>
<link rel="preconnect" href="https://fonts.googleapis.com"/>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="../assets/css/style.css?v=<?= time() ?>"/>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
<div class="app-layout">
<?php include '../includes/sidebar_admin.php'; ?>
<main class="main-content">

  <!-- Page Header -->
  <div class="page-header">
    <div>
      <h1 class="page-header__title">Owner Dashboard</h1>
      <div class="page-header__sub">Selamat datang, <?= htmlspecialchars($_SESSION['nama']) ?>. Ringkasan performa bisnis A-LINKS hari ini.</div>
    </div>
    <div style="display:flex;gap:8px;">
      <a href="../admin/laporan.php" class="btn btn--secondary btn--sm" id="btnLaporan">📊 Laporan</a>
      <a href="../owner/pengaturan_situs.php" class="btn btn--primary btn--sm" id="btnPengaturan">⚙️ Pengaturan</a>
    </div>
  </div>

  <?php renderFlash(); ?>

  <!-- Revenue Banner -->
  <div style="background:linear-gradient(135deg,var(--color-navy) 0%,var(--color-navy-dark) 100%);border-radius:var(--radius-card);padding:28px 32px;margin-bottom:24px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;box-shadow:var(--shadow-navy);">
    <div>
      <div style="font-size:11px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:rgba(255,255,255,0.50);margin-bottom:8px;">Total Pendapatan All-Time</div>
      <div style="font-size:36px;font-weight:700;color:white;letter-spacing:-1px;line-height:1;"><?= formatRupiah($stats['rev_total']) ?></div>
    </div>
    <div style="display:flex;gap:32px;flex-wrap:wrap;">
      <div style="text-align:center;padding:12px 16px;background:rgba(255,255,255,0.08);border-radius:12px;">
        <div style="font-size:22px;font-weight:700;color:white;"><?= formatRupiah($stats['rev_bulan']) ?></div>
        <div style="font-size:11px;color:rgba(255,255,255,0.55);margin-top:4px;letter-spacing:0.5px;">BULAN INI</div>
      </div>
      <div style="text-align:center;padding:12px 16px;background:rgba(255,255,255,0.08);border-radius:12px;">
        <div style="font-size:22px;font-weight:700;color:white;"><?= $stats['trx_bulan'] ?></div>
        <div style="font-size:11px;color:rgba(255,255,255,0.55);margin-top:4px;letter-spacing:0.5px;">TRANSAKSI</div>
      </div>
      <?php if ($stats['pending'] > 0): ?>
      <div style="text-align:center;padding:12px 16px;background:rgba(245,194,122,0.15);border:1px solid rgba(245,194,122,0.30);border-radius:12px;">
        <div style="font-size:22px;font-weight:700;color:#F5C27A;"><?= $stats['pending'] ?></div>
        <div style="font-size:11px;color:rgba(255,255,255,0.55);margin-top:4px;letter-spacing:0.5px;">â³ VERIFIKASI</div>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- KPI Stat Cards -->
  <div class="stat-grid" style="margin-bottom:24px;">
    <a href="../admin/kelola_produk.php" class="stat-card" style="text-decoration:none;color:inherit;" id="statProduk">
      <div class="stat-card__icon stat-card__icon--blue"><svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg></div>
      <div class="stat-card__label">Total Produk</div>
      <div class="stat-card__value"><?= $stats['produk'] ?></div>
      <div class="stat-card__change">Unit aktif di katalog</div>
    </a>
    <a href="../admin/kelola_user.php" class="stat-card" style="text-decoration:none;color:inherit;" id="statUser">
      <div class="stat-card__icon stat-card__icon--green"><svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg></div>
      <div class="stat-card__label">Total Pelanggan</div>
      <div class="stat-card__value"><?= $stats['users'] ?></div>
      <div class="stat-card__change">Akun terdaftar</div>
    </a>
    <a href="../admin/kelola_servis.php" class="stat-card" style="text-decoration:none;color:inherit;" id="statServis">
      <div class="stat-card__icon stat-card__icon--amber"><svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z"/></svg></div>
      <div class="stat-card__label">Servis Aktif</div>
      <div class="stat-card__value"><?= $stats['servis'] ?></div>
      <div class="stat-card__change">Pending & diproses</div>
    </a>
    <a href="../admin/verifikasi_pembayaran.php" class="stat-card" style="text-decoration:none;color:inherit;" id="statVerifikasi">
      <div class="stat-card__icon stat-card__icon--taupe"><svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
      <div class="stat-card__label">Perlu Verifikasi</div>
      <div class="stat-card__value"><?= $stats['pending'] ?></div>
      <div class="stat-card__change">Bukti bayar masuk</div>
    </a>
  </div>

  <!-- Chart + Top Products -->
  <div style="display:grid;grid-template-columns:3fr 2fr;gap:20px;margin-bottom:24px;">
    <div class="card" style="padding:24px;">
      <div style="font-size:15px;font-weight:600;color:var(--color-carbon);margin-bottom:20px;">📈 Pendapatan 6 Bulan Terakhir</div>
      <canvas id="chartRevenue" height="130"></canvas>
    </div>
    <div class="table-wrap">
      <div class="table-toolbar">
        <div class="table-toolbar__title">🏆 Produk Terlaris</div>
        <a href="../admin/laporan.php" class="btn btn--ghost btn--sm">Laporan →</a>
      </div>
      <table>
        <thead><tr><th>#</th><th>Produk</th><th>Unit</th></tr></thead>
        <tbody>
        <?php if ($rTop && $rTop->num_rows > 0): $rank=1; while ($p=$rTop->fetch_assoc()): ?>
        <tr>
          <td><div style="width:24px;height:24px;border-radius:50%;background:<?= $rank===1?'var(--color-navy)':($rank===2?'var(--color-taupe)':'var(--color-cream)') ?>;color:<?= $rank<=2?'white':'var(--color-pewter)' ?>;display:grid;place-items:center;font-size:11px;font-weight:700;"><?= $rank++ ?></div></td>
          <td style="font-size:13px;font-weight:500;color:var(--color-carbon);"><?= htmlspecialchars($p['nama_laptop']) ?></td>
          <td><span class="badge badge--blue"><?= $p['terjual'] ?></span></td>
        </tr>
        <?php endwhile; else: ?>
        <tr><td colspan="3"><div class="empty-state" style="padding:20px;"><p>Belum ada data</p></div></td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Recent Transactions -->
  <div class="table-wrap">
    <div class="table-toolbar">
      <div class="table-toolbar__title">Transaksi Terbaru</div>
      <a href="../admin/kelola_transaksi.php" class="btn btn--ghost btn--sm" id="btnSemuaTrx">Lihat Semua →</a>
    </div>
    <table>
      <thead><tr><th>Order ID</th><th>Pelanggan</th><th>Total</th><th>Status</th><th>Waktu</th></tr></thead>
      <tbody>
      <?php if ($rTrx && $rTrx->num_rows > 0): while ($t=$rTrx->fetch_assoc()): ?>
      <tr>
        <td><code style="font-size:11px;background:var(--color-cream);padding:2px 5px;border-radius:3px;border:1px solid var(--color-cream-border);"><?= htmlspecialchars(substr($t['order_id']??'-',0,14)) ?>…</code></td>
        <td style="font-weight:600;color:var(--color-carbon);"><?= htmlspecialchars($t['nama_user']) ?></td>
        <td style="color:var(--color-navy);font-weight:700;"><?= formatRupiah($t['total_harga']) ?></td>
        <td><?php $bm=['paid'=>['green','Lunas'],'unpaid'=>['amber','Menunggu'],'failed'=>['red','Gagal'],'pending_verify'=>['taupe','Verifikasi']]; [$bc,$bl]=$bm[$t['status_pembayaran']]??['gray','—']; ?><span class="badge badge--<?= $bc ?>"><?= $bl ?></span></td>
        <td style="font-size:12px;color:var(--color-pewter);"><?= date('d M Y', strtotime($t['waktu_transaksi'])) ?></td>
      </tr>
      <?php endwhile; else: ?>
      <tr><td colspan="5"><div class="empty-state"><p>Belum ada transaksi</p></div></td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>

</main>
</div>

<script src="../assets/js/main.js"></script>
<script>
new Chart(document.getElementById('chartRevenue'), {
  type: 'bar',
  data: {
    labels: <?= json_encode($chartMonths) ?>,
    datasets: [{ label: 'Pendapatan', data: <?= json_encode($chartRevs) ?>, backgroundColor: 'rgba(62,92,118,0.15)', borderColor: '#3E5C76', borderWidth: 2, borderRadius: 8, hoverBackgroundColor: 'rgba(62,92,118,0.28)' }]
  },
  options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { ticks: { callback: v => 'Rp'+(v/1000000).toFixed(1)+'jt' }, grid: { color: 'rgba(221,216,203,0.6)' } }, x: { grid: { display: false } } } }
});
</script>
</body>
</html>
