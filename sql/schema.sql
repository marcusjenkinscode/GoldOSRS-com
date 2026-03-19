-- GoldOSRS.com Database Schema
-- Run this SQL to set up the required tables

CREATE DATABASE IF NOT EXISTS goldosrs CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE goldosrs;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    credits DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Orders (pending / basket snapshot)
CREATE TABLE IF NOT EXISTS orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED,
    service VARCHAR(255) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    payment_method ENUM('bitcoin','card') NOT NULL DEFAULT 'bitcoin',
    status ENUM('pending','paid','cancelled') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Order history (completed orders)
CREATE TABLE IF NOT EXISTS order_history (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED,
    service VARCHAR(255) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    payment_method ENUM('bitcoin','card') NOT NULL DEFAULT 'bitcoin',
    status ENUM('paid','refunded') NOT NULL DEFAULT 'paid',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Betting history
CREATE TABLE IF NOT EXISTS betting_history (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    game VARCHAR(50) NOT NULL,
    bet_amount DECIMAL(12,2) NOT NULL,
    win_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    result ENUM('win','loss') NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Raffle prizes
CREATE TABLE IF NOT EXISTS raffle_prizes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    value DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    added_date DATE NOT NULL DEFAULT (CURRENT_DATE)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Password reset tokens
CREATE TABLE IF NOT EXISTS password_resets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    token CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_token (token),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample raffle prizes
INSERT IGNORE INTO raffle_prizes (name, value, added_date) VALUES
    ('Party Hat (Blue)',   5000000.00, CURRENT_DATE),
    ('Twisted Bow',        1200000000.00, CURRENT_DATE),
    ('Scythe of Vitur',    750000000.00, CURRENT_DATE),
    ('Elysian Spirit Shield', 800000000.00, CURRENT_DATE),
    ('Armadyl Godsword',   40000000.00, CURRENT_DATE),
    ('Dragon Claws',       80000000.00, CURRENT_DATE),
    ('10M OSRS Gold',      10000000.00, CURRENT_DATE),
    ('50M OSRS Gold',      50000000.00, CURRENT_DATE);
