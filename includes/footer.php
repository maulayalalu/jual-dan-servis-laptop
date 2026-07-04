<?php
// includes/footer.php — Site-wide footer
// Usage: <?php include '../includes/footer.php'; ?>
global $basePath;
/** @var string $basePath */
if (empty($basePath)) {
    $basePath = '../';
}
?>

<!-- ── Footer ── -->
<footer class="footer">
  <div class="container">
    <div class="footer__grid">
      <div>
        <div class="footer__brand">A-LINKS</div>
        <p class="footer__tagline">Toko laptop terpercaya & layanan servis profesional sejak 2024. Kualitas premium, harga terjangkau.</p>
        <p class="footer__tagline" style="margin-top:12px;"><strong>Alamat Kami:</strong><br>73RH+PG6, Jl. Yos Sudarso, Jemb. Kembar, Kec. Lembar, Kabupaten Lombok Barat, Nusa Tenggara Bar. 83364</p>
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
        <a href="https://wa.me/6281216851726?text=Halo%20A-LINKS,%20saya%20ingin%20konsultasi%20mengenai%20laptop/servis." target="_blank" class="footer__col-link">Konsultasi</a>
      </div>
      <div>
        <div class="footer__col-title">Perusahaan</div>
        <a href="#tentang" class="footer__col-link">Tentang Kami</a>
        <a href="https://wa.me/6281216851726?text=Halo%20A-LINKS,%20saya%20butuh%20bantuan." target="_blank" class="footer__col-link">Kontak</a>
        <a href="#" class="footer__col-link">Kebijakan Privasi</a>
        <a href="#" class="footer__col-link">Syarat & Ketentuan</a>
      </div>
    </div>
    <div class="footer__bottom">
      <span>&copy; <?= date('Y') ?> A-LINKS. Semua hak dilindungi.</span>
      <span style="color:rgba(255,255,255,0.3)">Dibuat dengan ❤ di Indonesia</span>
    </div>
  </div>
</footer>
<!-- WhatsApp Floating Button -->
<a href="https://wa.me/6281216851726?text=Halo%20A-LINKS,%20saya%20ingin%20konsultasi%20mengenai%20laptop/servis." target="_blank" class="wa-float" title="Konsultasi via WhatsApp">
  <img src="https://upload.wikimedia.org/wikipedia/commons/6/6b/WhatsApp.svg" alt="WhatsApp" width="60" height="60" style="width: 60px; height: 60px; object-fit: contain;">
</a>

<script src="<?= $basePath ?>assets/js/main.js"></script>
</body>
</html>
