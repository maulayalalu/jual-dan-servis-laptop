<?php
session_start();
require_once '../config/koneksi.php';
requireUser();
$basePath = '../';
$pageTitle = 'Request Servis â€” A-LINKS';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $tipe_laptop = trim($_POST['tipe_laptop'] ?? '');
    $keluhan = trim($_POST['keluhan'] ?? '');
    $id_user = $_SESSION['id_user'];
    $status = 'pending';
    $tgl_masuk = date('Y-m-d');

    if (empty($tipe_laptop) || empty($keluhan)) {
        setFlash('error', 'Tipe laptop dan keluhan wajib diisi.');
    } else {
        $stmt = $koneksi->prepare("INSERT INTO servis (id_user, tipe_laptop, keluhan, status, tgl_masuk) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param('issss', $id_user, $tipe_laptop, $keluhan, $status, $tgl_masuk);
        
        if ($stmt->execute()) {
            setFlash('success', 'Permintaan servis berhasil diajukan. Kami akan segera memprosesnya.');
            redirect('riwayat.php?tab=servis');
        } else {
            setFlash('error', 'Gagal mengajukan servis. Silakan coba lagi.');
        }
        $stmt->close();
    }
}

include '../includes/header.php';
?>

<div style="min-height:100vh;background:var(--color-cream);padding-top:72px;">
  <div class="container" style="max-width:800px;padding-top:32px;padding-bottom:80px;">
    
    <div class="page-header" style="text-align:center;display:block;">
      <h1 class="page-header__title">Request Servis Laptop</h1>
      <div class="page-header__sub" style="margin:8px auto 0;">Isi formulir di bawah ini untuk mengajukan perbaikan laptop Anda</div>
    </div>

    <?php renderFlash(); ?>

    <div class="card" style="padding:32px;">
        <form method="POST">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <div class="form-group" style="margin-bottom:20px;">
                <label class="form-label" for="tipe_laptop">Merek &amp; Tipe Laptop <span style="color:var(--color-danger);">*</span></label>
                <input class="form-control" id="tipe_laptop" name="tipe_laptop" type="text" required placeholder="Contoh: ASUS ROG Strix G513, Lenovo ThinkPad T480">
            </div>
            
            <div class="form-group" style="margin-bottom:24px;">
                <label class="form-label" for="keluhan">Deskripsi Keluhan <span style="color:var(--color-danger);">*</span></label>
                <textarea class="form-control form-control--textarea" id="keluhan" name="keluhan" rows="5" required placeholder="Jelaskan masalah yang dialami sedetail mungkin. Contoh: Layar bergaris saat dinyalakan, keyboard beberapa tombol tidak berfungsi, atau laptop sering mati tiba-tiba."></textarea>
            </div>

            <div style="background:var(--color-cream);border:1px solid var(--color-cream-border);border-left:4px solid var(--color-navy);border-radius:8px;padding:16px;margin-bottom:24px;">
                <div style="font-weight:600;font-size:14px;color:var(--color-navy);margin-bottom:8px;">&#9432; Informasi Penting:</div>
                <ul style="list-style-type:disc;padding-left:20px;font-size:13px;color:var(--color-graphite);display:flex;flex-direction:column;gap:4px;">
                    <li>Teknisi kami akan memeriksa laptop Anda terlebih dahulu.</li>
                    <li>Estimasi biaya servis akan dikonfirmasi setelah pemeriksaan selesai.</li>
                    <li>Anda dapat memantau status pengerjaan pada halaman Riwayat.</li>
                </ul>
            </div>

            <button type="submit" class="btn btn--primary btn--lg btn--full" id="btnAjukanServis">Ajukan Permintaan Servis</button>
        </form>
    </div>

  </div>
</div>

<?php include '../includes/footer.php'; ?>
