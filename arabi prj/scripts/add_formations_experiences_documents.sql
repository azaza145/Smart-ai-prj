-- Add multiple formations/experiences and documents (certificats, preuves). Run once.
USE smartrecruit;

ALTER TABLE candidates
  ADD COLUMN formations_json TEXT NULL COMMENT 'JSON array of {niveau,diplome,universite,annee}',
  ADD COLUMN experiences_json TEXT NULL COMMENT 'JSON array of {poste,entreprise,annees,description}';

CREATE TABLE IF NOT EXISTS candidate_documents (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    candidate_id INT UNSIGNED NOT NULL,
    file_path VARCHAR(512) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    doc_type VARCHAR(50) NOT NULL DEFAULT 'preuve' COMMENT 'certificat, preuve, autre',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (candidate_id) REFERENCES candidates(id) ON DELETE CASCADE,
    INDEX idx_candoc_candidate_id (candidate_id)
) ENGINE=InnoDB;
