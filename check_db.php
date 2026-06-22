<?php
require 'config/koneksi.php';
$res = $koneksi->query("DESCRIBE hero_slides");
while ($row = $res->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
