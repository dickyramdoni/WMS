<?php
// ============================================
// Konfigurasi Database - WMS (SQLite)
// ============================================
// Database berupa 1 file: database/wms.sqlite
// Saat file ini belum ada, aplikasi akan otomatis membuatnya
// dan menjalankan skema dari database/wms_sqlite.sql - TIDAK perlu
// import manual lewat phpMyAdmin atau tool database lain.
// ============================================
define('DB_PATH', __DIR__ . '/../database/wms.sqlite');
define('DB_SCHEMA_FILE', __DIR__ . '/../database/wms_sqlite.sql');

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        $isNew = !file_exists(DB_PATH);
        try {
            $pdo = new PDO(
                'sqlite:' . DB_PATH,
                null,
                null,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
            // Aktifkan foreign key constraint (tidak aktif secara default di SQLite)
            $pdo->exec('PRAGMA foreign_keys = ON;');
            // WAL mode: mengurangi kemungkinan "database is locked" saat beberapa
            // proses baca/tulis bersamaan
            $pdo->exec('PRAGMA journal_mode = WAL;');

            if ($isNew) {
                if (!is_writable(dirname(DB_PATH))) {
                    die('Folder "database/" tidak bisa ditulis. Berikan izin write (chmod 775) pada folder tersebut lalu muat ulang halaman.');
                }
                $schema = file_get_contents(DB_SCHEMA_FILE);
                if ($schema === false) {
                    die('File skema database/wms_sqlite.sql tidak ditemukan.');
                }
                $pdo->exec($schema);
            }
        } catch (PDOException $e) {
            die('Koneksi database gagal: ' . $e->getMessage());
        }
    }
    return $pdo;
}
