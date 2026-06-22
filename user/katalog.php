<?php
session_start();
require_once '../config/koneksi.php';
$basePath = '../';
$pageTitle = 'Katalog Produk — A-LINKS';
$navActive = 'katalog';

// Handle Add to Cart & Buy Now
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['add_to_cart']) || isset($_POST['buy_now']))) {
    verify_csrf();
    requireUser(); // Ensure logged in
    $id_produk = (int)($_POST['id_produk'] ?? 0);
    $qty = (int)($_POST['qty'] ?? 1);
    $id_user = $_SESSION['id_user'];
    
    if ($id_produk > 0 && $qty > 0) {
        $stmt = $koneksi->prepare("INSERT INTO keranjang (id_user, id_produk, qty) VALUES (?,?,?) ON DUPLICATE KEY UPDATE qty = qty + VALUES(qty)");
        $stmt->bind_param('iii', $id_user, $id_produk, $qty);
        $stmt->execute();
        $stmt->close();
        if (isset($_POST['buy_now'])) {
            redirect('keranjang.php');
        } else {
            setFlash('success', 'Produk berhasil ditambahkan ke keranjang.');
            redirect('katalog.php');
        }
    }
}

// Filters
$search = trim($_GET['q'] ?? '');
$tipe = trim($_GET['tipe'] ?? '');
$min_harga = (int)($_GET['min_harga'] ?? 0);
$max_harga = (int)($_GET['max_harga'] ?? 0);

$page = (int)($_GET['page'] ?? 1);
if ($page < 1) $page = 1;
$limit = 8;
$offset = ($page - 1) * $limit;

$sql = "SELECT * FROM produk WHERE is_deleted=0";
$sqlCount = "SELECT COUNT(*) AS total FROM produk WHERE is_deleted=0";
$params = [];
$types = '';

if ($search) {
    $clause = " AND (nama_laptop LIKE ? OR deskripsi LIKE ?)";
    $sql .= $clause; $sqlCount .= $clause;
    $params[] = "%$search%"; $params[] = "%$search%";
    $types .= 'ss';
}

if ($tipe) {
    $clause = " AND (nama_laptop LIKE ? OR deskripsi LIKE ?)";
    $sql .= $clause; $sqlCount .= $clause;
    $params[] = "%$tipe%"; $params[] = "%$tipe%";
    $types .= 'ss';
}

if ($min_harga > 0) {
    $sql .= " AND harga >= ?"; $sqlCount .= " AND harga >= ?";
    $params[] = $min_harga; $types .= 'i';
}

if ($max_harga > 0) {
    $sql .= " AND harga <= ?"; $sqlCount .= " AND harga <= ?";
    $params[] = $max_harga; $types .= 'i';
}

// Get Total
if ($types) {
    $stmtC = $koneksi->prepare($sqlCount);
    $stmtC->bind_param($types, ...$params);
    $stmtC->execute();
    $totalData = $stmtC->get_result()->fetch_assoc()['total'];
    $stmtC->close();
} else {
    $totalData = $koneksi->query($sqlCount)->fetch_assoc()['total'];
}

$totalPages = ceil($totalData / $limit);

$sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit; $params[] = $offset;
$types .= 'ii';

$stmt = $koneksi->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$produkList = $stmt->get_result();
$stmt->close();

include '../includes/header.php';
?>

