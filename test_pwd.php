<?php
echo password_verify('admin123', '$2y$10$ZdP43.FOfNd6U.P0rysdQeVDv5WaUhF7b6kcZZHs1kDRL5YvU5TVS') ? "admin123 OK\n" : "admin123 FAIL\n";
echo password_verify('user123', '$2y$10$rHRkCovL8iu3hpO5OHtWH.N0pLHF9wq3NkYCM9yKDpeaNwYOrN74.') ? "user123 OK\n" : "user123 FAIL\n";
