<?php
session_start();
require_once '../config/koneksi.php';

// Hanya bisa diakses oleh owner
if (!isLoggedIn() || $_SESSION['role'] !== 'owner') {
    setFlash('error', 'Akses ditolak. Halaman ini hanya untuk Owner.');
    redirect('../login.php');
}

$basePath = '../';
$pageTitle = 'Pengaturan Situs — A-LINKS';

// Handle Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    
    // Sesuaikan dengan yang ada di sistem A-LINKS (sama dengan pengaturan_web.php)
    $settingsToUpdate = [
        'nama_toko', 'tagline', 'alamat', 'no_wa', 'pesan_wa',
        'servis_judul', 'servis_deskripsi', 'servis_fitur1_judul', 'servis_fitur1_desc',
        'servis_fitur2_judul', 'servis_fitur2_desc', 'servis_fitur3_judul', 'servis_fitur3_desc',
        'tentang_judul', 'tentang_deskripsi', 'tentang_poin1', 'tentang_poin2', 'tentang_poin3', 'tentang_poin4', 'tentang_gambar',
        'stat1_nilai', 'stat1_label', 'stat2_nilai', 'stat2_label',
        'stat3_nilai', 'stat3_label', 'stat4_nilai', 'stat4_label',
        'sosmed_ig', 'sosmed_fb', 'sosmed_tiktok'
    ];
    
    $success = true;
    foreach ($settingsToUpdate as $kunci) {
        if (isset($_POST[$kunci])) {
            $nilai = trim($_POST[$kunci]);
            
            // Cek apakah key ada
            $stmtCek = $koneksi->prepare("SELECT 1 FROM pengaturan WHERE kunci = ?");
            $stmtCek->bind_param('s', $kunci);
            $stmtCek->execute();
            $exists = $stmtCek->get_result()->num_rows > 0;
            $stmtCek->close();
            
            if ($exists) {
                $stmt = $koneksi->prepare("UPDATE pengaturan SET nilai = ? WHERE kunci = ?");
                $stmt->bind_param('ss', $nilai, $kunci);
            } else {
                $stmt = $koneksi->prepare("INSERT INTO pengaturan (kunci, nilai) VALUES (?, ?)");
                $stmt->bind_param('ss', $kunci, $nilai);
            }
            
            if (!$stmt->execute()) {
                $success = false;
            }
            $stmt->close();
        }
    }
    
    if ($success) {
        setFlash('success', 'Pengaturan situs berhasil diperbarui.');
    } else {
        setFlash('error', 'Gagal memperbarui beberapa pengaturan.');
    }
    redirect('pengaturan_situs.php');
}

// Ambil Data Saat Ini
$res = $koneksi->query("SELECT kunci, nilai FROM pengaturan");
$pengaturan = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $pengaturan[$row['kunci']] = $row['nilai'];
    }
}

// Fungsi bantu untuk nilai default
function getSet($key, $default = '') {
    global $pengaturan;
    return $pengaturan[$key] ?? $default;
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= $pageTitle ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="../assets/css/style.css?v=<?= time() ?>"/>
  <style>
    .page-header-container {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 24px;
    }
    .settings-tabs-wrapper {
      background: var(--color-cream);
      border: 1px solid var(--color-cream-border);
      border-radius: 12px;
      padding: 12px;
      margin-bottom: 24px;
      display: flex;
      gap: 8px;
      overflow-x: auto;
      scrollbar-width: none;
    }
    .settings-tabs-wrapper::-webkit-scrollbar {
      display: none;
    }
    .settings-tab {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 10px 16px;
      border-radius: 20px;
      background: transparent;
      color: var(--color-pewter);
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      border: 1px solid transparent;
      white-space: nowrap;
      transition: all 0.2s ease;
    }
    .settings-tab:hover {
      background: rgba(0,0,0,0.03);
    }
    .settings-tab.active {
      background: #fff;
      color: #d4a373;
      border: 1px solid var(--color-cloud);
      box-shadow: 0 2px 4px rgba(0,0,0,0.04);
    }
    
    .tab-content {
      display: none;
      animation: fadeIn 0.3s ease;
    }
    .tab-content.active {
      display: block;
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(5px); }
      to { opacity: 1; transform: translateY(0); }
    }

    /* Content styling */
    .settings-card {
      background: #fff;
      border: 1px solid var(--color-cloud);
      border-radius: 12px;
      padding: 24px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.02);
    }
    .settings-card h3 {
      font-size: 16px;
      font-weight: 600;
      color: var(--color-carbon);
      margin-bottom: 20px;
      padding-bottom: 12px;
      border-bottom: 1px solid var(--color-cloud);
    }
    
    .btn-lihat-situs {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 8px 16px;
      background: #fff;
      border: 1px solid var(--color-cloud);
      border-radius: 20px;
      color: var(--color-carbon);
      font-size: 14px;
      font-weight: 500;
      text-decoration: none;
      box-shadow: 0 2px 4px rgba(0,0,0,0.02);
      transition: all 0.2s;
    }
    .btn-lihat-situs:hover {
      background: var(--color-light-ash);
      border-color: var(--color-pewter);
    }

    .settings-fitur-row {
      background: var(--color-cream);
      border: 1px solid var(--color-cream-border);
      border-radius: var(--radius-md);
      padding: 16px;
      margin-bottom: 12px;
    }
    .grid-2-col {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 16px;
    }
    @media(max-width:768px){ .grid-2-col { grid-template-columns: 1fr; } }
  </style>
