<?php
session_start();
require_once '../config/koneksi.php';
requireAdmin();

$basePath = '../';
$pageTitle = 'Pengaturan Web — A-LINKS';

// ── Handle Update ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    
    $settingsToUpdate = [
        'nama_toko', 'tagline', 'alamat', 'no_wa', 'pesan_wa',
        'servis_judul', 'servis_deskripsi', 'servis_fitur1_judul', 'servis_fitur1_desc',
        'servis_fitur2_judul', 'servis_fitur2_desc', 'servis_fitur3_judul', 'servis_fitur3_desc',
        'tentang_judul', 'tentang_deskripsi', 'tentang_poin1', 'tentang_poin2', 'tentang_poin3', 'tentang_poin4', 'tentang_gambar',
        'stat1_nilai', 'stat1_label', 'stat2_nilai', 'stat2_label',
        'stat3_nilai', 'stat3_label', 'stat4_nilai', 'stat4_label'
    ];
    
    $success = true;
    foreach ($settingsToUpdate as $kunci) {
        if (isset($_POST[$kunci])) {
            $nilai = $_POST[$kunci];
            $stmt = $koneksi->prepare("UPDATE pengaturan SET nilai = ? WHERE kunci = ?");
            if ($stmt) {
                $stmt->bind_param('ss', $nilai, $kunci);
                if (!$stmt->execute()) {
                    $success = false;
                }
                $stmt->close();
            } else {
                $success = false;
            }
        }
    }
    
    if ($success) {
        setFlash('success', 'Pengaturan web berhasil diperbarui.');
    } else {
        setFlash('error', 'Gagal memperbarui pengaturan web.');
    }
    redirect('pengaturan_web.php');
}

// ── Ambil Data Saat Ini ──
$res = $koneksi->query("SELECT kunci, nilai FROM pengaturan");
$pengaturan = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $pengaturan[$row['kunci']] = $row['nilai'];
    }
}

