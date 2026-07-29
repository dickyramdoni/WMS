<?php
// ============================================
// Konfigurasi Aplikasi - WMS
// ============================================
define('APP_NAME', 'Warehouse Management System');
define('APP_URL', 'https://dickyramdoni.github.io/WMS/'); // sesuaikan dengan folder instalasi

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Jakarta');
error_reporting(E_ALL);
ini_set('display_errors', 1);
