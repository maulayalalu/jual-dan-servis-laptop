<?php
session_start();
require_once 'config/koneksi.php';

if (isLoggedIn()) {
    redirect($_SESSION['role'] === 'admin' ? 'admin/dashboard.php' : 'user/dashboard.php');
}

$errors = [];
$values = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $nama     = trim($_POST['nama']     ?? '');
    $email    = trim($_POST['email']    ?? '');
    $no_telp  = trim($_POST['no_telp']  ?? '');
    $alamat   = trim($_POST['alamat']   ?? '');
    $password = $_POST['password']      ?? '';
    $konfirm  = $_POST['konfirm']       ?? '';

    $values = compact('nama','email','no_telp','alamat');

    // Validasi
    if (empty($nama))                         $errors['nama']     = 'Nama wajib diisi.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Format email tidak valid.';
    if (strlen($password) < 8)                $errors['password'] = 'Password minimal 8 karakter.';
    if ($password !== $konfirm)               $errors['konfirm']  = 'Konfirmasi password tidak cocok.';

    // Cek email sudah terdaftar
    if (empty($errors['email'])) {
        $stmt = $koneksi->prepare("SELECT id_user FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) $errors['email'] = 'Email sudah terdaftar.';
        $stmt->close();
    }

    if (empty($errors)) {
        $hashed   = password_hash($password, PASSWORD_DEFAULT);
        $role     = 'user';
        $stmt     = $koneksi->prepare(
            "INSERT INTO users (nama, email, password, role, no_telp, alamat) VALUES (?,?,?,?,?,?)"
        );
        $stmt->bind_param('ssssss', $nama, $email, $hashed, $role, $no_telp, $alamat);

        if ($stmt->execute()) {
            $stmt->close();
            setFlash('success', 'Akun berhasil dibuat! Silakan masuk.');
            redirect('login.php');
        } else {
            $errors['global'] = 'Terjadi kesalahan. Silakan coba lagi.';
        }
        $stmt->close();
    }
}

$pageTitle = 'Daftar Akun — A-LINKS';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= $pageTitle ?></title>
  <meta name="description" content="Daftar akun A-LINKS gratis dan nikmati kemudahan belanja laptop & layanan servis profesional."/>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="assets/css/style.css"/>
</head>
<body>