<div style="min-height:100vh;background:var(--color-light-ash);padding-top:72px;">
  <div class="container" style="padding-top:32px;padding-bottom:80px;">
    
    <div class="page-header">
      <div>
        <h1 class="page-header__title">Katalog Laptop</h1>
        <div class="page-header__sub">Temukan laptop terbaik untuk kebutuhan Anda</div>
      </div>
    </div>

    <?php renderFlash(); ?>

    <!-- Search & Filter -->
    <div style="margin-bottom:32px;display:flex;gap:16px;flex-wrap:wrap;justify-content:space-between;align-items:center;">
        <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
            <div class="search-input" style="width:250px;">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="var(--color-silver-fog)" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                <input type="text" name="q" placeholder="Cari merk..." value="<?= htmlspecialchars($search) ?>" />
            </div>
            <input type="number" name="min_harga" class="form-control" style="width:130px;height:38px;padding:8px 12px;" placeholder="Min Harga" value="<?= $min_harga ?: '' ?>">
            <span style="color:var(--color-pewter)">-</span>
            <input type="number" name="max_harga" class="form-control" style="width:130px;height:38px;padding:8px 12px;" placeholder="Max Harga" value="<?= $max_harga ?: '' ?>">
            <button type="submit" class="btn btn--secondary">Cari</button>
            <?php if($search || $tipe || $min_harga || $max_harga): ?>
                <a href="katalog.php" class="btn btn--secondary">Reset</a>
            <?php endif; ?>
        </form>

        <div style="display:flex;gap:8px;">
            <a href="katalog.php" class="btn btn--sm <?= !$tipe ? 'btn--primary' : 'btn--secondary' ?>">Semua</a>
            <a href="katalog.php?tipe=gaming" class="btn btn--sm <?= $tipe==='gaming' ? 'btn--primary' : 'btn--secondary' ?>">Gaming</a>
            <a href="katalog.php?tipe=bisnis" class="btn btn--sm <?= $tipe==='bisnis' ? 'btn--primary' : 'btn--secondary' ?>">Bisnis</a>
            <a href="katalog.php?tipe=pelajar" class="btn btn--sm <?= $tipe==='pelajar' ? 'btn--primary' : 'btn--secondary' ?>">Pelajar</a>
        </div>
    </div>

    <!-- Product Grid -->
    <div class="grid-4" style="gap:24px;">
      <?php if ($produkList->num_rows > 0): while ($p = $produkList->fetch_assoc()): ?>
      <div class="product-card">
        <a href="detail_produk.php?id=<?= $p['id_produk'] ?>">
          <img class="product-card__img" src="<?= !empty($p['gambar']) ? '../'.$p['gambar'] : 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=600&q=80' ?>" alt="<?= htmlspecialchars($p['nama_laptop']) ?>" onerror="this.src='https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=600&q=80'">
        </a>
        <div class="product-card__body">
          <a href="detail_produk.php?id=<?= $p['id_produk'] ?>" style="text-decoration:none;">
            <div class="product-card__name"><?= htmlspecialchars($p['nama_laptop']) ?></div>
          </a>
          <div class="product-card__desc"><?= htmlspecialchars($p['deskripsi']) ?></div>
          
          <div style="font-size:12px;color:var(--color-blue);margin-top:8px;font-weight:600;display:flex;align-items:center;gap:4px;">
            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
            <?= htmlspecialchars($p['garansi']) ?>
          </div>

          <!-- Rating Stars -->
          <?php if ($p['rata_rating'] > 0): ?>
          <div style="display:flex;gap:2px;margin-top:8px;">
            <?php $r = round($p['rata_rating']); for($i=1;$i<=5;$i++) echo '<span style="font-size:12px;color:'.($i<=$r?'#f59e0b':'#d1d5db').'">★</span>'; ?>
            <span style="font-size:11px;color:var(--color-pewter);margin-left:4px;"><?= number_format($p['rata_rating'],1) ?></span>
          </div>
          <?php endif; ?>
          <div style="margin-top:auto;">
              <div class="product-card__price"><?= formatRupiah($p['harga']) ?></div>
              <div style="font-size:12px;color:<?= $p['stok'] > 0 ? 'var(--color-pewter)' : '#d92b2b' ?>;margin-top:4px;">Stok: <?= $p['stok'] > 0 ? $p['stok'] . ' unit' : 'Habis' ?></div>
          </div>
        </div>
        <div class="product-card__footer">
            <?php if (isLoggedIn()): ?>
            <form method="POST" style="width:100%;">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="id_produk" value="<?= $p['id_produk'] ?>">
                <input type="hidden" name="qty" value="1">
                <div style="display:grid; grid-template-columns: 1fr 40px; gap: 8px; margin-bottom: 8px;">
                    <button type="submit" name="buy_now" class="btn btn--primary btn--sm" <?= $p['stok'] <= 0 ? 'disabled' : '' ?> style="padding: 8px 0; width: 100%; min-width: 0; white-space: nowrap; <?= $p['stok'] <= 0 ? 'opacity:0.5;cursor:not-allowed;' : '' ?>">
                        Beli Langsung
                    </button>
                    <button type="submit" name="add_to_cart" class="btn btn--secondary btn--sm" title="Tambah ke Keranjang" <?= $p['stok'] <= 0 ? 'disabled' : '' ?> style="padding: 0; width: 100%; height: 32px; min-width: 0; display: flex; align-items: center; justify-content: center; <?= $p['stok'] <= 0 ? 'opacity:0.5;cursor:not-allowed;' : '' ?>">
                        <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </button>
                </div>
                <a href="detail_produk.php?id=<?= $p['id_produk'] ?>" class="btn btn--secondary btn--sm btn--full" style="background:var(--color-cloud);color:var(--color-carbon);border:none;">
                    Lihat Detail
                </a>
            </form>
            <?php else: ?>
            <a href="detail_produk.php?id=<?= $p['id_produk'] ?>" class="btn btn--secondary btn--sm btn--full" style="margin-bottom:8px;">Lihat Detail</a>
            <a href="../login.php" class="btn btn--primary btn--sm btn--full">Login untuk Beli</a>
            <?php endif; ?>
        </div>
      </div>
      <?php endwhile; else: ?>
        <div style="grid-column:1/-1;text-align:center;padding:60px 20px;">
            <div class="empty-state">
                <svg width="48" height="48" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                <p style="margin-top:16px;">Produk tidak ditemukan.</p>
            </div>
        </div>
      <?php endif; ?>
    </div>

    <?php if ($totalPages > 1): ?>
    <div style="display:flex;gap:8px;justify-content:center;margin-top:40px;">
        <?php 
        $queryStr = [];
        if($search) $queryStr[] = "q=".urlencode($search);
        if($tipe) $queryStr[] = "tipe=".urlencode($tipe);
        if($min_harga) $queryStr[] = "min_harga=".$min_harga;
        if($max_harga) $queryStr[] = "max_harga=".$max_harga;
        $qstr = !empty($queryStr) ? '&'.implode('&', $queryStr) : '';
        for ($i=1; $i<=$totalPages; $i++): 
        ?>
        <a href="?page=<?= $i ?><?= $qstr ?>" class="btn <?= $i === $page ? 'btn--primary' : 'btn--secondary' ?>" style="width:40px;height:40px;display:flex;align-items:center;justify-content:center;padding:0;"><?= $i ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>

  </div>
</div>

<?php include '../includes/footer.php'; ?>
