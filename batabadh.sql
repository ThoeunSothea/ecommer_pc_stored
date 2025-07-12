-- Create users table
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,  -- Store hashed passwords only
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    email VARCHAR(100) NOT NULL UNIQUE,
    role ENUM('admin','customer') DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (username),
    INDEX (email),
    INDEX (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create orders table with foreign key
CREATE TABLE orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    order_date DATETIME NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending','processing','completed','cancelled','refunded') DEFAULT 'pending',
    payment_method ENUM('credit_card','paypal','aba_pay','wing','cod') NOT NULL,
    shipping_address TEXT NOT NULL,
    billing_address TEXT,
    payment_status ENUM('pending','paid','failed','refunded') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    order_code VARCHAR(20) UNIQUE NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    INDEX (user_id),
    INDEX (order_date),
    INDEX (status),
    INDEX (payment_status),
    INDEX (order_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create categories table first

CREATE TABLE categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active','inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create products table
CREATE TABLE products (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    image VARCHAR(255),
    category_id INT NOT NULL,
    discount DECIMAL(5,2) DEFAULT 0.00,
    featured BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active','inactive','out_of_stock') DEFAULT 'active',
    FOREIGN KEY (category_id) REFERENCES categories(category_id),
    INDEX (name),
    INDEX (category_id),
    INDEX (price),
    INDEX (featured),
    INDEX (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Create order_items table
CREATE TABLE order_items (
    order_item_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    price DECIMAL(10,2) NOT NULL, -- snapshot of product price at time of order
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id),
    INDEX (order_id),
    INDEX (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `site_name` VARCHAR(100) NOT NULL DEFAULT 'My E-commerce',
    `site_email` VARCHAR(100) NOT NULL,
    `currency` VARCHAR(10) NOT NULL DEFAULT 'USD',
    `tax_rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `shipping_cost` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `maintenance_mode` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE categories ADD image_path VARCHAR(255) DEFAULT NULL;
ALTER TABLE orders ADD COLUMN notes TEXT NULL AFTER order_code;
ALTER TABLE products CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

ALTER TABLE users 
ADD COLUMN reset_token VARCHAR(255) DEFAULT NULL, 
ADD COLUMN reset_expires DATETIME DEFAULT NULL;



