-- Create the recommendations table (when it does not exist yet).
-- Run in MySQL Workbench: open file, then Execute (Ctrl+Shift+Enter).
-- Or from terminal (adjust user/password/host): mysql -u root -p -h 127.0.0.1 smartrecruit < scripts/create_recommendations_table_only.sql
USE smartrecruit;

CREATE TABLE IF NOT EXISTS recommendations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    job_id INT UNSIGNED NOT NULL,
    candidate_id INT UNSIGNED NOT NULL,
    score DECIMAL(6,5) NOT NULL,
    ranking INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_recommendations_job_candidate (job_id, candidate_id),
    INDEX idx_recommendations_job_score (job_id, score DESC),
    INDEX idx_recommendations_candidate (candidate_id),
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
    FOREIGN KEY (candidate_id) REFERENCES candidates(id) ON DELETE CASCADE
) ENGINE=InnoDB;