<div class="auth-page">
  <!-- Visual Side -->
  <div class="auth-visual">
    <img src="https://images.unsplash.com/photo-1588702547923-7093a6c3ba33?w=1200&q=85"
         alt="Laptop workspace"
         onerror="this.parentElement.style.background='linear-gradient(135deg,#1a1a2e,#16213e,#0f3460)'">
    <div class="auth-visual__overlay">
      <div>
        <div style="font-size:22px;font-weight:600;letter-spacing:4px;color:white;text-transform:uppercase;margin-bottom:24px;">A-LINKS</div>
        <div class="auth-visual__title">Bergabung &<br>Nikmati Manfaatnya</div>
        <div class="auth-visual__subtitle">Ribuan produk laptop & servis terpercaya menanti</div>
        <div style="margin-top:32px;display:flex;flex-direction:column;gap:12px;">
          <?php foreach ([
            ['icon'=>'🛒','text'=>'Belanja laptop dengan cicilan 0%'],
            ['icon'=>'🔧','text'=>'Tracking status servis real-time'],
            ['icon'=>'🎁','text'=>'Promo & diskon eksklusif member'],
            ['icon'=>'📦','text'=>'Pengiriman gratis seluruh Indonesia'],
          ] as $b): ?>
          <div style="display:flex;align-items:center;gap:10px;">
            <span style="font-size:18px;"><?= $b['icon'] ?></span>
            <span style="font-size:13px;color:rgba(255,255,255,0.8);"><?= $b['text'] ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Form Side -->
  <div class="auth-form-area" style="padding:40px 32px;overflow-y:auto;">
    <div class="auth-form-box" style="max-width:460px;">
      <div>
        <a href="index.php" class="auth-form-box__brand">A-LINKS</a>
        <h1 class="auth-form-box__title" style="margin-top:8px;">Buat Akun</h1>
        <p style="font-size:14px;color:var(--color-pewter);margin-top:4px;">Sudah punya akun? <a href="login.php" style="color:var(--color-blue);font-weight:500;">Masuk di sini</a></p>
      </div>

      <?php if (!empty($errors['global'])): ?>
      <div class="alert alert--error"><?= htmlspecialchars($errors['global']) ?></div>
      <?php endif; ?>

      <!-- Progress indicator -->
      <div class="steps">
        <div class="step active" id="step1Ind">
          <div class="step__dot">1</div>
          <div class="step__label">Identitas</div>
        </div>
        <div class="step" id="step2Ind">
          <div class="step__dot">2</div>
          <div class="step__label">Kontak</div>
        </div>
        <div class="step" id="step3Ind">
          <div class="step__dot">3</div>
          <div class="step__label">Password</div>
        </div>
      </div>

      <form method="POST" action="register.php" id="registerForm" novalidate>
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <div style="display:flex;flex-direction:column;gap:16px;">

          <!-- Nama -->
          <div class="form-group">
            <label class="form-label" for="regNama">Nama Lengkap <span style="color:#d92b2b;">*</span></label>
            <input class="form-control <?= isset($errors['nama']) ? 'border-red' : '' ?>"
                   id="regNama" name="nama" type="text"
                   placeholder="Nama lengkap Anda"
                   value="<?= htmlspecialchars($values['nama'] ?? '') ?>"
                   autocomplete="name" required />
            <?php if (isset($errors['nama'])): ?>
            <div class="form-error"><?= htmlspecialchars($errors['nama']) ?></div>
            <?php endif; ?>
          </div>

          <!-- Email -->
          <div class="form-group">
            <label class="form-label" for="regEmail">Alamat Email <span style="color:#d92b2b;">*</span></label>
            <input class="form-control <?= isset($errors['email']) ? 'border-red' : '' ?>"
                   id="regEmail" name="email" type="email"
                   placeholder="email@contoh.com"
                   value="<?= htmlspecialchars($values['email'] ?? '') ?>"
                   autocomplete="email" required />
            <?php if (isset($errors['email'])): ?>
            <div class="form-error"><?= htmlspecialchars($errors['email']) ?></div>
            <?php endif; ?>
          </div>

          <!-- No Telp -->
          <div class="form-group">
            <label class="form-label" for="regTelp">Nomor Telepon</label>
            <input class="form-control" id="regTelp" name="no_telp" type="tel"
                   placeholder="08xxxxxxxxxx"
                   value="<?= htmlspecialchars($values['no_telp'] ?? '') ?>"
                   autocomplete="tel" />
            <div class="form-hint">Untuk konfirmasi pesanan & servis</div>
          </div>

          <!-- Alamat -->
          <div class="form-group">
            <label class="form-label" for="regAlamat">Alamat Pengiriman</label>
            <textarea class="form-control form-control--textarea" id="regAlamat" name="alamat"
                      placeholder="Jl. Contoh No. 123, Kota, Provinsi"
                      rows="2"><?= htmlspecialchars($values['alamat'] ?? '') ?></textarea>
          </div>

          <!-- Password -->
          <div class="form-group">
            <label class="form-label" for="regPassword">Password <span style="color:#d92b2b;">*</span></label>
            <div style="position:relative;">
              <input class="form-control <?= isset($errors['password']) ? 'border-red' : '' ?>"
                     id="regPassword" name="password" type="password"
                     placeholder="Minimal 8 karakter"
                     autocomplete="new-password" required
                     style="padding-right:44px;" />
              <button type="button" id="toggleRegPassword"
                      style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--color-silver-fog);" aria-label="Tampilkan password">
                <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
              </button>
            </div>
            <!-- Password strength bar -->
            <div id="strengthBar" style="height:3px;background:var(--color-cloud);border-radius:2px;margin-top:6px;overflow:hidden;">
              <div id="strengthFill" style="height:100%;width:0;background:var(--color-silver-fog);transition:width 0.3s,background 0.3s;border-radius:2px;"></div>
            </div>
            <div id="strengthLabel" class="form-hint" style="margin-top:2px;"></div>
            <?php if (isset($errors['password'])): ?>
            <div class="form-error"><?= htmlspecialchars($errors['password']) ?></div>
            <?php endif; ?>
          </div>

          <!-- Konfirmasi Password -->
          <div class="form-group">
            <label class="form-label" for="regKonfirm">Konfirmasi Password <span style="color:#d92b2b;">*</span></label>
            <input class="form-control <?= isset($errors['konfirm']) ? 'border-red' : '' ?>"
                   id="regKonfirm" name="konfirm" type="password"
                   placeholder="Ulangi password"
                   autocomplete="new-password" required />
            <?php if (isset($errors['konfirm'])): ?>
            <div class="form-error"><?= htmlspecialchars($errors['konfirm']) ?></div>
            <?php endif; ?>
          </div>

          <!-- Terms -->
          <div style="display:flex;align-items:flex-start;gap:8px;">
            <input type="checkbox" id="agreeTerms" name="agree" required
                   style="width:16px;height:16px;margin-top:2px;cursor:pointer;flex-shrink:0;">
            <label for="agreeTerms" style="font-size:13px;color:var(--color-graphite);cursor:pointer;line-height:1.5;">
              Saya menyetujui <a href="#" style="color:var(--color-blue);">Syarat & Ketentuan</a> dan <a href="#" style="color:var(--color-blue);">Kebijakan Privasi</a> A-LINKS
            </label>
          </div>

          <button type="submit" class="btn btn--primary btn--full" id="btnDaftar">Buat Akun Gratis</button>
        </div>
      </form>
    </div>
  </div>
