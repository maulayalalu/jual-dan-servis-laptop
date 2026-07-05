<?php
session_start();
require_once '../config/koneksi.php';
requireUser();

$id_transaksi = (int)($_GET['id'] ?? 0);
$id_user = $_SESSION['id_user'];

// Cek apakah transaksi milik user ini (atau admin bisa melihat semua)
if ($_SESSION['role'] === 'admin') {
    $stmt = $koneksi->prepare("SELECT t.*, u.nama, u.email, u.no_telp, u.alamat FROM transaksi t JOIN users u ON t.id_user=u.id_user WHERE t.id_transaksi=?");
    $stmt->bind_param('i', $id_transaksi);
} else {
    $stmt = $koneksi->prepare("SELECT t.*, u.nama, u.email, u.no_telp, u.alamat FROM transaksi t JOIN users u ON t.id_user=u.id_user WHERE t.id_transaksi=? AND t.id_user=?");
    $stmt->bind_param('ii', $id_transaksi, $id_user);
}
$stmt->execute();
$transaksi = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$transaksi) {
    die("Data pesanan tidak ditemukan atau Anda tidak memiliki akses.");
}

// Ambil detail produk
$stmtDet = $koneksi->prepare("SELECT d.*, p.nama_laptop FROM detail_transaksi d JOIN produk p ON d.id_produk=p.id_produk WHERE d.id_transaksi=?");
$stmtDet->bind_param('i', $id_transaksi);
$stmtDet->execute();
$details = $stmtDet->get_result();
$stmtDet->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice <?= htmlspecialchars($transaksi['order_id']) ?> - A-LINKS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --blue: #3E5C76;
            --dark: #2B4158;
            --gray: #6b7280;
            --light-gray: #f3f4f6;
            --border: #e5e7eb;
        }
        body {
            font-family: 'Inter', sans-serif;
            color: var(--dark);
            margin: 0;
            padding: 0;
            background: #F5F1E8;
            -webkit-font-smoothing: antialiased;
        }
        .invoice-box {
            max-width: 800px;
            margin: 40px auto;
            padding: 40px;
            background: #fff;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            border-radius: 8px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid var(--light-gray);
            padding-bottom: 24px;
            margin-bottom: 32px;
        }
        .brand {
            font-size: 28px;
            font-weight: 800;
            letter-spacing: -1px;
            color: var(--blue);
            margin: 0;
            text-transform: uppercase;
        }
        .brand-sub {
            font-size: 13px;
            color: var(--gray);
            margin-top: 4px;
        }
        .invoice-title {
            text-align: right;
        }
        .invoice-title h1 {
            font-size: 24px;
            font-weight: 700;
            margin: 0 0 8px 0;
            text-transform: uppercase;
            color: var(--dark);
            letter-spacing: 1px;
        }
        .invoice-title p {
            margin: 0;
            font-size: 14px;
            color: var(--gray);
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-bottom: 40px;
        }
        .info-section h3 {
            font-size: 12px;
            text-transform: uppercase;
            color: var(--gray);
            letter-spacing: 1px;
            margin: 0 0 12px 0;
            border-bottom: 1px solid var(--border);
            padding-bottom: 8px;
        }
        .info-content {
            font-size: 14px;
            line-height: 1.6;
        }
        .info-content strong {
            font-weight: 600;
            color: var(--dark);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 32px;
        }
        th {
            text-align: left;
            padding: 12px 16px;
            background: var(--light-gray);
            font-size: 13px;
            font-weight: 600;
            color: var(--dark);
            border-top: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
        }
        td {
            padding: 16px;
            font-size: 14px;
            border-bottom: 1px solid var(--border);
            color: var(--dark);
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .totals {
            width: 300px;
            margin-left: auto;
        }
        .totals-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 14px;
            color: var(--gray);
        }
        .totals-row.grand-total {
            font-size: 18px;
            font-weight: 700;
            color: var(--blue);
            border-top: 2px solid var(--light-gray);
            padding-top: 16px;
            margin-top: 8px;
        }
        .footer {
            text-align: center;
            margin-top: 60px;
            padding-top: 24px;
            border-top: 1px solid var(--light-gray);
            font-size: 13px;
            color: var(--gray);
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .badge-paid { background: #dcfce7; color: #166534; }
        .badge-unpaid { background: #fef3c7; color: #92400e; }
        .badge-verify { background: #dbeafe; color: #1e40af; }
        .badge-failed { background: #fee2e2; color: #991b1b; }
        
        @media print {
            body { background: #fff; }
            .invoice-box { 
                margin: 0; 
                padding: 0; 
                box-shadow: none; 
                max-width: 100%;
            }
            @page { margin: 2cm; size: A4; }
            .no-print { display: none !important; }
        }
        
        .btn-print {
            display: inline-block;
            background: var(--blue);
            color: white;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-weight: 500;
            margin-bottom: 20px;
            font-size: 14px;
            transition: opacity 0.2s;
            border: none;
            cursor: pointer;
        }
        .btn-print:hover { opacity: 0.9; }
    </style>
</head>
<body>

<div style="text-align:center; padding-top:20px;" class="no-print">
    <button onclick="window.print()" class="btn-print">
        <svg style="vertical-align:middle;margin-right:6px;" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
        Cetak / Simpan PDF
    </button>
    <a href="riwayat.php?tab=transaksi" style="display:inline-block;margin-left:12px;color:var(--gray);text-decoration:none;font-size:14px;">Kembali</a>
</div>

<div class="invoice-box">
    <div class="header">
        <div>
            <div class="brand">A-LINKS</div>
            <div class="brand-sub">Platform E-Commerce Laptop Terpercaya</div>
            <div style="font-size:12px; color:var(--gray); margin-top:8px; max-width:320px; line-height:1.5;">
                <strong>A-LINKS Store</strong><br>
                73RH+PG6, Jl. Yos Sudarso, Jemb. Kembar, Kec. Lembar, Kabupaten Lombok Barat, Nusa Tenggara Bar. 83364
            </div>
        </div>
        <div class="invoice-title">
            <h1>INVOICE</h1>
            <p>Order ID: <strong><?= htmlspecialchars($transaksi['order_id']) ?></strong></p>
            <p>Tanggal: <?= date('d M Y, H:i', strtotime($transaksi['waktu_transaksi'])) ?></p>
        </div>
    </div>

    <div class="info-grid">
        <div class="info-section">
            <h3>Ditagihkan Kepada</h3>
            <div class="info-content">
                <strong><?= htmlspecialchars($transaksi['nama']) ?></strong><br>
                <?= htmlspecialchars($transaksi['alamat']) ?><br>
                Telp: <?= htmlspecialchars($transaksi['no_telp']) ?><br>
                Email: <?= htmlspecialchars($transaksi['email']) ?>
            </div>
        </div>
        <div class="info-section">
            <h3>Informasi Pembayaran</h3>
            <div class="info-content">
                <table style="width:auto;margin:0;table-layout:auto;">
                    <tr><td style="padding:4px 16px 4px 0;border:none;color:var(--gray);">Metode</td><td style="padding:4px 0;border:none;font-weight:500;"><?= htmlspecialchars(ucwords(str_replace('_',' ',$transaksi['tipe_pembayaran'] ?? 'Transfer Bank'))) ?></td></tr>
                    <tr><td style="padding:4px 16px 4px 0;border:none;color:var(--gray);">Status</td><td style="padding:4px 0;border:none;">
                        <?php
                        if ($transaksi['status_pembayaran'] === 'paid') echo '<span class="badge badge-paid">LUNAS</span>';
                        elseif ($transaksi['status_pembayaran'] === 'pending_verify') echo '<span class="badge badge-verify">MENUNGGU VERIFIKASI</span>';
                        elseif ($transaksi['status_pembayaran'] === 'failed') echo '<span class="badge badge-failed">DIBATALKAN</span>';
                        else echo '<span class="badge badge-unpaid">BELUM DIBAYAR</span>';
                        ?>
                    </td></tr>
                </table>
            </div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Deskripsi Produk</th>
                <th class="text-center">Kuantitas</th>
                <th class="text-right">Harga Satuan</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($d = $details->fetch_assoc()): ?>
            <tr>
                <td style="font-weight:500;"><?= htmlspecialchars($d['nama_laptop']) ?></td>
                <td class="text-center"><?= $d['jumlah'] ?></td>
                <td class="text-right"><?= formatRupiah($d['harga_satuan']) ?></td>
                <td class="text-right" style="font-weight:600;"><?= formatRupiah($d['jumlah'] * $d['harga_satuan']) ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <div class="totals">
        <div class="totals-row">
            <span>Subtotal</span>
            <span><?= formatRupiah($transaksi['total_harga']) ?></span>
        </div>
        <div class="totals-row">
            <span>Biaya Pengiriman</span>
            <span>Gratis</span>
        </div>
        <div class="totals-row grand-total">
            <span>Total Tagihan</span>
            <span><?= formatRupiah($transaksi['total_harga']) ?></span>
        </div>
    </div>

    <div class="footer">
        <p>Terima kasih telah berbelanja di A-LINKS.</p>
        <p style="font-size:11px;color:#9ca3af;margin-top:4px;">Invoice ini sah dan digenerate oleh sistem pada <?= date('d M Y H:i') ?></p>
    </div>
</div>

<script>
// Auto print if ?print=1 is in URL
if (new URLSearchParams(window.location.search).get('print') === '1') {
    window.onload = function() { window.print(); }
}
</script>

</body>
</html>
