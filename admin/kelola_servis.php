<?php
session_start();
require_once '../config/koneksi.php';
requireAdmin();
$basePath = '../'; $pageTitle = 'Kelola Servis — A-LINKS';

// Handle update status & biaya
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action    = $_POST['action'] ?? '';
    $id_servis = (int)($_POST['id_servis'] ?? 0);

    if ($action === 'update_status') {
        $status      = $_POST['status']    ?? 'pending';
        $biaya       = (int)($_POST['biaya'] ?? 0);
        if ($status === 'selesai' || $status === 'diambil') {
            $tgl_selesai = date('Y-m-d');
            $stmt = $koneksi->prepare("UPDATE servis SET status=?, biaya=?, tgl_selesai=? WHERE id_servis=?");
            $stmt->bind_param('sisi', $status, $biaya, $tgl_selesai, $id_servis);
        } else {
            $stmt = $koneksi->prepare("UPDATE servis SET status=?, biaya=?, tgl_selesai=NULL WHERE id_servis=?");
            $stmt->bind_param('sii', $status, $biaya, $id_servis);
        }
        $stmt->execute(); $stmt->close();
        setFlash('success', 'Status servis berhasil diperbarui.');
        redirect('kelola_servis.php');
    }
    if ($action === 'hapus') {
        $stmt = $koneksi->prepare("DELETE FROM servis WHERE id_servis=?");
        $stmt->bind_param('i', $id_servis);
        $stmt->execute(); $stmt->close();
        setFlash('success', 'Data servis dihapus.');
        redirect('kelola_servis.php');
    }
}

// Filter by status
$page = (int)($_GET['page'] ?? 1);
if ($page < 1) $page = 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$filterStatus = $_GET['status'] ?? '';
$editId       = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;

if ($filterStatus) {
    // Count total for pagination
    $stmtCount = $koneksi->prepare("SELECT COUNT(*) as c FROM servis WHERE status=?");
    $stmtCount->bind_param('s', $filterStatus);
    $stmtCount->execute();
    $totalRows = $stmtCount->get_result()->fetch_assoc()['c'];
    $stmtCount->close();
    
    $stmt = $koneksi->prepare("SELECT s.*, u.nama AS nama_user, u.no_telp FROM servis s JOIN users u ON s.id_user=u.id_user WHERE s.status=? ORDER BY s.tgl_masuk DESC LIMIT ? OFFSET ?");
    $stmt->bind_param('sii', $filterStatus, $limit, $offset);
    $stmt->execute();
    $servisList = $stmt->get_result(); $stmt->close();
} else {
    // Count total for pagination
    $resCount = $koneksi->query("SELECT COUNT(*) as c FROM servis");
    $totalRows = $resCount->fetch_assoc()['c'];
    
    $servisList = $koneksi->query("SELECT s.*, u.nama AS nama_user, u.no_telp FROM servis s JOIN users u ON s.id_user=u.id_user ORDER BY s.tgl_masuk DESC LIMIT $limit OFFSET $offset");
}

$totalPages = ceil($totalRows / $limit);
if ($totalPages < 1) $totalPages = 1;

$editData = null;
if ($editId) {
    $stmt = $koneksi->prepare("SELECT s.*, u.nama AS nama_user FROM servis s JOIN users u ON s.id_user=u.id_user WHERE s.id_servis=?");
    $stmt->bind_param('i', $editId);
    $stmt->execute();
    $editData = $stmt->get_result()->fetch_assoc(); $stmt->close();
}

