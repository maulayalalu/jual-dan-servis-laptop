<?php
session_start();
require_once 'config/koneksi.php';
$pageTitle = 'A-LINKS — Jual & Servis Laptop Terbaik';
$navActive = 'home';
$basePath  = '';
include 'includes/header.php';

// Demo: Produk unggulan (nanti diganti query DB)
$produk_unggulan = [
  ['id'=>1,'nama'=>'ASUS ROG Strix G16','harga'=>18500000,'gambar'=>'https://images.unsplash.com/photo-1593642632559-0c6d3fc62b89?w=600&q=80','deskripsi'=>'Gaming laptop RTX 4060, RAM 16GB, SSD 512GB'],
  ['id'=>2,'nama'=>'MacBook Air M3','harga'=>22000000,'gambar'=>'https://images.unsplash.com/photo-1541807084-5c52b6b3adef?w=600&q=80','deskripsi'=>'Chip Apple M3, 8GB RAM, 256GB SSD, Layar Retina'],
  ['id'=>3,'nama'=>'Lenovo ThinkPad X1','harga'=>16750000,'gambar'=>'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=600&q=80','deskripsi'=>'Intel Core i7, RAM 16GB, SSD 512GB, Bisnis Premium'],
];

$res_pengaturan = $koneksi->query("SELECT kunci, nilai FROM pengaturan");
$pengaturan = [];
if ($res_pengaturan) {
    while ($row = $res_pengaturan->fetch_assoc()) {
        $pengaturan[$row['kunci']] = $row['nilai'];
    }
}
function getSetHome($key, $default) {
    global $pengaturan;
    return $pengaturan[$key] ?? $default;
}
?>

<?php
$slides = [];
$res_slides = $koneksi->query("SELECT * FROM hero_slides ORDER BY urutan ASC, id_slide DESC");
if ($res_slides) {
    while ($r = $res_slides->fetch_assoc()) $slides[] = $r;
}
?>
<!-- â”€â”€ Hero Carousel â”€â”€ -->
<div class="hero-carousel" style="position:relative;width:100%;height:100vh;overflow:hidden;background:var(--color-light-ash);">
  <?php if (count($slides) > 0): ?>
    <?php foreach ($slides as $i => $s): 
      $imgSrc = strpos($s['gambar'], 'http') === 0 ? $s['gambar'] : $s['gambar'];
    ?>
    <div class="hero-carousel__slide <?= $i === 0 ? 'active' : '' ?>" style="position:absolute;inset:0;opacity:0;transition:opacity 1s ease;display:flex;align-items:center;">
      
      <div style="position:absolute;inset:0;z-index:1;">
        <img src="<?= htmlspecialchars($imgSrc) ?>" 
             alt="<?= htmlspecialchars($s['judul']) ?>" 
             style="width:100%;height:100%;object-fit:cover;object-position:<?= htmlspecialchars($s['posisi_gambar'] ?? 'center') ?>;"
             onerror="this.style.background='var(--color-light-ash)'">
        <div style="position:absolute;inset:0;background:linear-gradient(to right, var(--color-light-ash) 40%, transparent 100%);"></div>
      </div>
      
      <div style="position:relative;z-index:2;width:100%;max-width:1200px;margin:0 auto;padding:0 var(--sp-4);text-align:left;transform:translateY(-10%);">
        <?php if ($s['badge_text']): ?><p style="font-size:14px;color:var(--color-taupe);margin-bottom:8px;font-weight:600;letter-spacing:1px;text-transform:uppercase;"><?= htmlspecialchars($s['badge_text']) ?></p><?php endif; ?>
        <h1 style="font-size:48px;font-weight:700;color:var(--color-blue);margin-bottom:16px;line-height:1.2;max-width:600px;"><?= htmlspecialchars($s['judul']) ?></h1>
        <?php if ($s['deskripsi']): ?><p style="font-size:16px;color:var(--color-carbon);margin-bottom:32px;max-width:500px;line-height:1.6;"><?= htmlspecialchars($s['deskripsi']) ?></p><?php endif; ?>
        <div style="display:flex;gap:16px;flex-wrap:wrap;">
          <?php if ($s['btn1_text']): ?><a href="<?= htmlspecialchars($s['btn1_url']) ?>" class="btn btn--primary"><?= htmlspecialchars($s['btn1_text']) ?></a><?php endif; ?>
          <?php if ($s['btn2_text']): ?><a href="<?= htmlspecialchars($s['btn2_url']) ?>" class="btn btn--secondary" style="background:transparent;border:2px solid var(--color-taupe);color:var(--color-taupe);"><?= htmlspecialchars($s['btn2_text']) ?></a><?php endif; ?>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  <?php else: ?>
    <div style="position:absolute;inset:0;display:grid;place-items:center;color:white;font-size:24px;">Belum ada slide.</div>
  <?php endif; ?>

  <!-- Carousel controls -->
  <?php if (count($slides) > 1): ?>
  <button class="carousel-prev" id="carouselPrev" aria-label="Sebelumnya"
          style="position:absolute;left:20px;top:50%;transform:translateY(-50%);z-index:10;width:44px;height:44px;border-radius:50%;border:none;background:rgba(62,92,118,0.1);backdrop-filter:blur(6px);cursor:pointer;display:grid;place-items:center;color:var(--color-blue);transition:background 0.3s;">
    <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
  </button>
  <button class="carousel-next" id="carouselNext" aria-label="Selanjutnya"
          style="position:absolute;right:20px;top:50%;transform:translateY(-50%);z-index:10;width:44px;height:44px;border-radius:50%;border:none;background:rgba(62,92,118,0.1);backdrop-filter:blur(6px);cursor:pointer;display:grid;place-items:center;color:var(--color-blue);transition:background 0.3s;">
    <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
  </button>

  <!-- Dot indicators -->
  <div style="position:absolute;bottom:40px;left:50%;transform:translateX(-50%);display:flex;gap:8px;z-index:10;">
    <?php foreach ($slides as $i => $s): ?>
    <button class="carousel-dot <?= $i === 0 ? 'active' : '' ?>" style="width:10px;height:10px;border-radius:50%;border:none;background:var(--color-blue);opacity:<?= $i===0?'1':'0.3' ?>;cursor:pointer;transition:all 0.3s;padding:0;"></button>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<!-- â”€â”€ Produk Unggulan â”€â”€ -->
