<?php
session_start();
require_once '../config/koneksi.php';
requireUser();
$basePath = '../'; $pageTitle = 'Dashboard Saya — A-LINKS';
$id_user  = (int)$_SESSION['id_user'];

// Stats user
$rTrx = $koneksi->prepare("SELECT COUNT(*) AS total, COALESCE(SUM(total_harga),0) AS spend FROM transaksi WHERE id_user=? AND status_pembayaran='paid'");
$rTrx->bind_param('i',$id_user); $rTrx->execute(); $statsT = $rTrx->get_result()->fetch_assoc(); $rTrx->close();

$rSrv = $koneksi->prepare("SELECT COUNT(*) AS total FROM servis WHERE id_user=? AND status IN('pending','proses')");
$rSrv->bind_param('i',$id_user); $rSrv->execute(); $statsS = $rSrv->get_result()->fetch_assoc(); $rSrv->close();

// Transaksi terbaru user
$rRec = $koneksi->prepare("SELECT * FROM transaksi WHERE id_user=? ORDER BY waktu_transaksi DESC LIMIT 5");
$rRec->bind_param('i',$id_user); $rRec->execute(); $recTrx = $rRec->get_result(); $rRec->close();

// Servis terbaru user
$rRecS = $koneksi->prepare("SELECT * FROM servis WHERE id_user=? ORDER BY tgl_masuk DESC LIMIT 3");
$rRecS->bind_param('i',$id_user); $rRecS->execute(); $recSrv = $rRecS->get_result(); $rRecS->close();

// Data profil
$rProf = $koneksi->prepare("SELECT * FROM users WHERE id_user=? LIMIT 1");
$rProf->bind_param('i',$id_user); $rProf->execute(); $profil = $rProf->get_result()->fetch_assoc(); $rProf->close();

include '../includes/header.php';
?>

