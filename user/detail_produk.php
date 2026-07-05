<?php
session_start();
require_once '../config/koneksi.php';
$basePath = '../';
$pageTitle = 'Detail Produk — A-LINKS';
$navActive = 'katalog';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: katalog.php'); exit; }

$stmt = $koneksi->prepare("SELECT p.*, k.nama_kategori FROM produk p LEFT JOIN kategori k ON p.id_kategori=k.id_kategori WHERE p.id_produk=? AND p.is_deleted=0");
$stmt->bind_param('i', $id); $stmt->execute();
$produk = $stmt->get_result()->fetch_assoc(); $stmt->close();
if (!$produk) { header('Location: katalog.php'); exit; }

$pageTitle = htmlspecialchars($produk['nama_laptop']) . ' — A-LINKS';

// Handle Add to Cart (DB-based)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    verify_csrf();
    if (!isLoggedIn()) { redirect('../login.php'); }
    $qty = max(1, (int)($_POST['qty'] ?? 1));
    $id_user = $_SESSION['id_user'];
    $stmt = $koneksi->prepare("INSERT INTO keranjang (id_user, id_produk, qty) VALUES (?,?,?) ON DUPLICATE KEY UPDATE qty = qty + VALUES(qty)");
    $stmt->bind_param('iii', $id_user, $id, $qty); $stmt->execute(); $stmt->close();
    setFlash('success', 'Produk berhasil ditambahkan ke keranjang!');
    redirect('detail_produk.php?id=' . $id);
}

// Handle Review Submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    verify_csrf();
    if (!isLoggedIn()) { redirect('../login.php'); }
    $id_user = $_SESSION['id_user'];
    $rating = min(5, max(1, (int)($_POST['rating'] ?? 5)));
    $komentar = trim($_POST['komentar'] ?? '');
    $stmt = $koneksi->prepare("INSERT INTO ulasan (id_produk, id_user, rating, komentar) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE rating=VALUES(rating), komentar=VALUES(komentar)");
    $stmt->bind_param('iiis', $id, $id_user, $rating, $komentar); $stmt->execute(); $stmt->close();
    // Update avg rating
    $koneksi->query("UPDATE produk SET rata_rating=(SELECT AVG(rating) FROM ulasan WHERE id_produk=$id) WHERE id_produk=$id");
    setFlash('success', 'Ulasan berhasil disimpan!');
    redirect('detail_produk.php?id=' . $id);
}

// Get reviews
$rUlasan = $koneksi->prepare("SELECT u.*, us.nama FROM ulasan u JOIN users us ON u.id_user=us.id_user WHERE u.id_produk=? ORDER BY u.created_at DESC");
$rUlasan->bind_param('i', $id); $rUlasan->execute();
$ulasanList = $rUlasan->get_result(); $rUlasan->close();

// Check if user already reviewed
$sudahUlasan = false;
if (isLoggedIn()) {
    $stmtCek = $koneksi->prepare("SELECT id_ulasan FROM ulasan WHERE id_produk=? AND id_user=?");
    $stmtCek->bind_param('ii', $id, $_SESSION['id_user']); $stmtCek->execute();
    $sudahUlasan = $stmtCek->get_result()->num_rows > 0; $stmtCek->close();
}

// Cart count from DB
$cartCount = 0;
if (isLoggedIn()) {
    $stmtCart = $koneksi->prepare("SELECT SUM(qty) as total FROM keranjang WHERE id_user=?");
    $stmtCart->bind_param('i', $_SESSION['id_user']); $stmtCart->execute();
    $cartCount = (int)($stmtCart->get_result()->fetch_assoc()['total'] ?? 0); $stmtCart->close();
}

include '../includes/header.php';
?>

