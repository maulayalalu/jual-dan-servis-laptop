<?php
session_start();
require_once '../config/koneksi.php';
requireUser();
$basePath = '../';
$pageTitle = 'Checkout — A-LINKS';

$id_user = $_SESSION['id_user'];
$rProf = $koneksi->prepare("SELECT * FROM users WHERE id_user=?");
$rProf->bind_param('i', $id_user); $rProf->execute();
$profil = $rProf->get_result()->fetch_assoc(); $rProf->close();

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

if (empty($cartItems)) {
    redirect('katalog.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $alamat = trim($_POST['alamat'] ?? '');
    $metode = $_POST['metode_pembayaran'] ?? 'bank_transfer';
    
    if (empty($alamat)) {
        setFlash('error', 'Alamat pengiriman harus diisi.');
    } else {
        // Update user address if empty
        if (empty($profil['alamat'])) {
            $stmt = $koneksi->prepare("UPDATE users SET alamat=? WHERE id_user=?");
            $stmt->bind_param('si', $alamat, $id_user);
            $stmt->execute(); $stmt->close();
        }

        // Database Transaction & Locking
        try {
            $koneksi->begin_transaction();
            
            // Verifikasi stok dengan row locking
            $stockOk = true;
            foreach ($cartItems as $item) {
                $stmtCek = $koneksi->prepare("SELECT stok FROM produk WHERE id_produk=? FOR UPDATE");
                $stmtCek->bind_param('i', $item['id_produk']);
                $stmtCek->execute();
                $stokDb = $stmtCek->get_result()->fetch_assoc()['stok'] ?? 0;
                $stmtCek->close();
                
                if ($stokDb < $item['qty']) {
                    $stockOk = false;
                    setFlash('error', "Stok untuk produk {$item['nama_laptop']} tidak mencukupi. Sisa stok: $stokDb");
                    break;
                }
            }
            
            if (!$stockOk) {
                $koneksi->rollback();
                redirect('keranjang.php');
            }

            // Generate Order ID
            $order_id = 'ORD-' . time() . '-' . rand(1000, 9999);
            $status_pembayaran = 'unpaid';

            // Insert Transaksi
            $stmt = $koneksi->prepare("INSERT INTO transaksi (id_user, order_id, total_harga, status_pembayaran, tipe_pembayaran) VALUES (?,?,?,?,?)");
            $stmt->bind_param('isiss', $id_user, $order_id, $totalHarga, $status_pembayaran, $metode);
            $stmt->execute();
            $id_transaksi = $stmt->insert_id;
            $stmt->close();

            // Insert Detail Transaksi & Update Stok
            $stmtDetail = $koneksi->prepare("INSERT INTO detail_transaksi (id_transaksi, id_produk, jumlah, harga_satuan) VALUES (?,?,?,?)");
            $stmtStok = $koneksi->prepare("UPDATE produk SET stok = stok - ? WHERE id_produk = ?");
            
            foreach ($cartItems as $item) {
                $stmtDetail->bind_param('iiid', $id_transaksi, $item['id_produk'], $item['qty'], $item['harga']);
                $stmtDetail->execute();
                
                $stmtStok->bind_param('ii', $item['qty'], $item['id_produk']);
                $stmtStok->execute();
            }
            $stmtDetail->close();
            $stmtStok->close();

            $koneksi->commit();
            
            // Clear Cart from DB
            $stmtClear = $koneksi->prepare("DELETE FROM keranjang WHERE id_user=?");
            $stmtClear->bind_param('i', $id_user);
            $stmtClear->execute();
            $stmtClear->close();
            
            setFlash('success', 'Pesanan berhasil dibuat. Silakan lakukan pembayaran.');
            redirect('riwayat.php?tab=transaksi');
        } catch (Exception $e) {
            $koneksi->rollback();
            setFlash('error', 'Terjadi kesalahan sistem saat checkout. Silakan coba lagi.');
        }
    }
}

include '../includes/header.php';
?>

<div style="min-height:100vh;background:var(--color-light-ash);padding-top:72px;">
  <div class="container" style="max-width:1000px;padding-top:32px;padding-bottom:80px;">
    
    <div class="page-header">
      <div>
        <h1 class="page-header__title">Checkout</h1>
        <div class="page-header__sub">Selesaikan pesanan Anda</div>
      </div>
    </div>

    <?php renderFlash(); ?>

    <form method="POST" style="display:grid;grid-template-columns:1fr 360px;gap:24px;align-items:start;">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        
        <div style="display:flex;flex-direction:column;gap:24px;">
            <!-- Informasi Pengiriman -->
            <div class="card" style="padding:24px;">
                <h3 style="font-size:18px;font-weight:500;color:var(--color-carbon);margin-bottom:20px;">Informasi Pengiriman</h3>
                <div class="form-group" style="margin-bottom:16px;">
                    <label class="form-label">Nama Penerima</label>
                    <input class="form-control" type="text" value="<?= htmlspecialchars($profil['nama']) ?>" readonly style="background:var(--color-light-ash);">
                </div>
                <div class="form-group" style="margin-bottom:16px;">
                    <label class="form-label">Nomor Telepon</label>
                    <input class="form-control" type="text" value="<?= htmlspecialchars($profil['no_telp'] ?? '') ?>" readonly style="background:var(--color-light-ash);">
                </div>
                <div class="form-group">
                    <label class="form-label">Alamat Lengkap <span style="color:#d92b2b;">*</span></label>
                    <textarea class="form-control form-control--textarea" name="alamat" rows="3" required placeholder="Jalan, RT/RW, Kelurahan, Kecamatan, Kota, Kodepos"><?= htmlspecialchars($profil['alamat'] ?? '') ?></textarea>
                </div>
            </div>

            <!-- Metode Pembayaran -->
            <div class="card" style="padding:24px;">
                <h3 style="font-size:18px;font-weight:500;color:var(--color-carbon);margin-bottom:20px;">Metode Pembayaran</h3>
                <div style="display:flex;flex-direction:column;gap:12px;">
                    <label style="display:flex;align-items:center;gap:12px;padding:16px;border:1.5px solid var(--color-cloud);border-radius:8px;cursor:pointer;">
                        <input type="radio" name="metode_pembayaran" value="bank_transfer" checked style="width:18px;height:18px;cursor:pointer;">
                        <div style="flex:1;">
                            <div style="font-weight:500;color:var(--color-carbon);">Transfer Bank (Virtual Account)</div>
                            <div style="font-size:13px;color:var(--color-pewter);margin-top:2px;">BCA, Mandiri, BNI, BRI</div>
                        </div>
                    </label>
                    <label style="display:flex;align-items:center;gap:12px;padding:16px;border:1.5px solid var(--color-cloud);border-radius:8px;cursor:pointer;">
                        <input type="radio" name="metode_pembayaran" value="ewallet" style="width:18px;height:18px;cursor:pointer;">
                        <div style="flex:1;">
                            <div style="font-weight:500;color:var(--color-carbon);">E-Wallet</div>
                            <div style="font-size:13px;color:var(--color-pewter);margin-top:2px;">GoPay, OVO, Dana, ShopeePay</div>
                        </div>
                    </label>
                </div>
            </div>
        </div>

        <!-- Ringkasan -->
        <div class="card" style="padding:24px;position:sticky;top:80px;">
            <h3 style="font-size:18px;font-weight:500;color:var(--color-carbon);margin-bottom:20px;">Ringkasan Pesanan</h3>
            
            <div style="display:flex;flex-direction:column;gap:12px;margin-bottom:20px;max-height:300px;overflow-y:auto;padding-right:8px;">
                <?php foreach ($cartItems as $item): ?>
                <div style="display:flex;gap:12px;align-items:center;">
                    <img src="<?= !empty($item['gambar']) ? '../'.$item['gambar'] : 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=100&q=60' ?>" style="width:60px;height:45px;object-fit:cover;border-radius:4px;background:var(--color-light-ash);">
                    <div style="flex:1;">
                        <div style="font-size:13px;font-weight:500;color:var(--color-carbon);max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($item['nama_laptop']) ?></div>
                        <div style="font-size:12px;color:var(--color-pewter);"><?= $item['qty'] ?> x <?= formatRupiah($item['harga']) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div style="height:1px;background:var(--color-cloud);margin:16px 0;"></div>
            
            <div style="display:flex;justify-content:space-between;margin-bottom:12px;color:var(--color-pewter);font-size:14px;">
                <span>Total Harga</span>
                <span><?= formatRupiah($totalHarga) ?></span>
            </div>
            <div style="display:flex;justify-content:space-between;margin-bottom:12px;color:var(--color-pewter);font-size:14px;">
                <span>Biaya Pengiriman</span>
                <span style="color:var(--color-blue);">Gratis</span>
            </div>

            <div style="height:1px;background:var(--color-cloud);margin:16px 0;"></div>
            
            <div style="display:flex;justify-content:space-between;margin-bottom:24px;font-weight:600;font-size:18px;color:var(--color-carbon);">
                <span>Total Tagihan</span>
                <span style="color:var(--color-blue);"><?= formatRupiah($totalHarga) ?></span>
            </div>
            
            <button type="submit" class="btn btn--primary btn--full btn--lg">Bayar Sekarang</button>
            <div style="text-align:center;margin-top:12px;font-size:12px;color:var(--color-pewter);">
                Transaksi aman & terenkripsi
            </div>
        </div>

    </form>

  </div>
</div>

<?php include '../includes/footer.php'; ?>