<div style="min-height:100vh;background:var(--color-light-ash);padding-top:72px;">
  <div class="container" style="max-width:1100px;padding-top:32px;padding-bottom:80px;">

    <!-- Header greeting -->
    <div style="display:flex;align-items:center;gap:20px;margin-bottom:32px;flex-wrap:wrap;">
      <div style="width:56px;height:56px;border-radius:50%;background:var(--color-blue);color:white;display:grid;place-items:center;font-size:22px;font-weight:600;flex-shrink:0;">
        <?= strtoupper(substr($_SESSION['nama'],0,1)) ?>
      </div>
      <div>
        <h1 style="font-size:24px;font-weight:500;color:var(--color-carbon);">Halo, <?= htmlspecialchars($_SESSION['nama']) ?> 👋</h1>
        <div style="font-size:14px;color:var(--color-pewter);">Selamat datang di dashboard akun Anda</div>
      </div>
      <div style="margin-left:auto;display:flex;gap:8px;">
        <a href="katalog.php" class="btn btn--primary btn--sm" id="btnKeKatalog">Belanja Sekarang</a>
        <a href="request_servis.php" class="btn btn--secondary btn--sm" id="btnKeServis">Ajukan Servis</a>
      </div>
    </div>

    <?php renderFlash(); ?>

    <!-- Stat cards -->
    <div class="stat-grid" style="margin-bottom:24px;">
      <div class="stat-card">
        <div class="stat-card__icon stat-card__icon--blue">
          <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.3 2.3c-.6.6-.2 1.7.7 1.7H17m0 0a2 2 0 100 4 2 2 0 000-4zm-10 2a2 2 0 100 4 2 2 0 000-4z"/></svg>
        </div>
        <div class="stat-card__label">Total Pesanan</div>
        <div class="stat-card__value"><?= $statsT['total'] ?></div>
        <div class="stat-card__change">Transaksi berhasil</div>
      </div>
      <div class="stat-card">
        <div class="stat-card__icon stat-card__icon--green">
          <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div class="stat-card__label">Total Belanja</div>
        <div class="stat-card__value" style="font-size:18px;"><?= formatRupiah($statsT['spend']) ?></div>
        <div class="stat-card__change">Kumulatif pembelian</div>
      </div>
      <div class="stat-card">
        <div class="stat-card__icon stat-card__icon--amber">
          <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z"/></svg>
        </div>
        <div class="stat-card__label">Servis Aktif</div>
        <div class="stat-card__value"><?= $statsS['total'] ?></div>
        <div class="stat-card__change">Sedang diproses</div>
      </div>
      <div class="stat-card">
        <div class="stat-card__icon stat-card__icon--red">
          <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
        </div>
        <div class="stat-card__label">Profil</div>
        <div class="stat-card__value" style="font-size:16px;line-height:1.3;"><?= htmlspecialchars($_SESSION['nama']) ?></div>
        <div class="stat-card__change"><?= htmlspecialchars($profil['email']) ?></div>
      </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
      <!-- Transaksi terbaru -->
      <div class="table-wrap">
        <div class="table-toolbar">
          <div class="table-toolbar__title">Pesanan Terbaru</div>
          <a href="riwayat.php" class="btn btn--secondary btn--sm" id="btnLihatRiwayat">Lihat Semua</a>
        </div>
        <table>
          <thead><tr><th>Order ID</th><th>Total</th><th>Status</th><th>Waktu</th></tr></thead>
          <tbody>
          <?php if ($recTrx->num_rows > 0): while ($t = $recTrx->fetch_assoc()): ?>
          <tr>
            <td><code style="font-size:11px;"><?= htmlspecialchars(substr($t['order_id']??'-',0,14)) ?>...</code></td>
            <td style="color:var(--color-blue);font-weight:500;"><?= formatRupiah($t['total_harga']) ?></td>
            <td><?php $m=['paid'=>['green','Lunas'],'unpaid'=>['amber','Menunggu'],'failed'=>['red','Gagal']]; [$c,$l]=$m[$t['status_pembayaran']]??['gray','—']; ?><span class="badge badge--<?= $c ?>"><?= $l ?></span></td>
            <td style="font-size:12px;color:var(--color-pewter);"><?= date('d M Y', strtotime($t['waktu_transaksi'])) ?></td>
          </tr>
          <?php endwhile; else: ?>
          <tr><td colspan="4"><div class="empty-state" style="padding:24px;"><p>Belum ada pesanan. <a href="katalog.php" style="color:var(--color-blue);">Belanja sekarang!</a></p></div></td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Servis terbaru -->
      <div class="table-wrap">
        <div class="table-toolbar">
          <div class="table-toolbar__title">Status Servis</div>
          <a href="riwayat.php?tab=servis" class="btn btn--secondary btn--sm" id="btnLihatServisRiwayat">Lihat Semua</a>
        </div>
        <table>
          <thead><tr><th>Laptop</th><th>Status</th><th>Biaya</th><th>Tgl</th></tr></thead>
          <tbody>
          <?php if ($recSrv->num_rows > 0): while ($s = $recSrv->fetch_assoc()): ?>
          <tr>
            <td>
              <div style="font-weight:500;font-size:13px;color:var(--color-carbon);"><?= htmlspecialchars($s['tipe_laptop']) ?></div>
              <div style="font-size:11px;color:var(--color-pewter);max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($s['keluhan']) ?></div>
            </td>
            <td><?php $sm=['pending'=>['gray','Pending'],'proses'=>['amber','Diproses'],'selesai'=>['green','Selesai'],'diambil'=>['blue','Diambil']]; [$sc,$sl]=$sm[$s['status']]??['gray',$s['status']]; ?><span class="badge badge--<?= $sc ?>"><?= $sl ?></span></td>
            <td style="font-size:13px;color:var(--color-blue);"><?= $s['biaya'] ? formatRupiah($s['biaya']) : '—' ?></td>
            <td style="font-size:12px;color:var(--color-pewter);"><?= date('d M Y', strtotime($s['tgl_masuk'])) ?></td>
          </tr>
          <?php endwhile; else: ?>
          <tr><td colspan="4"><div class="empty-state" style="padding:24px;"><p>Belum ada servis. <a href="request_servis.php" style="color:var(--color-blue);">Ajukan sekarang!</a></p></div></td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Profil singkat -->
    <div class="table-wrap" style="margin-top:20px;padding:24px;">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
        <div style="font-size:15px;font-weight:500;color:var(--color-carbon);">Informasi Profil</div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
        <?php foreach (['Nama Lengkap'=>$profil['nama'],'Email'=>$profil['email'],'No. Telepon'=>$profil['no_telp']??'—','Alamat'=>$profil['alamat']??'—'] as $lbl=>$val): ?>
        <div>
          <div style="font-size:11px;color:var(--color-silver-fog);text-transform:uppercase;letter-spacing:1px;margin-bottom:3px;"><?= $lbl ?></div>
          <div style="font-size:14px;color:var(--color-carbon);"><?= htmlspecialchars($val) ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

  </div>
</div>

<?php include '../includes/footer.php'; ?>
