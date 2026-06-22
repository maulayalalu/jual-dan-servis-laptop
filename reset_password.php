<?php
session_start();
require_once 'config/koneksi.php';
$basePath = '';
$pageTitle = 'Reset Password — A-LINKS';

if (isLoggedIn()) redirect('index.php');

$token = trim($_GET['token'] ?? '');
$valid = false;
$resetDone = false;
$resetRow = null;

if ($token) {
    $stmt = $koneksi->prepare("SELECT * FROM password_resets WHERE token=? AND used=0 AND expires_at > NOW()");
    $stmt->bind_param('s', $token); $stmt->execute();
    $resetRow = $stmt->get_result()->fetch_assoc(); $stmt->close();
    $valid = !empty($resetRow);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid) {
    verify_csrf();
    $pass = $_POST['password'] ?? '';
    $conf = $_POST['konfirmasi'] ?? '';
    if (strlen($pass) < 6) {
        setFlash('error', 'Password minimal 6 karakter.');
    } elseif ($pass !== $conf) {
        setFlash('error', 'Konfirmasi password tidak cocok.');
    } else {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $stmt = $koneksi->prepare("UPDATE users SET password=? WHERE email=?");
        $stmt->bind_param('ss', $hash, $resetRow['email']); $stmt->execute(); $stmt->close();
        $koneksi->query("UPDATE password_resets SET used=1 WHERE token='$token'");
        $resetDone = true;
    }
}

include 'includes/header.php';
?>

<div style="min-height:100vh;background:var(--color-light-ash);display:flex;align-items:center;justify-content:center;padding:20px;">
  <div style="width:100%;max-width:420px;">
    <a href="index.php" style="display:block;text-align:center;font-size:24px;font-weight:700;color:var(--color-carbon);letter-spacing:-0.5px;margin-bottom:32px;text-decoration:none;">A-LINKS</a>

    <?php if ($resetDone): ?>
    <div class="card" style="padding:40px;text-align:center;">
      <div style="font-size:48px;margin-bottom:16px;">✅</div>
      <h1 style="font-size:22px;font-weight:600;margin-bottom:12px;">Password Berhasil Direset!</h1>
      <p style="color:var(--color-pewter);font-size:14px;margin-bottom:24px;">Silakan login menggunakan password baru Anda.</p>
      <a href="login.php" class="btn btn--primary btn--full">Login Sekarang</a>
    </div>
    <?php elseif (!$valid): ?>
    <div class="card" style="padding:40px;text-align:center;">
      <div style="font-size:48px;margin-bottom:16px;">❌</div>
      <h1 style="font-size:22px;font-weight:600;margin-bottom:12px;">Link Tidak Valid</h1>
      <p style="color:var(--color-pewter);font-size:14px;margin-bottom:24px;">Link reset password sudah kadaluarsa atau tidak valid (1 jam). Silakan minta link baru.</p>
      <a href="lupa_password.php" class="btn btn--primary btn--full">Minta Link Baru</a>
    </div>
    <?php else: ?>
    <div class="card" style="padding:40px;">
      <h1 style="font-size:24px;font-weight:600;margin-bottom:8px;color:var(--color-carbon);">Buat Password Baru</h1>
      <p style="color:var(--color-pewter);font-size:14px;margin-bottom:28px;">Untuk akun: <strong><?= htmlspecialchars($resetRow['email']) ?></strong></p>

      <?php renderFlash(); ?>

      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <div class="form-group" style="margin-bottom:16px;">
          <label class="form-label">Password Baru</label>
          <input class="form-control" type="password" name="password" required placeholder="Minimal 6 karakter">
        </div>
        <div class="form-group" style="margin-bottom:24px;">
          <label class="form-label">Konfirmasi Password</label>
          <input class="form-control" type="password" name="konfirmasi" required placeholder="Ulangi password baru">
        </div>
        <button type="submit" class="btn btn--primary btn--full btn--lg">Simpan Password Baru</button>
      </form>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
