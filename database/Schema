CREATE DATABASE IF NOT EXISTS omkar_trading CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE omkar_trading;

CREATE TABLE users (
 id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(120) NOT NULL, username VARCHAR(80) NOT NULL UNIQUE,
 password_hash VARCHAR(255) NOT NULL, role ENUM('Admin','Staff','Sales') NOT NULL DEFAULT 'Staff',
 is_active TINYINT(1) NOT NULL DEFAULT 1, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE categories (
 id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE products (
 id INT AUTO_INCREMENT PRIMARY KEY, sku VARCHAR(60) NOT NULL UNIQUE, name VARCHAR(180) NOT NULL,
 category_id INT NULL, unit VARCHAR(30) NOT NULL DEFAULT 'Piece', purchase_rate DECIMAL(12,2) NOT NULL DEFAULT 0,
 selling_rate DECIMAL(12,2) NOT NULL DEFAULT 0, stock_qty DECIMAL(12,3) NOT NULL DEFAULT 0,
 reorder_level DECIMAL(12,3) NOT NULL DEFAULT 0, is_active TINYINT(1) NOT NULL DEFAULT 1,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY(category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE parties (
 id INT AUTO_INCREMENT PRIMARY KEY, party_type ENUM('Customer','Supplier') NOT NULL, name VARCHAR(180) NOT NULL,
 phone VARCHAR(30), email VARCHAR(150), address TEXT, gst_number VARCHAR(30), is_active TINYINT(1) DEFAULT 1,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE purchases (
 id INT AUTO_INCREMENT PRIMARY KEY, purchase_no VARCHAR(40) NOT NULL UNIQUE, supplier_name VARCHAR(180) NOT NULL,
 supplier_phone VARCHAR(30), purchase_date DATE NOT NULL, subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
 tax_amount DECIMAL(12,2) NOT NULL DEFAULT 0, tax_mode ENUM('GST','Non-GST') NOT NULL DEFAULT 'Non-GST', tax_rate DECIMAL(5,2) NOT NULL DEFAULT 0, discount DECIMAL(12,2) NOT NULL DEFAULT 0,
 grand_total DECIMAL(12,2) NOT NULL DEFAULT 0, notes TEXT, created_by INT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;
CREATE TABLE purchase_items (
 id INT AUTO_INCREMENT PRIMARY KEY, purchase_id INT NOT NULL, product_id INT NOT NULL,
 quantity DECIMAL(12,3) NOT NULL, rate DECIMAL(12,2) NOT NULL, line_total DECIMAL(12,2) NOT NULL,
 FOREIGN KEY(purchase_id) REFERENCES purchases(id) ON DELETE CASCADE,
 FOREIGN KEY(product_id) REFERENCES products(id)
) ENGINE=InnoDB;

CREATE TABLE sales (
 id INT AUTO_INCREMENT PRIMARY KEY, invoice_no VARCHAR(40) NOT NULL UNIQUE, customer_name VARCHAR(180) NOT NULL,
 customer_phone VARCHAR(30), customer_address TEXT, sale_date DATE NOT NULL, subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
 labour_charges DECIMAL(12,2) NOT NULL DEFAULT 0, other_charges DECIMAL(12,2) NOT NULL DEFAULT 0,
 tax_amount DECIMAL(12,2) NOT NULL DEFAULT 0, tax_mode ENUM('GST','Non-GST') NOT NULL DEFAULT 'Non-GST', tax_rate DECIMAL(5,2) NOT NULL DEFAULT 0, discount DECIMAL(12,2) NOT NULL DEFAULT 0,
 grand_total DECIMAL(12,2) NOT NULL DEFAULT 0, payment_status ENUM('Paid','Unpaid','Partial') DEFAULT 'Unpaid',
 notes TEXT, created_by INT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;
CREATE TABLE sale_items (
 id INT AUTO_INCREMENT PRIMARY KEY, sale_id INT NOT NULL, product_id INT NOT NULL,
 quantity DECIMAL(12,3) NOT NULL, rate DECIMAL(12,2) NOT NULL, line_total DECIMAL(12,2) NOT NULL,
 FOREIGN KEY(sale_id) REFERENCES sales(id) ON DELETE CASCADE, FOREIGN KEY(product_id) REFERENCES products(id)
) ENGINE=InnoDB;

CREATE TABLE quotations (
 id INT AUTO_INCREMENT PRIMARY KEY, quotation_no VARCHAR(40) NOT NULL UNIQUE, customer_name VARCHAR(180) NOT NULL,
 customer_phone VARCHAR(30), customer_address TEXT, quote_date DATE NOT NULL, valid_until DATE NOT NULL,
 subtotal DECIMAL(12,2) NOT NULL DEFAULT 0, labour_charges DECIMAL(12,2) NOT NULL DEFAULT 0,
 other_charges DECIMAL(12,2) NOT NULL DEFAULT 0, tax_amount DECIMAL(12,2) NOT NULL DEFAULT 0, tax_mode ENUM('GST','Non-GST') NOT NULL DEFAULT 'Non-GST', tax_rate DECIMAL(5,2) NOT NULL DEFAULT 0,
 discount DECIMAL(12,2) NOT NULL DEFAULT 0, grand_total DECIMAL(12,2) NOT NULL DEFAULT 0,
 status ENUM('Pending','Accepted','Rejected','Converted') DEFAULT 'Pending', notes TEXT, created_by INT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;
CREATE TABLE quotation_items (
 id INT AUTO_INCREMENT PRIMARY KEY, quotation_id INT NOT NULL, description VARCHAR(220) NOT NULL,
 quantity DECIMAL(12,3) NOT NULL, unit VARCHAR(30) NOT NULL, rate DECIMAL(12,2) NOT NULL, line_total DECIMAL(12,2) NOT NULL,
 FOREIGN KEY(quotation_id) REFERENCES quotations(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE stock_movements (
 id INT AUTO_INCREMENT PRIMARY KEY, product_id INT NOT NULL, movement_type ENUM('IN','OUT','ADJUST') NOT NULL,
 quantity DECIMAL(12,3) NOT NULL, reference_no VARCHAR(50), note VARCHAR(255), created_by INT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY(product_id) REFERENCES products(id),
 FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Demo login: admin / password (change immediately after first login).
INSERT INTO users(name,username,password_hash,role) VALUES
('Omkar Admin','admin','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Admin'),
('Sales Staff','sales','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Sales');

INSERT INTO categories(name) VALUES ('Foam'),('Fabric & Cloth'),('Steel'),('Wood & Plywood'),('Accessories'),('Finished Furniture');

INSERT INTO products(sku,name,category_id,unit,purchase_rate,selling_rate,stock_qty,reorder_level) VALUES
('FOAM-001','HR Foam 40 Density',1,'KG',155,210,85,20),
('FOAM-002','Regular Foam 32 Density',1,'KG',115,160,42,15),
('FAB-001','Premium Sofa Fabric',2,'Meter',95,145,120,25),
('FAB-002','Bed Upholstery Cloth',2,'Meter',75,120,68,20),
('STL-001','MS Steel Pipe',3,'Piece',260,340,35,10),
('WOOD-001','Commercial Plywood 8x4',4,'Sheet',950,1250,18,5),
('ACC-001','Sofa Spring Set',5,'Set',180,260,50,10),
('FIN-001','3 Seater Sofa',6,'Piece',8500,12500,4,2),
('FIN-002','Queen Size Bed',6,'Piece',9200,14500,2,2);

INSERT INTO parties(party_type,name,phone,address) VALUES
('Customer','Aarav Furniture House','9876543210','Pune, Maharashtra'),
('Customer','Shree Homes','9823012345','Akluj, Maharashtra'),
('Supplier','Mahalaxmi Foam Suppliers','9890011223','Pune, Maharashtra'),
('Supplier','Classic Fabrics','9765432109','Pimpri, Pune');


CREATE TABLE production_batches (
 id INT AUTO_INCREMENT PRIMARY KEY, batch_no VARCHAR(40) NOT NULL UNIQUE, finished_product_id INT NOT NULL,
 output_quantity DECIMAL(12,3) NOT NULL, labour_cost DECIMAL(12,2) NOT NULL DEFAULT 0,
 other_cost DECIMAL(12,2) NOT NULL DEFAULT 0, total_material_cost DECIMAL(12,2) NOT NULL DEFAULT 0,
 total_cost DECIMAL(12,2) NOT NULL DEFAULT 0, production_date DATE NOT NULL, notes TEXT,
 created_by INT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(finished_product_id) REFERENCES products(id),
 FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;
CREATE TABLE production_items (
 id INT AUTO_INCREMENT PRIMARY KEY, batch_id INT NOT NULL, raw_product_id INT NOT NULL,
 quantity_used DECIMAL(12,3) NOT NULL, unit_cost DECIMAL(12,2) NOT NULL, line_cost DECIMAL(12,2) NOT NULL,
 FOREIGN KEY(batch_id) REFERENCES production_batches(id) ON DELETE CASCADE,
 FOREIGN KEY(raw_product_id) REFERENCES products(id)
) ENGINE=InnoDB;
