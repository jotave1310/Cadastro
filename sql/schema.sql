CREATE DATABASE IF NOT EXISTS secure_contact_system
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE secure_contact_system;

CREATE TABLE IF NOT EXISTS submissions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    message_ciphertext MEDIUMTEXT NOT NULL,
    message_iv VARCHAR(64) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_email (email),
    KEY idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
