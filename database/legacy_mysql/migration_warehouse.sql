-- =============================================
-- MIGRASI: Fitur Multi-Gudang (Warehouse)
-- Jalankan file ini SETELAH database/wms.sql
-- Menambahkan level "Gudang" di atas "Lokasi/Rak"
-- =============================================

USE wms_db;

-- Tabel gudang
CREATE TABLE IF NOT EXISTS warehouses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    address VARCHAR(255) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Data contoh: Gudang A, B, C
INSERT INTO warehouses (code, name, address) VALUES
('GD-A', 'Gudang A', 'Alamat Gudang A'),
('GD-B', 'Gudang B', 'Alamat Gudang B'),
('GD-C', 'Gudang C', 'Alamat Gudang C');

-- Tambahkan kolom warehouse_id ke tabel locations
ALTER TABLE locations ADD COLUMN warehouse_id INT NULL AFTER id;

-- Arahkan lokasi/rak yang sudah ada ke Gudang A secara default
UPDATE locations SET warehouse_id = (SELECT id FROM warehouses WHERE code = 'GD-A' LIMIT 1) WHERE warehouse_id IS NULL;

-- Set NOT NULL setelah data lama terisi, lalu tambahkan foreign key
ALTER TABLE locations MODIFY COLUMN warehouse_id INT NOT NULL;
ALTER TABLE locations ADD CONSTRAINT fk_locations_warehouse FOREIGN KEY (warehouse_id) REFERENCES warehouses(id);

-- Tambahan lokasi contoh untuk Gudang B dan C (opsional, silakan sesuaikan)
INSERT INTO locations (warehouse_id, code, name, zone, rack) VALUES
((SELECT id FROM warehouses WHERE code='GD-B'), 'B-A-01-01', 'Rak B1', 'Zone A', '01'),
((SELECT id FROM warehouses WHERE code='GD-C'), 'C-A-01-01', 'Rak C1', 'Zone A', '01');
