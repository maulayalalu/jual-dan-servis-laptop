<?php
// includes/sidebar_admin.php — Admin sidebar navigation (multi-role: admin, owner, kasir)
$currentFile = basename($_SERVER['PHP_SELF']);
$basePath ??= '../';
$_role = $_SESSION['role'] ?? 'admin';

// Helper: tampilkan menu hanya untuk role tertentu
function sidebarAllow(array $allowed): bool {
    global $_role;
    return in_array($_role, $allowed, true);
}

$dashboardUrl = $basePath . ($_role === 'admin' ? 'admin' : ($_role === 'owner' ? 'owner' : 'kasir')) . '/dashboard.php';

// Ambil logo dan nama toko
$sbLogoSitus = '';
$sbNamaToko = 'A-LINKS';
if (isset($koneksi)) {
    $q = $koneksi->query("SELECT kunci, nilai FROM pengaturan WHERE kunci IN ('logo_situs', 'nama_toko')");
    if ($q) {
        while ($row = $q->fetch_assoc()) {
            if ($row['kunci'] === 'logo_situs') $sbLogoSitus = $row['nilai'];
            if ($row['kunci'] === 'nama_toko') $sbNamaToko = $row['nilai'];
        }
    }
}
?>
<aside class="sidebar" id="adminSidebar">
  <div class="sidebar__brand">
    <a href="<?= $basePath ?>index.php" style="display:flex;align-items:center;gap:12px;text-decoration:none;">
      <?php if ($sbLogoSitus): ?>
        <img src="<?= $basePath . htmlspecialchars($sbLogoSitus) ?>" alt="Logo" style="height: 32px; width: auto; object-fit: contain;">
      <?php else: ?>
        <div class="sidebar__logo-box">
          <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
          </svg>
        </div>
      <?php endif; ?>
      <span class="sidebar__logo-text"><?= htmlspecialchars($sbNamaToko) ?></span>
    </a>
  </div>

  <div class="sidebar__group-label">Menu Utama</div>

  <a href="<?= $dashboardUrl ?>"
     class="sidebar__link <?= $currentFile === 'dashboard.php' ? 'active' : '' ?>" id="sidebarDashboard">
    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
      <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
    </svg>
    Dashboard
  </a>

  <a href="<?= $basePath ?>index.php" target="_blank"
     class="sidebar__link" id="sidebarLihatSitus">
    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
      <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
    </svg>
    Lihat Situs
  </a>

  <div class="sidebar__group-label">Kelola Toko</div>

  <?php if (sidebarAllow(['admin', 'owner'])): ?>
  <a href="<?= $basePath ?>admin/kelola_produk.php"
     class="sidebar__link <?= $currentFile === 'kelola_produk.php' ? 'active' : '' ?>" id="sidebarProduk">
    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
      <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
    </svg>
    Kelola Produk
  </a>
  <?php endif; ?>

  <a href="<?= $basePath ?>admin/kelola_transaksi.php"
     class="sidebar__link <?= $currentFile === 'kelola_transaksi.php' ? 'active' : '' ?>" id="sidebarTransaksi">
    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
      <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z"/>
    </svg>
    Transaksi
  </a>

  <a href="<?= $basePath ?>admin/verifikasi_pembayaran.php"
     class="sidebar__link <?= $currentFile === 'verifikasi_pembayaran.php' ? 'active' : '' ?>" id="sidebarVerifikasi">
    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
      <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z"/>
    </svg>
    Verifikasi Bayar
    <?php
    $pendingCount = $koneksi->query("SELECT COUNT(*) as c FROM transaksi WHERE status_pembayaran='pending_verify'")->fetch_assoc()['c'] ?? 0;
    if ($pendingCount > 0): ?>
    <span style="margin-left:auto;background:var(--color-taupe);color:white;font-size:11px;font-weight:700;border-radius:10px;padding:1px 7px;"><?= $pendingCount ?></span>
    <?php endif; ?>
  </a>

  <?php if (sidebarAllow(['admin', 'owner'])): ?>
  <a href="<?= $basePath ?>admin/laporan.php"
     class="sidebar__link <?= $currentFile === 'laporan.php' ? 'active' : '' ?>" id="sidebarLaporan">
    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
      <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/>
    </svg>
    Laporan
  </a>
  <?php endif; ?>

  <div class="sidebar__group-label">Layanan</div>

  <a href="<?= $basePath ?>admin/kelola_servis.php"
     class="sidebar__link <?= $currentFile === 'kelola_servis.php' ? 'active' : '' ?>" id="sidebarServis">
    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
      <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z"/>
    </svg>
    Kelola Servis
  </a>

  <?php if (sidebarAllow(['admin', 'owner'])): ?>
  <div class="sidebar__group-label">Pengguna</div>

  <a href="<?= $basePath ?>admin/kelola_user.php"
     class="sidebar__link <?= $currentFile === 'kelola_user.php' ? 'active' : '' ?>" id="sidebarUser">
    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
      <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
    </svg>
    Kelola User
  </a>
  <?php endif; ?>

  <?php if (sidebarAllow(['admin'])): ?>
  <div class="sidebar__group-label">Pengaturan</div>

  <a href="<?= $basePath ?>admin/kelola_kategori.php"
     class="sidebar__link <?= $currentFile === 'kelola_kategori.php' ? 'active' : '' ?>" id="sidebarKategori">
    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
      <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z"/>
      <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z"/>
    </svg>
    Kelola Kategori
  </a>


  <a href="<?= $basePath ?>admin/pengaturan_web.php"
     class="sidebar__link <?= $currentFile === 'pengaturan_web.php' ? 'active' : '' ?>" id="sidebarPengaturanWeb">
    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
      <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
      <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
    </svg>
    Pengaturan Web
  </a>
  <?php endif; ?>

  <?php if (sidebarAllow(['owner'])): ?>
  <div class="sidebar__group-label">Pengaturan Owner</div>

  <a href="<?= $basePath ?>owner/pengaturan_situs.php"
     class="sidebar__link <?= $currentFile === 'pengaturan_situs.php' ? 'active' : '' ?>" id="sidebarPengaturanSitus">
    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
      <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
      <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
    </svg>
    Pengaturan Situs
  </a>

  <a href="<?= $basePath ?>owner/kelola_hero.php"
     class="sidebar__link <?= $currentFile === 'kelola_hero.php' ? 'active' : '' ?>" id="sidebarHero">
    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
      <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
    </svg>
    Kelola Hero
  </a>
  <?php endif; ?>


  <!-- Sidebar footer / user info -->
  <div class="sidebar__footer">
    <div class="sidebar__avatar" id="adminAvatar"
         style="background:<?php
           echo match($_role) {
               'owner' => 'var(--color-blue-light)',
               'kasir' => 'var(--color-taupe)',
               default => 'var(--color-blue)',
           };
         ?>;">
      <?= strtoupper(substr($_SESSION['nama'] ?? 'A', 0, 1)) ?>
    </div>
    <div style="flex:1;min-width:0;">
      <div class="sidebar__user-name" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
        <?= htmlspecialchars($_SESSION['nama'] ?? 'Admin') ?>
      </div>
      <div class="sidebar__user-role"><?= getRoleLabel($_role) ?></div>
    </div>
    <a href="<?= $basePath ?>logout.php" id="sidebarLogout"
       style="color:var(--color-silver-fog);transition:color 0.33s;"
       title="Keluar"
       onmouseover="this.style.color='var(--color-graphite)'"
       onmouseout="this.style.color='var(--color-silver-fog)'">
      <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/>
      </svg>
    </a>
  </div>
</aside>
