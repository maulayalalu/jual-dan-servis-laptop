<?php
session_start();
require_once '../config/koneksi.php';
requireStaff('admin', 'owner'); // kasir tidak bisa akses laporan
$basePath = '../';
$pageTitle = 'Laporan Penjualan — A-LINKS';

// Date filter
$bulan = $_GET['bulan'] ?? date('m');
$tahun = $_GET['tahun'] ?? date('Y');

// Revenue per day this month
$rHarian = $koneksi->query("SELECT DATE(waktu_transaksi) as tgl, SUM(total_harga) as total FROM transaksi WHERE MONTH(waktu_transaksi)=$bulan AND YEAR(waktu_transaksi)=$tahun AND status_pembayaran='paid' GROUP BY DATE(waktu_transaksi) ORDER BY tgl ASC");
$harian = [];
while ($r = $rHarian->fetch_assoc()) $harian[$r['tgl']] = $r['total'];

// Total summary
$rSummary = $koneksi->query("SELECT 
    COUNT(*) as total_order, 
    COALESCE(SUM(total_harga),0) as total_revenue,
    COUNT(CASE WHEN status_pembayaran='paid' THEN 1 END) as paid,
    COUNT(CASE WHEN status_pembayaran='unpaid' THEN 1 END) as unpaid,
    COUNT(CASE WHEN status_pembayaran='pending_verify' THEN 1 END) as pending
  FROM transaksi WHERE MONTH(waktu_transaksi)=$bulan AND YEAR(waktu_transaksi)=$tahun");
$summary = $rSummary->fetch_assoc();

// Top products this month
$rTop = $koneksi->query("SELECT p.nama_laptop, SUM(d.jumlah) as terjual, SUM(d.jumlah * d.harga_satuan) as pendapatan FROM detail_transaksi d JOIN produk p ON d.id_produk=p.id_produk JOIN transaksi t ON d.id_transaksi=t.id_transaksi WHERE MONTH(t.waktu_transaksi)=$bulan AND YEAR(t.waktu_transaksi)=$tahun AND t.status_pembayaran='paid' GROUP BY d.id_produk ORDER BY terjual DESC LIMIT 5");

// Revenue per month (current year)
$rBulanan = $koneksi->query("SELECT MONTH(waktu_transaksi) as bln, SUM(total_harga) as total FROM transaksi WHERE YEAR(waktu_transaksi)=$tahun AND status_pembayaran='paid' GROUP BY MONTH(waktu_transaksi) ORDER BY bln ASC");
$bulanan = array_fill(1, 12, 0);
while ($r = $rBulanan->fetch_assoc()) $bulanan[$r['bln']] = $r['total'];

$namaBulan = ['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
$chartLabels = json_encode(array_values($namaBulan), JSON_UNESCAPED_UNICODE);
$chartData = json_encode(array_values($bulanan));

// Servis summary
$rServis = $koneksi->query("SELECT status, COUNT(*) as total FROM servis WHERE MONTH(tgl_masuk)=$bulan AND YEAR(tgl_masuk)=$tahun GROUP BY status");
$servisStats = ['pending'=>0,'proses'=>0,'selesai'=>0,'diambil'=>0];
while($r = $rServis->fetch_assoc()) $servisStats[$r['status']] = $r['total'];
?>
<!DOCTYPE html><html lang="id"><head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title><?= $pageTitle ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="../assets/css/style.css?v=<?= time() ?>"/>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head><body>
<div class="app-layout">
<?php include '../includes/sidebar_admin.php'; ?>
<main class="main-content">
  <div class="page-header">
    <div><h1 class="page-header__title">Laporan Penjualan</h1><div class="page-header__sub">Analisis performa toko & keuangan</div></div>
    <!-- Month/Year Filter -->
    <form method="GET" style="display:flex;gap:8px;">
      <select name="bulan" class="form-control form-select" style="height:auto;padding:8px 12px;width:130px;">
        <?php for($m=1;$m<=12;$m++): ?>
        <option value="<?= $m ?>" <?= $m==(int)$bulan?'selected':'' ?>><?= $namaBulan[$m] ?></option>
        <?php endfor; ?>
      </select>
      <select name="tahun" class="form-control form-select" style="height:auto;padding:8px 12px;width:100px;">
        <?php for($y=date('Y');$y>=2023;$y--): ?>
        <option value="<?= $y ?>" <?= $y==(int)$tahun?'selected':'' ?>><?= $y ?></option>
        <?php endfor; ?>
      </select>
      <button type="submit" class="btn btn--secondary btn--sm">Filter</button>
    </form>
  </div>

  <!-- Summary Cards -->
  <div class="stat-grid" style="margin-bottom:28px;">
    <div class="stat-card">
      <div class="stat-card__label">Total Pendapatan (Bulan ini)</div>
      <div class="stat-card__value"><?= formatRupiah($summary['total_revenue']) ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-card__label">Total Pesanan</div>
      <div class="stat-card__value"><?= $summary['total_order'] ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-card__label">Pesanan Lunas</div>
      <div class="stat-card__value"><?= $summary['paid'] ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-card__label">Menunggu Verifikasi</div>
      <div class="stat-card__value"><?= $summary['pending'] ?? 0 ?></div>
    </div>
  </div>

  <!-- Charts Row -->
  <div style="display:grid;grid-template-columns:2fr 1fr;gap:24px;margin-bottom:28px;">
    <!-- Monthly Chart -->
    <div class="card" style="padding:24px;">
      <div style="font-size:15px;font-weight:600;color:var(--color-carbon);margin-bottom:20px;">Pendapatan per Bulan (<?= $tahun ?>)</div>
      <canvas id="chartBulanan" height="100"></canvas>
    </div>

    <!-- Servis Status -->
    <div class="card" style="padding:24px;">
      <div style="font-size:15px;font-weight:600;color:var(--color-carbon);margin-bottom:20px;">Status Servis Bulan Ini</div>
      <canvas id="chartServis" height="200"></canvas>
    </div>
  </div>

  <!-- Top Products -->
  <div class="card">
    <div class="table-toolbar">
      <div class="table-toolbar__title">Top 5 Produk Terlaris — <?= $namaBulan[(int)$bulan] ?> <?= $tahun ?></div>
    </div>
    <div style="overflow-x: auto; width: 100%;">
      <table style="min-width: 600px;">
        <thead><tr><th>Peringkat</th><th>Nama Produk</th><th>Unit Terjual</th><th>Pendapatan</th></tr></thead>
        <tbody>
        <?php $rank=1; while($p = $rTop->fetch_assoc()): ?>
        <tr>
          <td><div style="width:28px;height:28px;border-radius:50%;background:<?= $rank===1?'var(--color-navy)':'var(--color-cream)' ?>;color:<?= $rank===1?'white':'var(--color-pewter)' ?>;display:grid;place-items:center;font-weight:700;font-size:13px;border:<?= $rank===1?'none':'1px solid var(--color-cream-border)' ?>;"><?= $rank++ ?></div></td>
          <td style="font-weight:500;color:var(--color-carbon);"><?= htmlspecialchars($p['nama_laptop']) ?></td>
          <td><span class="badge badge--blue"><?= $p['terjual'] ?> unit</span></td>
          <td style="color:var(--color-navy);font-weight:700;"><?= formatRupiah($p['pendapatan']) ?></td>
        </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>

</main>
</div>

<script>
// Monthly Revenue Chart
const ctx1 = document.getElementById('chartBulanan');
new Chart(ctx1, {
  type: 'bar',
  data: {
    labels: <?= $chartLabels ?>.slice(1),
    datasets: [{
      label: 'Pendapatan (Rp)',
      data: <?= $chartData ?>.slice(1),
      backgroundColor: 'rgba(62, 92, 118, 0.15)',
      borderColor: '#3E5C76',
      borderWidth: 2,
      borderRadius: 6,
    }]
  },
  options: {
    responsive: true,
    plugins: { legend: { display: false } },
    scales: {
      y: { ticks: { callback: v => 'Rp ' + (v/1000000).toFixed(1) + 'jt' }, grid: { color: '#f3f4f6' } },
      x: { grid: { display: false } }
    }
  }
});

// Servis Donut Chart
const ctx2 = document.getElementById('chartServis');
new Chart(ctx2, {
  type: 'doughnut',
  data: {
    labels: ['Pending', 'Diproses', 'Selesai', 'Diambil'],
    datasets: [{
      data: [<?= $servisStats['pending'] ?>, <?= $servisStats['proses'] ?>, <?= $servisStats['selesai'] ?>, <?= $servisStats['diambil'] ?>],
      backgroundColor: ['#A8B7C2','#D8CFC2','#3E5C76','#2B4158'],
      borderWidth: 0,
      hoverOffset: 8
    }]
  },
  options: { responsive: true, plugins: { legend: { position: 'bottom' } }, cutout: '65%' }
});
</script>
</body></html>
