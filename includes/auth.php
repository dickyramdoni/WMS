<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . APP_URL . '/login.php');
        exit;
    }
}

// Batasi akses berdasarkan role. Contoh: requireRole(['admin','supervisor'])
function requireRole(array $roles) {
    requireLogin();
    if (!in_array($_SESSION['role'], $roles, true)) {
        http_response_code(403);
        die('<div style="font-family:sans-serif;padding:40px;text-align:center;">
                <h2>403 - Akses Ditolak</h2>
                <p>Anda tidak memiliki izin untuk mengakses halaman ini.</p>
                <a href="' . APP_URL . '/dashboard.php">Kembali ke Dashboard</a>
             </div>');
    }
}

function currentUser() {
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? null,
        'full_name' => $_SESSION['full_name'] ?? null,
        'role' => $_SESSION['role'] ?? null,
    ];
}

// ============ CSRF PROTECTION ============
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token) {
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token ?? '')) {
        die('Token keamanan tidak valid (CSRF). Silakan muat ulang halaman dan coba lagi.');
    }
}

function csrfField() {
    $token = generateCsrfToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}
