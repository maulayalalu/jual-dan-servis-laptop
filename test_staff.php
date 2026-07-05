<?php
session_start();
$_SESSION['id_user'] = 1;
$_SESSION['role'] = 'owner';

function isLoggedIn(): bool {
    return isset($_SESSION['id_user']);
}

function hasRole(string ...$roles): bool {
    var_dump($roles);
    return in_array($_SESSION['role'] ?? '', $roles, true);
}

function requireStaff(string ...$roles): void {
    $allowed = empty($roles) ? ['admin', 'owner', 'kasir'] : $roles;
    if (!isLoggedIn() || !hasRole(...$allowed)) {
        echo "Redirecting to login...\n";
    } else {
        echo "Access granted!\n";
    }
}

requireStaff();
requireStaff('admin', 'owner');
