<?php
require_once 'config/koneksi.php';

$res = $koneksi->query("SHOW TABLES");
$tables = [];
while ($row = $res->fetch_array()) {
    $tables[] = $row[0];
}
echo "Tables:\n" . implode("\n", $tables) . "\n";
