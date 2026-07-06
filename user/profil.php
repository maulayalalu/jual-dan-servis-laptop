<?php
session_start();
require_once '../config/koneksi.php';
requireLogin();
$basePath = '../';
$pageTitle = 'Profil Saya — A-LINKS';
$id_user = (int)$_SESSION['id_user'];

$rProf = $koneksi->prepare("SELECT * FROM users WHERE id_user=?");
$rProf->bind_param('i', $id_user); $rProf->execute();
$profil = $rProf->get_result()->fetch_assoc(); $rProf->close();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profil') {
        $nama    = trim($_POST['nama'] ?? '');
        $no_telp = trim($_POST['no_telp'] ?? '');
        
        if (empty($nama)) {
            setFlash('error', 'Nama Lengkap tidak boleh kosong.');
        } else {
            $stmt = $koneksi->prepare("UPDATE users SET nama=?, no_telp=? WHERE id_user=?");
            $stmt->bind_param('ssi', $nama, $no_telp, $id_user);
            $stmt->execute(); $stmt->close();
            $_SESSION['nama'] = $nama;
            setFlash('success', 'Profil berhasil diperbarui!');
        }
        redirect('profil.php');
    }

    if ($action === 'ganti_password') {
        $pass_lama = $_POST['password_lama'] ?? '';
        $pass_baru = $_POST['password_baru'] ?? '';
        $pass_conf = $_POST['password_konfirmasi'] ?? '';

        if (!password_verify($pass_lama, $profil['password'])) {
            setFlash('error', 'Password lama tidak sesuai.');
        } elseif (strlen($pass_baru) < 8) {
            setFlash('error', 'Password baru minimal 8 karakter.');
        } elseif ($pass_baru !== $pass_conf) {
            setFlash('error', 'Konfirmasi password tidak cocok.');
        } else {
            $hash = password_hash($pass_baru, PASSWORD_DEFAULT);
            $stmt = $koneksi->prepare("UPDATE users SET password=? WHERE id_user=?");
            $stmt->bind_param('si', $hash, $id_user);
            $stmt->execute(); $stmt->close();
            setFlash('success', 'Password berhasil diganti!');
        }
        redirect('profil.php');
    }
}

// Stats
$rStats = $koneksi->prepare("SELECT COUNT(*) as total_trx, COUNT(CASE WHEN status_pembayaran='paid' THEN 1 END) as total_lunas FROM transaksi WHERE id_user=?");
$rStats->bind_param('i', $id_user); $rStats->execute();
$stats = $rStats->get_result()->fetch_assoc(); $rStats->close();

include '../includes/header.php';

// Format Date
$months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
$createdAt = $profil['created_at'] ? strtotime($profil['created_at']) : time();
$joinDate = $months[date('n', $createdAt) - 1] . ' ' . date('Y', $createdAt);
?>

<style>
/* Profil Page — NEXOTECH */
body { background-color: var(--color-cream) !important; }
.profil-wrapper { background: var(--color-cream); min-height: calc(100vh - 64px); padding: 100px 20px 60px; }
.profil-container { max-width: 960px; margin: 0 auto; }
.profil-breadcrumb { font-size: 13px; color: var(--color-pewter); margin-bottom: 24px; display: flex; align-items: center; gap: 6px; }
.profil-breadcrumb a { color: var(--color-navy); text-decoration: none; transition: color 0.2s; }
.profil-breadcrumb a:hover { color: var(--color-navy-dark); }
.profil-breadcrumb span { color: var(--color-carbon); font-weight: 600; }

.profil-grid { display: grid; grid-template-columns: 260px 1fr; gap: 24px; }

.profil-card {
  background: var(--color-white);
  border-radius: var(--radius-card);
  border: 1px solid var(--color-cream-border);
  box-shadow: var(--shadow-sm);
  overflow: hidden;
}
.profil-card-inner { padding: 28px 28px 24px; }
.profil-card-header {
  background: var(--color-cream);
  padding: 16px 28px;
  border-bottom: 1px solid var(--color-cream-border);
}
.profil-card-title { font-size: 16px; font-weight: 700; color: var(--color-carbon); }

