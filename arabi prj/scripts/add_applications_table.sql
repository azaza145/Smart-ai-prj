-- Run this if your database was created before applications were added.
-- Usage: Get-Content scripts\add_applications_table.sql | docker exec -i smartrecruit-mysql mysql -u root -proot_secret smartrecruit

USE smartrecruit;

CREATE TABLE IF NOT EXISTS applications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    job_id INT UNSIGNED NOT NULL,
    candidate_id INT UNSIGNED NOT NULL,
    status ENUM('submitted', 'viewed', 'shortlisted', 'rejected') NOT NULL DEFAULT 'submitted',
    cover_letter TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_applications_job_candidate (job_id, candidate_id),
    INDEX idx_applications_candidate (candidate_id),
    INDEX idx_applications_job (job_id),
    INDEX idx_applications_status (status),
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
    FOREIGN KEY (candidate_id) REFERENCES candidates(id) ON DELETE CASCADE
) ENGINE=InnoDB;
