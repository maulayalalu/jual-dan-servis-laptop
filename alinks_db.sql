-- phpMyAdmin SQL Dump
-- version 5.2.1
-- Host: 127.0.0.1
-- Database: alinks_db
-- --------------------------------------------------------

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+07:00";

-- --------------------------------------------------------
-- Table structure for table `users`
-- --------------------------------------------------------
CREATE TABLE `users` (
  `id_user` int(11) NOT NULL AUTO_INCREMENT,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') NOT NULL DEFAULT 'user',
  `no_telp` varchar(20) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  PRIMARY KEY (`id_user`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Dumping data for table `users`
-- Admin pwd: admin123 | User pwd: user123
INSERT INTO `users` (`nama`, `email`, `password`, `role`, `no_telp`, `alamat`) VALUES
('Administrator', 'admin@alinks.id', '$2y$10$wTfA2qP/j8q2aO2D.S.yq.T74mF8A2m4O5b0Q5f5C8z0A5x6I4HlG', 'admin', '081234567890', 'Gedung IT Center, Lantai 2'),
('User Demo', 'user@alinks.id', '$2y$10$tZ2E2H.N.N9T8a/O3h8W8uq3P9Z4Z5x5X5b5X5A5z8O5b0Q5f5C8z', 'user', '089876543210', 'Jl. Merdeka No. 45, Jakarta Selatan');

-- --------------------------------------------------------
-- Table structure for table `produk`
-- --------------------------------------------------------
CREATE TABLE `produk` (
  `id_produk` int(11) NOT NULL AUTO_INCREMENT,
  `nama_laptop` varchar(150) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `harga` decimal(15,2) NOT NULL,
  `stok` int(11) NOT NULL DEFAULT 0,
  `gambar` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_produk`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Dumping data for table `produk`
INSERT INTO `produk` (`nama_laptop`, `deskripsi`, `harga`, `stok`, `gambar`) VALUES
('ASUS ROG Strix G16', 'Laptop gaming premium dengan prosesor Intel Core i7 Generasi ke-13, NVIDIA GeForce RTX 4060, RAM 16GB DDR5, dan penyimpanan 512GB SSD PCIe Gen4. Layar 16 inci WUXGA 165Hz memberikan visual mulus.', 18500000.00, 15, 'https://images.unsplash.com/photo-1593642632559-0c6d3fc62b89?w=600&q=80'),
('MacBook Air M3', 'Desain tipis ringan dengan chip Apple M3 yang sangat bertenaga. RAM 8GB dan penyimpanan 256GB SSD. Layar Liquid Retina 13,6 inci menghadirkan warna cemerlang. Baterai tahan hingga 18 jam.', 22000000.00, 8, 'https://images.unsplash.com/photo-1541807084-5c52b6b3adef?w=600&q=80'),
('Lenovo ThinkPad X1 Carbon', 'Laptop bisnis legendaris dengan bodi karbon yang kokoh. Dilengkapi prosesor Intel Core i7 vPro, RAM 16GB, dan 512GB SSD. Keyboard ergonomis terbaik di kelasnya dengan fitur keamanan tinggi.', 16750000.00, 5, 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=600&q=80'),
('Acer Aspire 5', 'Pilihan ideal untuk pelajar dan mahasiswa. Prosesor Intel Core i5, RAM 8GB, dan 512GB SSD NVMe. Performa responsif untuk multitasking sehari-hari dengan harga yang terjangkau.', 7500000.00, 20, 'https://images.unsplash.com/photo-1588702547923-7093a6c3ba33?w=600&q=80');

-- --------------------------------------------------------
-- Table structure for table `servis`
-- --------------------------------------------------------
CREATE TABLE `servis` (
  `id_servis` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) NOT NULL,
  `tipe_laptop` varchar(150) NOT NULL,
  `keluhan` text NOT NULL,
  `status` enum('pending','proses','selesai','diambil') NOT NULL DEFAULT 'pending',
  `biaya` decimal(15,2) DEFAULT 0.00,
  `tgl_masuk` date NOT NULL,
  `tgl_selesai` date DEFAULT NULL,
  PRIMARY KEY (`id_servis`),
  KEY `id_user` (`id_user`),
  CONSTRAINT `fk_servis_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Dumping data for table `servis`
INSERT INTO `servis` (`id_user`, `tipe_laptop`, `keluhan`, `status`, `biaya`, `tgl_masuk`, `tgl_selesai`) VALUES
(2, 'Lenovo IdeaPad Gaming 3', 'Kipas berisik dan laptop cepat panas saat main game. Kadang blue screen.', 'proses', 0.00, '2024-10-15', NULL),
(2, 'HP Pavilion X360', 'Layar sentuh tidak berfungsi di bagian pojok kanan atas.', 'selesai', 450000.00, '2024-10-10', '2024-10-12');

-- --------------------------------------------------------
-- Table structure for table `transaksi`
-- --------------------------------------------------------
CREATE TABLE `transaksi` (
  `id_transaksi` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) NOT NULL,
  `order_id` varchar(50) NOT NULL,
  `total_harga` decimal(15,2) NOT NULL,
  `status_pembayaran` enum('unpaid','paid','failed') NOT NULL DEFAULT 'unpaid',
  `tipe_pembayaran` varchar(50) DEFAULT NULL,
  `waktu_transaksi` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_transaksi`),
  UNIQUE KEY `order_id` (`order_id`),
  KEY `id_user` (`id_user`),
  CONSTRAINT `fk_transaksi_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Dumping data for table `transaksi`
INSERT INTO `transaksi` (`id_user`, `order_id`, `total_harga`, `status_pembayaran`, `tipe_pembayaran`, `waktu_transaksi`) VALUES
(2, 'ORD-1718000000-1234', 18500000.00, 'paid', 'bank_transfer', '2024-10-14 08:30:00');

-- --------------------------------------------------------
-- Table structure for table `detail_transaksi`
-- --------------------------------------------------------
CREATE TABLE `detail_transaksi` (
  `id_detail` int(11) NOT NULL AUTO_INCREMENT,
  `id_transaksi` int(11) NOT NULL,
  `id_produk` int(11) NOT NULL,
  `jumlah` int(11) NOT NULL,
  `harga_satuan` decimal(15,2) NOT NULL,
  PRIMARY KEY (`id_detail`),
  KEY `id_transaksi` (`id_transaksi`),
  KEY `id_produk` (`id_produk`),
  CONSTRAINT `fk_dt_transaksi` FOREIGN KEY (`id_transaksi`) REFERENCES `transaksi` (`id_transaksi`) ON DELETE CASCADE,
  CONSTRAINT `fk_dt_produk` FOREIGN KEY (`id_produk`) REFERENCES `produk` (`id_produk`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Dumping data for table `detail_transaksi`
INSERT INTO `detail_transaksi` (`id_transaksi`, `id_produk`, `jumlah`, `harga_satuan`) VALUES
(1, 1, 1, 18500000.00);

COMMIT;
