{
  "proyek": "Aplikasi Jual dan Servis Laptop",
  "teknologi": {
    "backend": "PHP Native",
    "database": "MySQL",
    "frontend": "HTML, CSS, JavaScript (merujuk pada panduan desain.md)",
    "payment_gateway": "Midtrans / Xendit / Tripay (Rekomendasi API untuk Indonesia)"
  },
  "struktur_folder_rekomendasi": {
    "config": "Menyimpan file koneksi database (koneksi.php) dan konfigurasi global",
    "assets": "Menyimpan file CSS, JS, dan gambar yang diekstrak dari desain.md",
    "includes": "Menyimpan komponen berulang seperti header.php, sidebar.php, dan footer.php",
    "admin": "Direktori khusus untuk halaman dan proses administrator",
    "user": "Direktori khusus untuk halaman dan proses pelanggan",
    "api": "Direktori untuk menerima notifikasi/webhook dari Payment Gateway"
  },
  "skema_database": [
    {
      "tabel": "users",
      "kolom": ["id_user (PK)", "nama", "email", "password", "role (enum: admin, user)", "no_telp", "alamat"]
    },
    {
      "tabel": "produk",
      "kolom": ["id_produk (PK)", "nama_laptop", "deskripsi", "harga", "stok", "gambar", "created_at"]
    },
    {
      "tabel": "servis",
      "kolom": ["id_servis (PK)", "id_user (FK)", "tipe_laptop", "keluhan", "status (pending, proses, selesai, diambil)", "biaya", "tgl_masuk", "tgl_selesai"]
    },
    {
      "tabel": "transaksi",
      "kolom": ["id_transaksi (PK)", "id_user (FK)", "order_id (untuk Payment Gateway)", "total_harga", "status_pembayaran (unpaid, paid, failed)", "tipe_pembayaran", "waktu_transaksi"]
    },
    {
      "tabel": "detail_transaksi",
      "kolom": ["id_detail (PK)", "id_transaksi (FK)", "id_produk (FK)", "jumlah", "harga_satuan"]
    }
  ],
  "daftar_halaman": {
    "publik_dan_autentikasi": [
      "index.php (Landing Page utama)",
      "login.php (Form masuk dengan validasi role)",
      "register.php (Form pendaftaran akun user)",
      "logout.php (Menghancurkan session)"
    ],
    "halaman_admin": [
      "admin/dashboard.php (Statistik jumlah pesanan, pendapatan, dan servis aktif)",
      "admin/kelola_produk.php (CRUD data laptop yang dijual)",
      "admin/kelola_servis.php (Menerima, mengubah status, dan menetapkan biaya servis)",
      "admin/kelola_transaksi.php (Melihat riwayat pesanan dan status pembayaran)",
      "admin/kelola_user.php (Melihat dan mengelola data pelanggan)"
    ],
    "halaman_user": [
      "user/dashboard.php (Ringkasan akun pelanggan)",
      "user/katalog.php (Melihat daftar laptop dan detail produk)",
      "user/keranjang.php (Menyimpan sementara produk yang akan dibeli menggunakan PHP Session)",
      "user/checkout.php (Halaman pengisian alamat dan pemilihan metode pembayaran)",
      "user/request_servis.php (Formulir pengajuan perbaikan laptop)",
      "user/riwayat.php (Melihat status pesanan produk dan progres servis)"
    ]
  },
  "tahapan_pengembangan": [
    {
      "fase": "1. Analisis & Integrasi Desain",
      "deskripsi": "Membaca panduan desain.md, mengonversi desain UI/UX menjadi template HTML/CSS statis, dan merapikannya ke dalam folder assets."
    },
    {
      "fase": "2. Setup Database & Struktur Direktori",
      "deskripsi": "Membuat database MySQL sesuai skema di atas dan menyiapkan struktur folder proyek serta file koneksi (mysqli/PDO)."
    },
    {
      "fase": "3. Sistem Autentikasi (Login/Register)",
      "deskripsi": "Membangun sistem registrasi (dengan enkripsi password_hash) dan login. Menerapkan session PHP untuk memisahkan hak akses antara 'admin' dan 'user'."
    },
    {
      "fase": "4. Pengembangan Modul Admin (Backend)",
      "deskripsi": "Membuat fungsi Create, Read, Update, Delete (CRUD) menggunakan PHP Native dan MySQLi Prepared Statements untuk mengamankan input data produk dan servis."
    },
    {
      "fase": "5. Pengembangan Modul User & Keranjang Belanja",
      "deskripsi": "Membuat katalog dinamis yang mengambil data dari database, fitur add-to-cart menggunakan session, dan sistem input form untuk request servis laptop."
    },
    {
      "fase": "6. Integrasi Payment Gateway",
      "deskripsi": "Menghubungkan sistem checkout dengan API Payment Gateway. Membuat file webhook (misal: api/payment_notification.php) untuk menerima respon otomatis dari gateway dan mengubah status_pembayaran di database."
    },
    {
      "fase": "7. Keamanan, Testing & Deployment",
      "deskripsi": "Memastikan tidak ada celah SQL Injection atau XSS, melakukan User Acceptance Testing (UAT), dan mengunggah (deploy) aplikasi ke hosting menggunakan cPanel/FTP."
    }
  ]
}