</div>

<style>
.border-red { border-color: #d92b2b !important; }
</style>
<script src="assets/js/main.js"></script>
<script>
  // Toggle password
  const toggleRegPwd = document.getElementById('toggleRegPassword');
  const regPwd = document.getElementById('regPassword');
  toggleRegPwd?.addEventListener('click', () => {
    regPwd.type = regPwd.type === 'text' ? 'password' : 'text';
  });

  // Password strength
  const strengthFill  = document.getElementById('strengthFill');
  const strengthLabel = document.getElementById('strengthLabel');
  const strengthColors = ['#d92b2b','#f59e0b','#3E6AE1','#16a34a'];
  const strengthLabels = ['Sangat Lemah','Lemah','Sedang','Kuat'];

  regPwd?.addEventListener('input', () => {
    const v = regPwd.value;
    let score = 0;
    if (v.length >= 8)           score++;
    if (/[A-Z]/.test(v))         score++;
    if (/[0-9]/.test(v))         score++;
    if (/[^A-Za-z0-9]/.test(v))  score++;
    const pct = (score / 4) * 100;
    strengthFill.style.width  = pct + '%';
    strengthFill.style.background = strengthColors[score - 1] || 'var(--color-cloud)';
    strengthLabel.textContent = v.length > 0 ? strengthLabels[score - 1] || '' : '';
  });

  // Check password match on blur
  document.getElementById('regKonfirm')?.addEventListener('blur', function() {
    const match = regPwd.value === this.value;
    this.style.borderColor = this.value ? (match ? 'var(--color-blue)' : '#d92b2b') : '';
  });

  // Form validation
  document.getElementById('registerForm')?.addEventListener('submit', (e) => {
    const agree = document.getElementById('agreeTerms').checked;
    if (!agree) { e.preventDefault(); alert('Harap setujui syarat & ketentuan terlebih dahulu.'); }
  });
</script>
</body>
</html>
