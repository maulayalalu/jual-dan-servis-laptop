<?php
session_start();
require_once '../config/koneksi.php';
requireAdmin();

$basePath = '../';
$pageTitle = 'Pengaturan Web â€” A-LINKS';

// â”€â”€ Handle Update â”€â”€
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

// â”€â”€ Ambil Data Saat Ini â”€â”€
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
  <link rel="stylesheet" href="../assets/css/style.css?v=<?= time() ?>"/>
  <style>
    .settings-section {
      background: var(--color-white);
      border: 1px solid var(--color-cream-border);
      border-radius: var(--radius-card);
      overflow: hidden;
      margin-bottom: var(--sp-3);
      box-shadow: var(--shadow-sm);
    }
    .settings-section__header {
      background: var(--color-cream);
      padding: 14px 24px;
      border-bottom: 1px solid var(--color-cream-border);
    }
    .settings-section__title {
      font-size: 15px;
      font-weight: 600;
      color: var(--color-carbon);
      margin: 0;
    }
    .settings-section__body {
      padding: 24px;
      display: flex;
      flex-direction: column;
      gap: 16px;
    }
    .settings-fitur-row {
      background: var(--color-cream);
      border: 1px solid var(--color-cream-border);
      border-radius: var(--radius-md);
      padding: 16px;
      margin-bottom: 8px;
    }
    .settings-fitur-row:last-child { margin-bottom: 0; }
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
    <div class="page-header">
      <div>
        <h1 class="page-header__title">Pengaturan Web</h1>
        <div class="page-header__sub">Ubah konten footer, halaman utama, dan pengaturan umum situs di sini.</div>
      </div>
    </div>

    <?php renderFlash(); ?>

    <form action="pengaturan_web.php" method="POST" style="max-width:900px;">
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

      <!-- 1. UMUM & FOOTER -->
      <div class="settings-section">
        <div class="settings-section__header">
          <h2 class="settings-section__title">1. Pengaturan Umum &amp; Footer</h2>
        </div>
        <div class="settings-section__body">
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

      <!-- 2. TENTANG KAMI -->
      <div class="settings-section">
        <div class="settings-section__header">
          <h2 class="settings-section__title">2. Bagian &quot;Tentang Kami&quot; (Beranda)</h2>
        </div>
        <div class="settings-section__body">
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

      <!-- 3. LAYANAN SERVIS -->
      <div class="settings-section">
        <div class="settings-section__header">
          <h2 class="settings-section__title">3. Bagian &quot;Layanan Servis&quot; (Beranda)</h2>
        </div>
        <div class="settings-section__body">
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

      <!-- 4. STATISTIK -->
      <div class="settings-section">
        <div class="settings-section__header">
          <h2 class="settings-section__title">4. Bagian &quot;Statistik&quot; (Beranda)</h2>
        </div>
        <div class="settings-section__body">
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
        <button type="submit" class="btn btn--primary btn--lg btn--full" id="btnSimpanPengaturan">Simpan Semua Perubahan</button>
      </div>
    </form>
  </main>
</div>

<script src="../assets/js/main.js"></script>
</body>
</html>
