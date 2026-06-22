<?php
// includes/header.php — Site-wide navigation header
// Usage: include '../includes/header.php';
//        Set $pageTitle and $navActive before including.
$pageTitle = $pageTitle ?? 'A-LINKS — Jual & Servis Laptop';
$navActive = $navActive ?? '';
$isAdmin   = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$isUser    = isset($_SESSION['role']) && $_SESSION['role'] === 'user';
$userName  = $_SESSION['nama'] ?? '';
$basePath  = $basePath ?? '../'; // set to '' for root-level files
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <meta name="description" content="A-LINKS — Toko laptop terpercaya & layanan servis profesional. Temukan laptop terbaik dengan harga kompetitif." />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="<?= $basePath ?>assets/css/style.css?v=<?= time() ?>" />
</head>
<body>

<!-- ── Navigation ── -->
<nav id="mainNav" class="nav">
  <a href="<?= $basePath ?>index.php" class="nav__brand">A-LINKS</a>

  <!-- Desktop links -->
  <div class="nav__links" role="navigation" aria-label="Main navigation">
    <a href="<?= $basePath ?>index.php"       class="nav__link <?= $navActive === 'home'    ? 'active' : '' ?>">Beranda</a>
    <a href="<?= $basePath ?>user/katalog.php" class="nav__link <?= $navActive === 'katalog' ? 'active' : '' ?>">Katalog</a>
    <a href="<?= (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') ? $basePath.'admin/kelola_servis.php' : $basePath.'user/request_servis.php' ?>" 
       class="nav__link <?= $navActive === 'servis'  ? 'active' : '' ?>">Servis</a>
    <a href="<?= $basePath ?>index.php#tentang" class="nav__link">Tentang</a>
  </div>

  <!-- Right-side actions -->
  <div class="nav__actions">
    <!-- Search Bar -->
    <form action="<?= $basePath ?>user/katalog.php" method="GET" style="display:flex;align-items:center;background:var(--color-light-ash);border-radius:20px;padding:4px 12px;width:200px;border:1px solid var(--color-cloud);margin-right:8px;" class="nav-search">
      <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="var(--color-pewter)" stroke-width="2" style="flex-shrink:0;">
        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
      </svg>
      <input type="text" name="q" placeholder="Cari produk..." style="border:none;background:transparent;outline:none;padding:4px 8px;font-size:13px;width:100%;color:var(--color-carbon);" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
    </form>
    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
      <a href="<?= $basePath ?>admin/dashboard.php" class="nav__link" id="navDashboardBtn">Dashboard</a>
      <a href="<?= $basePath ?>user/profil.php" class="nav__link" id="navProfileBtn">Profil Saya</a>
      <a href="<?= $basePath ?>logout.php" class="btn btn--secondary btn--sm" id="navLogoutBtn">Keluar</a>
    <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'user'): ?>
      <a href="<?= $basePath ?>user/keranjang.php" class="nav__icon-btn" title="Keranjang" id="navCartBtn">
        <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.3 2.3c-.6.6-.2 1.7.7 1.7H17m0 0a2 2 0 100 4 2 2 0 000-4zm-10 2a2 2 0 100 4 2 2 0 000-4z"/>
        </svg>
        <?php
        $cartBadge = 0;
        if (isLoggedIn()) {
            $stmtBadge = $koneksi->prepare("SELECT COALESCE(SUM(qty),0) AS total FROM keranjang WHERE id_user=?");
            $stmtBadge->bind_param('i', $_SESSION['id_user']); $stmtBadge->execute();
            $cartBadge = (int)$stmtBadge->get_result()->fetch_assoc()['total']; $stmtBadge->close();
        }
        if ($cartBadge > 0): ?>
        <span style="position:absolute;top:-4px;right:-4px;background:var(--color-blue);color:white;font-size:10px;font-weight:700;border-radius:50%;width:18px;height:18px;display:flex;align-items:center;justify-content:center;"><?= min(99,$cartBadge) ?></span>
        <?php endif; ?>
      </a>
      <a href="<?= $basePath ?>user/profil.php" class="nav__link" id="navAccountBtn">Profil Saya</a>
      <a href="<?= $basePath ?>logout.php" class="btn btn--secondary btn--sm" id="navLogoutBtn">Keluar</a>
    <?php else: ?>
      <a href="<?= $basePath ?>login.php"    class="nav__link" id="navLoginBtn">Masuk</a>
      <a href="<?= $basePath ?>register.php" class="btn btn--primary btn--sm" id="navRegisterBtn">Daftar</a>
    <?php endif; ?>

    <!-- Hamburger -->
    <button class="nav__hamburger" id="hamburger" aria-label="Buka menu" aria-expanded="false">
      <span></span><span></span><span></span>
    </button>
  </div>
</nav>

<!-- Mobile drawer -->
<div class="nav__drawer" id="navDrawer" role="dialog" aria-label="Menu navigasi">
  <button class="nav__drawer-close" id="drawerClose" aria-label="Tutup menu">&times;</button>
  <a href="<?= $basePath ?>index.php"        class="nav__drawer-link">Beranda</a>
  <a href="<?= $basePath ?>user/katalog.php"  class="nav__drawer-link">Katalog</a>
  <a href="<?= $basePath ?>user/request_servis.php" class="nav__drawer-link">Servis</a>
  <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
    <a href="<?= $basePath ?>admin/dashboard.php" class="nav__drawer-link">Dashboard Admin</a>
    <a href="<?= $basePath ?>user/profil.php" class="nav__drawer-link">Profil Saya</a>
    <a href="<?= $basePath ?>logout.php" class="nav__drawer-link">Keluar</a>
  <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'user'): ?>
    <a href="<?= $basePath ?>user/keranjang.php"  class="nav__drawer-link">Keranjang</a>
    <a href="<?= $basePath ?>user/profil.php"  class="nav__drawer-link">Profil Saya</a>
    <a href="<?= $basePath ?>user/riwayat.php"    class="nav__drawer-link">Riwayat</a>
    <a href="<?= $basePath ?>logout.php" class="nav__drawer-link">Keluar</a>
  <?php else: ?>
    <a href="<?= $basePath ?>login.php"    class="nav__drawer-link">Masuk</a>
    <a href="<?= $basePath ?>register.php" class="nav__drawer-link">Daftar</a>
  <?php endif; ?>
</div>
