<?php
session_start();
require_once '../config/koneksi.php';
requireUser();
$basePath = '../';
$pageTitle = 'Upload Bukti Pembayaran â€” A-LINKS';
$id_user = (int)$_SESSION['id_user'];

$id_transaksi = (int)($_GET['id'] ?? 0);

// Fetch transaction
$stmt = $koneksi->prepare("SELECT * FROM transaksi WHERE id_transaksi=? AND id_user=?");
$stmt->bind_param('ii', $id_transaksi, $id_user); $stmt->execute();
$transaksi = $stmt->get_result()->fetch_assoc(); $stmt->close();

if (!$transaksi || $transaksi['status_pembayaran'] !== 'unpaid') {
    setFlash('error', 'Transaksi tidak ditemukan atau sudah diproses.');
    redirect('riwayat.php?tab=transaksi');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (!empty($_FILES['bukti']['name']) && $_FILES['bukti']['error'] === UPLOAD_ERR_OK) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $_FILES['bukti']['tmp_name']);
        finfo_close($finfo);
        $allowed = ['image/jpeg','image/png','image/webp'];
        if (!in_array($mime, $allowed)) {
            setFlash('error', 'Format file harus JPG, PNG, atau WEBP.');
            redirect('upload_bukti.php?id='.$id_transaksi);
        }
        if ($_FILES['bukti']['size'] > 3*1024*1024) {
            setFlash('error', 'Ukuran file maksimal 3MB.');
            redirect('upload_bukti.php?id='.$id_transaksi);
        }
        $ext = pathinfo($_FILES['bukti']['name'], PATHINFO_EXTENSION);
        $filename = 'bukti_' . $id_transaksi . '_' . time() . '.' . $ext;
        $uploadDir = '../assets/images/bukti/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        if (move_uploaded_file($_FILES['bukti']['tmp_name'], $uploadDir . $filename)) {
            $path = 'assets/images/bukti/' . $filename;
            $stmt = $koneksi->prepare("UPDATE transaksi SET bukti_pembayaran=?, status_pembayaran='pending_verify' WHERE id_transaksi=?");
            $stmt->bind_param('si', $path, $id_transaksi); $stmt->execute(); $stmt->close();
            setFlash('success', 'Bukti pembayaran berhasil diunggah! Admin akan memverifikasi dalam 1x24 jam.');
            redirect('riwayat.php?tab=transaksi');
        } else {
            setFlash('error', 'Gagal mengunggah file. Silakan coba lagi.');
        }
    } else {
        setFlash('error', 'Silakan pilih file bukti pembayaran.');
    }
    redirect('upload_bukti.php?id='.$id_transaksi);
}

include '../includes/header.php';
?>

<div style="min-height:100vh;background:var(--color-cream);padding-top:72px;">
  <div class="container" style="max-width:600px;padding-top:40px;padding-bottom:80px;">
    <?php renderFlash(); ?>

    <div class="page-header">
      <div>
        <h1 class="page-header__title">Upload Bukti Pembayaran</h1>
        <div class="page-header__sub">Order ID: <?= htmlspecialchars($transaksi['order_id']) ?></div>
      </div>
    </div>

    <div class="card" style="padding:32px;">
      <!-- Info Rekening -->
      <div style="background:var(--color-cream);border-radius:10px;padding:20px;margin-bottom:28px;border-left:4px solid var(--color-navy);">
        <div style="font-weight:600;font-size:15px;color:var(--color-carbon);margin-bottom:12px;">ðŸ“‹ Info Transfer</div>
        <div style="display:flex;flex-direction:column;gap:8px;font-size:14px;">
          <div style="display:flex;justify-content:space-between;"><span style="color:var(--color-pewter);">Total Bayar:</span><span style="font-weight:700;color:var(--color-navy);"><?= formatRupiah($transaksi['total_harga']) ?></span></div>
          <div style="display:flex;justify-content:space-between;"><span style="color:var(--color-pewter);">Bank:</span><span style="font-weight:600;">BCA â€” 1234567890 a/n A-LINKS Store</span></div>
          <div style="display:flex;justify-content:space-between;"><span style="color:var(--color-pewter);">Metode:</span><span><?= htmlspecialchars($transaksi['tipe_pembayaran'] ?? 'Bank Transfer') ?></span></div>
        </div>
      </div>

      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <div class="form-group" style="margin-bottom:24px;">
          <label class="form-label">File Bukti Transfer <span style="color:#d92b2b;">*</span></label>
          <div id="dropZone" style="border:2px dashed var(--color-cream-border);border-radius:10px;padding:40px;text-align:center;cursor:pointer;transition:border-color 0.25s;background:var(--color-cream);" onclick="document.getElementById('buktiInput').click()">
            <div style="font-size:40px;margin-bottom:8px;">ðŸ“·</div>
            <div style="font-weight:600;color:var(--color-carbon);">Klik atau seret file ke sini</div>
            <div style="font-size:13px;color:var(--color-pewter);margin-top:4px;">JPG, PNG, WEBP â€” Maks 3MB</div>
          </div>
          <input type="file" id="buktiInput" name="bukti" accept="image/*" style="display:none;" onchange="previewFile(this)">
          <img id="previewBukti" src="" alt="" style="display:none;margin-top:12px;max-width:100%;border-radius:8px;">
        </div>
        <button type="submit" class="btn btn--primary btn--full btn--lg">Kirim Bukti Pembayaran</button>
        <a href="riwayat.php?tab=transaksi" class="btn btn--secondary btn--full" style="margin-top:8px;">Batal</a>
      </form>
    </div>
  </div>
</div>

<script>
function previewFile(input) {
  const img = document.getElementById('previewBukti');
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = e => { img.src = e.target.result; img.style.display = 'block'; };
    reader.readAsDataURL(input.files[0]);
    document.getElementById('dropZone').style.borderColor = 'var(--color-navy)';
  }
}
const dz = document.getElementById('dropZone');
['dragover','dragenter'].forEach(e => dz.addEventListener(e, ev => { ev.preventDefault(); dz.style.borderColor='var(--color-navy)'; }));
['dragleave','drop'].forEach(e => dz.addEventListener(e, ev => { ev.preventDefault(); dz.style.borderColor='var(--color-cream-border)'; }));
dz.addEventListener('drop', ev => { ev.preventDefault(); const f=ev.dataTransfer.files[0]; const inp=document.getElementById('buktiInput'); const dt=new DataTransfer(); dt.items.add(f); inp.files=dt.files; previewFile(inp); });
</script>

<?php include '../includes/footer.php'; ?>