<section class="section" id="produk">
  <div class="container">
    <h2 class="section__title">Produk Unggulan</h2>
    <p class="section__sub">Laptop pilihan terbaik dengan garansi resmi dan harga kompetitif</p>

    <div class="grid-3">
      <?php foreach ($produk_unggulan as $p): ?>
      <div class="product-card" id="produk-<?= $p['id'] ?>">
        <img class="product-card__img" src="<?= $p['gambar'] ?>" alt="<?= htmlspecialchars($p['nama']) ?>"
             onerror="this.src='https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=600&q=80'">
        <div class="product-card__body">
          <div class="product-card__name"><?= htmlspecialchars($p['nama']) ?></div>
          <div class="product-card__desc"><?= htmlspecialchars($p['deskripsi']) ?></div>
          <div class="product-card__price">Rp <?= number_format($p['harga'], 0, ',', '.') ?></div>
        </div>
        <div class="product-card__footer">
          <a href="user/katalog.php?id=<?= $p['id'] ?>" class="btn btn--primary btn--sm" id="btnBeli-<?= $p['id'] ?>">Beli Sekarang</a>
          <a href="user/katalog.php?id=<?= $p['id'] ?>" class="btn btn--secondary btn--sm" id="btnDetail-<?= $p['id'] ?>">Detail</a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="text-center mt-4">
      <a href="user/katalog.php" class="btn btn--outline btn--lg" id="btnLihatSemua">Lihat Semua Produk</a>
    </div>
  </div>
</section>

