<?php
session_start();
require_once '../config/koneksi.php';
requireAdmin();
$basePath  = '../';
$pageTitle = 'Kelola Produk — A-LINKS';

// ── Handle POST actions ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'tambah' || $action === 'edit') {
        $nama      = trim($_POST['nama_laptop'] ?? '');
        $deskripsi = trim($_POST['deskripsi']   ?? '');
        $harga     = (int) ($_POST['harga']     ?? 0);
        $stok      = (int) ($_POST['stok']      ?? 0);
        $gambar    = trim($_POST['gambar']       ?? '');
        $garansi   = trim($_POST['garansi']      ?? 'Garansi Resmi 1 Tahun');
        $id        = (int) ($_POST['id_produk']  ?? 0);

        // Upload gambar jika ada
        if (!empty($_FILES['file_gambar']['name']) && $_FILES['file_gambar']['error'] === UPLOAD_ERR_OK) {
            $tmpPath = $_FILES['file_gambar']['tmp_name'];
            $fileSize = $_FILES['file_gambar']['size'];
            
            // Check size (2MB)
            if ($fileSize > 2 * 1024 * 1024) {
                setFlash('error', 'Ukuran gambar maksimal 2MB.');
                redirect('kelola_produk.php');
            }
            
            // Check MIME type
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $tmpPath);
            finfo_close($finfo);
            
            $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
            if (!in_array($mime, $allowedMimes)) {
                setFlash('error', 'Format gambar harus JPG, PNG, atau WEBP.');
                redirect('kelola_produk.php');
            }

            $ext       = pathinfo($_FILES['file_gambar']['name'], PATHINFO_EXTENSION);
            $filename  = time() . '_' . rand(100, 999) . '.' . $ext;
            $uploadDir = '../assets/images/';
            if (move_uploaded_file($tmpPath, $uploadDir . $filename)) {
                $gambar = 'assets/images/' . $filename;
            }
        }

        if ($action === 'tambah') {
            $stmt = $koneksi->prepare("INSERT INTO produk (nama_laptop, deskripsi, harga, stok, gambar, garansi) VALUES (?,?,?,?,?,?)");
            $stmt->bind_param('ssidss', $nama, $deskripsi, $harga, $stok, $gambar, $garansi);
            $stmt->execute(); $stmt->close();
            setFlash('success', 'Produk berhasil ditambahkan!');
        } else {
            $stmt = $koneksi->prepare("UPDATE produk SET nama_laptop=?, deskripsi=?, harga=?, stok=?, gambar=?, garansi=? WHERE id_produk=?");
            $stmt->bind_param('ssidssi', $nama, $deskripsi, $harga, $stok, $gambar, $garansi, $id);
            $stmt->execute(); $stmt->close();
            setFlash('success', 'Produk berhasil diperbarui!');
        }
        redirect('kelola_produk.php');
    }

    if ($action === 'hapus') {
        $id   = (int) ($_POST['id_produk'] ?? 0);
        $stmt = $koneksi->prepare("UPDATE produk SET is_deleted=1 WHERE id_produk=?");
        $stmt->bind_param('i', $id);
        $stmt->execute(); $stmt->close();
        setFlash('success', 'Produk berhasil dihapus (soft delete).');
        redirect('kelola_produk.php');
    }
}