/* Avatar */
.profil-avatar-box { text-align: center; padding: 32px 24px 24px; }
.profil-avatar {
  width: 88px; height: 88px; border-radius: 50%;
  background: var(--color-taupe); color: white;
  font-size: 32px; font-weight: 700;
  display: flex; align-items: center; justify-content: center;
  margin: 0 auto 16px;
  border: 3px solid rgba(140,123,117,0.25);
  box-shadow: 0 4px 16px rgba(140,123,117,0.30);
}
.profil-name { font-size: 18px; font-weight: 700; color: var(--color-carbon); margin-bottom: 4px; }
.profil-uname { font-size: 14px; color: var(--color-pewter); margin-bottom: 6px; }
.profil-join { font-size: 12px; color: var(--color-taupe-light); }

/* Stats Card */
.profil-stats-card { padding: 20px 24px; }
.profil-stat-title { font-size: 14px; font-weight: 600; color: var(--color-carbon); margin-bottom: 16px; text-transform: uppercase; letter-spacing: 0.5px; }
.profil-stat-row { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid var(--color-cream); }
.profil-stat-row:last-of-type { border-bottom: none; }
.profil-stat-label { font-size: 13px; color: var(--color-pewter); }
.profil-stat-val { font-size: 16px; font-weight: 700; color: var(--color-carbon); }
.profil-stat-val.green { color: var(--color-success); }
.profil-stat-link { display: inline-flex; align-items: center; gap: 4px; font-size: 13px; font-weight: 600; color: var(--color-navy); text-decoration: none; margin-top: 14px; transition: color 0.2s; }
.profil-stat-link:hover { color: var(--color-navy-dark); }

/* Form Elements */
.p-form-group { margin-bottom: 18px; }
.p-form-label { display: block; font-size: 13px; font-weight: 600; color: var(--color-graphite); margin-bottom: 6px; letter-spacing: 0.1px; }
.p-form-label span.req { color: var(--color-danger); }
.p-form-control {
  width: 100%; background: var(--color-white);
  border: 1.5px solid var(--color-cream-border); border-radius: var(--radius-btn);
  padding: 10px 14px; font-size: 14px; color: var(--color-carbon);
  transition: border-color 0.25s, box-shadow 0.25s;
  font-family: var(--font-text);
}
.p-form-control:focus { outline: none; border-color: var(--color-navy); box-shadow: 0 0 0 3px rgba(62,92,118,0.15); }
.p-form-control:read-only { background: var(--color-cream); color: var(--color-pewter); cursor: not-allowed; }
.p-form-hint { font-size: 12px; color: var(--color-silver-fog); margin-top: 4px; }

/* Buttons */
.p-btn { padding: 10px 22px; border-radius: var(--radius-btn); font-size: 14px; font-weight: 600; cursor: pointer; border: 1.5px solid transparent; transition: all 0.25s; }
.p-btn-gold { background: var(--color-navy); color: white; border-color: var(--color-navy); box-shadow: 0 2px 8px rgba(62,92,118,0.28); }
.p-btn-gold:hover { background: var(--color-navy-dark); border-color: var(--color-navy-dark); }
.p-btn-dark { background: var(--color-taupe); color: white; border-color: var(--color-taupe); }
.p-btn-dark:hover { background: var(--color-taupe-dark); border-color: var(--color-taupe-dark); }

.p-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }

.pwd-toggle-btn {
  position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
  background: none; border: none; cursor: pointer;
  color: var(--color-taupe-light); display: grid; place-items: center;
  transition: color 0.2s;
}
.pwd-toggle-btn:hover { color: var(--color-taupe); }

@media (max-width: 768px) {
  .profil-grid { grid-template-columns: 1fr; }
  .p-grid-2 { grid-template-columns: 1fr; }
}
</style>

