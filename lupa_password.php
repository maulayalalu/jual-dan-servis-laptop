<?php
session_start();
require_once 'config/koneksi.php';
$basePath = '';
$pageTitle = 'Lupa Password â€” A-LINKS';

if (isLoggedIn()) redirect('index.php');

$sent = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = trim($_POST['email'] ?? '');
    $stmt = $koneksi->prepare("SELECT id_user FROM users WHERE email=? AND is_deleted=0");
    $stmt->bind_param('s', $email); $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc(); $stmt->close();

    if ($user) {
        // Generate token
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        // Invalidate old tokens
        $koneksi->query("UPDATE password_resets SET used=1 WHERE email='$email'");
        $stmt = $koneksi->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?,?,?)");
        $stmt->bind_param('sss', $email, $token, $expires); $stmt->execute(); $stmt->close();

        $resetLink = "http://localhost/A-LINKS/reset_password.php?token=$token";
        // In production: send email. Here we save to a log file for demo
        file_put_contents('reset_link.log', "[$email] $resetLink\n", FILE_APPEND);
    }
    // Always show success (security)
    $sent = true;
}

include 'includes/header.php';
?>

<div style="min-height:100vh;background:var(--color-light-ash);display:flex;align-items:center;justify-content:center;padding:20px;">
  <div style="width:100%;max-width:420px;">
    <a href="index.php" style="display:block;text-align:center;font-size:24px;font-weight:700;color:var(--color-carbon);letter-spacing:-0.5px;margin-bottom:32px;text-decoration:none;">A-LINKS</a>

    <?php if ($sent): ?>
    <div class="card" style="padding:40px;text-align:center;">
      <div style="font-size:48px;margin-bottom:16px;">ðŸ“§</div>
      <h1 style="font-size:22px;font-weight:600;margin-bottom:12px;">Email Dikirim!</h1>
      <p style="color:var(--color-pewter);font-size:14px;line-height:1.7;margin-bottom:24px;">
        Jika email tersebut terdaftar, kami telah mengirimkan link reset password.<br>
        <strong>Untuk demo:</strong> Link tersimpan di file <code>reset_link.log</code> di folder proyek.
      </p>
      <a href="login.php" class="btn btn--primary btn--full">Kembali ke Login</a>
    </div>
    <?php else: ?>
    <div class="card" style="padding:40px;">
      <h1 style="font-size:24px;font-weight:600;margin-bottom:8px;color:var(--color-carbon);">Lupa Password</h1>
      <p style="color:var(--color-pewter);font-size:14px;margin-bottom:28px;">Masukkan email Anda dan kami akan mengirim link reset password.</p>

      <?php renderFlash(); ?>

      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <div class="form-group" style="margin-bottom:20px;">
          <label class="form-label" for="emailInput">Alamat Email</label>
          <input class="form-control" id="emailInput" type="email" name="email" required placeholder="email@contoh.com">
        </div>
        <button type="submit" class="btn btn--primary btn--full btn--lg">Kirim Link Reset</button>
      </form>

      <div style="text-align:center;margin-top:20px;font-size:14px;color:var(--color-pewter);">
        Ingat password? <a href="login.php" style="color:var(--color-blue);font-weight:500;">Masuk</a>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