<!-- â”€â”€ Kategori Cards â”€â”€ -->
<section class="section section--ash">
  <div class="container">
    <h2 class="section__title">Jelajahi Kategori</h2>
    <p class="section__sub">Temukan laptop yang tepat sesuai kebutuhan kamu</p>
    <div class="grid-2" style="gap:12px;">
      <a href="user/katalog.php?tipe=gaming" class="card card--category" id="catGaming" style="aspect-ratio:16/8;">
        <img src="https://images.unsplash.com/photo-1593642702821-c8da6771f0c6?w=800&q=80" alt="Gaming Laptop"
             onerror="this.parentElement.style.background='var(--color-blue)'">
        <div class="card__label" style="font-size:20px;font-weight:600;">Gaming</div>
      </a>
      <div style="display:grid;gap:12px;">
        <a href="user/katalog.php?tipe=bisnis" class="card card--category" id="catBisnis" style="aspect-ratio:16/5;">
          <img src="https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=800&q=80" alt="Bisnis Laptop"
               onerror="this.parentElement.style.background='var(--color-taupe)'">
          <div class="card__label" style="font-size:17px;font-weight:600;">Bisnis & Profesional</div>
        </a>
        <a href="user/katalog.php?tipe=pelajar" class="card card--category" id="catPelajar" style="aspect-ratio:16/5;">
          <img src="https://images.unsplash.com/photo-1588702547923-7093a6c3ba33?w=800&q=80" alt="Laptop Pelajar"
               onerror="this.parentElement.style.background='var(--color-cloud)'">
          <div class="card__label" style="font-size:17px;font-weight:600;color:var(--color-carbon);">Pelajar & Mahasiswa</div>
        </a>
      </div>
    </div>
  </div>
</section>

<!-- â”€â”€ Layanan Servis â”€â”€ -->
<section class="section" id="servis-info">
  <div class="container">
    <h2 class="section__title"><?= htmlspecialchars(getSetHome('servis_judul', 'Layanan Servis Profesional')) ?></h2>
    <p class="section__sub"><?= htmlspecialchars(getSetHome('servis_deskripsi', 'Percayakan laptop kamu pada teknisi berpengalaman kami')) ?></p>
    <div class="grid-3" style="text-align:center;gap:32px;">

      <div style="padding:24px 16px;">
        <div style="width:56px;height:56px;border-radius:50%;background:rgba(62,92,118,0.1);display:grid;place-items:center;margin:0 auto 16px;color:var(--color-blue);">
          <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z"/></svg>
        </div>
        <h3 style="font-size:17px;font-weight:500;color:var(--color-carbon);margin-bottom:8px;"><?= htmlspecialchars(getSetHome('servis_fitur1_judul', 'Perbaikan Hardware')) ?></h3>
        <p style="font-size:14px;color:var(--color-pewter);line-height:1.6;"><?= htmlspecialchars(getSetHome('servis_fitur1_desc', 'Layar retak, keyboard rusak, baterai drop, motherboard bermasalah — semua kami tangani.')) ?></p>
      </div>

      <div style="padding:24px 16px;">
        <div style="width:56px;height:56px;border-radius:50%;background:rgba(62,92,118,0.1);display:grid;place-items:center;margin:0 auto 16px;color:var(--color-blue);">
          <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0H3"/></svg>
        </div>
        <h3 style="font-size:17px;font-weight:500;color:var(--color-carbon);margin-bottom:8px;"><?= htmlspecialchars(getSetHome('servis_fitur2_judul', 'Instal & Optimasi OS')) ?></h3>
        <p style="font-size:14px;color:var(--color-pewter);line-height:1.6;"><?= htmlspecialchars(getSetHome('servis_fitur2_desc', 'Instal ulang Windows/Linux, optimasi performa, hapus virus & malware secara tuntas.')) ?></p>
      </div>

      <div style="padding:24px 16px;">
        <div style="width:56px;height:56px;border-radius:50%;background:rgba(62,92,118,0.1);display:grid;place-items:center;margin:0 auto 16px;color:var(--color-blue);">
          <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/></svg>
        </div>
        <h3 style="font-size:17px;font-weight:500;color:var(--color-carbon);margin-bottom:8px;"><?= htmlspecialchars(getSetHome('servis_fitur3_judul', 'Garansi 30 Hari')) ?></h3>
        <p style="font-size:14px;color:var(--color-pewter);line-height:1.6;"><?= htmlspecialchars(getSetHome('servis_fitur3_desc', 'Setiap pengerjaan dilindungi garansi 30 hari. Jika ada masalah, kami perbaiki tanpa biaya tambahan.')) ?></p>
      </div>
    </div>

    <div class="text-center mt-4">
      <a href="user/request_servis.php" class="btn btn--primary btn--lg" id="btnRequestServis">Ajukan Servis Sekarang</a>
    </div>
  </div>
