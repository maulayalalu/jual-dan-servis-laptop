<?php
session_start();
require 'config/koneksi.php';

$email = 'admin@alinks.id';
$password = 'admin123';

$stmt = $koneksi->prepare("SELECT id_user, nama, password, role FROM users WHERE email = ? LIMIT 1");
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();
$user   = $result->fetch_assoc();

if ($user && password_verify($password, $user['password'])) {
    echo "Login success for admin\n";
} else {
    echo "Login failed for admin\n";
}

$email = 'owner@alinks.id';
$password = 'owner123';

$stmt = $koneksi->prepare("SELECT id_user, nama, password, role FROM users WHERE email = ? LIMIT 1");
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();
$user   = $result->fetch_assoc();

if ($user && password_verify($password, $user['password'])) {
    echo "Login success for owner\n";
} else {
    echo "Login failed for owner\n";
}
