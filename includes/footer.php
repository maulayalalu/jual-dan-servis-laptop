<?php
// includes/footer.php â€” Site-wide footer
// Usage: include '../includes/footer.php';
global $koneksi;
global $basePath;
/** @var string $basePath */
if (empty($basePath)) {
    $basePath = '../';
}

$pengaturan = [];
if (isset($koneksi)) {
    $res_pengaturan = $koneksi->query("SELECT kunci, nilai FROM pengaturan");
    if ($res_pengaturan) {
        while ($row = $res_pengaturan->fetch_assoc()) {
            $pengaturan[$row['kunci']] = $row['nilai'];
        }
    }
}
$ft_nama_toko = $pengaturan['nama_toko'] ?? 'A-LINKS';
$ft_tagline = $pengaturan['tagline'] ?? 'Toko laptop terpercaya & layanan servis profesional sejak 2024. Kualitas premium, harga terjangkau.';
$ft_alamat = $pengaturan['alamat'] ?? '73RH+PG6, Jl. Yos Sudarso, Jemb. Kembar, Kec. Lembar, Kabupaten Lombok Barat, Nusa Tenggara Bar. 83364';
$ft_no_wa = $pengaturan['no_wa'] ?? '6281216851726';
$ft_pesan_wa = $pengaturan['pesan_wa'] ?? 'Halo A-LINKS, saya ingin konsultasi mengenai laptop/servis.';
$ft_wa_link = "https://wa.me/" . preg_replace('/[^0-9]/', '', $ft_no_wa) . "?text=" . rawurlencode($ft_pesan_wa);
?>

<!-- â”€â”€ Footer â”€â”€ -->
<footer class="footer">
  <div class="container">
    <div class="footer__grid">
      <div>
        <div class="footer__brand"><?= htmlspecialchars($ft_nama_toko) ?></div>
        <p class="footer__tagline"><?= nl2br(htmlspecialchars($ft_tagline)) ?></p>
        <p class="footer__tagline" style="margin-top:12px;"><strong>Alamat Kami:</strong><br><?= nl2br(htmlspecialchars($ft_alamat)) ?></p>
      </div>
      <div>
        <div class="footer__col-title">Produk</div>
        <a href="<?= $basePath ?>user/katalog.php" class="footer__col-link">Katalog Laptop</a>
        <a href="<?= $basePath ?>user/katalog.php?tipe=gaming" class="footer__col-link">Laptop Gaming</a>
        <a href="<?= $basePath ?>user/katalog.php?tipe=bisnis" class="footer__col-link">Laptop Bisnis</a>
        <a href="<?= $basePath ?>user/katalog.php?tipe=pelajar" class="footer__col-link">Laptop Pelajar</a>
      </div>
      <div>
        <div class="footer__col-title">Layanan</div>
        <a href="<?= $basePath ?>user/request_servis.php" class="footer__col-link">Request Servis</a>
        <a href="<?= $basePath ?>user/riwayat.php" class="footer__col-link">Cek Status Servis</a>
        <a href="#" class="footer__col-link">Garansi</a>
        <a href="<?= htmlspecialchars($ft_wa_link) ?>" target="_blank" class="footer__col-link">Konsultasi</a>
      </div>
      <div>
        <div class="footer__col-title">Perusahaan</div>
        <a href="#tentang" class="footer__col-link">Tentang Kami</a>
        <a href="<?= htmlspecialchars($ft_wa_link) ?>" target="_blank" class="footer__col-link">Kontak</a>
        <a href="#" class="footer__col-link">Kebijakan Privasi</a>
        <a href="#" class="footer__col-link">Syarat & Ketentuan</a>
      </div>
    </div>
    <div class="footer__bottom">
      <span>&copy; <?= date('Y') ?> <?= htmlspecialchars($ft_nama_toko) ?>. Semua hak dilindungi.</span>
      <span style="color:rgba(255,255,255,0.3)">Dibuat dengan â¤ di Indonesia</span>
    </div>
  </div>
</footer>
<!-- WhatsApp Floating Button -->
<a href="<?= htmlspecialchars($ft_wa_link) ?>" target="_blank" class="wa-float" title="Konsultasi via WhatsApp">
  <img src="https://upload.wikimedia.org/wikipedia/commons/6/6b/WhatsApp.svg" alt="WhatsApp" width="60" height="60" style="width: 60px; height: 60px; object-fit: contain;">
</a>

<script src="<?= $basePath ?>assets/js/main.js"></script>
</body>
</html>
