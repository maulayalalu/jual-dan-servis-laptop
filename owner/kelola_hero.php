<?php
session_start();
require_once '../config/koneksi.php';
// Hanya bisa diakses oleh owner
if (!isLoggedIn() || $_SESSION['role'] !== 'owner') {
    setFlash('error', 'Akses ditolak. Halaman ini hanya untuk Owner.');
    redirect('../login.php');
    exit;
}
$basePath = '../';
$pageTitle = 'Visual Builder Hero — A-LINKS';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    
    if ($action === 'simpan') {
        $id = (int)($_POST['id_slide'] ?? 0);
        $posisi = $_POST['posisi_gambar'] ?? '50% 50%';
        $overlay = (int)($_POST['overlay'] ?? 55);
        $badge = trim($_POST['badge_text'] ?? '');
        $judul = trim($_POST['judul'] ?? '');
        $deskripsi = trim($_POST['deskripsi'] ?? '');
        $b1t = trim($_POST['btn1_text'] ?? '');
        $b1u = trim($_POST['btn1_url'] ?? '');
        $b2t = trim($_POST['btn2_text'] ?? '');
        $b2u = trim($_POST['btn2_url'] ?? '');
        $urutan = (int)($_POST['urutan'] ?? 1);
        
        $gambar = $_POST['gambar_old'] ?? '';
        
        // Handle file upload
        if (isset($_FILES['gambar_baru']) && $_FILES['gambar_baru']['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES['gambar_baru']['error'] === UPLOAD_ERR_INI_SIZE || $_FILES['gambar_baru']['size'] > 10 * 1024 * 1024) {
                setFlash('error', 'Ukuran gambar maksimal 10MB.');
                $qEmbed = isset($_POST['embed']) ? "&embed=1" : "";
                redirect('kelola_hero.php' . ($id ? "?edit=$id$qEmbed" : "?edit=new$qEmbed"));
                exit;
            } elseif ($_FILES['gambar_baru']['error'] === UPLOAD_ERR_OK) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $_FILES['gambar_baru']['tmp_name']);
                finfo_close($finfo);
                if (in_array($mime, ['image/jpeg','image/png','image/webp'])) {
                    $ext = pathinfo($_FILES['gambar_baru']['name'], PATHINFO_EXTENSION);
                    $filename = 'hero_' . time() . '.' . $ext;
                    $uploadDir = '../assets/images/hero/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                    if (move_uploaded_file($_FILES['gambar_baru']['tmp_name'], $uploadDir . $filename)) {
                        $gambar = 'assets/images/hero/' . $filename;
                    }
                    setFlash('error', 'Format gambar hero harus JPG, PNG, atau WEBP.');
                    $qEmbed = isset($_POST['embed']) ? "&embed=1" : "";
                    redirect('kelola_hero.php' . ($id ? "?edit=$id$qEmbed" : "?edit=new$qEmbed"));
                    exit;
                }
            }
        }
        
        $gambarUrl = trim($_POST['gambar_url'] ?? '');
        if ($gambarUrl !== '') {
            $gambar = $gambarUrl;
        }

        $qEmbed = isset($_POST['embed']) ? "&embed=1" : "";

        if ($id === 0) {
            $stmt = $koneksi->prepare("INSERT INTO hero_slides (gambar, posisi_gambar, overlay, badge_text, judul, deskripsi, btn1_text, btn1_url, btn2_text, btn2_url, urutan) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param('ssisssssssi', $gambar, $posisi, $overlay, $badge, $judul, $deskripsi, $b1t, $b1u, $b2t, $b2u, $urutan);
            $stmt->execute(); 
            $newId = $stmt->insert_id;
            $stmt->close();
            setFlash('success', 'Slide baru berhasil ditambahkan!');
            redirect('kelola_hero.php?edit=' . $newId . $qEmbed);
            exit;
        } else {
            $stmt = $koneksi->prepare("UPDATE hero_slides SET gambar=?, posisi_gambar=?, overlay=?, badge_text=?, judul=?, deskripsi=?, btn1_text=?, btn1_url=?, btn2_text=?, btn2_url=?, urutan=? WHERE id_slide=?");
            $stmt->bind_param('ssisssssssii', $gambar, $posisi, $overlay, $badge, $judul, $deskripsi, $b1t, $b1u, $b2t, $b2u, $urutan, $id);
            $stmt->execute(); $stmt->close();
            setFlash('success', 'Pengaturan slide berhasil diperbarui!');
            redirect('kelola_hero.php?edit=' . $id . $qEmbed);
            exit;
        }
    }
    
    if ($action === 'hapus') {
        $id = (int)$_POST['id_slide'];
        $stmt = $koneksi->prepare("DELETE FROM hero_slides WHERE id_slide=?");
        $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close();
        setFlash('success', 'Slide berhasil dihapus.');
        $qEmbed = isset($_POST['embed']) ? "?embed=1" : "";
        redirect('kelola_hero.php' . $qEmbed);
        exit;
    }
}

