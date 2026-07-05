<?php
// config/koneksi.php â€” Database connection (MySQLi + PDO)
// Gunakan $koneksi untuk MySQLi, $pdo untuk PDO

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'alinks_db');
define('DB_CHARSET', 'utf8mb4');

/* â”€â”€ MySQLi connection â”€â”€ */
$koneksi = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($koneksi->connect_error) {
    die('<div style="font-family:sans-serif;padding:40px;text-align:center;">
        <h2 style="color:#d92b2b;">âš  Koneksi Database Gagal</h2>
        <p style="color:#555;">Pastikan XAMPP/MySQL aktif dan database <strong>' . DB_NAME . '</strong> sudah dibuat.</p>
        <code style="display:block;margin-top:12px;color:#888;">' . htmlspecialchars($koneksi->connect_error) . '</code>
    </div>');
}

$koneksi->set_charset(DB_CHARSET);

/* â”€â”€ PDO connection (opsional, untuk query lanjutan) â”€â”€ */
try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    // PDO hanya diinisialisasi jika MySQLi sudah berhasil
    $pdo = null;
}

/* â”€â”€ Helper functions â”€â”€ */

/**
 * Sanitize input string
 */
function sanitize(string $str): string {
    return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}

/**
 * Format harga ke Rupiah
 */
function formatRupiah(int|float $harga): string {
    return 'Rp ' . number_format($harga, 0, ',', '.');
}

/**
 * Redirect helper
 */
function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

/**
 * Flash message (set)
 */
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = compact('type', 'message');
}

/**
 * Flash message (get & clear)
 */
function getFlash(): ?array {
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

/**
 * Render flash message HTML
 */
function renderFlash(): void {
    $flash = getFlash();
    if (!$flash) return;
    $typeMap = ['success'=>'success','error'=>'error','warning'=>'warning','info'=>'info'];
    $cls     = $typeMap[$flash['type']] ?? 'info';
    echo '<div class="alert alert--' . $cls . '" data-autohide>' . htmlspecialchars($flash['message']) . '</div>';
}

/**
 * Cek apakah user sudah login
 */
function isLoggedIn(): bool {
    return isset($_SESSION['id_user']);
}

/**
 * Paksa login â€” redirect ke login jika belum
 */
function requireLogin(string $redirect = '../login.php'): void {
    if (!isLoggedIn()) {
        redirect($redirect);
    }
}

/**
 * Cek apakah user memiliki salah satu dari role yang diberikan
 */
function hasRole(string ...$roles): bool {
    return in_array($_SESSION['role'] ?? '', $roles, true);
}

/**
 * Paksa role admin
 */
function requireAdmin(): void {
    if (!isLoggedIn() || !hasRole('admin')) {
        redirect('../login.php');
    }
}

/**
 * Paksa role owner (atau admin)
 */
function requireOwner(): void {
    if (!isLoggedIn() || !hasRole('admin', 'owner')) {
        redirect('../login.php');
    }
}

/**
 * Paksa role kasir (atau admin)
 */
function requireKasir(): void {
    if (!isLoggedIn() || !hasRole('admin', 'kasir')) {
        redirect('../login.php');
    }
}

/**
 * Paksa staff (admin, owner, atau kasir)
 * Opsional: batasi ke role tertentu saja
 * Contoh: requireStaff('admin','owner') hanya izinkan admin & owner
 */
function requireStaff(string ...$roles): void {
    $allowed = empty($roles) ? ['admin', 'owner', 'kasir'] : $roles;
    if (!isLoggedIn() || !hasRole(...$allowed)) {
        redirect('../login.php');
    }
}

/**
 * Paksa role user
 */
function requireUser(): void {
    if (!isLoggedIn() || !hasRole('user')) {
        redirect('../login.php');
    }
}

/**
 * Ambil label role yang ramah
 */
function getRoleLabel(string $role): string {
    return match($role) {
        'admin'  => 'Administrator',
        'owner'  => 'Owner',
        'kasir'  => 'Kasir',
        'user'   => 'Pelanggan',
        default  => ucfirst($role),
    };
}

/**
 * CSRF Token Generator
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * CSRF Token Verifier
 */
function verify_csrf(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
            die('<div style="font-family:sans-serif;padding:40px;text-align:center;"><h2 style="color:#d92b2b;">âš  Invalid CSRF Token</h2><p>Akses ditolak demi keamanan. Form kadaluarsa atau tidak valid.</p><a href="javascript:history.back()">Kembali</a></div>');
        }
    }
}
