<?php
session_start();
require_once '../config/koneksi.php';
requireAdmin();
$basePath = '../'; $pageTitle = 'Kelola User — A-LINKS';

// Handle hapus/toggle role
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action  = $_POST['action']  ?? '';
    $id_user = (int)($_POST['id_user'] ?? 0);
    if ($action === 'hapus' && $id_user !== (int)$_SESSION['id_user']) {
        $stmt = $koneksi->prepare("UPDATE users SET is_deleted=1 WHERE id_user=?");
        $stmt->bind_param('i', $id_user); $stmt->execute(); $stmt->close();
        setFlash('success', 'User berhasil dihapus (soft delete).');
    }
    if ($action === 'toggle_role') {
        $newRole = $_POST['new_role'] ?? 'user';
        $stmt = $koneksi->prepare("UPDATE users SET role=? WHERE id_user=?");
        $stmt->bind_param('si', $newRole, $id_user); $stmt->execute(); $stmt->close();
        setFlash('success', 'Role user berhasil diubah.');
    }
    redirect('kelola_user.php');
}

$page = (int)($_GET['page'] ?? 1);
if ($page < 1) $page = 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$search = trim($_GET['q'] ?? '');
if ($search) {
    $like = "%$search%";
    $stmtC = $koneksi->prepare("SELECT COUNT(*) AS total FROM users WHERE is_deleted=0 AND (nama LIKE ? OR email LIKE ?)");
    $stmtC->bind_param('ss', $like, $like);
    $stmtC->execute();
    $totalData = $stmtC->get_result()->fetch_assoc()['total'];
    $stmtC->close();

    $stmt = $koneksi->prepare("SELECT * FROM users WHERE is_deleted=0 AND (nama LIKE ? OR email LIKE ?) ORDER BY id_user DESC LIMIT ? OFFSET ?");
    $stmt->bind_param('ssii', $like, $like, $limit, $offset); $stmt->execute();
    $userList = $stmt->get_result(); $stmt->close();
} else {
    $totalData = $koneksi->query("SELECT COUNT(*) AS total FROM users WHERE is_deleted=0")->fetch_assoc()['total'];
    $stmt = $koneksi->prepare("SELECT * FROM users WHERE is_deleted=0 ORDER BY id_user DESC LIMIT ? OFFSET ?");
    $stmt->bind_param('ii', $limit, $offset); $stmt->execute();
    $userList = $stmt->get_result(); $stmt->close();
}
$totalPages = ceil($totalData / $limit);
?>
<!DOCTYPE html><html lang="id"><head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title><?= $pageTitle ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="../assets/css/style.css"/>
</head><body>
<div class="app-layout">
<?php include '../includes/sidebar_admin.php'; ?>
<main class="main-content">
  <div class="page-header">
    <div><h1 class="page-header__title">Kelola User</h1>
    <div class="page-header__sub">Daftar seluruh akun pelanggan dan administrator</div></div>
  </div>
  <?php renderFlash(); ?>

  <form method="GET" style="margin-bottom:20px;display:flex;gap:8px;">
    <div class="search-input" style="width:280px;">
      <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="var(--color-silver-fog)" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
      <input type="text" name="q" placeholder="Cari nama atau email..." value="<?= htmlspecialchars($search) ?>"/>
    </div>
    <button type="submit" class="btn btn--secondary btn--sm">Cari</button>
    <?php if ($search): ?><a href="kelola_user.php" class="btn btn--secondary btn--sm">Reset</a><?php endif; ?>
  </form>

  <div class="table-wrap">
    <div class="table-toolbar">
      <div class="table-toolbar__title">Daftar Pengguna</div>
      <span style="font-size:13px;color:var(--color-pewter);"><?= $totalData ?> akun</span>
    </div>
    <div style="overflow-x: auto; width: 100%;">
      <table style="min-width: 950px;">
        <thead><tr><th>#</th><th>Nama</th><th>Email</th><th>No. Telp</th><th>Role</th><th>Aksi</th></tr></thead>
        <tbody>
        <?php if ($userList->num_rows > 0): $no=1; while ($u = $userList->fetch_assoc()): $isSelf = ((int)$u['id_user'] === (int)$_SESSION['id_user']); ?>
        <tr id="rowUser-<?= $u['id_user'] ?>">
          <td style="color:var(--color-silver-fog);"><?= $no++ ?></td>
          <td>
            <div style="display:flex;align-items:center;gap:10px;">
              <div style="width:32px;height:32px;border-radius:50%;background:<?= $u['role']==='admin' ? 'var(--color-blue)' : 'var(--color-light-ash)' ?>;color:<?= $u['role']==='admin' ? 'white' : 'var(--color-pewter)' ?>;display:grid;place-items:center;font-size:13px;font-weight:600;flex-shrink:0;"><?= strtoupper(substr($u['nama'],0,1)) ?></div>
              <div>
                <div style="font-weight:500;color:var(--color-carbon);"><?= htmlspecialchars($u['nama']) ?><?= $isSelf ? ' <span class="badge badge--blue" style="font-size:10px;">Saya</span>' : '' ?></div>
                <?php if (!empty($u['alamat'])): ?><div style="font-size:11px;color:var(--color-silver-fog);max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($u['alamat']) ?></div><?php endif; ?>
              </div>
            </div>
          </td>
          <td style="font-size:13px;"><?= htmlspecialchars($u['email']) ?></td>
          <td style="font-size:13px;color:var(--color-pewter);"><?= htmlspecialchars($u['no_telp'] ?? '—') ?></td>
          <td><span class="badge <?= $u['role']==='admin' ? 'badge--blue' : 'badge--gray' ?>"><?= ucfirst($u['role']) ?></span></td>
          <td>
            <?php if (!$isSelf): ?>
            <div style="display:flex;gap:4px;">
              <form method="POST" style="display:inline;">
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="toggle_role">
                <input type="hidden" name="id_user" value="<?= $u['id_user'] ?>">
                <input type="hidden" name="new_role" value="<?= $u['role']==='admin'?'user':'admin' ?>">
                <button type="submit" class="btn btn--secondary btn--sm" id="btnToggleRole-<?= $u['id_user'] ?>"
                        data-confirm="Ubah role <?= htmlspecialchars($u['nama'],ENT_QUOTES) ?> menjadi <?= $u['role']==='admin'?'user':'admin' ?>?">
                  → <?= $u['role']==='admin'?'Jadikan User':'Jadikan Admin' ?>
                </button>
              </form>
              <form method="POST" style="display:inline;">
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="hapus">
                <input type="hidden" name="id_user" value="<?= $u['id_user'] ?>">
                <button type="submit" class="btn btn--danger btn--sm" id="btnHapusUser-<?= $u['id_user'] ?>"
                        data-confirm="Hapus akun <?= htmlspecialchars($u['nama'],ENT_QUOTES) ?>? Data tidak bisa dipulihkan.">Hapus</button>
              </form>
            </div>
            <?php else: ?><span style="font-size:12px;color:var(--color-silver-fog);">Akun aktif</span><?php endif; ?>
          </td>
        </tr>
        <?php endwhile; else: ?>
        <tr><td colspan="6"><div class="empty-state"><p>Tidak ada user ditemukan.</p></div></td></tr>
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
<script src="../assets/js/main.js"></script>
</body></html>