// Get all slides for the bottom history list
$slides = [];
$res = $koneksi->query("SELECT * FROM hero_slides ORDER BY urutan ASC, id_slide DESC");
while($row = $res->fetch_assoc()) {
    $slides[] = $row;
}

// Get currently active slide to edit
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : (count($slides) > 0 ? $slides[0]['id_slide'] : 0);
$activeSlide = null;
if ($editId > 0) {
    foreach($slides as $s) {
        if ($s['id_slide'] == $editId) {
            $activeSlide = $s;
            break;
        }
    }
}

// Default values for new or empty
$posisi_val = $activeSlide['posisi_gambar'] ?? '50% 50%';
$overlay_val = $activeSlide['overlay'] ?? 55;
$imgSrc = $activeSlide ? (strpos($activeSlide['gambar'], 'http') === 0 ? $activeSlide['gambar'] : '../'.$activeSlide['gambar']) : '';

// Parse X and Y from posisi_gambar (e.g., "78% 50%" or "center")
$posX = 50;
$posY = 50;
if (preg_match('/(\d+)%\s+(\d+)%/', $posisi_val, $matches)) {
    $posX = (int)$matches[1];
    $posY = (int)$matches[2];
} elseif ($posisi_val === 'center') {
    $posX = 50; $posY = 50;
} elseif ($posisi_val === 'top') {
    $posX = 50; $posY = 0;
} elseif ($posisi_val === 'bottom') {
    $posX = 50; $posY = 100;
} elseif ($posisi_val === 'left') {
    $posX = 0; $posY = 50;
} elseif ($posisi_val === 'right') {
    $posX = 100; $posY = 50;
}

