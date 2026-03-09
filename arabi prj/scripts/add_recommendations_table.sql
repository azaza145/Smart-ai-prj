-- Create recommendations table if missing (required for IA ranking and candidate "Postes correspondants").
-- Usage: mysql -u user -p smartrecruit < scripts/add_recommendations_table.sql

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
