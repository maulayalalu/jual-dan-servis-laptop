<?php
session_start();
$_SESSION['id_user'] = 1;
$_SESSION['role'] = 'owner';
require_once 'config/koneksi.php';

echo "Before requireStaff\n";
requireStaff();
echo "After requireStaff\n";
