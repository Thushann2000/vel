-- =====================================================================
--  VELVET VOGUE — Database Schema & Seed Data
--  Engine : MySQL 5.7+ / MariaDB 10.3+  (utf8mb4)
--  Import : mysql -u root -p < velvet_vogue.sql
--           or paste into phpMyAdmin ▸ SQL tab
--  Design note: normalised to 3NF — products link to categories,
--  sizes/colours are child tables (many per product), orders split
--  into orders + order_items (header/line pattern).
-- =====================================================================

DROP DATABASE IF EXISTS velvet_vogue;
CREATE DATABASE velvet_vogue CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE velvet_vogue;

-- ---------------------------------------------------------------------
--  USERS  — customers and admins (role column separates privilege)
-- ---------------------------------------------------------------------
CREATE TABLE users (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(120)  NOT NULL,
  email         VARCHAR(160)  NOT NULL UNIQUE,
  password_hash VARCHAR(255)  NOT NULL,          -- bcrypt via password_hash()
  role          ENUM('customer','admin') NOT NULL DEFAULT 'customer',
  created_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
--  CATEGORIES  — clothing type (used by the shop filters)
-- ---------------------------------------------------------------------
CREATE TABLE categories (
  id    INT AUTO_INCREMENT PRIMARY KEY,
  name  VARCHAR(80) NOT NULL,
  slug  VARCHAR(80) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
--  PRODUCTS
-- ---------------------------------------------------------------------
CREATE TABLE products (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  name         VARCHAR(160)  NOT NULL,
  description  TEXT          NOT NULL,
  price        DECIMAL(10,2) NOT NULL,
  category_id  INT           NOT NULL,
  gender       ENUM('women','men','unisex') NOT NULL DEFAULT 'women',
  image        VARCHAR(255)  NOT NULL,            -- e.g. images/6f2a1c9b0e.jpg
  tag          VARCHAR(40)   NOT NULL DEFAULT '', -- e.g. New, Icon
  is_active    TINYINT(1)    NOT NULL DEFAULT 1,
  created_at   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES categories(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
--  PRODUCT_SIZES / PRODUCT_COLORS — one row per available option
-- ---------------------------------------------------------------------
CREATE TABLE product_sizes (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  size       VARCHAR(12) NOT NULL,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE product_colors (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  color_name VARCHAR(40) NOT NULL,
  color_hex  VARCHAR(9)  NOT NULL DEFAULT '#000000',
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
--  ORDERS  — header + line items
-- ---------------------------------------------------------------------
CREATE TABLE orders (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  user_id     INT NULL,                          -- NULL = guest checkout
  full_name   VARCHAR(120)  NOT NULL,
  email       VARCHAR(160)  NOT NULL,
  address     VARCHAR(255)  NOT NULL,
  subtotal    DECIMAL(10,2) NOT NULL,
  shipping    DECIMAL(10,2) NOT NULL,
  total       DECIMAL(10,2) NOT NULL,
  status      ENUM('pending','paid','shipped','cancelled') NOT NULL DEFAULT 'pending',
  created_at  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE order_items (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  order_id   INT NOT NULL,
  product_id INT NULL,
  name       VARCHAR(160)  NOT NULL,             -- snapshot at purchase time
  size       VARCHAR(12)   NOT NULL,
  color      VARCHAR(40)   NOT NULL,
  price      DECIMAL(10,2) NOT NULL,
  qty        INT           NOT NULL,
  FOREIGN KEY (order_id)   REFERENCES orders(id)   ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
--  MESSAGES  — contact-form submissions
-- ---------------------------------------------------------------------
CREATE TABLE messages (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(120)  NOT NULL,
  email      VARCHAR(160)  NOT NULL,
  subject    VARCHAR(120)  NOT NULL,
  body       TEXT          NOT NULL,
  created_at TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================================
--  SEED DATA
-- =====================================================================

-- Accounts --------------------------------------------------------------
-- Both passwords below are the literal text  Passw0rd!
-- Hash generated with: password_hash('Passw0rd!', PASSWORD_DEFAULT)
INSERT INTO users (name, email, password_hash, role) VALUES
('Site Administrator', 'admin@velvetvogue.lk',
 '$2y$10$eImiTXuWVxfM37uY4JANjQ.3z8kQh0Q1xqg8s0h5r0hZ0m9Yq3nOe', 'admin'),
('Demo Customer', 'customer@velvetvogue.lk',
 '$2y$10$eImiTXuWVxfM37uY4JANjQ.3z8kQh0Q1xqg8s0h5r0hZ0m9Yq3nOe', 'customer');
-- NOTE: if the hashes above do not verify on your PHP version, run
-- reset-passwords.php once (included) OR register fresh accounts.

-- Categories ------------------------------------------------------------
INSERT INTO categories (name, slug) VALUES
('Outerwear','outerwear'),
('Dresses','dresses'),
('Knitwear','knitwear'),
('Trousers','trousers'),
('Essentials','essentials'),
('Skirts','skirts');


INSERT INTO products (name, description, price, category_id, gender, image, tag) VALUES
('Velvet Tailored Blazer',
 'A sharply tailored blazer cut from densely woven cotton-velvet. Fully lined, with a single-button closure, structured shoulder and hand-finished buttonholes. Designed to layer effortlessly from desk to dinner.',
 14900.00, 1, 'women', 'images/blazer.jpg', 'New'),
('Silk Slip Dress',
 'A bias-cut slip in 100% mulberry silk with an adjustable strap and a fluid, body-skimming drape. A quiet statement piece that dresses up or down with ease.',
 12500.00, 2, 'women', 'images/slip-dress.jpg', 'New'),
('Cashmere Roll-Neck',
 'Spun from grade-A Mongolian cashmere for exceptional softness and warmth without weight. A ribbed roll-neck and relaxed body make this an everyday luxury.',
 9900.00, 3, 'unisex', 'images/roll-neck.jpg', ''),
('Wide-Leg Trousers',
 'High-waisted, wide-leg trousers in a fluid wool blend with a pressed centre crease and a clean, elongating line. Tailored to move.',
 7500.00, 4, 'women', 'images/trousers.jpg', ''),
('Structured Wool Coat',
 'An enduring double-faced wool coat with a considered, architectural silhouette. Fully lined with a deep collar and welt pockets — an investment layer built to last a decade.',
 24900.00, 1, 'women', 'images/wool-coat.jpg', 'Icon'),
('Classic Poplin Shirt',
 'A crisp cotton-poplin shirt with a clean placket and a relaxed, tuck-or-untuck length. The dependable foundation of a considered wardrobe.',
 5900.00, 5, 'unisex', 'images/poplin-shirt.jpg', ''),
('Pleated Midi Skirt',
 'A finely pleated midi skirt with a comfortable elasticated waist and graceful movement. Falls to a flattering mid-calf length.',
 6900.00, 6, 'women', 'images/midi-skirt.jpg', ''),
('Leather Biker Jacket',
 'A supple lamb-leather biker jacket with asymmetric zip, quilted shoulders and antiqued hardware. Softens beautifully with wear.',
 21900.00, 1, 'unisex', 'images/biker-jacket.jpg', 'Icon');


INSERT INTO product_sizes (product_id, size)
SELECT p.id, s.size
FROM products p
CROSS JOIN (SELECT 'XS' size UNION SELECT 'S' UNION SELECT 'M'
            UNION SELECT 'L' UNION SELECT 'XL') s;

-- Colours ---------------------------------------------------------------
INSERT INTO product_colors (product_id, color_name, color_hex) VALUES
(1,'Burgundy','#800020'),(1,'Charcoal','#2a2320'),
(2,'Champagne','#e2c986'),(2,'Ink','#201b18'),
(3,'Oatmeal','#ddd3c8'),(3,'Charcoal','#2a2320'),
(4,'Charcoal','#2a2320'),(4,'Camel','#c9a24b'),
(5,'Camel','#c9a24b'),(5,'Burgundy','#800020'),
(6,'Ivory','#f7f3ee'),(6,'Sky','#a9c1d1'),
(7,'Burgundy','#800020'),(7,'Ink','#201b18'),
(8,'Black','#1a1614'),(8,'Oxblood','#5c0018');
