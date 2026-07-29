-- =============================================
-- Warehouse Management System (WMS) - Skema SQLite
-- Skema ini otomatis dijalankan oleh aplikasi saat file
-- database/wms.sqlite belum ada (lihat config/database.php).
-- Tidak perlu dijalankan manual kecuali Anda ingin membuat ulang dari nol.
-- =============================================

PRAGMA foreign_keys = ON;

-- ============ USERS ============
CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL,
    full_name TEXT NOT NULL,
    role TEXT NOT NULL DEFAULT 'staff' CHECK (role IN ('admin','supervisor','staff')),
    is_active INTEGER NOT NULL DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ============ MASTER DATA ============
CREATE TABLE categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL
);

CREATE TABLE products (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    sku TEXT NOT NULL UNIQUE,
    name TEXT NOT NULL,
    category_id INTEGER NULL,
    unit TEXT NOT NULL DEFAULT 'pcs',
    min_stock INTEGER NOT NULL DEFAULT 0,
    is_active INTEGER NOT NULL DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

CREATE TABLE warehouses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code TEXT NOT NULL UNIQUE,
    name TEXT NOT NULL,
    address TEXT NULL,
    is_active INTEGER NOT NULL DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE locations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    warehouse_id INTEGER NOT NULL,
    code TEXT NOT NULL UNIQUE,
    name TEXT NOT NULL,
    zone TEXT NULL,
    rack TEXT NULL,
    description TEXT NULL,
    is_active INTEGER NOT NULL DEFAULT 1,
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(id)
);

CREATE TABLE suppliers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    contact_person TEXT NULL,
    phone TEXT NULL,
    address TEXT NULL
);

CREATE TABLE customers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    contact_person TEXT NULL,
    phone TEXT NULL,
    address TEXT NULL
);

-- ============ STOCK ============
CREATE TABLE stock (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_id INTEGER NOT NULL,
    location_id INTEGER NOT NULL,
    qty INTEGER NOT NULL DEFAULT 0,
    UNIQUE (product_id, location_id),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE CASCADE
);

CREATE TABLE stock_movements (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_id INTEGER NOT NULL,
    location_id INTEGER NOT NULL,
    movement_type TEXT NOT NULL CHECK (movement_type IN ('in','out','transfer_in','transfer_out','adjustment')),
    qty INTEGER NOT NULL,
    reference_type TEXT NULL,
    reference_id INTEGER NULL,
    notes TEXT NULL,
    created_by INTEGER NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (location_id) REFERENCES locations(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- ============ PURCHASE ORDER (INBOUND PLANNING) ============
CREATE TABLE purchase_orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    po_number TEXT NOT NULL UNIQUE,
    supplier_id INTEGER NOT NULL,
    order_date TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'draft' CHECK (status IN ('draft','ordered','partial','received','cancelled')),
    notes TEXT NULL,
    created_by INTEGER NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE purchase_order_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    po_id INTEGER NOT NULL,
    product_id INTEGER NOT NULL,
    qty_ordered INTEGER NOT NULL,
    qty_received INTEGER NOT NULL DEFAULT 0,
    unit_price REAL NOT NULL DEFAULT 0,
    FOREIGN KEY (po_id) REFERENCES purchase_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- ============ GOODS RECEIPT (INBOUND EXECUTION) ============
CREATE TABLE goods_receipts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    gr_number TEXT NOT NULL UNIQUE,
    po_id INTEGER NOT NULL,
    received_date TEXT NOT NULL,
    received_by INTEGER NULL,
    notes TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (po_id) REFERENCES purchase_orders(id),
    FOREIGN KEY (received_by) REFERENCES users(id)
);

CREATE TABLE goods_receipt_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    gr_id INTEGER NOT NULL,
    po_item_id INTEGER NOT NULL,
    product_id INTEGER NOT NULL,
    location_id INTEGER NOT NULL,
    qty_received INTEGER NOT NULL,
    FOREIGN KEY (gr_id) REFERENCES goods_receipts(id) ON DELETE CASCADE,
    FOREIGN KEY (po_item_id) REFERENCES purchase_order_items(id),
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (location_id) REFERENCES locations(id)
);

-- ============ SHIPPING ORDER (OUTBOUND) ============
CREATE TABLE shipping_orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    so_number TEXT NOT NULL UNIQUE,
    customer_id INTEGER NOT NULL,
    order_date TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'draft' CHECK (status IN ('draft','picking','shipped','cancelled')),
    notes TEXT NULL,
    created_by INTEGER NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE shipping_order_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    so_id INTEGER NOT NULL,
    product_id INTEGER NOT NULL,
    location_id INTEGER NOT NULL,
    qty_requested INTEGER NOT NULL,
    qty_shipped INTEGER NOT NULL DEFAULT 0,
    FOREIGN KEY (so_id) REFERENCES shipping_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (location_id) REFERENCES locations(id)
);

-- ============ SAMPLE MASTER DATA ============
INSERT INTO categories (name) VALUES ('Elektronik'), ('Sparepart'), ('Consumable');

INSERT INTO warehouses (code, name, address) VALUES
('GD-A', 'Gudang A', 'Alamat Gudang A'),
('GD-B', 'Gudang B', 'Alamat Gudang B'),
('GD-C', 'Gudang C', 'Alamat Gudang C');

INSERT INTO locations (warehouse_id, code, name, zone, rack) VALUES
((SELECT id FROM warehouses WHERE code='GD-A'), 'A-01-01','Rak A1','Zone A','01'),
((SELECT id FROM warehouses WHERE code='GD-A'), 'A-01-02','Rak A2','Zone A','02'),
((SELECT id FROM warehouses WHERE code='GD-B'), 'B-01-01','Rak B1','Zone B','01'),
((SELECT id FROM warehouses WHERE code='GD-C'), 'C-01-01','Rak C1','Zone C','01');
