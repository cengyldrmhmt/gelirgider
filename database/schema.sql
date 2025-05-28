-- Create database
CREATE DATABASE IF NOT EXISTS gelirgider CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gelirgider;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    surname VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    is_admin TINYINT(1) DEFAULT 0,
    email_verified TINYINT(1) DEFAULT 0,
    twofa_secret VARCHAR(32),
    last_login DATETIME NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP
);

-- Wallets table
CREATE TABLE IF NOT EXISTS wallets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    type ENUM('cash', 'bank', 'credit_card', 'savings', 'investment', 'crypto') NOT NULL,
    currency VARCHAR(10) DEFAULT 'TRY',
    balance DECIMAL(18,2) DEFAULT 0.00,
    color VARCHAR(20),
    icon VARCHAR(50),
    is_default TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Wallet transactions table
CREATE TABLE IF NOT EXISTS wallet_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    wallet_id INT NOT NULL,
    type ENUM('deposit', 'withdraw', 'transfer') NOT NULL,
    amount DECIMAL(18,2) NOT NULL,
    description VARCHAR(255),
    source_wallet_id INT NULL,
    target_wallet_id INT NULL,
    transaction_date DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (wallet_id) REFERENCES wallets(id) ON DELETE CASCADE,
    FOREIGN KEY (source_wallet_id) REFERENCES wallets(id) ON DELETE SET NULL,
    FOREIGN KEY (target_wallet_id) REFERENCES wallets(id) ON DELETE SET NULL
);

-- Shared wallets table
CREATE TABLE IF NOT EXISTS shared_wallets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    wallet_id INT NOT NULL,
    user_id INT NOT NULL,
    role ENUM('owner', 'editor', 'viewer') DEFAULT 'viewer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (wallet_id) REFERENCES wallets(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Categories table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    type ENUM('income', 'expense') NOT NULL,
    icon VARCHAR(50),
    color VARCHAR(20),
    parent_id INT,
    is_default TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Tags table
CREATE TABLE IF NOT EXISTS tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(50) NOT NULL,
    color VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Transactions table
CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    wallet_id INT NOT NULL,
    category_id INT NULL,
    type ENUM('income', 'expense', 'transfer') NOT NULL,
    amount DECIMAL(18,2) NOT NULL,
    currency VARCHAR(10) DEFAULT 'TRY',
    description VARCHAR(255),
    transaction_date DATETIME NOT NULL,
    is_recurring TINYINT(1) DEFAULT 0,
    photo VARCHAR(255),
    location VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (wallet_id) REFERENCES wallets(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Transaction tags table
CREATE TABLE IF NOT EXISTS transaction_tags (
    transaction_id INT NOT NULL,
    tag_id INT NOT NULL,
    PRIMARY KEY (transaction_id, tag_id),
    FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
);

-- Budgets table
CREATE TABLE IF NOT EXISTS budgets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_id INT NULL,
    wallet_id INT NULL,
    amount DECIMAL(18,2) NOT NULL,
    period ENUM('daily', 'weekly', 'monthly', 'yearly', 'custom') DEFAULT 'monthly',
    start_date DATE NOT NULL,
    end_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (wallet_id) REFERENCES wallets(id) ON DELETE SET NULL
);

-- Scheduled payments table
CREATE TABLE IF NOT EXISTS scheduled_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    wallet_id INT NOT NULL,
    category_id INT NULL,
    type ENUM('income', 'expense') NOT NULL,
    amount DECIMAL(18,2) NOT NULL,
    description VARCHAR(255),
    frequency ENUM('daily', 'weekly', 'monthly', 'yearly', 'custom') NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE,
    last_processed DATE,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (wallet_id) REFERENCES wallets(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Financial goals table
CREATE TABLE IF NOT EXISTS financial_goals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    category_id INT NULL,
    wallet_id INT NULL,
    target_amount DECIMAL(18,2) NOT NULL,
    current_amount DECIMAL(18,2) DEFAULT 0.00,
    currency VARCHAR(10) DEFAULT 'TRY',
    target_date DATE,
    description TEXT,
    status ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (wallet_id) REFERENCES wallets(id) ON DELETE SET NULL
);

-- Notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Activity log table
CREATE TABLE IF NOT EXISTS activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Settings table
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    setting_key VARCHAR(50) NOT NULL,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_setting (user_id, setting_key)
);

-- Currency rates table
CREATE TABLE IF NOT EXISTS currency_rates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    base_currency VARCHAR(10) NOT NULL,
    target_currency VARCHAR(10) NOT NULL,
    rate DECIMAL(18,6) NOT NULL,
    fetched_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create default admin user (password: password)
INSERT INTO users (name, surname, email, password, is_admin, email_verified)
SELECT 'Admin', 'User', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'admin@example.com');

-- Insert default categories
INSERT INTO categories (user_id, name, type, color, icon, is_default)
SELECT 1, 'Maaş', 'income', '#28a745', 'money-bill', 1
WHERE NOT EXISTS (SELECT 1 FROM categories WHERE user_id = 1 AND name = 'Maaş');

INSERT INTO categories (user_id, name, type, color, icon, is_default)
SELECT 1, 'Diğer Gelir', 'income', '#17a2b8', 'plus-circle', 1
WHERE NOT EXISTS (SELECT 1 FROM categories WHERE user_id = 1 AND name = 'Diğer Gelir');

INSERT INTO categories (user_id, name, type, color, icon, is_default)
SELECT 1, 'Market', 'expense', '#dc3545', 'shopping-cart', 1
WHERE NOT EXISTS (SELECT 1 FROM categories WHERE user_id = 1 AND name = 'Market');

INSERT INTO categories (user_id, name, type, color, icon, is_default)
SELECT 1, 'Faturalar', 'expense', '#ffc107', 'file-invoice', 1
WHERE NOT EXISTS (SELECT 1 FROM categories WHERE user_id = 1 AND name = 'Faturalar');

INSERT INTO categories (user_id, name, type, color, icon, is_default)
SELECT 1, 'Ulaşım', 'expense', '#6f42c1', 'car', 1
WHERE NOT EXISTS (SELECT 1 FROM categories WHERE user_id = 1 AND name = 'Ulaşım');

INSERT INTO categories (user_id, name, type, color, icon, is_default)
SELECT 1, 'Sağlık', 'expense', '#20c997', 'heartbeat', 1
WHERE NOT EXISTS (SELECT 1 FROM categories WHERE user_id = 1 AND name = 'Sağlık');

INSERT INTO categories (user_id, name, type, color, icon, is_default)
SELECT 1, 'Eğlence', 'expense', '#fd7e14', 'film', 1
WHERE NOT EXISTS (SELECT 1 FROM categories WHERE user_id = 1 AND name = 'Eğlence');

INSERT INTO categories (user_id, name, type, color, icon, is_default)
SELECT 1, 'Diğer Gider', 'expense', '#6c757d', 'ellipsis-h', 1
WHERE NOT EXISTS (SELECT 1 FROM categories WHERE user_id = 1 AND name = 'Diğer Gider'); 