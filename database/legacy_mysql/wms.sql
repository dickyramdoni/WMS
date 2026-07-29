-- =============================================
-- Warehouse Management System (WMS) - Database Schema
-- PHP Native + MySQL
-- =============================================

CREATE DATABASE IF NOT EXISTS wms_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE wms_db;

-- ============ USERS ============
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin','supervisor','staff') NOT NULL DEFAULT 'staff',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Default admin user is created automatically by install.php (password hashed at runtime with PHP's password_hash)

-- ============ MASTER DATA ============
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL
);

CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sku VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    category_id INT NULL,
    unit VARCHAR(20) NOT NULL DEFAULT 'pcs',
    min_stock INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

CREATE TABLE warehouses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    address VARCHAR(255) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    warehouse_id INT NOT NULL,
    code VARCHAR(30) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    zone VARCHAR(50) NULL,
    rack VARCHAR(50) NULL,
    description VARCHAR(255) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(id)
);

CREATE TABLE suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    contact_person VARCHAR(100) NULL,
    phone VARCHAR(30) NULL,
    address TEXT NULL
);

CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    contact_person VARCHAR(100) NULL,
    phone VARCHAR(30) NULL,
    address TEXT NULL
);

-- ============ STOCK ============
CREATE TABLE stock (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    location_id INT NOT NULL,
    qty INT NOT NULL DEFAULT 0,
    UNIQUE KEY uniq_product_location (product_id, location_id),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE CASCADE
);

CREATE TABLE stock_movements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    location_id INT NOT NULL,
    movement_type ENUM('in','out','transfer_in','transfer_out','adjustment') NOT NULL,
    qty INT NOT NULL,
    reference_type VARCHAR(50) NULL,
    reference_id INT NULL,
    notes VARCHAR(255) NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (location_id) REFERENCES locations(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- ============ PURCHASE ORDER (INBOUND PLANNING) ============
CREATE TABLE purchase_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    po_number VARCHAR(30) NOT NULL UNIQUE,
    supplier_id INT NOT NULL,
    order_date DATE NOT NULL,
    status ENUM('draft','ordered','partial','received','cancelled') NOT NULL DEFAULT 'draft',
    notes VARCHAR(255) NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE purchase_order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    po_id INT NOT NULL,
    product_id INT NOT NULL,
    qty_ordered INT NOT NULL,
    qty_received INT NOT NULL DEFAULT 0,
    unit_price DECIMAL(15,2) NOT NULL DEFAULT 0,
    FOREIGN KEY (po_id) REFERENCES purchase_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- ============ GOODS RECEIPT (INBOUND EXECUTION) ============
CREATE TABLE goods_receipts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    gr_number VARCHAR(30) NOT NULL UNIQUE,
    po_id INT NOT NULL,
    received_date DATE NOT NULL,
    received_by INT NULL,
    notes VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (po_id) REFERENCES purchase_orders(id),
    FOREIGN KEY (received_by) REFERENCES users(id)
);

CREATE TABLE goods_receipt_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    gr_id INT NOT NULL,
    po_item_id INT NOT NULL,
    product_id INT NOT NULL,
    location_id INT NOT NULL,
    qty_received INT NOT NULL,
    FOREIGN KEY (gr_id) REFERENCES goods_receipts(id) ON DELETE CASCADE,
    FOREIGN KEY (po_item_id) REFERENCES purchase_order_items(id),
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (location_id) REFERENCES locations(id)
);

-- ============ SHIPPING ORDER (OUTBOUND) ============
CREATE TABLE shipping_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    so_number VARCHAR(30) NOT NULL UNIQUE,
    customer_id INT NOT NULL,
    order_date DATE NOT NULL,
    status ENUM('draft','picking','shipped','cancelled') NOT NULL DEFAULT 'draft',
    notes VARCHAR(255) NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE shipping_order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    so_id INT NOT NULL,
    product_id INT NOT NULL,
    location_id INT NOT NULL,
    qty_requested INT NOT NULL,
    qty_shipped INT NOT NULL DEFAULT 0,
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
