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
/* Profil Specific CSS */
body { background-color: var(--color-light-ash) !important; }
.profil-wrapper {
    background-color: var(--color-light-ash);
    min-height: calc(100vh - 56px); /* subtract nav height */
    padding: 100px 20px 60px;
    font-family: 'Inter', sans-serif;
}
.profil-container {
    max-width: 960px;
    margin: 0 auto;
}
.profil-breadcrumb {
    font-size: 14px;
    color: var(--color-pewter);
    margin-bottom: 24px;
}
.profil-breadcrumb a { color: var(--color-pewter); text-decoration: none; }
.profil-breadcrumb span { color: var(--color-carbon); font-weight: 600; margin-left: 6px; }

.profil-grid {
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 24px;
}

.profil-card {
    background: #ffffff;
    border-radius: 16px;
    border: 1px solid var(--color-cloud);
    box-shadow: 0 4px 20px rgba(0,0,0,0.02);
    padding: 32px;
}
.profil-card-header {
    border-bottom: 1px solid var(--color-cloud);
    padding-bottom: 20px;
    margin-bottom: 24px;
    margin-top: -8px;
}
.profil-card-title {
    font-size: 20px;
    font-weight: 700;
    color: var(--color-carbon);
}

/* Avatar Card */
.profil-avatar-box {
    text-align: center;
}
.profil-avatar {
    width: 96px;
    height: 96px;
    border-radius: 50%;
    background-color: var(--color-blue);
    color: white;
    font-size: 36px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
}
.profil-name { font-size: 20px; font-weight: 700; color: var(--color-carbon); margin-bottom: 4px; }
.profil-uname { font-size: 15px; color: var(--color-pewter); margin-bottom: 8px; }
.profil-join { font-size: 13px; color: var(--color-silver-fog); }

