<?php
require 'config/koneksi.php';
$res = $koneksi->query("SELECT email, role, password FROM users");
while($r = $res->fetch_assoc()) {
    echo $r['email'] . ' - ' . $r['role'] . ' - ' . $r['password'] . "\n";
}