// Counts per status
$counts = []; 
foreach (['pending','proses','selesai','diambil'] as $st) {
    $r = $koneksi->query("SELECT COUNT(*) AS c FROM servis WHERE status='$st'");
    $counts[$st] = $r->fetch_assoc()['c'];
}
?>
<!DOCTYPE html><html lang="id"><head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title><?= $pageTitle ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="../assets/css/style.css"/>
</head><body>
<div class="app-layout">
<?php include '../includes/sidebar_admin.php'; ?>
<main class="main-content">

  <div class="page-header">
    <div><h1 class="page-header__title">Kelola Servis</h1>
    <div class="page-header__sub">Terima, proses, dan update status perbaikan laptop pelanggan</div></div>
  </div>
  <?php renderFlash(); ?>

  <!-- Status filter tabs -->
  <div style="display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap;">
    <?php
    $tabs = [''=> 'Semua','pending'=>'Pending','proses'=>'Diproses','selesai'=>'Selesai','diambil'=>'Diambil'];
    foreach ($tabs as $val => $lbl):
      $active = $filterStatus === $val;
      $count  = $val ? $counts[$val] : array_sum($counts);
    ?>
    <a href="kelola_servis.php<?= $val ? '?status='.$val : '' ?>"
       class="btn btn--sm <?= $active ? 'btn--primary' : 'btn--secondary' ?>"
       id="tabServis-<?= $val ?: 'all' ?>">
       <?= $lbl ?> <span style="opacity:0.7;font-size:11px;">(<?= $count ?>)</span>
    </a>
    <?php endforeach; ?>
  </div>

  <!-- Edit panel (inline) -->
  <?php if ($editData): ?>
  <div class="table-wrap" style="margin-bottom:20px;padding:0;">
    <div class="modal__header" style="border-radius:12px 12px 0 0;">
      <div class="modal__title">Update Status Servis #<?= $editData['id_servis'] ?></div>
      <a href="kelola_servis.php" style="color:var(--color-pewter);font-size:22px;text-decoration:none;">&times;</a>
    </div>
    <form method="POST" style="padding:20px;display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:12px;align-items:end;">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
      <input type="hidden" name="action" value="update_status">
      <input type="hidden" name="id_servis" value="<?= $editData['id_servis'] ?>">
      <div class="form-group">
        <label class="form-label">Status</label>
        <select class="form-control form-select" name="status" id="editStatus">
          <?php foreach (['pending'=>'Pending','proses'=>'Sedang Diproses','selesai'=>'Selesai','diambil'=>'Sudah Diambil'] as $v=>$l): ?>
          <option value="<?= $v ?>" <?= $editData['status']===$v?'selected':'' ?>><?= $l ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Biaya Servis (Rp)</label>
        <input class="form-control" name="biaya" type="number" min="0" value="<?= $editData['biaya'] ?>" placeholder="0"/>
      </div>
      <div>
        <div class="form-label" style="margin-bottom:6px;">Info Pelanggan</div>
        <div style="font-size:14px;color:var(--color-carbon);font-weight:600;"><?= htmlspecialchars($editData['nama_user'] ?? 'Unknown') ?></div>
        <div style="font-size:13px;color:var(--color-carbon);font-weight:500;margin-top:4px;"><?= htmlspecialchars($editData['tipe_laptop']) ?></div>
        <div style="font-size:12px;color:var(--color-pewter);"><?= htmlspecialchars($editData['keluhan']) ?></div>
      </div>
      <button type="submit" class="btn btn--primary" id="btnSimpanStatus">Simpan</button>
    </form>
  </div>
  <?php endif; ?>

  <!-- Table -->
  <div class="table-wrap">
    <div class="table-toolbar">
      <div class="table-toolbar__title">Daftar Servis <?= $filterStatus ? '('.ucfirst($filterStatus).')' : '' ?></div>
      <span style="font-size:13px;color:var(--color-pewter);"><?= $totalRows ?> data</span>
    </div>
    <div style="overflow-x: auto; width: 100%;">
      <table style="min-width: 950px;">
        <thead><tr><th>#</th><th>Pelanggan</th><th>Tipe Laptop</th><th>Keluhan</th><th>Status</th><th>Biaya</th><th>Tgl Masuk</th><th>Tgl Selesai</th><th>Aksi</th></tr></thead>
      <tbody>
      <?php if ($servisList->num_rows > 0): $no=$offset+1; while ($s = $servisList->fetch_assoc()): ?>
      <tr id="rowServis-<?= $s['id_servis'] ?>">
        <td style="color:var(--color-silver-fog);"><?= $no++ ?></td>
        <td>
          <div style="font-weight:500;color:var(--color-carbon);"><?= htmlspecialchars($s['nama_user']) ?></div>
          <?php if ($s['no_telp']): ?><div style="font-size:12px;color:var(--color-pewter);"><?= htmlspecialchars($s['no_telp']) ?></div><?php endif; ?>
        </td>
        <td><?= htmlspecialchars($s['tipe_laptop']) ?></td>
        <td style="max-width:160px;"><div style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:13px;"><?= htmlspecialchars($s['keluhan']) ?></div></td>
        <td><?php $map=['pending'=>['gray','Pending'],'proses'=>['amber','Diproses'],'selesai'=>['green','Selesai'],'diambil'=>['blue','Diambil']]; [$c,$l]=$map[$s['status']]??['gray',$s['status']]; ?><span class="badge badge--<?= $c ?>"><?= $l ?></span></td>
        <td style="color:var(--color-blue);font-weight:500;"><?= $s['biaya'] ? formatRupiah($s['biaya']) : '<span style="color:var(--color-silver-fog);">—</span>' ?></td>
        <td style="font-size:13px;color:var(--color-pewter);"><?= date('d M Y', strtotime($s['tgl_masuk'])) ?></td>
        <td style="font-size:13px;color:var(--color-pewter);"><?= $s['tgl_selesai'] ? date('d M Y', strtotime($s['tgl_selesai'])) : '—' ?></td>
        <td>
          <div style="display:flex;gap:4px;">
            <a href="kelola_servis.php?edit=<?= $s['id_servis'] ?>" class="btn btn--secondary btn--sm" id="btnEditSrv-<?= $s['id_servis'] ?>">Update</a>
            <form method="POST" style="display:inline;">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
              <input type="hidden" name="action" value="hapus">
              <input type="hidden" name="id_servis" value="<?= $s['id_servis'] ?>">
              <button type="submit" class="btn btn--danger btn--sm" data-confirm="Hapus data servis ini?" id="btnHapusSrv-<?= $s['id_servis'] ?>">Hapus</button>
            </form>
          </div>
        </td>
      </tr>
      <?php endwhile; else: ?>
      <tr><td colspan="9"><div class="empty-state"><p>Belum ada data servis <?= $filterStatus ? "dengan status $filterStatus" : '' ?></p></div></td></tr>
      <?php endif; ?>
      </tbody>
    </table>
    </div>
      <?php if ($totalPages > 1): ?>
      <div style="display:flex;gap:8px;justify-content:center;margin-top:20px;">
          <?php for ($i=1; $i<=$totalPages; $i++): ?>
          <a href="?page=<?= $i ?><?= $filterStatus ? '&status='.$filterStatus : '' ?>" class="btn btn--sm <?= $i === $page ? 'btn--primary' : 'btn--secondary' ?>"><?= $i ?></a>
          <?php endfor; ?>
      </div>
      <?php endif; ?>
  </div>
</main>
</div>
<script src="../assets/js/main.js"></script>
</body></html>
