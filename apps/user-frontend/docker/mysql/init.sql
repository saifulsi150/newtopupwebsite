CREATE TABLE IF NOT EXISTS products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(120) NOT NULL,
  slug VARCHAR(150) NOT NULL UNIQUE,
  subtitle VARCHAR(255) NULL,
  image_url VARCHAR(500) NOT NULL,
  price_from DECIMAL(10,2) NOT NULL DEFAULT 0,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS topup_packages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  title VARCHAR(120) NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_topup_packages_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

INSERT INTO products (title, slug, subtitle, image_url, price_from, sort_order, is_active)
VALUES
('Free Fire TopUp', 'uid-topup-bd-server', 'Fast and secure topup', 'https://images.unsplash.com/photo-1542751371-adc38448a05e?auto=format&fit=crop&w=600&q=80', 25, 1, 1),
('Level Up Pass', 'level-up-pass', 'Official level up package', 'https://images.unsplash.com/photo-1511512578047-dfb367046420?auto=format&fit=crop&w=600&q=80', 199, 2, 1),
('Weekly Lite', 'weekly-lite-bd-server', 'Weekly package', 'https://images.unsplash.com/photo-1560253023-3ec5d502959f?auto=format&fit=crop&w=600&q=80', 99, 3, 1),
('Unipin Voucher', 'unipin-voucher-bd', 'Voucher option', 'https://images.unsplash.com/photo-1612287230202-1ff1d85d1bdf?auto=format&fit=crop&w=600&q=80', 50, 4, 1),
('Weekly Monthly', 'weeklymonthly', 'Weekly and monthly combo', 'https://images.unsplash.com/photo-1580327344181-c1163234e5a0?auto=format&fit=crop&w=600&q=80', 399, 5, 1)
ON DUPLICATE KEY UPDATE title=VALUES(title), subtitle=VALUES(subtitle), image_url=VALUES(image_url), price_from=VALUES(price_from), sort_order=VALUES(sort_order), is_active=VALUES(is_active);

INSERT INTO topup_packages (product_id, title, price, sort_order)
SELECT p.id, '100 Diamonds', 85, 1 FROM products p WHERE p.slug='uid-topup-bd-server'
UNION ALL
SELECT p.id, '310 Diamonds', 250, 2 FROM products p WHERE p.slug='uid-topup-bd-server'
UNION ALL
SELECT p.id, '520 Diamonds', 420, 3 FROM products p WHERE p.slug='uid-topup-bd-server'
UNION ALL
SELECT p.id, 'Level Up Pass', 199, 1 FROM products p WHERE p.slug='level-up-pass'
UNION ALL
SELECT p.id, 'Weekly Lite', 99, 1 FROM products p WHERE p.slug='weekly-lite-bd-server'
UNION ALL
SELECT p.id, 'Unipin 50', 50, 1 FROM products p WHERE p.slug='unipin-voucher-bd'
UNION ALL
SELECT p.id, 'Weekly Monthly Combo', 399, 1 FROM products p WHERE p.slug='weeklymonthly';
