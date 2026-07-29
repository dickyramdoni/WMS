<?php
// ============================================
// Fungsi bantu umum
// ============================================

function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

function formatDate($date) {
    if (!$date) return '-';
    return date('d/m/Y', strtotime($date));
}

function formatDateTime($date) {
    if (!$date) return '-';
    return date('d/m/Y H:i', strtotime($date));
}

function formatNumber($num) {
    return number_format((float)$num, 0, ',', '.');
}

function formatMoney($num) {
    return 'Rp ' . number_format((float)$num, 0, ',', '.');
}

function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function redirect($url) {
    header('Location: ' . $url);
    exit;
}

// Generate nomor dokumen otomatis, contoh: PO-20260727-0001
function generateDocNumber(PDO $pdo, $table, $column, $prefix) {
    $date = date('Ymd');
    $like = $prefix . '-' . $date . '-%';
    $stmt = $pdo->prepare("SELECT $column FROM $table WHERE $column LIKE ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$like]);
    $last = $stmt->fetchColumn();
    if ($last) {
        $lastSeq = (int)substr($last, -4);
        $seq = $lastSeq + 1;
    } else {
        $seq = 1;
    }
    return $prefix . '-' . $date . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);
}

// Update stok (menambah atau mengurangi) sekaligus mencatat pergerakan stok
function updateStock(PDO $pdo, $productId, $locationId, $qtyChange, $movementType, $referenceType, $referenceId, $userId, $notes = null) {
    // Pastikan baris stok ada
    $stmt = $pdo->prepare("SELECT id, qty FROM stock WHERE product_id = ? AND location_id = ?");
    $stmt->execute([$productId, $locationId]);
    $row = $stmt->fetch();

    if ($row) {
        $newQty = $row['qty'] + $qtyChange;
        if ($newQty < 0) $newQty = 0;
        $upd = $pdo->prepare("UPDATE stock SET qty = ? WHERE id = ?");
        $upd->execute([$newQty, $row['id']]);
    } else {
        $newQty = max(0, $qtyChange);
        $ins = $pdo->prepare("INSERT INTO stock (product_id, location_id, qty) VALUES (?, ?, ?)");
        $ins->execute([$productId, $locationId, $newQty]);
    }

    $mv = $pdo->prepare("INSERT INTO stock_movements (product_id, location_id, movement_type, qty, reference_type, reference_id, notes, created_by)
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $mv->execute([$productId, $locationId, $movementType, abs($qtyChange), $referenceType, $referenceId, $notes, $userId]);
}

function getStockQty(PDO $pdo, $productId, $locationId) {
    $stmt = $pdo->prepare("SELECT qty FROM stock WHERE product_id = ? AND location_id = ?");
    $stmt->execute([$productId, $locationId]);
    $val = $stmt->fetchColumn();
    return $val !== false ? (int)$val : 0;
}

// Deteksi error duplikat/unique constraint, kompatibel untuk MySQL maupun SQLite
function isDuplicateKeyError($message) {
    return str_contains($message, 'Duplicate') || str_contains($message, 'UNIQUE constraint failed');
}