</section>

<!-- â”€â”€ Tentang Kami â”€â”€ -->
<section class="section section--ash" id="tentang">
  <div class="container">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:64px;align-items:center;">
      <div>
        <h2 style="font-size:32px;font-weight:500;color:var(--color-carbon);margin-bottom:16px;"><?= htmlspecialchars(getSetHome('tentang_judul', 'Mengapa Memilih A-LINKS?')) ?></h2>
        <p style="font-size:14px;color:var(--color-pewter);line-height:1.7;margin-bottom:24px;">
          <?= nl2br(htmlspecialchars(getSetHome('tentang_deskripsi', 'A-LINKS hadir sebagai solusi lengkap untuk kebutuhan laptop Anda.'))) ?>
        </p>
        <div style="display:flex;flex-direction:column;gap:12px;">
          <?php 
          $poins = [
              getSetHome('tentang_poin1', '500+ Produk Tersedia'),
              getSetHome('tentang_poin2', 'Teknisi Bersertifikat'),
              getSetHome('tentang_poin3', 'Pengiriman ke Seluruh Indonesia'),
              getSetHome('tentang_poin4', 'Layanan 7 Hari Seminggu')
          ];
          foreach (array_filter($poins) as $item): ?>
          <div style="display:flex;align-items:center;gap:10px;">
            <div style="width:20px;height:20px;border-radius:50%;background:var(--color-blue);display:grid;place-items:center;flex-shrink:0;">
              <svg width="10" height="10" viewBox="0 0 10 10" fill="none"><path d="M2 5l2 2 4-4" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </div>
            <span style="font-size:14px;color:var(--color-graphite);"><?= htmlspecialchars($item) ?></span>
          </div>
          <?php endforeach; ?>
        </div>
        <div style="margin-top:32px;">
          <a href="register.php" class="btn btn--primary" id="btnDaftarTentang">Mulai Sekarang</a>
        </div>
      </div>
      <div style="position:relative;">
        <img src="<?= htmlspecialchars(getSetHome('tentang_gambar', 'https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?w=600&q=80')) ?>" 
             alt="Tim A-LINKS"
             style="width:100%;border-radius:12px;object-fit:cover;aspect-ratio:4/3;"
             onerror="this.style.background='var(--color-light-ash)'">
      </div>
    </div>
  </div>
</section>

