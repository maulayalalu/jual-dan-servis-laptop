<?php
session_start();
require_once '../config/koneksi.php';
$basePath = '../';
$pageTitle = 'Keranjang Belanja — A-LINKS';

requireUser(); // Enforce login for DB cart
$id_user = $_SESSION['id_user'];

// Handle Cart Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    $id_produk = (int)($_POST['id_produk'] ?? 0);

    if ($action === 'remove' && $id_produk > 0) {
        $stmt = $koneksi->prepare("DELETE FROM keranjang WHERE id_user=? AND id_produk=?");
        $stmt->bind_param('ii', $id_user, $id_produk);
        $stmt->execute(); $stmt->close();
        setFlash('success', 'Produk dihapus dari keranjang.');
        redirect('keranjang.php');
    }

    if ($action === 'update' && $id_produk > 0) {
        $qty = (int)($_POST['qty'] ?? 1);
        if ($qty > 0) {
            $stmt = $koneksi->prepare("UPDATE keranjang SET qty=? WHERE id_user=? AND id_produk=?");
            $stmt->bind_param('iii', $qty, $id_user, $id_produk);
            $stmt->execute(); $stmt->close();
        } else {
            $stmt = $koneksi->prepare("DELETE FROM keranjang WHERE id_user=? AND id_produk=?");
            $stmt->bind_param('ii', $id_user, $id_produk);
            $stmt->execute(); $stmt->close();
        }
        redirect('keranjang.php');
    }
}

$cartItems = [];
$totalHarga = 0;

$stmt = $koneksi->prepare("SELECT k.qty, p.* FROM keranjang k JOIN produk p ON k.id_produk=p.id_produk WHERE k.id_user=?");
$stmt->bind_param('i', $id_user);
$stmt->execute();
$res = $stmt->get_result();
while ($p = $res->fetch_assoc()) {
    $p['subtotal'] = $p['harga'] * $p['qty'];
    $totalHarga += $p['subtotal'];
    $cartItems[] = $p;
}
$stmt->close();

include '../includes/header.php';
?>

<div style="min-height:100vh;background:var(--color-light-ash);padding-top:72px;">
  <div class="container" style="max-width:1000px;padding-top:32px;padding-bottom:80px;">
    
    <div class="page-header">
      <div>
        <h1 class="page-header__title">Keranjang Belanja</h1>
        <div class="page-header__sub">Tinjau kembali barang belanjaan Anda sebelum checkout</div>
      </div>
    </div>

    <?php renderFlash(); ?>

    <?php if (empty($cartItems)): ?>
        <div class="card" style="padding:60px 20px;text-align:center;">
            <div class="empty-state">
                <svg width="48" height="48" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.3 2.3c-.6.6-.2 1.7.7 1.7H17m0 0a2 2 0 100 4 2 2 0 000-4zm-10 2a2 2 0 100 4 2 2 0 000-4z"/></svg>
                <p style="margin-top:16px;">Keranjang Anda masih kosong.</p>
                <a href="katalog.php" class="btn btn--primary" style="margin-top:16px;">Mulai Belanja</a>
            </div>
        </div>
    <?php else: ?>
        <div style="display:grid;grid-template-columns:1fr 340px;gap:24px;align-items:start;">
            
            <!-- Items List -->
            <div style="display:flex;flex-direction:column;gap:16px;">
                <?php foreach ($cartItems as $item): ?>
                <div class="card" style="padding:16px;display:flex;gap:20px;align-items:center;">
                    <img src="<?= !empty($item['gambar']) ? '../'.$item['gambar'] : 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=200&q=80' ?>" style="width:100px;height:75px;object-fit:cover;border-radius:4px;background:var(--color-light-ash);">
                    <div style="flex:1;">
                        <div style="font-weight:500;font-size:16px;color:var(--color-carbon);"><?= htmlspecialchars($item['nama_laptop']) ?></div>
                        <div style="color:var(--color-blue);font-weight:600;margin-top:4px;"><?= formatRupiah($item['harga']) ?></div>
                    </div>
                    
                    <form method="POST" style="display:flex;align-items:center;gap:12px;">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id_produk" value="<?= $item['id_produk'] ?>">
                        <div class="qty-control">
                            <button type="button" class="qty-btn qty-btn--minus">-</button>
                            <input type="number" name="qty" value="<?= $item['qty'] ?>" min="1" max="<?= $item['stok'] ?>" style="width:40px;text-align:center;border:none;background:transparent;font-weight:500;font-size:15px;" readonly>
                            <button type="button" class="qty-btn qty-btn--plus">+</button>
                        </div>
                        <button type="submit" class="btn btn--secondary btn--sm" style="display:none;" id="updateBtn_<?= $item['id_produk'] ?>">Update</button>
                    </form>

                    <div style="font-weight:600;color:var(--color-carbon);width:120px;text-align:right;">
                        <?= formatRupiah($item['subtotal']) ?>
                    </div>

                    <form method="POST">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="action" value="remove">
                        <input type="hidden" name="id_produk" value="<?= $item['id_produk'] ?>">
                        <button type="submit" class="btn btn--icon" style="color:#d92b2b;background:rgba(217,43,43,0.1);border-radius:50%;width:32px;height:32px;padding:0;" title="Hapus">
                            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Summary -->
            <div class="card" style="padding:24px;position:sticky;top:80px;">
                <h3 style="font-size:18px;font-weight:500;color:var(--color-carbon);margin-bottom:20px;">Ringkasan Belanja</h3>
                <div style="display:flex;justify-content:space-between;margin-bottom:12px;color:var(--color-pewter);font-size:14px;">
                    <span>Total Harga (<?= count($cartItems) ?> Barang)</span>
                    <span><?= formatRupiah($totalHarga) ?></span>
                </div>
                <div style="height:1px;background:var(--color-cloud);margin:16px 0;"></div>
                <div style="display:flex;justify-content:space-between;margin-bottom:24px;font-weight:600;font-size:18px;color:var(--color-carbon);">
                    <span>Total Tagihan</span>
                    <span style="color:var(--color-blue);"><?= formatRupiah($totalHarga) ?></span>
                </div>
                <a href="checkout.php" class="btn btn--primary btn--full btn--lg">Lanjut ke Pembayaran</a>
            </div>

        </div>
    <?php endif; ?>

  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.qty-control').forEach(ctrl => {
        const minus = ctrl.querySelector('.qty-btn--minus');
        const plus = ctrl.querySelector('.qty-btn--plus');
        const input = ctrl.querySelector('input');
        const updateBtn = ctrl.nextElementSibling;
        
        minus.addEventListener('click', () => {
            if(input.value > 1) {
                input.value--;
                updateBtn.click();
            }
        });
        plus.addEventListener('click', () => {
            if(parseInt(input.value) < parseInt(input.max)) {
                input.value++;
                updateBtn.click();
            }
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>
