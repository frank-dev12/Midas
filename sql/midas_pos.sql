-- Midas POS · MySQL / MariaDB (XAMPP)
-- Base de datos: midas_pos

CREATE DATABASE IF NOT EXISTS midas_pos
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE midas_pos;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS sale_items;
DROP TABLE IF EXISTS stock_movements;
DROP TABLE IF EXISTS sales;
DROP TABLE IF EXISTS products;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE products (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    barcode VARCHAR(64) NOT NULL UNIQUE,
    name VARCHAR(180) NOT NULL,
    description VARCHAR(255) NOT NULL DEFAULT '',
    price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    cost DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    stock INT NOT NULL DEFAULT 0,
    min_stock INT NOT NULL DEFAULT 5,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_products_name (name),
    INDEX idx_products_active (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE stock_movements (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    type ENUM('in','out','sale','adjust') NOT NULL,
    quantity INT NOT NULL,
    previous_stock INT NOT NULL,
    new_stock INT NOT NULL,
    note VARCHAR(255) NOT NULL DEFAULT '',
    reference VARCHAR(64) NOT NULL DEFAULT '',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_stock_product (product_id),
    INDEX idx_stock_created (created_at),
    CONSTRAINT fk_stock_product FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sales (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_number VARCHAR(32) NOT NULL UNIQUE,
    subtotal DECIMAL(12,2) NOT NULL,
    discount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    tax DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    total DECIMAL(12,2) NOT NULL,
    payment_method VARCHAR(32) NOT NULL DEFAULT 'efectivo',
    amount_paid DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    change_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    customer_name VARCHAR(120) NOT NULL DEFAULT 'Cliente general',
    notes VARCHAR(255) NOT NULL DEFAULT '',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_sales_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sale_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sale_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    barcode VARCHAR(64) NOT NULL,
    product_name VARCHAR(180) NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(12,2) NOT NULL,
    line_total DECIMAL(12,2) NOT NULL,
    INDEX idx_items_sale (sale_id),
    CONSTRAINT fk_items_sale FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    CONSTRAINT fk_items_product FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO products (barcode, name, description, price, cost, stock) VALUES
('7750243001234', 'Gaseosa Inca Kola 500ml', 'Bebida', 3.50, 2.00, 48),
('7750243005678', 'Agua San Luis 625ml', 'Bebida', 1.50, 0.80, 60),
('7750182001111', 'Galletas Oreo 36g', 'Snacks', 1.80, 1.00, 40),
('7750641002222', 'Papas Lays Clásicas', 'Snacks', 4.50, 2.80, 25),
('7750888003333', 'Chocolate Sublime', 'Dulces', 2.20, 1.40, 35),
('7750999004444', 'Arroz Costeño 1kg', 'Abarrotes', 5.90, 4.20, 30),
('7750111005555', 'Aceite Primor 1L', 'Abarrotes', 9.90, 7.50, 18),
('7750222006666', 'Leche Gloria Azul', 'Lácteos', 4.20, 3.10, 40),
('7750333007777', 'Pan molde Bimbo', 'Panadería', 8.50, 6.00, 12),
('7750444008888', 'Detergente Ace 1kg', 'Limpieza', 12.90, 9.50, 15);
