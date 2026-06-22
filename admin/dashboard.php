<?php
session_start();
require_once '../config/koneksi.php';
requireAdmin();

$basePath = '../';
$pageTitle = 'Dashboard Admin — A-LINKS';

// ── Statistik ──
$stats = [];

// Total produk
$r = $koneksi->query("SELECT COUNT(*) AS total FROM produk"); 
$stats['produk'] = $r->fetch_assoc()['total'] ?? 0;

// Total transaksi paid
$r = $koneksi->query("SELECT COUNT(*) AS total, COALESCE(SUM(total_harga),0) AS pendapatan FROM transaksi WHERE status_pembayaran='paid'");
$row = $r->fetch_assoc();
$stats['transaksi']  = $row['total'] ?? 0;
$stats['pendapatan'] = $row['pendapatan'] ?? 0;

// Servis aktif (pending + proses)
$r = $koneksi->query("SELECT COUNT(*) AS total FROM servis WHERE status IN('pending','proses')");
$stats['servis_aktif'] = $r->fetch_assoc()['total'] ?? 0;

// Total user
$r = $koneksi->query("SELECT COUNT(*) AS total FROM users WHERE role='user'");
$stats['users'] = $r->fetch_assoc()['total'] ?? 0;

// ── Transaksi terbaru ──
$transaksi_terbaru = $koneksi->query("
    SELECT t.*, u.nama AS nama_user
    FROM transaksi t
    JOIN users u ON t.id_user = u.id_user
    ORDER BY t.waktu_transaksi DESC
    LIMIT 8
");

// ── Servis terbaru ──
$servis_terbaru = $koneksi->query("
    SELECT s.*, u.nama AS nama_user
    FROM servis s
    JOIN users u ON s.id_user = u.id_user
    ORDER BY s.tgl_masuk DESC
    LIMIT 6
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= $pageTitle ?></title>
  <meta name="description" content="Dashboard admin A-LINKS — kelola produk, servis, dan transaksi."/>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="../assets/css/style.css"/>
</head>
<body>

<div class="app-layout">
  <?php include '../includes/sidebar_admin.php'; ?>

  <main class="main-content">
    <!-- Page header -->
    <div class="page-header">
      <div>
        <h1 class="page-header__title">Dashboard</h1>
        <div class="page-header__sub">Selamat datang, <?= htmlspecialchars($_SESSION['nama']) ?>. Berikut ringkasan hari ini.</div>
      </div>
      <div style="display:flex;gap:8px;">
        <a href="kelola_produk.php" class="btn btn--primary btn--sm" id="btnTambahProduk">+ Tambah Produk</a>
        <a href="kelola_servis.php" class="btn btn--secondary btn--sm" id="btnLihatServis">Lihat Servis</a>
      </div>
    </div>

    <?php renderFlash(); ?>

    <!-- Stat cards -->
    <div class="stat-grid">
      <a href="kelola_produk.php" class="stat-card" id="statProduk" style="text-decoration:none;color:inherit;display:block;">
        <div class="stat-card__icon stat-card__icon--blue">
          <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
          </svg>
        </div>
        <div class="stat-card__label">Total Produk</div>
        <div class="stat-card__value"><?= $stats['produk'] ?></div>
        <div class="stat-card__change">Unit tersedia di katalog</div>
      </a>

      <a href="laporan.php" class="stat-card" id="statPendapatan" style="text-decoration:none;color:inherit;display:block;">
        <div class="stat-card__icon stat-card__icon--green">
          <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
        </div>
        <div class="stat-card__label">Total Pendapatan</div>
        <div class="stat-card__value" style="font-size:20px;"><?= formatRupiah($stats['pendapatan']) ?></div>
        <div class="stat-card__change"><?= $stats['transaksi'] ?> transaksi berhasil</div>
      </a>

      <a href="kelola_servis.php" class="stat-card" id="statServis" style="text-decoration:none;color:inherit;display:block;">
        <div class="stat-card__icon stat-card__icon--amber">
          <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z"/>
          </svg>
        </div>
        <div class="stat-card__label">Servis Aktif</div>
        <div class="stat-card__value"><?= $stats['servis_aktif'] ?></div>
        <div class="stat-card__change">Sedang diproses</div>
      </a>

      <a href="kelola_user.php" class="stat-card" id="statUser" style="text-decoration:none;color:inherit;display:block;">
        <div class="stat-card__icon stat-card__icon--red">
          <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
          </svg>
        </div>
        <div class="stat-card__label">Total Pelanggan</div>
        <div class="stat-card__value"><?= $stats['users'] ?></div>
        <div class="stat-card__change">Akun terdaftar</div>
      </a>
    </div>

    <!-- Two column grid -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px;">

      <!-- Transaksi Terbaru -->
      <div class="table-wrap" style="grid-column:1/-1;">
        <div class="table-toolbar">
          <div class="table-toolbar__title">Transaksi Terbaru</div>
          <a href="kelola_transaksi.php" class="btn btn--secondary btn--sm" id="btnAllTransaksi">Lihat Semua</a>
        </div>
        <table>
          <thead>
            <tr>
              <th>Order ID</th>
              <th>Pelanggan</th>
              <th>Total</th>
              <th>Status</th>
              <th>Waktu</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($transaksi_terbaru && $transaksi_terbaru->num_rows > 0):
              while ($t = $transaksi_terbaru->fetch_assoc()): ?>
            <tr>
              <td><code style="font-size:12px;color:var(--color-pewter);"><?= htmlspecialchars($t['order_id'] ?? '-') ?></code></td>
              <td style="font-weight:500;color:var(--color-carbon);"><?= htmlspecialchars($t['nama_user']) ?></td>
              <td style="color:var(--color-blue);font-weight:500;"><?= formatRupiah($t['total_harga']) ?></td>
              <td>
                <?php
                  $badgeMap = ['paid'=>'green','unpaid'=>'amber','failed'=>'red'];
                  $labelMap = ['paid'=>'Lunas','unpaid'=>'Menunggu','failed'=>'Gagal'];
                  $cls = $badgeMap[$t['status_pembayaran']] ?? 'gray';
                  $lbl = $labelMap[$t['status_pembayaran']] ?? $t['status_pembayaran'];
                ?>
                <span class="badge badge--<?= $cls ?>"><?= $lbl ?></span>
              </td>
              <td style="color:var(--color-pewter);font-size:13px;"><?= date('d M Y H:i', strtotime($t['waktu_transaksi'])) ?></td>
            </tr>
            <?php endwhile; else: ?>
            <tr><td colspan="5" class="empty-state"><p>Belum ada transaksi</p></td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Servis Terbaru -->
    <div class="table-wrap">
      <div class="table-toolbar">
        <div class="table-toolbar__title">Servis Masuk Terbaru</div>
        <a href="kelola_servis.php" class="btn btn--secondary btn--sm" id="btnAllServis">Kelola Servis</a>
      </div>
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Pelanggan</th>
            <th>Tipe Laptop</th>
            <th>Keluhan</th>
            <th>Status</th>
            <th>Tgl Masuk</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($servis_terbaru && $servis_terbaru->num_rows > 0):
            while ($s = $servis_terbaru->fetch_assoc()): ?>
          <tr>
            <td><span style="font-size:12px;color:var(--color-pewter);">#<?= $s['id_servis'] ?></span></td>
            <td style="font-weight:500;color:var(--color-carbon);"><?= htmlspecialchars($s['nama_user']) ?></td>
            <td><?= htmlspecialchars($s['tipe_laptop']) ?></td>
            <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($s['keluhan']) ?></td>
            <td>
              <?php
                $sMap = ['pending'=>'gray','proses'=>'amber','selesai'=>'green','diambil'=>'blue'];
                $sLbl = ['pending'=>'Pending','proses'=>'Proses','selesai'=>'Selesai','diambil'=>'Diambil'];
                $sc   = $sMap[$s['status']] ?? 'gray';
                $sl   = $sLbl[$s['status']] ?? $s['status'];
              ?>
              <span class="badge badge--<?= $sc ?>"><?= $sl ?></span>
            </td>
            <td style="color:var(--color-pewter);font-size:13px;"><?= date('d M Y', strtotime($s['tgl_masuk'])) ?></td>
            <td>
              <a href="kelola_servis.php?edit=<?= $s['id_servis'] ?>" class="btn btn--secondary btn--sm" id="btnEditServis-<?= $s['id_servis'] ?>">Update</a>
            </td>
          </tr>
          <?php endwhile; else: ?>
          <tr><td colspan="7" class="empty-state"><p>Belum ada data servis</p></td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

  </main>
</div>

<script src="../assets/js/main.js"></script>
</body>
</html>