// ── Edit mode: ambil data produk ──
$editProduk = null;
if (isset($_GET['edit'])) {
    $id   = (int) $_GET['edit'];
    $stmt = $koneksi->prepare("SELECT * FROM produk WHERE id_produk=? LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $editProduk = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// ── Search & list produk ──
$page = (int)($_GET['page'] ?? 1);
if ($page < 1) $page = 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$search = trim($_GET['q'] ?? '');
if ($search) {
    $like = "%$search%";
    $stmtC = $koneksi->prepare("SELECT COUNT(*) AS total FROM produk WHERE is_deleted=0 AND (nama_laptop LIKE ? OR deskripsi LIKE ?)");
    $stmtC->bind_param('ss', $like, $like);
    $stmtC->execute();
    $totalData = $stmtC->get_result()->fetch_assoc()['total'];
    $stmtC->close();

    $stmt = $koneksi->prepare("SELECT * FROM produk WHERE is_deleted=0 AND (nama_laptop LIKE ? OR deskripsi LIKE ?) ORDER BY created_at DESC LIMIT ? OFFSET ?");
    $stmt->bind_param('ssii', $like, $like, $limit, $offset);
    $stmt->execute();
    $produkList = $stmt->get_result();
    $stmt->close();
} else {
    $totalData = $koneksi->query("SELECT COUNT(*) AS total FROM produk WHERE is_deleted=0")->fetch_assoc()['total'];
    $stmt = $koneksi->prepare("SELECT * FROM produk WHERE is_deleted=0 ORDER BY created_at DESC LIMIT ? OFFSET ?");
    $stmt->bind_param('ii', $limit, $offset);
    $stmt->execute();
    $produkList = $stmt->get_result();
    $stmt->close();
}
$totalPages = ceil($totalData / $limit);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= $pageTitle ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="../assets/css/style.css"/>
</head>
<body>
<div class="app-layout">
  <?php include '../includes/sidebar_admin.php'; ?>
  <main class="main-content">

    <div class="page-header">
      <div>
        <h1 class="page-header__title">Kelola Produk</h1>
        <div class="page-header__sub">CRUD data laptop yang dijual di katalog</div>
      </div>
      <button class="btn btn--primary btn--sm" id="btnOpenModal" data-modal-open="modalProduk">+ Tambah Produk</button>
    </div>

    <?php renderFlash(); ?>

    <!-- Search -->
    <form method="GET" style="margin-bottom:20px;display:flex;gap:8px;align-items:center;">
      <div class="search-input" style="width:280px;">
        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="var(--color-silver-fog)" stroke-width="1.8">
          <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
        </svg>
        <input type="text" name="q" id="tableSearch" placeholder="Cari produk..." value="<?= htmlspecialchars($search) ?>"/>
      </div>
      <button type="submit" class="btn btn--secondary btn--sm">Cari</button>
      <?php if ($search): ?><a href="kelola_produk.php" class="btn btn--secondary btn--sm">Reset</a><?php endif; ?>
    </form>

    <!-- Table -->
    <div class="table-wrap">
      <div class="table-toolbar">
        <div class="table-toolbar__title">Daftar Produk <?= $search ? '(Hasil: "'.htmlspecialchars($search).'")' : '' ?></div>
        <span style="font-size:13px;color:var(--color-pewter);"><?= $totalData ?> produk</span>
      </div>
    <div style="overflow-x: auto; width: 100%;">
      <table style="min-width: 950px;">
        <thead>
          <tr>
            <th>#</th>
            <th>Gambar</th>
            <th>Nama Laptop</th>
            <th>Harga</th>
            <th>Stok</th>
            <th>Ditambahkan</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($produkList->num_rows > 0):
            $no = $offset + 1;
            while ($p = $produkList->fetch_assoc()): ?>
          <tr id="row-produk-<?= $p['id_produk'] ?>">
            <td style="color:var(--color-silver-fog);"><?= $no++ ?></td>
            <td>
              <img src="<?= !empty($p['gambar']) ? '../'.$p['gambar'] : 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=100&q=60' ?>"
                   alt="<?= htmlspecialchars($p['nama_laptop']) ?>"
                   style="width:56px;height:40px;object-fit:cover;border-radius:4px;background:var(--color-light-ash);">
            </td>
            <td>
              <div style="font-weight:500;color:var(--color-carbon);"><?= htmlspecialchars($p['nama_laptop']) ?></div>
              <div style="font-size:12px;color:var(--color-pewter);max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($p['deskripsi']) ?></div>
            </td>
            <td style="color:var(--color-blue);font-weight:500;"><?= formatRupiah($p['harga']) ?></td>
            <td>
              <span class="badge <?= $p['stok'] > 5 ? 'badge--green' : ($p['stok'] > 0 ? 'badge--amber' : 'badge--red') ?>">
                <?= $p['stok'] ?> unit
              </span>
            </td>
            <td style="font-size:13px;color:var(--color-pewter);"><?= date('d M Y', strtotime($p['created_at'])) ?></td>
            <td>
              <div style="display:flex;gap:4px;">
                <!-- Edit button triggers modal with data -->
                <button class="btn btn--secondary btn--sm btn-edit-produk" id="btnEdit-<?= $p['id_produk'] ?>"
                        data-id="<?= $p['id_produk'] ?>"
                        data-nama="<?= htmlspecialchars($p['nama_laptop'], ENT_QUOTES) ?>"
                        data-deskripsi="<?= htmlspecialchars($p['deskripsi'], ENT_QUOTES) ?>"
                        data-harga="<?= $p['harga'] ?>"
                        data-stok="<?= $p['stok'] ?>"
                        data-garansi="<?= htmlspecialchars($p['garansi'] ?? '', ENT_QUOTES) ?>"
                        data-gambar="<?= htmlspecialchars($p['gambar'] ?? '', ENT_QUOTES) ?>">
                  Edit
                </button>
                <form method="POST" style="display:inline;">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                  <input type="hidden" name="action" value="hapus">
                  <input type="hidden" name="id_produk" value="<?= $p['id_produk'] ?>">
                  <button type="submit" class="btn btn--danger btn--sm" id="btnHapus-<?= $p['id_produk'] ?>"
                          data-confirm="Hapus produk '<?= htmlspecialchars($p['nama_laptop'], ENT_QUOTES) ?>'? Data tidak bisa dipulihkan.">
                    Hapus
                  </button>
                </form>
              </div>
            </td>
          </tr>
          <?php endwhile; else: ?>
          <tr><td colspan="7"><div class="empty-state"><p>Belum ada produk. Klik "+ Tambah Produk" untuk mulai.</p></div></td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
      
      <?php if ($totalPages > 1): ?>
      <div style="display:flex;gap:8px;justify-content:center;margin-top:20px;">
          <?php for ($i=1; $i<=$totalPages; $i++): ?>
          <a href="?page=<?= $i ?><?= $search ? '&q='.urlencode($search) : '' ?>" class="btn btn--sm <?= $i === $page ? 'btn--primary' : 'btn--secondary' ?>"><?= $i ?></a>
          <?php endfor; ?>
      </div>
      <?php endif; ?>
    </div>

  </main>
</div>

<!-- ── Modal Tambah/Edit Produk ── -->
<div class="modal-backdrop" id="modalProduk">
  <div class="modal">
    <div class="modal__header">
      <div class="modal__title" id="modalTitle">Tambah Produk Baru</div>
      <button class="modal__close" data-modal-close aria-label="Tutup">&times;</button>
    </div>
    <form method="POST" action="kelola_produk.php" enctype="multipart/form-data" id="formProduk">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
      <input type="hidden" name="action" id="formAction" value="tambah">
      <input type="hidden" name="id_produk" id="formId" value="">
      <div class="modal__body">
        <div class="form-group">
          <label class="form-label" for="inputNama">Nama Laptop <span style="color:#d92b2b;">*</span></label>
          <input class="form-control" id="inputNama" name="nama_laptop" type="text" placeholder="cth: ASUS ROG Strix G16" required/>
        </div>
        <div class="form-group">
          <label class="form-label" for="inputDesc">Deskripsi</label>
          <textarea class="form-control form-control--textarea" id="inputDesc" name="deskripsi" rows="3" placeholder="Spesifikasi singkat..."></textarea>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
          <div class="form-group">
            <label class="form-label" for="inputHarga">Harga (Rp) <span style="color:#d92b2b;">*</span></label>
            <input class="form-control" id="inputHarga" name="harga" type="number" min="0" step="1000" placeholder="15000000" required/>
          </div>
          <div class="form-group">
            <label class="form-label" for="inputStok">Stok (Unit) <span style="color:#d92b2b;">*</span></label>
            <input class="form-control" id="inputStok" name="stok" type="number" min="0" placeholder="10" required/>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label" for="inputGaransi">Garansi</label>
          <input class="form-control" id="inputGaransi" name="garansi" type="text" placeholder="cth: Garansi Resmi 1 Tahun" value="Garansi Resmi 1 Tahun"/>
        </div>
        <div class="form-group">
          <label class="form-label" for="inputGambarUrl">URL Gambar</label>
          <input class="form-control" id="inputGambarUrl" name="gambar" type="url" placeholder="https://..."/>
          <div class="form-hint">Atau upload file di bawah</div>
        </div>
        <div class="form-group">
          <label class="form-label" for="inputFileGambar">Upload Gambar</label>
          <input class="form-control" id="inputFileGambar" name="file_gambar" type="file" accept="image/*" data-preview="previewImg"/>
          <img id="previewImg" src="" alt="" style="margin-top:8px;max-height:80px;border-radius:4px;display:none;" onerror="this.style.display='none'" onload="this.style.display='block'">
        </div>
      </div>
      <div class="modal__footer">
        <button type="button" class="btn btn--secondary" data-modal-close>Batal</button>
        <button type="submit" class="btn btn--primary" id="btnSubmitProduk">Simpan Produk</button>
      </div>
    </form>
  </div>
</div>

<script src="../assets/js/main.js"></script>
<script>
  // Populate modal for edit
  document.querySelectorAll('.btn-edit-produk').forEach(btn => {
    btn.addEventListener('click', () => {
      document.getElementById('modalTitle').textContent   = 'Edit Produk';
      document.getElementById('formAction').value         = 'edit';
      document.getElementById('formId').value             = btn.dataset.id;
      document.getElementById('inputNama').value          = btn.dataset.nama;
      document.getElementById('inputDesc').value          = btn.dataset.deskripsi;
      document.getElementById('inputHarga').value         = btn.dataset.harga;
      document.getElementById('inputStok').value          = btn.dataset.stok;
      document.getElementById('inputGaransi').value       = btn.dataset.garansi;
      document.getElementById('inputGambarUrl').value     = btn.dataset.gambar;
      document.getElementById('modalProduk').classList.add('open');
    });
  });

  // Reset modal when opening for add
  document.getElementById('btnOpenModal')?.addEventListener('click', () => {
    document.getElementById('modalTitle').textContent = 'Tambah Produk Baru';
    document.getElementById('formAction').value       = 'tambah';
    document.getElementById('formId').value           = '';
    document.getElementById('formProduk').reset();
    document.getElementById('previewImg').style.display = 'none';
  });
</script>
</body>
</html>