<!-- â”€â”€ Stats â”€â”€ -->
<section class="section" id="kontak">
  <div class="container">
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:24px;text-align:center;margin-bottom:64px;">
      <?php 
      $stats = [
          [getSetHome('stat1_nilai', '500+'), getSetHome('stat1_label', 'Produk')],
          [getSetHome('stat2_nilai', '1000+'), getSetHome('stat2_label', 'Pelanggan')],
          [getSetHome('stat3_nilai', '50+'), getSetHome('stat3_label', 'Merk')],
          [getSetHome('stat4_nilai', '30'), getSetHome('stat4_label', 'Hari Garansi')]
      ];
      foreach ($stats as [$val,$lbl]): ?>
      <div>
        <div style="font-size:36px;font-weight:600;color:var(--color-blue);margin-bottom:4px;"><?= htmlspecialchars($val) ?></div>
        <div style="font-size:14px;color:var(--color-pewter);"><?= htmlspecialchars($lbl) ?></div>
      </div>
      <?php endforeach; ?>
    </div>

    <h2 class="section__title">Hubungi Kami</h2>
    <p class="section__sub">Punya pertanyaan? Tim kami siap membantu</p>
    
    <div style="text-align:center;margin-bottom:24px;">
      <p style="font-size:15px;color:var(--color-carbon);max-width:600px;margin:0 auto;line-height:1.6;">
        <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="var(--color-blue)" stroke-width="2" style="vertical-align:middle;margin-right:6px;margin-top:-2px;"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
        <strong>Alamat Toko Kami:</strong><br>
        <span style="color:var(--color-pewter);"><?= nl2br(htmlspecialchars(getSetHome('alamat', '73RH+PG6, Jl. Yos Sudarso...'))) ?></span>
      </p>
    </div>

    <div style="max-width:480px;margin:0 auto;display:flex;flex-direction:column;gap:16px;">
      <div class="form-group">
        <label class="form-label" for="contactName">Nama</label>
        <input class="form-control" id="contactName" type="text" placeholder="Nama lengkap Anda">
      </div>
      <div class="form-group">
        <label class="form-label" for="contactEmail">Email</label>
        <input class="form-control" id="contactEmail" type="email" placeholder="email@contoh.com">
      </div>
      <div class="form-group">
        <label class="form-label" for="contactMsg">Pesan</label>
        <textarea class="form-control form-control--textarea" id="contactMsg" placeholder="Tulis pesan Anda..."></textarea>
      </div>
      <button class="btn btn--primary btn--full" id="btnKirimPesan" type="button" onclick="kirimPesanWA()">Kirim Pesan ke WhatsApp</button>
    </div>
    <script>
      function kirimPesanWA() {
        const nama = document.getElementById('contactName').value;
        const email = document.getElementById('contactEmail').value;
        const msg = document.getElementById('contactMsg').value;
        
        if(!nama || !msg) {
          alert('Mohon isi nama dan pesan Anda terlebih dahulu.');
          return;
        }
        
        const adminWA = "<?= preg_replace('/[^0-9]/', '', getSetHome('no_wa', '6281216851726')) ?>";
        const text = `Halo <?= addslashes(htmlspecialchars(getSetHome('nama_toko', 'A-LINKS'))) ?>,\n\nNama: ${nama}\nEmail: ${email}\n\nPesan:\n${msg}`;
        const url = `https://wa.me/${adminWA}?text=${encodeURIComponent(text)}`;
        window.open(url, '_blank');
      }
    </script>
    </div>
  </div>
</section>

<!-- â”€â”€ Persistent Chat Bar â”€â”€ -->
<div class="chat-bar" id="chatBar">
  <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="var(--color-blue)" stroke-width="1.8" style="flex-shrink:0;">
    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 01-.825-.242m9.345-8.334a2.126 2.126 0 00-.476-.095 48.64 48.64 0 00-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0011.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V20.25a.75.75 0 001.28.53l3.58-3.58A48.45 48.45 0 0011.25 17c.896 0 1.78-.044 2.651-.126"/>
  </svg>
  <div class="chat-bar__input-wrap">
    <input class="chat-bar__input" id="chatInput" type="text" placeholder="Tanya sesuatu... (cth: 'Laptop gaming murah?')" />
    <button class="chat-bar__send" id="chatSendBtn" aria-label="Kirim">
      <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/></svg>
    </button>
  </div>
  <a href="user/request_servis.php" class="btn btn--primary btn--sm" id="chatScheduleBtn" style="white-space:nowrap;flex-shrink:0;">
    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="color:#4ade80;"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 9v7.5"/></svg>
    Jadwalkan Servis
  </a>
</div>

<style>
/* Carousel slide active state */
.hero-carousel__slide.active { opacity: 1 !important; }
.carousel-dot.active { opacity: 1 !important; width: 20px !important; border-radius: 4px !important; }
@media (max-width: 768px) {
  [style*="grid-template-columns:1fr 1fr"] { grid-template-columns: 1fr !important; gap: 32px !important; }
  [style*="grid-template-columns:repeat(4"] { grid-template-columns: repeat(2,1fr) !important; }
}
/* Carousel hover buttons */
#carouselPrev:hover, #carouselNext:hover { background: rgba(255,255,255,0.35) !important; }
/* Chat bar space at bottom */
body { padding-bottom: 64px; }
</style>

<?php include 'includes/footer.php'; ?>