<div class="profil-wrapper">
  <div class="profil-container">
    <div class="profil-breadcrumb">
      <a href="<?= $basePath ?>index.php">Beranda</a> / <span>Profil Saya</span>
    </div>

    <?php renderFlash(); ?>

    <div class="profil-grid">
  <div>
        <!-- Avatar Card -->
        <div class="profil-card">
          <div class="profil-avatar-box">
            <div class="profil-avatar"><?= strtoupper(substr($profil['nama'], 0, 1)) ?></div>
            <div class="profil-name"><?= htmlspecialchars($profil['nama']) ?></div>
            <div class="profil-uname"><?= htmlspecialchars($profil['email']) ?></div>
            <div class="profil-join">Bergabung <?= $joinDate ?></div>
          </div>
        </div>

        <!-- Stats Card -->
        <div class="profil-card" style="margin-top:16px;">
          <div class="profil-stats-card">
            <div class="profil-stat-title">Statistik Anda</div>
            <div class="profil-stat-row">
              <span class="profil-stat-label">Total Pesanan</span>
              <span class="profil-stat-val"><?= $stats['total_trx'] ?></span>
            </div>
            <div class="profil-stat-row">
              <span class="profil-stat-label">Pesanan Lunas</span>
              <span class="profil-stat-val green"><?= $stats['total_lunas'] ?></span>
            </div>
            <a href="riwayat.php" class="profil-stat-link">Lihat Riwayat &rarr;</a>
          </div>
        </div>
      </div>

      <!-- Main Forms -->
      <div style="display: flex; flex-direction: column; gap: 24px;">
        <!-- Informasi Pribadi -->
        <div class="profil-card">
          <div class="profil-card-header">
            <div class="profil-card-title">Informasi Pribadi</div>
          </div>
          <div class="profil-card-inner">
            <form method="POST">
              <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
              <input type="hidden" name="action" value="update_profil">
              <div class="p-grid-2">
                <div class="p-form-group">
                  <label class="p-form-label">Nama Lengkap <span class="req">*</span></label>
                  <input type="text" class="p-form-control" name="nama" value="<?= htmlspecialchars($profil['nama']) ?>" required>
                </div>
                <div class="p-form-group">
                  <label class="p-form-label">No. HP / WhatsApp</label>
                  <input type="tel" class="p-form-control" name="no_telp" value="<?= htmlspecialchars($profil['no_telp'] ?? '') ?>">
                </div>
              </div>
              <div class="p-form-group">
                <label class="p-form-label">Email</label>
                <input type="email" class="p-form-control" value="<?= htmlspecialchars($profil['email']) ?>" readonly>
                <div class="p-form-hint">Email tidak bisa diubah.</div>
              </div>
              <button type="submit" class="p-btn p-btn-gold" id="btnSimpanProfil">Simpan Perubahan</button>
            </form>
          </div>
        </div>

        <!-- Ganti Password -->
        <div class="profil-card" style="margin-top:0;">
          <div class="profil-card-header">
            <div class="profil-card-title">Ganti Password</div>
          </div>
          <div class="profil-card-inner">
            <form method="POST">
              <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
              <input type="hidden" name="action" value="ganti_password">
              <div class="p-form-group">
                <label class="p-form-label">Password Lama</label>
                <div style="position:relative;">
                  <input type="password" class="p-form-control" name="password_lama" id="pwd_lama" required style="padding-right:44px;">
                  <button type="button" class="pwd-toggle-btn" onclick="togglePwd('pwd_lama', this)"><svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg></button>
                </div>
              </div>
              <div class="p-grid-2" style="margin-bottom:24px;">
                <div class="p-form-group">
                  <label class="p-form-label">Password Baru <span style="font-weight:400;color:var(--color-taupe-light);">(min. 8 karakter)</span></label>
                  <div style="position:relative;">
                    <input type="password" class="p-form-control" name="password_baru" id="pwd_baru" required minlength="8" style="padding-right:44px;">
                    <button type="button" class="pwd-toggle-btn" onclick="togglePwd('pwd_baru', this)"><svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg></button>
                  </div>
                </div>
                <div class="p-form-group">
                  <label class="p-form-label">Konfirmasi Password Baru</label>
                  <div style="position:relative;">
                    <input type="password" class="p-form-control" name="password_konfirmasi" id="pwd_conf" required minlength="8" style="padding-right:44px;">
                    <button type="button" class="pwd-toggle-btn" onclick="togglePwd('pwd_conf', this)"><svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg></button>
                  </div>
                </div>
              </div>
              <button type="submit" class="p-btn p-btn-dark" id="btnGantiPassword">Ganti Password</button>
            </form>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<script>
function togglePwd(id, btn) {
    const input = document.getElementById(id);
    if (input.type === 'password') {
        input.type = 'text';
        btn.innerHTML = `<svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>`;
    } else {
        input.type = 'password';
        btn.innerHTML = `<svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>`;
    }
}
</script>

<?php include '../includes/footer.php'; ?>
