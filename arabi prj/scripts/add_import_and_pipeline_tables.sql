-- Run this if your database was created before import_logs / pipeline_logs were added.
-- Usage: mysql -u user -p smartrecruit < scripts/add_import_and_pipeline_tables.sql
-- Or from Docker: docker exec -i <mysql_container> mysql -u root -p smartrecruit < scripts/add_import_and_pipeline_tables.sql

USE smartrecruit;

CREATE TABLE IF NOT EXISTS import_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    file_path VARCHAR(512) NOT NULL,
    rows_processed INT UNSIGNED DEFAULT 0,
    rows_inserted INT UNSIGNED DEFAULT 0,
    rows_updated INT UNSIGNED DEFAULT 0,
    rows_failed INT UNSIGNED DEFAULT 0,
    error_log TEXT,
    status ENUM('running', 'completed', 'failed') DEFAULT 'running',
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    INDEX idx_import_logs_started (started_at DESC)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pipeline_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    type ENUM('normalization', 'recommendation') NOT NULL,
    job_id INT UNSIGNED NULL,
    rows_affected INT UNSIGNED DEFAULT 0,
    status ENUM('running', 'completed', 'failed') DEFAULT 'running',
    error_message TEXT,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    INDEX idx_pipeline_type (type),
    INDEX idx_pipeline_started (started_at DESC)
) ENGINE=InnoDB;