</head>
<body>

<div class="app-layout">
  <?php include '../includes/sidebar_admin.php'; ?>

  <main class="main-content">
    
    <div class="page-header-container">
      <div>
        <h1 class="page-header__title">Pengaturan Situs</h1>
        <div class="page-header__sub">Kelola teks, kontak, dan konten website secara dinamis tanpa ubah kode.</div>
      </div>
      <div>
        <a href="<?= $basePath ?>index.php" target="_blank" class="btn-lihat-situs">
          <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
          </svg>
          Lihat Situs
        </a>
      </div>
    </div>

    <?php renderFlash(); ?>

    <form action="" method="POST" style="max-width: 900px;">
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

      <div class="settings-tabs-wrapper">
        <div class="settings-tab active" data-target="#tab-umum">🏢 Umum & Footer</div>
        <div class="settings-tab" data-target="#tab-sosmed">📱 Media Sosial</div>
        <div class="settings-tab" data-target="#tab-tentang">ℹ️ Tentang Kami</div>
        <div class="settings-tab" data-target="#tab-servis">🛠️ Layanan Servis</div>
        <div class="settings-tab" data-target="#tab-statistik">📊 Statistik</div>
      </div>

      <!-- Tab 1: Umum & Footer -->
      <div class="tab-content active" id="tab-umum">
        <div class="settings-card">
          <h3>Pengaturan Umum & Footer</h3>
          <div class="form-group">
            <label class="form-label">Nama Toko</label>
            <input type="text" class="form-control" name="nama_toko" value="<?= htmlspecialchars(getSet('nama_toko', 'A-LINKS')) ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label">Tagline / Deskripsi Singkat</label>
            <textarea class="form-control form-control--textarea" name="tagline" rows="2" required><?= htmlspecialchars(getSet('tagline', 'Toko laptop terpercaya...')) ?></textarea>
          </div>
          <div class="form-group">
            <label class="form-label">Alamat Lengkap</label>
            <textarea class="form-control form-control--textarea" name="alamat" rows="2" required><?= htmlspecialchars(getSet('alamat', '73RH+PG6...')) ?></textarea>
          </div>
          <div class="grid-2-col">
            <div class="form-group">
              <label class="form-label">Nomor WhatsApp</label>
              <input type="text" class="form-control" name="no_wa" value="<?= htmlspecialchars(getSet('no_wa', '6281216851726')) ?>" required>
              <span class="form-hint">Format: 628xxxxxxxxx</span>
            </div>
            <div class="form-group">
              <label class="form-label">Pesan Template WA</label>
              <input type="text" class="form-control" name="pesan_wa" value="<?= htmlspecialchars(getSet('pesan_wa', 'Halo A-LINKS...')) ?>" required>
            </div>
          </div>
        </div>
      </div>

      <!-- Tab 1.5: Media Sosial -->
      <div class="tab-content" id="tab-sosmed">
        <div class="settings-card">
          <h3>Tautan Media Sosial</h3>
          <div class="form-group">
            <label class="form-label">Link Instagram</label>
            <input type="text" class="form-control" name="sosmed_ig" value="<?= htmlspecialchars(getSet('sosmed_ig', 'https://instagram.com/')) ?>" placeholder="https://instagram.com/...">
          </div>
          <div class="form-group">
            <label class="form-label">Link Facebook</label>
            <input type="text" class="form-control" name="sosmed_fb" value="<?= htmlspecialchars(getSet('sosmed_fb', 'https://facebook.com/')) ?>" placeholder="https://facebook.com/...">
          </div>
          <div class="form-group">
            <label class="form-label">Link TikTok</label>
            <input type="text" class="form-control" name="sosmed_tiktok" value="<?= htmlspecialchars(getSet('sosmed_tiktok', 'https://tiktok.com/')) ?>" placeholder="https://tiktok.com/...">
          </div>
        </div>
      </div>

      <!-- Tab 2: Tentang Kami -->
      <div class="tab-content" id="tab-tentang">
        <div class="settings-card">
          <h3>Bagian "Tentang Kami" (Beranda)</h3>
          <div class="form-group">
            <label class="form-label">Judul Tentang Kami</label>
            <input type="text" class="form-control" name="tentang_judul" value="<?= htmlspecialchars(getSet('tentang_judul', 'Mengapa Memilih A-LINKS?')) ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label">Deskripsi Tentang Kami</label>
            <textarea class="form-control form-control--textarea" name="tentang_deskripsi" rows="3" required><?= htmlspecialchars(getSet('tentang_deskripsi', 'A-LINKS hadir sebagai solusi lengkap...')) ?></textarea>
          </div>
          <div class="form-group">
            <label class="form-label">URL Gambar Tentang Kami</label>
            <input type="text" class="form-control" name="tentang_gambar" value="<?= htmlspecialchars(getSet('tentang_gambar', '')) ?>" placeholder="https://...">
          </div>
          <div class="grid-2-col">
            <div class="form-group">
              <label class="form-label">Poin Keunggulan 1</label>
              <input type="text" class="form-control" name="tentang_poin1" value="<?= htmlspecialchars(getSet('tentang_poin1', '500+ Produk Tersedia')) ?>" required>
            </div>
            <div class="form-group">
              <label class="form-label">Poin Keunggulan 2</label>
              <input type="text" class="form-control" name="tentang_poin2" value="<?= htmlspecialchars(getSet('tentang_poin2', 'Teknisi Bersertifikat')) ?>" required>
            </div>
            <div class="form-group">
              <label class="form-label">Poin Keunggulan 3</label>
              <input type="text" class="form-control" name="tentang_poin3" value="<?= htmlspecialchars(getSet('tentang_poin3', 'Pengiriman ke Seluruh Indonesia')) ?>" required>
            </div>
            <div class="form-group">
              <label class="form-label">Poin Keunggulan 4</label>
              <input type="text" class="form-control" name="tentang_poin4" value="<?= htmlspecialchars(getSet('tentang_poin4', 'Layanan 7 Hari Seminggu')) ?>" required>
            </div>
          </div>
        </div>
      </div>

      <!-- Tab 3: Layanan Servis -->
      <div class="tab-content" id="tab-servis">
        <div class="settings-card">
          <h3>Bagian "Layanan Servis" (Beranda)</h3>
          <div class="grid-2-col">
            <div class="form-group">
              <label class="form-label">Judul Layanan</label>
              <input type="text" class="form-control" name="servis_judul" value="<?= htmlspecialchars(getSet('servis_judul', 'Layanan Servis Profesional')) ?>" required>
            </div>
            <div class="form-group">
              <label class="form-label">Deskripsi Layanan</label>
              <input type="text" class="form-control" name="servis_deskripsi" value="<?= htmlspecialchars(getSet('servis_deskripsi', 'Percayakan laptop kamu...')) ?>" required>
            </div>
          </div>

          <div class="settings-fitur-row">
            <div style="font-size:12px;font-weight:600;color:var(--color-taupe);text-transform:uppercase;letter-spacing:1px;margin-bottom:12px;">Fitur 1</div>
            <div class="grid-2-col">
              <div class="form-group">
                <label class="form-label">Judul</label>
                <input type="text" class="form-control" name="servis_fitur1_judul" value="<?= htmlspecialchars(getSet('servis_fitur1_judul', 'Perbaikan Hardware')) ?>" required>
              </div>
              <div class="form-group">
                <label class="form-label">Deskripsi</label>
                <input type="text" class="form-control" name="servis_fitur1_desc" value="<?= htmlspecialchars(getSet('servis_fitur1_desc', 'Layar retak, keyboard...')) ?>" required>
              </div>
            </div>
          </div>

          <div class="settings-fitur-row">
            <div style="font-size:12px;font-weight:600;color:var(--color-taupe);text-transform:uppercase;letter-spacing:1px;margin-bottom:12px;">Fitur 2</div>
            <div class="grid-2-col">
              <div class="form-group">
                <label class="form-label">Judul</label>
                <input type="text" class="form-control" name="servis_fitur2_judul" value="<?= htmlspecialchars(getSet('servis_fitur2_judul', 'Instal &amp; Optimasi OS')) ?>" required>
              </div>
              <div class="form-group">
                <label class="form-label">Deskripsi</label>
                <input type="text" class="form-control" name="servis_fitur2_desc" value="<?= htmlspecialchars(getSet('servis_fitur2_desc', 'Instal ulang...')) ?>" required>
              </div>
            </div>
          </div>

          <div class="settings-fitur-row">
            <div style="font-size:12px;font-weight:600;color:var(--color-taupe);text-transform:uppercase;letter-spacing:1px;margin-bottom:12px;">Fitur 3</div>
            <div class="grid-2-col">
              <div class="form-group">
                <label class="form-label">Judul</label>
                <input type="text" class="form-control" name="servis_fitur3_judul" value="<?= htmlspecialchars(getSet('servis_fitur3_judul', 'Garansi 30 Hari')) ?>" required>
              </div>
              <div class="form-group">
                <label class="form-label">Deskripsi</label>
                <input type="text" class="form-control" name="servis_fitur3_desc" value="<?= htmlspecialchars(getSet('servis_fitur3_desc', 'Setiap pengerjaan...')) ?>" required>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Tab 4: Statistik -->
      <div class="tab-content" id="tab-statistik">
        <div class="settings-card">
          <h3>Bagian "Statistik" (Beranda)</h3>
          <div class="grid-2-col">
            <div class="form-group">
              <label class="form-label">Statistik 1 (Nilai &amp; Label)</label>
              <div style="display:flex;gap:8px;">
                <input type="text" class="form-control" name="stat1_nilai" value="<?= htmlspecialchars(getSet('stat1_nilai', '500+')) ?>" style="width:80px;flex-shrink:0;" required>
                <input type="text" class="form-control" name="stat1_label" value="<?= htmlspecialchars(getSet('stat1_label', 'Produk')) ?>" style="flex:1;" required>
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Statistik 2 (Nilai &amp; Label)</label>
              <div style="display:flex;gap:8px;">
                <input type="text" class="form-control" name="stat2_nilai" value="<?= htmlspecialchars(getSet('stat2_nilai', '1000+')) ?>" style="width:80px;flex-shrink:0;" required>
                <input type="text" class="form-control" name="stat2_label" value="<?= htmlspecialchars(getSet('stat2_label', 'Pelanggan')) ?>" style="flex:1;" required>
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Statistik 3 (Nilai &amp; Label)</label>
              <div style="display:flex;gap:8px;">
                <input type="text" class="form-control" name="stat3_nilai" value="<?= htmlspecialchars(getSet('stat3_nilai', '50+')) ?>" style="width:80px;flex-shrink:0;" required>
                <input type="text" class="form-control" name="stat3_label" value="<?= htmlspecialchars(getSet('stat3_label', 'Merk')) ?>" style="flex:1;" required>
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Statistik 4 (Nilai &amp; Label)</label>
              <div style="display:flex;gap:8px;">
                <input type="text" class="form-control" name="stat4_nilai" value="<?= htmlspecialchars(getSet('stat4_nilai', '30')) ?>" style="width:80px;flex-shrink:0;" required>
                <input type="text" class="form-control" name="stat4_label" value="<?= htmlspecialchars(getSet('stat4_label', 'Hari Garansi')) ?>" style="flex:1;" required>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div style="margin-top:24px;margin-bottom:48px;">
        <button type="submit" class="btn btn--primary btn--lg btn--full">Simpan Semua Perubahan</button>
      </div>

    </form>
  </main>
</div>

<script src="../assets/js/main.js"></script>
<script>
  // Script untuk navigasi tab
  document.querySelectorAll('.settings-tab').forEach(tab => {
    tab.addEventListener('click', () => {
      // Hilangkan class active dari semua tab
      document.querySelectorAll('.settings-tab').forEach(t => t.classList.remove('active'));
      // Hilangkan class active dari semua konten tab
      document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
      
      // Tambahkan class active ke tab yang diklik
      tab.classList.add('active');
      // Tambahkan class active ke konten yang sesuai
      const target = document.querySelector(tab.dataset.target);
      if (target) {
        target.classList.add('active');
      }
    });
  });
</script>
</body>
</html>
