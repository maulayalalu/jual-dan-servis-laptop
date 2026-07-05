<?php
session_start();
require_once 'config/koneksi.php';

// Kalau sudah login, redirect ke halaman yang sesuai
if (isLoggedIn()) {
    $role = $_SESSION['role'] ?? 'user';
    redirect($role === 'user' ? 'user/dashboard.php' : 'admin/dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email    = trim($_POST['email']   ?? '');
    $password = $_POST['password']     ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Email dan password wajib diisi.';
    } else {
        // Prepared statement — aman dari SQL Injection
        $stmt = $koneksi->prepare("SELECT id_user, nama, password, role FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user   = $result->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password'])) {
            // Regenerate session ID untuk keamanan
            session_regenerate_id(true);

            $_SESSION['id_user'] = $user['id_user'];
            $_SESSION['nama']    = $user['nama'];
            $_SESSION['email']   = $email;
            $_SESSION['role']    = $user['role'];

            setFlash('success', 'Selamat datang kembali, ' . $user['nama'] . '!');

            redirect($user['role'] === 'user' ? 'user/dashboard.php' : 'admin/dashboard.php');
        } else {
            $error = 'Email atau password tidak sesuai.';
        }
    }
}

$pageTitle = 'Masuk — A-LINKS';
$basePath  = '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= $pageTitle ?></title>
  <meta name="description" content="Masuk ke akun A-LINKS Anda untuk mulai belanja laptop atau cek status servis."/>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="assets/css/style.css"/>
</head>
<body>

<div class="auth-page">
  <!-- Visual Side -->
  <div class="auth-visual">
    <img src="https://images.unsplash.com/photo-1453928582365-b6ad33cbcf64?w=1200&q=85"
         alt="Laptop showcase"
         onerror="this.parentElement.style.background='var(--color-blue)'">
    <div class="auth-visual__overlay">
      <div>
        <div style="font-size:22px;font-weight:600;letter-spacing:4px;color:white;text-transform:uppercase;margin-bottom:24px;">A-LINKS</div>
        <div class="auth-visual__title">Selamat Datang<br>Kembali</div>
        <div class="auth-visual__subtitle">Masuk untuk lanjutkan belanja & cek servis</div>
        <!-- Quick stats -->
        <div style="display:flex;gap:32px;margin-top:40px;">
          <?php foreach (['500+ Produk','1000+ Pelanggan','4.9★ Rating'] as $stat): ?>
          <div style="text-align:center;">
            <div style="font-size:13px;font-weight:500;color:white;"><?= $stat ?></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Form Side -->
  <div class="auth-form-area">
    <div class="auth-form-box">
      <div>
        <a href="index.php" class="auth-form-box__brand">A-LINKS</a>
        <h1 class="auth-form-box__title" style="margin-top:8px;">Masuk</h1>
        <p class="auth-form-box__sub" style="margin-top:4px;color:var(--color-pewter);font-size:14px;">Belum punya akun? <a href="register.php" style="color:var(--color-blue);font-weight:500;">Daftar gratis</a></p>
      </div>

      <?php if ($error): ?>
      <div class="alert alert--error" id="loginError"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST" action="login.php" id="loginForm" novalidate>
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <div style="display:flex;flex-direction:column;gap:16px;">
          <div class="form-group">
            <label class="form-label" for="loginEmail">Alamat Email</label>
            <input class="form-control" id="loginEmail" name="email" type="email"
                   placeholder="email@contoh.com"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                   autocomplete="email" required />
          </div>

          <div class="form-group">
            <div style="display:flex;justify-content:space-between;align-items:center;">
              <label class="form-label" for="loginPassword">Password</label>
              <a href="lupa_password.php" style="font-size:12px;color:var(--color-blue);">Lupa password?</a>
            </div>
            <div style="position:relative;">
              <input class="form-control" id="loginPassword" name="password" type="password"
                     placeholder="••••••••" autocomplete="current-password" required
                     style="padding-right:44px;" />
              <button type="button" id="togglePassword"
                      style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--color-silver-fog);"
                      aria-label="Tampilkan password">
                <svg id="eyeIcon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
              </button>
            </div>
          </div>

          <div style="display:flex;align-items:center;gap:8px;">
            <input type="checkbox" id="rememberMe" name="remember" style="width:16px;height:16px;cursor:pointer;">
            <label for="rememberMe" style="font-size:13px;color:var(--color-graphite);cursor:pointer;">Ingat saya selama 30 hari</label>
          </div>

          <button type="submit" class="btn btn--primary btn--full" id="btnMasuk">Masuk</button>
        </div>
      </form>

      <!-- Divider -->
      <div style="display:flex;align-items:center;gap:12px;color:var(--color-pale-silver);">
        <div style="flex:1;height:1px;background:var(--color-cloud);"></div>
        <span style="font-size:12px;color:var(--color-silver-fog);">atau masuk sebagai</span>
        <div style="flex:1;height:1px;background:var(--color-cloud);"></div>
      </div>

      <!-- Demo login hints -->
      <div style="background:var(--color-light-ash);border-radius:8px;padding:12px 16px;font-size:12px;color:var(--color-pewter);">
        <div style="font-weight:500;color:var(--color-graphite);margin-bottom:6px;">Demo Login:</div>
        <div>Admin: <code style="background:white;padding:1px 6px;border-radius:3px;">admin@alinks.id</code> / <code style="background:white;padding:1px 6px;border-radius:3px;">admin123</code></div>
        <div style="margin-top:4px;">Owner: <code style="background:white;padding:1px 6px;border-radius:3px;">owner@alinks.id</code> / <code style="background:white;padding:1px 6px;border-radius:3px;">owner123</code></div>
        <div style="margin-top:4px;">Kasir: <code style="background:white;padding:1px 6px;border-radius:3px;">kasir@alinks.id</code> / <code style="background:white;padding:1px 6px;border-radius:3px;">kasir123</code></div>
        <div style="margin-top:4px;">User: <code style="background:white;padding:1px 6px;border-radius:3px;">user@alinks.id</code> / <code style="background:white;padding:1px 6px;border-radius:3px;">user123</code></div>
      </div>

      <p style="font-size:12px;color:var(--color-silver-fog);text-align:center;">
        Dengan masuk, Anda menyetujui <a href="#" style="color:var(--color-blue);">Syarat & Ketentuan</a> dan <a href="#" style="color:var(--color-blue);">Kebijakan Privasi</a> A-LINKS.
      </p>
    </div>
  </div>
</div>

<script src="assets/js/main.js"></script>
<script>
  // Toggle password visibility
  const toggleBtn = document.getElementById('togglePassword');
  const pwdInput  = document.getElementById('loginPassword');
  toggleBtn?.addEventListener('click', () => {
    const isText = pwdInput.type === 'text';
    pwdInput.type = isText ? 'password' : 'text';
    toggleBtn.style.color = isText ? 'var(--color-silver-fog)' : 'var(--color-blue)';
  });

  // Client-side validation
  document.getElementById('loginForm')?.addEventListener('submit', (e) => {
    const email = document.getElementById('loginEmail').value.trim();
    const pass  = document.getElementById('loginPassword').value;
    if (!email || !pass) {
      e.preventDefault();
      alert('Email dan password wajib diisi.');
    }
  });
</script>
</body>
</html>