?>
<!DOCTYPE html><html lang="id"><head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title><?= $pageTitle ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="../assets/css/style.css?v=<?= time() ?>"/>
<style>
  .builder-container {
    display: grid;
    grid-template-columns: 1.5fr 1fr;
    gap: 24px;
    align-items: flex-start;
  }
  .preview-box {
    width: 100%;
    aspect-ratio: 16/9;
    border-radius: 12px;
    overflow: hidden;
    position: relative;
    background: #1a1a2e;
    background-size: cover;
    background-position: center;
    border: 1px solid var(--color-cloud);
  }
  .preview-overlay {
    position: absolute;
    inset: 0;
    pointer-events: none;
  }
  .preview-content {
    position: absolute;
    bottom: 40px;
    left: 50%;
    transform: translateX(-50%);
    text-align: center;
    width: 100%;
    padding: 0 24px;
    pointer-events: none;
  }
  .slider-group {
    margin-bottom: 16px;
  }
  .slider-group label {
    display: flex;
    justify-content: space-between;
    font-size: 13px;
    color: var(--color-pewter);
    margin-bottom: 8px;
    font-weight: 500;
  }
  input[type=range] {
    width: 100%;
    accent-color: #d4a373; /* Goldenish color like mockup */
  }
  .card-header-badge {
    background: #dcfce7;
    color: #166534;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
  }
  .slide-history {
    display: flex;
    gap: 16px;
    overflow-x: auto;
    padding-bottom: 8px;
  }
  .slide-thumb {
    width: 160px;
    aspect-ratio: 16/9;
    border-radius: 8px;
    border: 2px solid transparent;
    cursor: pointer;
    overflow: hidden;
    position: relative;
    transition: all 0.2s;
    background: var(--color-light-ash);
  }
  .slide-thumb:hover { border-color: var(--color-blue); }
  .slide-thumb.active { border-color: #d4a373; }
  .slide-thumb img {
    width: 100%; height: 100%; object-fit: cover;
  }
  .slide-thumb-badge {
    position: absolute;
    top: 6px; left: 6px;
    background: #d4a373;
    color: white;
    font-size: 10px;
    font-weight: bold;
    padding: 2px 6px;
    border-radius: 4px;
  }
  @media(max-width: 900px) {
    .builder-container { grid-template-columns: 1fr; }
  }
  /* File upload dropzone styling */
  .dropzone {
    border: 2px dashed var(--color-cloud);
    border-radius: 8px;
    padding: 32px 16px;
    text-align: center;
    background: var(--color-light-ash);
    cursor: pointer;
    transition: all 0.2s;
  }
  .dropzone:hover { border-color: var(--color-blue); background: #f0f7ff; }
</style>
<?php $isEmbed = isset($_GET['embed']) && $_GET['embed'] == '1'; ?>
<style>
  <?php if ($isEmbed): ?>
  .app-layout { grid-template-columns: 1fr; }
  .main-content { padding: 16px; background: white; }
  .page-header { display: none; }
  <?php endif; ?>
</style>
</head><body>
<div class="app-layout">
<?php if (!$isEmbed) include '../includes/sidebar_admin.php'; ?>
<main class="main-content">
  <?php if (!$isEmbed): ?>
  <div class="page-header" style="margin-bottom:24px;">
    <div><h1 class="page-header__title">Visual Builder Hero</h1><div class="page-header__sub">Desain dan atur posisi gambar banner secara real-time</div></div>
    <a href="kelola_hero.php?edit=new" class="btn btn--secondary btn--sm">+ Slide Kosong Baru</a>
  </div>
  <?php else: ?>
  <div style="margin-bottom:16px; display:flex; justify-content:space-between; align-items:center;">
    <h2 style="font-size:18px;font-weight:600;">Editor Banner</h2>
    <a href="kelola_hero.php?edit=new&embed=1" class="btn btn--secondary btn--sm">+ Slide Kosong Baru</a>
  </div>
  <?php endif; ?>
  <?php renderFlash(); ?>

    <form method="POST" enctype="multipart/form-data" id="heroForm">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
    <input type="hidden" name="action" value="simpan">
    <input type="hidden" name="id_slide" value="<?= $editId ?>">
    <?php if ($isEmbed): ?><input type="hidden" name="embed" value="1"><?php endif; ?>
    <input type="hidden" name="gambar_old" value="<?= htmlspecialchars($activeSlide['gambar'] ?? '') ?>">
    <!-- Hidden inputs to be updated by JS -->
    <input type="hidden" name="posisi_gambar" id="inpPosisi" value="<?= htmlspecialchars($posisi_val) ?>">
    <input type="hidden" name="overlay" id="inpOverlay" value="<?= $overlay_val ?>">

    <div class="builder-container">
      
      <!-- LEFT COLUMN: Preview & Text Details -->
      <div>
        <!-- PREVIEW BOX -->
        <div class="card" style="padding:0; overflow:hidden; border:none; box-shadow:0 4px 12px rgba(0,0,0,0.08);">
          <div style="padding:16px 24px; border-bottom:1px solid var(--color-cloud); display:flex; justify-content:space-between; align-items:center; background:white;">
            <h2 style="font-size:16px; font-weight:600; margin:0;">Preview Tampilan Hero <?= $activeSlide ? "(ID: {$activeSlide['id_slide']})" : "(Baru)" ?></h2>
            <span class="card-header-badge">Live Custom</span>
          </div>
          <div style="padding:16px; background:white;">
            <div class="preview-box" id="previewBox" style="background-image: url('<?= htmlspecialchars($imgSrc) ?>'); background-position: <?= htmlspecialchars($posisi_val) ?>;">
              
              <!-- Gradient Overlays based on index.php -->
              <div class="preview-overlay" id="previewOverlayGrad" style="background:linear-gradient(to top, rgba(0,0,0,<?= $overlay_val/100 ?>) 0%, transparent 80%);"></div>
              <div class="preview-overlay" id="previewOverlaySolid" style="background:rgba(0,0,0,<?= ($overlay_val/100)*0.4 ?>);"></div>
              
              <div class="preview-content">
                <p id="prevBadge" style="font-size:12px; color:var(--color-blue); margin-bottom:6px; font-weight:500; text-shadow: 0 2px 4px rgba(0,0,0,0.5);"><?= htmlspecialchars($activeSlide['badge_text'] ?? 'Badge Spesial') ?></p>
                <h1 id="prevJudul" style="font-size:28px; font-weight:600; color:#fff; margin-bottom:6px; line-height:1.2; text-shadow: 0 2px 4px rgba(0,0,0,0.5);"><?= htmlspecialchars($activeSlide['judul'] ?? 'Judul Hero Utama') ?></h1>
                <p id="prevDesc" style="font-size:12px; color:rgba(255,255,255,0.85); margin-bottom:16px; text-shadow: 0 2px 4px rgba(0,0,0,0.5);"><?= htmlspecialchars($activeSlide['deskripsi'] ?? 'Deskripsi singkat yang menarik untuk pelanggan.') ?></p>
                <div style="display:flex; gap:12px; justify-content:center;">
                  <div class="btn btn--primary btn--sm" id="prevBtn1" style="<?= empty($activeSlide['btn1_text']) && !$activeSlide ? '' : (empty($activeSlide['btn1_text'])?'display:none;':'') ?>"><?= htmlspecialchars($activeSlide['btn1_text'] ?? 'Tombol 1') ?></div>
                  <div class="btn btn--secondary btn--sm" id="prevBtn2" style="<?= empty($activeSlide['btn2_text']) ? 'display:none;' : '' ?>"><?= htmlspecialchars($activeSlide['btn2_text'] ?? 'Tombol 2') ?></div>
                </div>
              </div>

              <!-- Dev Overlay info -->
              <div id="devPosInfo" style="position:absolute; bottom:12px; right:12px; background:rgba(0,0,0,0.5); color:white; font-size:10px; padding:4px 8px; border-radius:4px;">
                pos: <?= htmlspecialchars($posisi_val) ?>
              </div>
            </div>
          </div>
        </div>

        <!-- TEXT INPUTS -->
        <div class="card mt-4" style="padding:24px;">
          <h3 style="font-size:16px; font-weight:600; margin-bottom:16px;">Konten Teks & Tautan</h3>
          <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
            <div>
              <label class="form-label">Teks Badge (Biru Kecil)</label>
              <input class="form-control bind-text" data-target="prevBadge" type="text" name="badge_text" value="<?= htmlspecialchars($activeSlide['badge_text'] ?? '') ?>">
            </div>
            <div>
              <label class="form-label">Urutan Tampil (1, 2, 3..)</label>
              <input class="form-control" type="number" name="urutan" value="<?= htmlspecialchars($activeSlide['urutan'] ?? count($slides)+1) ?>" required>
            </div>
            <div style="grid-column:1/-1;">
              <label class="form-label">Judul Besar</label>
              <input class="form-control bind-text" data-target="prevJudul" type="text" name="judul" value="<?= htmlspecialchars($activeSlide['judul'] ?? '') ?>" required>
            </div>
            <div style="grid-column:1/-1;">
              <label class="form-label">Deskripsi</label>
              <textarea class="form-control form-control--textarea bind-text" data-target="prevDesc" name="deskripsi" rows="2"><?= htmlspecialchars($activeSlide['deskripsi'] ?? '') ?></textarea>
            </div>
            <div>
              <label class="form-label">Teks Tombol 1</label>
              <input class="form-control bind-text" data-target="prevBtn1" data-hide-empty="true" type="text" name="btn1_text" value="<?= htmlspecialchars($activeSlide['btn1_text'] ?? '') ?>">
            </div>
            <div>
              <label class="form-label">URL Tombol 1</label>
              <input class="form-control" type="text" name="btn1_url" value="<?= htmlspecialchars($activeSlide['btn1_url'] ?? '') ?>">
            </div>
            <div>
              <label class="form-label">Teks Tombol 2</label>
              <input class="form-control bind-text" data-target="prevBtn2" data-hide-empty="true" type="text" name="btn2_text" value="<?= htmlspecialchars($activeSlide['btn2_text'] ?? '') ?>">
            </div>
            <div>
              <label class="form-label">URL Tombol 2</label>
              <input class="form-control" type="text" name="btn2_url" value="<?= htmlspecialchars($activeSlide['btn2_url'] ?? '') ?>">
            </div>
          </div>
        </div>
      </div>

      <!-- RIGHT COLUMN: Image & Visual Controls -->
      <div>
        <div class="card" style="padding:0; overflow:hidden;">
          <div style="padding:16px 24px; border-bottom:1px solid var(--color-cloud);">
            <h2 style="font-size:16px; font-weight:600; margin:0;">Upload Gambar Baru</h2>
          </div>
          <div style="padding:24px;">
            <div class="dropzone" onclick="document.getElementById('fileUpload').click()">
              <div style="margin-bottom:8px; color:var(--color-pewter);">
                <svg width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
              </div>
              <div style="font-weight:600; font-size:14px; margin-bottom:4px; color:var(--color-carbon);">Seret gambar ke sini atau klik</div>
              <div style="font-size:12px; color:var(--color-silver-fog);">JPG, PNG, WebP — maks 10MB</div>
              <input type="file" name="gambar_baru" id="fileUpload" accept="image/*" style="display:none;" onchange="previewImage(this)">
            </div>
            <div style="margin-top:12px; text-align:center;">
              <span style="font-size:12px; color:var(--color-silver-fog);">Atau masukkan URL:</span>
              <input type="text" name="gambar_url" id="urlUpload" class="form-control" style="margin-top:4px; font-size:12px; padding:6px;" placeholder="https://..." oninput="previewUrl(this.value)">
            </div>
          </div>
        </div>

        <div class="card mt-4" style="padding:0; overflow:hidden;">
          <div style="padding:16px 24px; border-bottom:1px solid var(--color-cloud);">
            <h2 style="font-size:16px; font-weight:600; margin:0;">Pengaturan Visual</h2>
          </div>
          <div style="padding:24px; background:var(--color-light-ash);">
            
            <div class="slider-group">
              <label><span>Posisi Kiri â†” Kanan (X)</span> <span id="valX"><?= $posX ?>%</span></label>
              <input type="range" id="rangeX" min="0" max="100" value="<?= $posX ?>" oninput="updateVisuals()">
            </div>
            
            <div class="slider-group mt-3">
              <label><span>Posisi Atas â†• Bawah (Y)</span> <span id="valY"><?= $posY ?>%</span></label>
              <input type="range" id="rangeY" min="0" max="100" value="<?= $posY ?>" oninput="updateVisuals()">
            </div>

            <div style="height:1px; background:var(--color-cloud); margin:20px 0;"></div>

            <div class="slider-group">
              <label><span>Kegelapan Overlay</span> <span id="valOverlay"><?= $overlay_val ?>%</span></label>
              <input type="range" id="rangeOverlay" min="0" max="100" value="<?= $overlay_val ?>" oninput="updateVisuals()">
              <div style="display:flex; justify-content:space-between; font-size:10px; color:var(--color-silver-fog); margin-top:4px;">
                <span>Terang (0%)</span><span>Gelap (100%)</span>
              </div>
            </div>

            <button type="submit" class="btn btn--primary btn--full mt-4" style="background:#d4a373; border-color:#c89666; font-size:15px; padding:12px;">Simpan Pengaturan</button>
            <button type="button" class="btn btn--outline btn--full mt-2" onclick="resetVisuals()" style="font-size:13px;">Reset ke Default</button>
          </div>
        </div>
      </div>
    </div>
  </form>

  <!-- BOTTOM: History / Daftar Slide -->
  <div class="card mt-4" style="padding:24px;">
    <h3 style="font-size:16px; font-weight:600; margin-bottom:16px;">Daftar Slide (Riwayat Gambar)</h3>
    <?php if (count($slides) > 0): ?>
      <div class="slide-history">
        <?php foreach($slides as $s): 
          $isActive = ($s['id_slide'] == $editId);
          $sImg = strpos($s['gambar'], 'http') === 0 ? $s['gambar'] : '../'.$s['gambar'];
        ?>
          <a href="kelola_hero.php?edit=<?= $s['id_slide'] ?><?= $isEmbed ? '&embed=1' : '' ?>">
            <div class="slide-thumb <?= $isActive ? 'active' : '' ?>">
              <img src="<?= htmlspecialchars($sImg) ?>">
              <?php if($isActive): ?><div class="slide-thumb-badge">AKTIF DIEDIT</div><?php endif; ?>
              <div style="position:absolute; bottom:0; left:0; right:0; background:rgba(0,0,0,0.6); color:white; font-size:11px; padding:4px 8px; text-align:center;">
                Urutan: <?= $s['urutan'] ?>
              </div>
            </div>
          </a>
            <?php if($isActive): ?>
            <form method="POST" onsubmit="return confirm('Yakin ingin menghapus slide ini?');">
              <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
              <input type="hidden" name="action" value="hapus">
              <input type="hidden" name="id_slide" value="<?= $s['id_slide'] ?>">
              <button type="submit" class="btn btn--danger btn--sm btn--full" style="padding:4px; font-size:11px;">Hapus</button>
            </form>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div style="font-size:13px; color:var(--color-silver-fog);">Belum ada slide tersimpan.</div>
    <?php endif; ?>
  </div>

</main>
</div>

<script src="../assets/js/main.js"></script>
<script>
  // Setup elements
  const box = document.getElementById('previewBox');
  const overlayGrad = document.getElementById('previewOverlayGrad');
  const overlaySolid = document.getElementById('previewOverlaySolid');
  const devPosInfo = document.getElementById('devPosInfo');
  
  const rangeX = document.getElementById('rangeX');
  const rangeY = document.getElementById('rangeY');
  const valX = document.getElementById('valX');
  const valY = document.getElementById('valY');
  const rangeOverlay = document.getElementById('rangeOverlay');
  const valOverlay = document.getElementById('valOverlay');

  const inpPosisi = document.getElementById('inpPosisi');
  const inpOverlay = document.getElementById('inpOverlay');

  function updateVisuals() {
    const x = rangeX.value;
    const y = rangeY.value;
    const ov = rangeOverlay.value;

    // Update labels
    valX.textContent = x + '%';
    valY.textContent = y + '%';
    valOverlay.textContent = ov + '%';

    // Update hidden inputs
    const posStr = `${x}% ${y}%`;
    inpPosisi.value = posStr;
    inpOverlay.value = ov;

    // Update Preview
    box.style.backgroundPosition = posStr;
    devPosInfo.textContent = 'pos: ' + posStr;

    const op = ov / 100;
    overlayGrad.style.background = `linear-gradient(to top, rgba(0,0,0,${op}) 0%, transparent 80%)`;
    overlaySolid.style.background = `rgba(0,0,0,${op * 0.4})`;
  }

  function resetVisuals() {
    rangeX.value = 50;
    rangeY.value = 50;
    rangeOverlay.value = 55;
    updateVisuals();
  }

  // Image preview locally
  function previewImage(input) {
    if (input.files && input.files[0]) {
      const reader = new FileReader();
      reader.onload = function(e) {
        box.style.backgroundImage = `url('${e.target.result}')`;
      }
      reader.readAsDataURL(input.files[0]);
    }
  }

  function previewUrl(url) {
    if (url.trim() !== '') {
      box.style.backgroundImage = `url('${url}')`;
    }
  }

  // Bind Text Inputs to Live Preview
  document.querySelectorAll('.bind-text').forEach(input => {
    input.addEventListener('input', function() {
      const targetEl = document.getElementById(this.dataset.target);
      if (targetEl) {
        targetEl.textContent = this.value;
        if (this.dataset.hideEmpty === 'true') {
          targetEl.style.display = this.value.trim() === '' ? 'none' : 'inline-block';
        }
      }
    });
  });

</script>
</body></html>