/* Stats Card */
.profil-stats-card {
    padding: 24px;
    margin-top: 24px;
}
.profil-stat-title { font-size: 16px; font-weight: 600; color: var(--color-carbon); margin-bottom: 20px; }
.profil-stat-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
.profil-stat-label { font-size: 14px; color: var(--color-pewter); }
.profil-stat-val { font-size: 16px; font-weight: 700; color: var(--color-carbon); }
.profil-stat-val.green { color: #2ea65a; }
.profil-stat-link { font-size: 14px; font-weight: 600; color: var(--color-blue); text-decoration: none; margin-top: 16px; display: inline-block; transition: color 0.2s; }
.profil-stat-link:hover { opacity: 0.8; }

/* Form Elements */
.p-form-group { margin-bottom: 20px; }
.p-form-label {
    display: block;
    font-size: 14px;
    font-weight: 600;
    color: var(--color-carbon);
    margin-bottom: 8px;
}
.p-form-label span.req { color: #d92b2b; }
.p-form-control {
    width: 100%;
    background-color: #fafafa;
    border: 1px solid var(--color-cloud);
    border-radius: 12px;
    padding: 12px 16px;
    font-size: 15px;
    color: var(--color-carbon);
    transition: all 0.3s;
}
.p-form-control:focus { outline: none; border-color: var(--color-blue); background-color: #fff; }
.p-form-control:read-only {
    background-color: var(--color-light-ash);
    color: var(--color-pewter);
    cursor: not-allowed;
}
.p-form-hint { font-size: 13px; color: var(--color-silver-fog); margin-top: 6px; }

/* Buttons */
.p-btn {
    padding: 12px 24px;
    border-radius: 24px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    border: none;
    transition: all 0.2s;
}
.p-btn-gold {
    background-color: var(--color-blue);
    color: #fff;
}
.p-btn-gold:hover { opacity: 0.9; }
.p-btn-dark {
    background-color: var(--color-carbon);
    color: #fff;
}
.p-btn-dark:hover { opacity: 0.9; }

.grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }

.pwd-toggle-btn {
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    cursor: pointer;
    color: #a19688;
    display: grid;
    place-items: center;
}
.pwd-toggle-btn:hover { color: #5c5243; }

@media (max-width: 768px) {
    .profil-grid { grid-template-columns: 1fr; }
    .grid-2 { grid-template-columns: 1fr; }
}
</style>

<div class="profil-wrapper">
  <div class="profil-container">
    <div class="profil-breadcrumb">
      <a href="<?= $basePath ?>index.php">Beranda</a> / <span>Profil Saya</span>
    </div>

    <?php renderFlash(); ?>

    <div class="profil-grid">
      <!-- Sidebar -->
      <div>
        <div class="profil-card profil-avatar-box">
          <div class="profil-avatar">
            <?= strtoupper(substr($profil['nama'], 0, 1)) ?>
          </div>
          <div class="profil-name"><?= htmlspecialchars($profil['nama']) ?></div>
          <div class="profil-uname">@<?= htmlspecialchars($profil['username'] ?? 'user') ?></div>
          <div class="profil-join">Bergabung <?= $joinDate ?></div>
        </div>

        <div class="profil-card profil-stats-card">
          <div class="profil-stat-title">Statistik Anda</div>
          <div class="profil-stat-row">
            <span class="profil-stat-label">Total Pesanan</span>
            <span class="profil-stat-val"><?= $stats['total_trx'] ?></span>
          </div>
          <div class="profil-stat-row">
            <span class="profil-stat-label">Perjalanan Lunas</span>
            <span class="profil-stat-val green"><?= $stats['total_lunas'] ?></span>
          </div>
          <hr style="border:none; border-top:1px solid #efeae0; margin: 16px 0;">
          <a href="riwayat.php" class="profil-stat-link">Lihat Riwayat &rarr;</a>
        </div>
      </div>

      <!-- Main Forms -->
      <div style="display: flex; flex-direction: column; gap: 24px;">
        <!-- Informasi Pribadi -->
        <div class="profil-card">
          <div class="profil-card-header">
            <div class="profil-card-title">Informasi Pribadi</div>
          </div>
          <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="action" value="update_profil">
            
            <div class="grid-2">
              <div class="p-form-group">
                <label class="p-form-label">Nama Lengkap <span class="req">*</span></label>
                <input type="text" class="p-form-control" name="nama" value="<?= htmlspecialchars($profil['nama']) ?>" required>
              </div>
              <div class="p-form-group">
                <label class="p-form-label">Username</label>
                <input type="text" class="p-form-control" value="<?= htmlspecialchars($profil['username'] ?? '') ?>" readonly>
                <div class="p-form-hint">Username tidak bisa diubah.</div>
              </div>
            </div>

            <div class="p-form-group">
              <label class="p-form-label">Email <span class="req">*</span></label>
              <input type="email" class="p-form-control" value="<?= htmlspecialchars($profil['email']) ?>" readonly>
            </div>

            <div class="p-form-group" style="margin-bottom: 32px;">
              <label class="p-form-label">No. Handphone / WhatsApp</label>
              <input type="tel" class="p-form-control" name="no_telp" value="<?= htmlspecialchars($profil['no_telp'] ?? '') ?>">
            </div>

            <button type="submit" class="p-btn p-btn-gold">Simpan Perubahan</button>
          </form>
        </div>

        <!-- Ganti Password -->
        <div class="profil-card">
          <div class="profil-card-header">
            <div class="profil-card-title">Ganti Password</div>
          </div>
          <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="action" value="ganti_password">
            
            <div class="p-form-group">
              <label class="p-form-label">Password Lama</label>
              <div style="position:relative;">
                <input type="password" class="p-form-control" name="password_lama" id="pwd_lama" required style="padding-right:48px;">
                <button type="button" class="pwd-toggle-btn" onclick="togglePwd('pwd_lama', this)">
                  <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                </button>
              </div>
            </div>

            <div class="grid-2" style="margin-bottom: 32px;">
              <div class="p-form-group">
                <label class="p-form-label">Password Baru <span style="font-weight:400;color:#a19688;">(min. 8 karakter)</span></label>
                <div style="position:relative;">
                  <input type="password" class="p-form-control" name="password_baru" id="pwd_baru" required minlength="8" style="padding-right:48px;">
                  <button type="button" class="pwd-toggle-btn" onclick="togglePwd('pwd_baru', this)">
                    <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                  </button>
                </div>
              </div>
              <div class="p-form-group">
                <label class="p-form-label">Konfirmasi Password Baru</label>
                <div style="position:relative;">
                  <input type="password" class="p-form-control" name="password_konfirmasi" id="pwd_conf" required minlength="8" style="padding-right:48px;">
                  <button type="button" class="pwd-toggle-btn" onclick="togglePwd('pwd_conf', this)">
                    <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                  </button>
                </div>
              </div>
            </div>

            <button type="submit" class="p-btn p-btn-dark">Ganti Password</button>
          </form>
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
