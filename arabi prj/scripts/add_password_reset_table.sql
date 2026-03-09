-- Run this if your database was created before password reset was added.
-- Usage: mysql -u user -p smartrecruit < scripts/add_password_reset_table.sql

USE smartrecruit;

CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(64) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_reset_email (email),
    INDEX idx_reset_token (token),
    INDEX idx_reset_expires (expires_at)
) ENGINE=InnoDB;