<div style="min-height:100vh;background:var(--color-light-ash);padding-top:72px;">
  <div class="container" style="max-width:1200px;padding-top:40px;padding-bottom:80px;">
    
    <?php renderFlash(); ?>

    <!-- Breadcrumb -->
    <div style="display:flex;gap:8px;align-items:center;margin-bottom:32px;font-size:13px;color:var(--color-pewter);">
      <a href="../index.php" style="color:var(--color-pewter);text-decoration:none;">Beranda</a>
      <span>›</span>
      <a href="katalog.php" style="color:var(--color-pewter);text-decoration:none;">Katalog</a>
      <span>›</span>
      <span style="color:var(--color-carbon);"><?= htmlspecialchars($produk['nama_laptop']) ?></span>
    </div>

    <!-- Product Detail Section -->
    <div class="card" style="padding:40px;display:grid;grid-template-columns:1fr 1fr;gap:48px;margin-bottom:32px;">
      <!-- Image -->
      <div>
        <img src="<?= !empty($produk['gambar']) ? '../'.$produk['gambar'] : 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=800&q=80' ?>"
             alt="<?= htmlspecialchars($produk['nama_laptop']) ?>"
             style="width:100%;border-radius:12px;object-fit:cover;aspect-ratio:4/3;background:var(--color-light-ash);"
             onerror="this.src='https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=800&q=80'">
      </div>

      <!-- Info -->
      <div style="display:flex;flex-direction:column;gap:20px;">
        <?php if ($produk['nama_kategori']): ?>
        <div><span class="badge badge--blue"><?= htmlspecialchars($produk['nama_kategori']) ?></span></div>
        <?php endif; ?>

        <h1 style="font-size:26px;font-weight:600;color:var(--color-carbon);line-height:1.3;"><?= htmlspecialchars($produk['nama_laptop']) ?></h1>

        <!-- Rating Stars -->
        <div style="display:flex;align-items:center;gap:8px;">
          <?php $rating = round($produk['rata_rating']); for($i=1;$i<=5;$i++): ?>
          <span style="font-size:20px;color:<?= $i<=$rating ? '#8C7B75' : '#D8CFC2' ?>;">★</span>
          <?php endfor; ?>
          <span style="font-size:13px;color:var(--color-pewter);">(<?= $ulasanList->num_rows ?> ulasan)</span>
        </div>

        <!-- Price -->
        <div>
          <?php if ($produk['harga_coret'] > 0): ?>
          <div style="font-size:14px;color:var(--color-silver-fog);text-decoration:line-through;margin-bottom:4px;"><?= formatRupiah($produk['harga_coret']) ?></div>
          <?php endif; ?>
          <div style="font-size:32px;font-weight:700;color:var(--color-blue);"><?= formatRupiah($produk['harga']) ?></div>
        </div>

        <!-- Stock -->
        <div style="display:flex;align-items:center;gap:8px;">
          <span style="font-size:14px;color:var(--color-pewter);">Stok:</span>
          <span style="font-weight:600;color:<?= $produk['stok'] > 5 ? 'var(--color-blue)' : ($produk['stok'] > 0 ? 'var(--color-taupe)' : '#d92b2b') ?>;">
            <?= $produk['stok'] > 0 ? $produk['stok'] . ' unit tersedia' : 'Habis' ?>
          </span>
        </div>

        <!-- Description -->
        <div style="background:var(--color-light-ash);border-radius:8px;padding:16px;">
          <div style="font-size:14px;font-weight:500;color:var(--color-carbon);margin-bottom:8px;">Deskripsi</div>
          <div style="font-size:14px;color:var(--color-pewter);line-height:1.7;"><?= nl2br(htmlspecialchars($produk['deskripsi'])) ?></div>
        </div>

        <!-- Add to Cart -->
        <?php if ($produk['stok'] > 0): ?>
        <form method="POST" style="display:flex;gap:12px;align-items:center;">
          <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
          <div style="display:flex;align-items:center;border:1.5px solid var(--color-cloud);border-radius:8px;overflow:hidden;">
            <button type="button" onclick="const q=document.getElementById('qtyInput');q.value=Math.max(1,+q.value-1);" style="width:40px;height:44px;border:none;background:var(--color-light-ash);cursor:pointer;font-size:18px;color:var(--color-carbon);">−</button>
            <input type="number" name="qty" id="qtyInput" value="1" min="1" max="<?= $produk['stok'] ?>" style="width:60px;height:44px;border:none;text-align:center;font-size:15px;font-weight:500;">
            <button type="button" onclick="const q=document.getElementById('qtyInput');q.value=Math.min(<?= $produk['stok'] ?>,+q.value+1);" style="width:40px;height:44px;border:none;background:var(--color-light-ash);cursor:pointer;font-size:18px;color:var(--color-carbon);">+</button>
          </div>
          <button type="submit" name="add_to_cart" class="btn btn--primary" style="flex:1;">🛒 Tambah ke Keranjang</button>
        </form>
        <a href="keranjang.php" class="btn btn--secondary btn--full">Lihat Keranjang <?= $cartCount > 0 ? "($cartCount)" : '' ?></a>
        <?php else: ?>
        <button class="btn btn--danger btn--full" disabled>Stok Habis</button>
        <?php endif; ?>
      </div>
    </div>

    <!-- Reviews Section -->
    <div class="card" style="padding:32px;">
      <h2 style="font-size:20px;font-weight:600;margin-bottom:24px;color:var(--color-carbon);">Ulasan Pembeli</h2>

      <?php if (isLoggedIn() && !$sudahUlasan): ?>
      <div style="background:var(--color-light-ash);border-radius:12px;padding:24px;margin-bottom:32px;">
        <div style="font-size:15px;font-weight:500;margin-bottom:16px;color:var(--color-carbon);">Tulis Ulasan Anda</div>
        <form method="POST">
          <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
          <!-- Star Rating Input -->
          <div style="margin-bottom:16px;">
            <div style="font-size:14px;color:var(--color-pewter);margin-bottom:8px;">Penilaian:</div>
            <div class="star-rating" style="display:flex;gap:4px;font-size:32px;cursor:pointer;" id="starRating">
              <?php for($i=1;$i<=5;$i++): ?>
              <span data-v="<?= $i ?>" style="color:#D8CFC2;transition:color 0.15s;">★</span>
              <?php endfor; ?>
            </div>
            <input type="hidden" name="rating" id="ratingInput" value="5">
          </div>
          <div class="form-group" style="margin-bottom:16px;">
            <label class="form-label">Komentar</label>
            <textarea class="form-control form-control--textarea" name="komentar" rows="3" placeholder="Ceritakan pengalaman Anda dengan produk ini..."></textarea>
          </div>
          <button type="submit" name="submit_review" class="btn btn--primary">Kirim Ulasan</button>
        </form>
      </div>
      <?php elseif (!isLoggedIn()): ?>
      <div style="background:var(--color-light-ash);border-radius:8px;padding:20px;margin-bottom:24px;text-align:center;">
        <a href="../login.php" class="btn btn--primary">Login untuk Memberikan Ulasan</a>
      </div>
      <?php endif; ?>

      <!-- Review List -->
      <?php
      $ulasanList->data_seek(0);
      if ($ulasanList->num_rows > 0): while($u = $ulasanList->fetch_assoc()): ?>
      <div style="border-bottom:1px solid var(--color-cloud);padding:20px 0;">
        <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:8px;">
          <div style="display:flex;align-items:center;gap:10px;">
            <div style="width:36px;height:36px;border-radius:50%;background:var(--color-blue);color:white;display:grid;place-items:center;font-weight:600;font-size:14px;"><?= strtoupper(substr($u['nama'],0,1)) ?></div>
            <div>
              <div style="font-weight:500;font-size:14px;color:var(--color-carbon);"><?= htmlspecialchars($u['nama']) ?></div>
              <div style="font-size:12px;color:var(--color-silver-fog);"><?= date('d M Y', strtotime($u['created_at'])) ?></div>
            </div>
          </div>
          <div><?php for($i=1;$i<=5;$i++) echo '<span style="color:'.($i<=$u['rating']?'#8C7B75':'#D8CFC2').';">★</span>'; ?></div>
        </div>
        <?php if ($u['komentar']): ?>
        <p style="font-size:14px;color:var(--color-pewter);line-height:1.6;margin:0 0 0 46px;"><?= htmlspecialchars($u['komentar']) ?></p>
        <?php endif; ?>
      </div>
      <?php endwhile; else: ?>
      <div class="empty-state"><p>Belum ada ulasan untuk produk ini.</p></div>
      <?php endif; ?>
    </div>

  </div>
</div>

<script>
// Star rating interaction
const stars = document.querySelectorAll('#starRating span');
const input = document.getElementById('ratingInput');
if (stars.length) {
  let currentRating = 5;
  stars.forEach((s, i) => {
    s.addEventListener('mouseover', () => stars.forEach((x, j) => x.style.color = j <= i ? '#8C7B75' : '#D8CFC2'));
    s.addEventListener('click', () => { currentRating = i+1; input.value = currentRating; });
    s.addEventListener('mouseleave', () => stars.forEach((x, j) => x.style.color = j < currentRating ? '#8C7B75' : '#D8CFC2'));
  });
  // Init color
  stars.forEach((s, j) => s.style.color = j < 5 ? '#8C7B75' : '#D8CFC2');
}
</script>

<?php include '../includes/footer.php'; ?>
