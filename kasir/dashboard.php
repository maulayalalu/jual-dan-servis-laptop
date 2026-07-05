<?php
session_start();
require_once '../config/koneksi.php';
requireKasir(); // admin & kasir only
$basePath  = '../';
$pageTitle = 'Kasir Dashboard — A-LINKS';

// ── Kasir Stats ──
$stats = [];
$today = date('Y-m-d');

// Transaksi hari ini
$stats['trx_today'] = $koneksi->query("SELECT COUNT(*) AS c FROM transaksi WHERE DATE(waktu_transaksi)='$today'")->fetch_assoc()['c'];
// Pendapatan hari ini
$stats['rev_today'] = $koneksi->query("SELECT COALESCE(SUM(total_harga),0) AS total FROM transaksi WHERE status_pembayaran='paid' AND DATE(waktu_transaksi)='$today'")->fetch_assoc()['total'];
// Perlu verifikasi
$stats['pending']   = $koneksi->query("SELECT COUNT(*) AS c FROM transaksi WHERE status_pembayaran='pending_verify'")->fetch_assoc()['c'];
// Belum lunas
$stats['unpaid']    = $koneksi->query("SELECT COUNT(*) AS c FROM transaksi WHERE status_pembayaran='unpaid'")->fetch_assoc()['c'];

// Transaksi pending verify
$rPending = $koneksi->query("SELECT t.*, u.nama AS nama_user FROM transaksi t JOIN users u ON t.id_user=u.id_user WHERE t.status_pembayaran='pending_verify' ORDER BY t.waktu_transaksi ASC");

// Transaksi hari ini
$rToday = $koneksi->query("SELECT t.*, u.nama AS nama_user FROM transaksi t JOIN users u ON t.id_user=u.id_user WHERE DATE(t.waktu_transaksi)='$today' ORDER BY t.waktu_transaksi DESC LIMIT 8");

?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= $pageTitle ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="../assets/css/style.css?v=<?= time() ?>"/>
</head>
<body>

<div class="app-layout">
  <?php include '../includes/sidebar_admin.php'; ?>

  <main class="main-content">

    <!-- Page Header -->
    <div class="page-header">
      <div>
        <h1 class="page-header__title">Dashboard Kasir</h1>
        <div class="page-header__sub">Halo, <?= htmlspecialchars($_SESSION['nama']) ?>. Mari kelola transaksi hari ini.</div>
      </div>
      <a href="../admin/verifikasi_pembayaran.php" class="btn btn--primary btn--sm">✓ Verifikasi Pembayaran</a>
    </div>

    <?php renderFlash(); ?>

    <!-- KPI Stat Cards -->
    <div class="stat-grid" style="margin-bottom:24px;">
      <a href="../admin/kelola_transaksi.php" class="stat-card" style="text-decoration:none;color:inherit;">
        <div class="stat-card__icon stat-card__icon--blue">
          <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div class="stat-card__label">Pendapatan Hari Ini</div>
        <div class="stat-card__value" style="font-size:24px;"><?= formatRupiah($stats['rev_today']) ?></div>
        <div class="stat-card__change">Total transaksi lunas</div>
      </a>
      <a href="../admin/kelola_transaksi.php" class="stat-card" style="text-decoration:none;color:inherit;">
        <div class="stat-card__icon stat-card__icon--green">
          <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
        </div>
        <div class="stat-card__label">Transaksi Hari Ini</div>
        <div class="stat-card__value"><?= $stats['trx_today'] ?></div>
        <div class="stat-card__change">Semua pesanan baru</div>
      </a>
      <a href="../admin/verifikasi_pembayaran.php" class="stat-card" style="text-decoration:none;color:inherit;">
        <div class="stat-card__icon stat-card__icon--amber">
          <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div class="stat-card__label">Perlu Verifikasi</div>
        <div class="stat-card__value"><?= $stats['pending'] ?></div>
        <div class="stat-card__change">Tunggu konfirmasi</div>
      </a>
      <a href="../admin/kelola_transaksi.php" class="stat-card" style="text-decoration:none;color:inherit;">
        <div class="stat-card__icon stat-card__icon--red">
          <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        </div>
        <div class="stat-card__label">Belum Dibayar</div>
        <div class="stat-card__value"><?= $stats['unpaid'] ?></div>
        <div class="stat-card__change">Pesanan unpaid</div>
      </a>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">
      <!-- Perlu Verifikasi -->
      <div class="table-wrap">
        <div class="table-toolbar">
          <div class="table-toolbar__title" style="color:var(--color-warning);">⏳ Perlu Verifikasi</div>
          <a href="../admin/verifikasi_pembayaran.php" class="btn btn--ghost btn--sm">Proses →</a>
        </div>
        <table>
          <thead><tr><th>Order ID</th><th>Total</th><th>Aksi</th></tr></thead>
          <tbody>
          <?php if ($rPending && $rPending->num_rows > 0): while ($p=$rPending->fetch_assoc()): ?>
          <tr>
            <td>
              <div style="font-weight:600;color:var(--color-carbon);font-size:13px;"><?= htmlspecialchars($p['nama_user']) ?></div>
              <code style="font-size:11px;color:var(--color-pewter);"><?= htmlspecialchars($p['order_id']) ?></code>
            </td>
            <td style="color:var(--color-navy);font-weight:600;"><?= formatRupiah($p['total_harga']) ?></td>
            <td><a href="../admin/verifikasi_pembayaran.php" class="btn btn--secondary btn--sm" style="padding:4px 10px;font-size:12px;">Cek</a></td>
          </tr>
          <?php endwhile; else: ?>
          <tr><td colspan="3"><div class="empty-state"><p>Tidak ada transaksi pending.</p></div></td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Transaksi Hari Ini -->
      <div class="table-wrap">
        <div class="table-toolbar">
          <div class="table-toolbar__title">🛒 Transaksi Hari Ini</div>
          <a href="../admin/kelola_transaksi.php" class="btn btn--ghost btn--sm">Semua →</a>
        </div>
        <table>
          <thead><tr><th>Order ID</th><th>Total</th><th>Status</th></tr></thead>
          <tbody>
          <?php if ($rToday && $rToday->num_rows > 0): while ($t=$rToday->fetch_assoc()): ?>
          <tr>
            <td>
              <div style="font-weight:600;color:var(--color-carbon);font-size:13px;"><?= htmlspecialchars($t['nama_user']) ?></div>
              <code style="font-size:11px;color:var(--color-pewter);"><?= htmlspecialchars($t['order_id']) ?></code>
            </td>
            <td style="color:var(--color-navy);font-weight:600;"><?= formatRupiah($t['total_harga']) ?></td>
            <td><?php $bm=['paid'=>['green','Lunas'],'unpaid'=>['amber','Menunggu'],'failed'=>['red','Gagal'],'pending_verify'=>['taupe','Verif']]; [$bc,$bl]=$bm[$t['status_pembayaran']]??['gray','—']; ?><span class="badge badge--<?= $bc ?>"><?= $bl ?></span></td>
          </tr>
          <?php endwhile; else: ?>
          <tr><td colspan="3"><div class="empty-state"><p>Belum ada transaksi hari ini.</p></div></td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </main>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>
