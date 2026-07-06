<?php
session_start();
require_once 'config/koneksi.php';

// Kalau sudah login, redirect ke halaman yang sesuai
if (isLoggedIn()) {
    $role = $_SESSION['role'] ?? 'user';
    $redirectMap = [
        'user'  => 'user/dashboard.php',
        'kasir' => 'kasir/dashboard.php',
        'owner' => 'owner/dashboard.php',
        'admin' => 'admin/dashboard.php',
    ];
    redirect($redirectMap[$role] ?? 'user/dashboard.php');
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

            redirect($redirectMap[$user['role']] ?? 'user/dashboard.php');
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
  <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>"/>
</head>
<body>
<div class="auth-page">
  <!-- Visual Side -->
  <div class="auth-visual">
    <img src="https://images.unsplash.com/photo-1453928582365-b6ad33cbcf64?w=1200&q=85"
         alt="Laptop showcase"
         onerror="this.parentElement.style.background='var(--color-navy)'">
    <div class="auth-visual__overlay">
      <div class="auth-visual__logo">
        <div class="auth-visual__logo-box">
          <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
          </svg>
        </div>
        <span class="auth-visual__logo-text">A-LINKS</span>
      </div>
      <div>
        <div class="auth-visual__title">Selamat Datang<br>Kembali</div>
        <div class="auth-visual__subtitle">Masuk untuk lanjutkan belanja &amp; cek servis</div>
        <!-- Quick stats -->
        <div class="auth-visual__stats">
          <?php foreach ([
            getSetting('stat1_nilai', '500+') . ' ' . getSetting('stat1_label', 'Produk'),
            getSetting('stat2_nilai', '1000+') . ' ' . getSetting('stat2_label', 'Pelanggan'),
            getSetting('stat_rating_nilai', '4.9★') . ' ' . getSetting('stat_rating_label', 'Rating')
          ] as $stat): ?>
          <div class="auth-visual__stat">
            <div class="auth-visual__stat-val"><?= explode(' ', $stat)[0] ?></div>
            <div class="auth-visual__stat-lbl"><?= implode(' ', array_slice(explode(' ', $stat), 1)) ?></div>
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
        <p class="auth-form-box__sub" style="margin-top:4px;">Belum punya akun? <a href="register.php" style="color:var(--color-navy);font-weight:600;">Daftar gratis</a></p>
      </div>

      <?php if ($error): ?>
      <div class="alert alert--error" id="loginError"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <div class="auth-form-card">
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
                <a href="lupa_password.php" style="font-size:12px;color:var(--color-navy);">Lupa password?</a>
              </div>
              <div style="position:relative;">
                <input class="form-control" id="loginPassword" name="password" type="password"
                       placeholder="••••••••" autocomplete="current-password" required
                       style="padding-right:44px;" />
                <button type="button" id="togglePassword"
                        style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--color-taupe-light);"
                        aria-label="Tampilkan password">
                  <svg id="eyeIcon" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                  </svg>
                </button>
              </div>
            </div>

            <div style="display:flex;align-items:center;gap:8px;">
              <input type="checkbox" id="rememberMe" name="remember" style="width:16px;height:16px;accent-color:var(--color-navy);cursor:pointer;">
              <label for="rememberMe" style="font-size:13px;color:var(--color-graphite);cursor:pointer;">Ingat saya selama 30 hari</label>
            </div>

            <button type="submit" class="btn btn--primary btn--full" id="btnMasuk">Masuk ke Akun</button>
          </div>
        </form>
      </div>

      <!-- Divider -->
      <div class="divider">atau masuk sebagai</div>

      <!-- Demo login hints -->
      <div class="auth-demo-box">
        <strong>Demo Login:</strong>
        <div>Admin: <code>admin@alinks.id</code> / <code>admin123</code></div>
        <div style="margin-top:4px;">Owner: <code>owner@alinks.id</code> / <code>owner123</code></div>
        <div style="margin-top:4px;">Kasir: <code>kasir@alinks.id</code> / <code>kasir123</code></div>
        <div style="margin-top:4px;">User: <code>user@alinks.id</code> / <code>user123</code></div>
      </div>

      <p style="font-size:12px;color:var(--color-silver-fog);text-align:center;">
        Dengan masuk, Anda menyetujui <a href="#" style="color:var(--color-navy);">Syarat &amp; Ketentuan</a> dan <a href="#" style="color:var(--color-navy);">Kebijakan Privasi</a> A-LINKS.
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