// Fungsi bantu untuk nilai default
function getSet($key, $default) {
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
  <link rel="stylesheet" href="../assets/css/style.css"/>
  <style>
    .settings-section {
      background: var(--color-white);
      border: 1px solid var(--color-cloud);
      border-radius: 8px;
      padding: 24px;
      margin-bottom: 24px;
    }
    .settings-section__title {
      font-size: 18px;
      font-weight: 600;
      color: var(--color-carbon);
      margin-bottom: 16px;
      padding-bottom: 8px;
      border-bottom: 1px solid var(--color-cloud);
    }
    .grid-2-col {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 16px;
    }
  </style>
</head>
<body>

<div class="app-layout">
  <?php include '../includes/sidebar_admin.php'; ?>

  <main class="main-content">
    <div class="page-header">
      <div>
        <h1 class="page-header__title">Pengaturan Web</h1>
        <div class="page-header__sub">Ubah konten footer, halaman utama, dan pengaturan umum situs di sini.</div>
      </div>
    </div>

    <?php renderFlash(); ?>

    <form action="pengaturan_web.php" method="POST" style="max-width: 900px;">
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
      
      <!-- UMUM & FOOTER -->
      <div class="settings-section">
        <h2 class="settings-section__title">1. Pengaturan Umum & Footer</h2>
        
        <div class="form-group">
          <label class="form-label">Nama Toko</label>
          <input type="text" class="form-input" name="nama_toko" value="<?= htmlspecialchars(getSet('nama_toko', 'A-LINKS')) ?>" required>
        </div>

        <div class="form-group">
          <label class="form-label">Tagline / Deskripsi Singkat</label>
          <textarea class="form-input" name="tagline" rows="2" required><?= htmlspecialchars(getSet('tagline', 'Toko laptop terpercaya...')) ?></textarea>
        </div>

        <div class="form-group">
          <label class="form-label">Alamat Lengkap</label>
          <textarea class="form-input" name="alamat" rows="2" required><?= htmlspecialchars(getSet('alamat', '73RH+PG6...')) ?></textarea>
        </div>

        <div class="grid-2-col">
          <div class="form-group">
            <label class="form-label">Nomor WhatsApp</label>
            <input type="text" class="form-input" name="no_wa" value="<?= htmlspecialchars(getSet('no_wa', '6281216851726')) ?>" required>
            <small style="color:var(--color-silver-fog); font-size:12px;">Format: 628...</small>
          </div>
          <div class="form-group">
            <label class="form-label">Pesan Template WA</label>
            <input type="text" class="form-input" name="pesan_wa" value="<?= htmlspecialchars(getSet('pesan_wa', 'Halo A-LINKS...')) ?>" required>
          </div>
        </div>
      </div>

      <!-- TENTANG KAMI -->
      <div class="settings-section">
        <h2 class="settings-section__title">2. Bagian "Tentang Kami" (Beranda)</h2>
        <div class="form-group">
          <label class="form-label">Judul Tentang Kami</label>
          <input type="text" class="form-input" name="tentang_judul" value="<?= htmlspecialchars(getSet('tentang_judul', 'Mengapa Memilih A-LINKS?')) ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Deskripsi Tentang Kami</label>
          <textarea class="form-input" name="tentang_deskripsi" rows="3" required><?= htmlspecialchars(getSet('tentang_deskripsi', 'A-LINKS hadir sebagai solusi lengkap...')) ?></textarea>
        </div>
        <div class="form-group">
          <label class="form-label">URL Gambar Tentang Kami</label>
          <input type="text" class="form-input" name="tentang_gambar" value="<?= htmlspecialchars(getSet('tentang_gambar', '')) ?>" required>
        </div>
        <div class="grid-2-col">
          <div class="form-group">
            <label class="form-label">Poin Keunggulan 1</label>
            <input type="text" class="form-input" name="tentang_poin1" value="<?= htmlspecialchars(getSet('tentang_poin1', '500+ Produk Tersedia')) ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label">Poin Keunggulan 2</label>
            <input type="text" class="form-input" name="tentang_poin2" value="<?= htmlspecialchars(getSet('tentang_poin2', 'Teknisi Bersertifikat')) ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label">Poin Keunggulan 3</label>
            <input type="text" class="form-input" name="tentang_poin3" value="<?= htmlspecialchars(getSet('tentang_poin3', 'Pengiriman ke Seluruh Indonesia')) ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label">Poin Keunggulan 4</label>
            <input type="text" class="form-input" name="tentang_poin4" value="<?= htmlspecialchars(getSet('tentang_poin4', 'Layanan 7 Hari Seminggu')) ?>" required>
          </div>
        </div>
      </div>

      <!-- LAYANAN SERVIS -->
      <div class="settings-section">
        <h2 class="settings-section__title">3. Bagian "Layanan Servis" (Beranda)</h2>
        <div class="grid-2-col">
            <div class="form-group">
            <label class="form-label">Judul Layanan</label>
            <input type="text" class="form-input" name="servis_judul" value="<?= htmlspecialchars(getSet('servis_judul', 'Layanan Servis Profesional')) ?>" required>
            </div>
            <div class="form-group">
            <label class="form-label">Deskripsi Layanan</label>
            <input type="text" class="form-input" name="servis_deskripsi" value="<?= htmlspecialchars(getSet('servis_deskripsi', 'Percayakan laptop kamu...')) ?>" required>
            </div>
        </div>
        
        <div class="grid-2-col" style="background:#fafafa; padding:12px; border-radius:6px; margin-bottom:12px;">
          <div class="form-group">
            <label class="form-label">Fitur 1: Judul</label>
            <input type="text" class="form-input" name="servis_fitur1_judul" value="<?= htmlspecialchars(getSet('servis_fitur1_judul', 'Perbaikan Hardware')) ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label">Fitur 1: Deskripsi</label>
            <input type="text" class="form-input" name="servis_fitur1_desc" value="<?= htmlspecialchars(getSet('servis_fitur1_desc', 'Layar retak, keyboard...')) ?>" required>
          </div>
        </div>

        <div class="grid-2-col" style="background:#fafafa; padding:12px; border-radius:6px; margin-bottom:12px;">
          <div class="form-group">
            <label class="form-label">Fitur 2: Judul</label>
            <input type="text" class="form-input" name="servis_fitur2_judul" value="<?= htmlspecialchars(getSet('servis_fitur2_judul', 'Instal & Optimasi OS')) ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label">Fitur 2: Deskripsi</label>
            <input type="text" class="form-input" name="servis_fitur2_desc" value="<?= htmlspecialchars(getSet('servis_fitur2_desc', 'Instal ulang...')) ?>" required>
          </div>
        </div>

        <div class="grid-2-col" style="background:#fafafa; padding:12px; border-radius:6px;">
          <div class="form-group">
            <label class="form-label">Fitur 3: Judul</label>
            <input type="text" class="form-input" name="servis_fitur3_judul" value="<?= htmlspecialchars(getSet('servis_fitur3_judul', 'Garansi 30 Hari')) ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label">Fitur 3: Deskripsi</label>
            <input type="text" class="form-input" name="servis_fitur3_desc" value="<?= htmlspecialchars(getSet('servis_fitur3_desc', 'Setiap pengerjaan...')) ?>" required>
          </div>
        </div>
      </div>

      <!-- STATISTIK -->
      <div class="settings-section">
        <h2 class="settings-section__title">4. Bagian "Statistik" (Beranda)</h2>
        <div class="grid-2-col">
          <div class="form-group">
            <label class="form-label">Statistik 1 (Nilai & Label)</label>
            <div style="display:flex;gap:8px;">
                <input type="text" class="form-input" name="stat1_nilai" value="<?= htmlspecialchars(getSet('stat1_nilai', '500+')) ?>" style="width:80px;" required>
                <input type="text" class="form-input" name="stat1_label" value="<?= htmlspecialchars(getSet('stat1_label', 'Produk')) ?>" style="flex:1;" required>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Statistik 2 (Nilai & Label)</label>
            <div style="display:flex;gap:8px;">
                <input type="text" class="form-input" name="stat2_nilai" value="<?= htmlspecialchars(getSet('stat2_nilai', '1000+')) ?>" style="width:80px;" required>
                <input type="text" class="form-input" name="stat2_label" value="<?= htmlspecialchars(getSet('stat2_label', 'Pelanggan')) ?>" style="flex:1;" required>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Statistik 3 (Nilai & Label)</label>
            <div style="display:flex;gap:8px;">
                <input type="text" class="form-input" name="stat3_nilai" value="<?= htmlspecialchars(getSet('stat3_nilai', '50+')) ?>" style="width:80px;" required>
                <input type="text" class="form-input" name="stat3_label" value="<?= htmlspecialchars(getSet('stat3_label', 'Merk')) ?>" style="flex:1;" required>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Statistik 4 (Nilai & Label)</label>
            <div style="display:flex;gap:8px;">
                <input type="text" class="form-input" name="stat4_nilai" value="<?= htmlspecialchars(getSet('stat4_nilai', '30')) ?>" style="width:80px;" required>
                <input type="text" class="form-input" name="stat4_label" value="<?= htmlspecialchars(getSet('stat4_label', 'Hari Garansi')) ?>" style="flex:1;" required>
            </div>
          </div>
        </div>
      </div>

      <div style="margin-top:24px; margin-bottom:48px;">
        <button type="submit" class="btn btn--primary btn--lg" style="width:100%;">Simpan Semua Perubahan</button>
      </div>
    </form>
  </main>
</div>

<script src="../assets/js/main.js"></script>
</body>
</html>
