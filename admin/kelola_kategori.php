<?php
session_start();
require_once '../config/koneksi.php';
requireAdmin();
$basePath = '../'; $pageTitle = 'Kelola Kategori â€” A-LINKS';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'tambah') {
        $nama = trim($_POST['nama_kategori'] ?? '');
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/','-', $nama));
        if ($nama) {
            $stmt = $koneksi->prepare("INSERT IGNORE INTO kategori (nama_kategori, slug) VALUES (?,?)");
            $stmt->bind_param('ss', $nama, $slug); $stmt->execute(); $stmt->close();
            setFlash('success', 'Kategori berhasil ditambahkan!');
        }
    }
    if ($action === 'hapus') {
        $id = (int)$_POST['id_kategori'];
        $koneksi->query("UPDATE produk SET id_kategori=NULL WHERE id_kategori=$id");
        $stmt = $koneksi->prepare("DELETE FROM kategori WHERE id_kategori=?");
        $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close();
        setFlash('success', 'Kategori dihapus.');
    }
    if ($action === 'edit') {
        $id = (int)$_POST['id_kategori'];
        $nama = trim($_POST['nama_kategori'] ?? '');
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/','-', $nama));
        $stmt = $koneksi->prepare("UPDATE kategori SET nama_kategori=?, slug=? WHERE id_kategori=?");
        $stmt->bind_param('ssi', $nama, $slug, $id); $stmt->execute(); $stmt->close();
        setFlash('success', 'Kategori diperbarui!');
    }
    redirect('kelola_kategori.php');
}

$list = $koneksi->query("SELECT k.*, (SELECT COUNT(*) FROM produk WHERE id_kategori=k.id_kategori AND is_deleted=0) as total_produk FROM kategori k ORDER BY k.nama_kategori ASC");
?>
<!DOCTYPE html><html lang="id"><head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title><?= $pageTitle ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="../assets/css/style.css?v=<?= time() ?>"/>
</head><body>
<div class="app-layout">
<?php include '../includes/sidebar_admin.php'; ?>
<main class="main-content">
  <div class="page-header">
    <div><h1 class="page-header__title">Kelola Kategori</h1><div class="page-header__sub">Atur kategori/jenis laptop yang dijual</div></div>
    <button class="btn btn--primary btn--sm" data-modal-open="modalKat">+ Tambah Kategori</button>
  </div>
  <?php renderFlash(); ?>

  <div class="table-wrap">
    <div class="table-toolbar"><div class="table-toolbar__title">Daftar Kategori</div></div>
    <table>
      <thead><tr><th>#</th><th>Nama Kategori</th><th>Slug</th><th>Total Produk</th><th>Aksi</th></tr></thead>
      <tbody>
      <?php $no=1; while($k = $list->fetch_assoc()): ?>
      <tr>
        <td style="color:var(--color-silver-fog);"><?= $no++ ?></td>
        <td style="font-weight:500;color:var(--color-carbon);"><?= htmlspecialchars($k['nama_kategori']) ?></td>
        <td><code style="font-size:12px;background:var(--color-cream);padding:2px 6px;border-radius:4px;border:1px solid var(--color-cream-border);"><?= htmlspecialchars($k['slug']) ?></code></td>
        <td><span class="badge badge--gray"><?= $k['total_produk'] ?> produk</span></td>
        <td>
          <div style="display:flex;gap:4px;">
            <button class="btn btn--secondary btn--sm btn-edit-kat" 
                    data-id="<?= $k['id_kategori'] ?>" 
                    data-nama="<?= htmlspecialchars($k['nama_kategori'], ENT_QUOTES) ?>">Edit</button>
            <form method="POST" style="display:inline;">
              <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
              <input type="hidden" name="action" value="hapus">
              <input type="hidden" name="id_kategori" value="<?= $k['id_kategori'] ?>">
              <button type="submit" class="btn btn--danger btn--sm" 
                      data-confirm="Hapus kategori '<?= htmlspecialchars($k['nama_kategori'],ENT_QUOTES) ?>'?">Hapus</button>
            </form>
          </div>
        </td>
      </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</main>
</div>

<!-- Modal -->
<div class="modal-backdrop" id="modalKat">
  <div class="modal" style="max-width:420px;">
    <div class="modal__header">
      <div class="modal__title" id="katModalTitle">Tambah Kategori</div>
      <button class="modal__close" data-modal-close>&times;</button>
    </div>
    <form method="POST" id="formKat">
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
      <input type="hidden" name="action" id="katAction" value="tambah">
      <input type="hidden" name="id_kategori" id="katId" value="">
      <div class="modal__body">
        <div class="form-group">
          <label class="form-label">Nama Kategori</label>
          <input class="form-control" type="text" name="nama_kategori" id="katNama" placeholder="cth: Gaming" required>
        </div>
      </div>
      <div class="modal__footer">
        <button type="button" class="btn btn--secondary" data-modal-close>Batal</button>
        <button type="submit" class="btn btn--primary">Simpan</button>
      </div>
    </form>
  </div>
</div>

<script src="../assets/js/main.js"></script>
<script>
document.querySelectorAll('.btn-edit-kat').forEach(btn => {
  btn.addEventListener('click', () => {
    document.getElementById('katModalTitle').textContent = 'Edit Kategori';
    document.getElementById('katAction').value = 'edit';
    document.getElementById('katId').value = btn.dataset.id;
    document.getElementById('katNama').value = btn.dataset.nama;
    document.getElementById('modalKat').classList.add('open');
  });
});
document.querySelector('[data-modal-open="modalKat"]')?.addEventListener('click', () => {
  document.getElementById('katModalTitle').textContent = 'Tambah Kategori';
  document.getElementById('katAction').value = 'tambah';
  document.getElementById('katId').value = '';
  document.getElementById('katNama').value = '';
});
</script>
</body></html>
