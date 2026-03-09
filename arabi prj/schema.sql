-- SmartRecruit Database Schema - MySQL 8

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS smartrecruit CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE smartrecruit;

-- Users (admin, recruiter, candidate)
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'recruiter', 'candidate') NOT NULL DEFAULT 'candidate',
    status ENUM('active', 'disabled') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_users_email (email),
    INDEX idx_users_role (role),
    INDEX idx_users_status (status)
) ENGINE=InnoDB;

-- Password reset tokens (for "forgot password")
CREATE TABLE password_reset_tokens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(64) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_reset_email (email),
    INDEX idx_reset_token (token),
    INDEX idx_reset_expires (expires_at)
) ENGINE=InnoDB;

-- Candidates (from CSV + optional form)
CREATE TABLE candidates (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    csv_source_id INT NULL,
    user_id INT UNSIGNED NULL,
    nom VARCHAR(255) NOT NULL DEFAULT '',
    prenom VARCHAR(255) NOT NULL DEFAULT '',
    email VARCHAR(255) NOT NULL UNIQUE,
    telephone VARCHAR(50) DEFAULT NULL,
    age INT UNSIGNED DEFAULT NULL,
    ville VARCHAR(255) DEFAULT NULL,
    experience_annees INT UNSIGNED DEFAULT NULL,
    poste_actuel VARCHAR(255) DEFAULT NULL,
    entreprise_actuelle VARCHAR(255) DEFAULT NULL,
    education_niveau VARCHAR(255) DEFAULT NULL,
    diplome VARCHAR(255) DEFAULT NULL,
    universite VARCHAR(255) DEFAULT NULL,
    annee_diplome VARCHAR(50) DEFAULT NULL,
    competences_techniques_raw TEXT,
    competences_langues_raw TEXT,
    langues_niveau_raw TEXT,
    experience_detail_raw TEXT,
    projets_raw TEXT,
    certifications_raw TEXT,
    disponibilite VARCHAR(255) DEFAULT NULL,
    pretention_salaire VARCHAR(50) DEFAULT NULL,
    profile_json LONGTEXT NULL COMMENT 'Canonical profile JSON for CV preview and PDF export',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_candidates_email (email),
    INDEX idx_candidates_ville (ville),
    INDEX idx_candidates_csv_source_id (csv_source_id)
) ENGINE=InnoDB;

-- Normalized candidate profiles (for AI ranking)
CREATE TABLE candidate_profiles (
    candidate_id INT UNSIGNED PRIMARY KEY,
    skills_norm JSON,
    languages_norm JSON,
    languages_level_norm JSON,
    education_norm JSON,
    experience_norm JSON,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (candidate_id) REFERENCES candidates(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- CV uploads (optional)
CREATE TABLE cvs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    candidate_id INT UNSIGNED NOT NULL,
    file_path VARCHAR(512) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    extracted_text LONGTEXT,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (candidate_id) REFERENCES candidates(id) ON DELETE CASCADE,
    INDEX idx_cvs_candidate_id (candidate_id)
) ENGINE=InnoDB;

-- Jobs (offres : titre, compétences recherchées, type de contrat pour le matching IA)
CREATE TABLE jobs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    department VARCHAR(255) DEFAULT NULL,
    description LONGTEXT,
    requirements LONGTEXT,
    skills_raw VARCHAR(500) DEFAULT NULL COMMENT 'Compétences recherchées (virgules ou tags)',
    type_contrat VARCHAR(50) DEFAULT NULL COMMENT 'CDI, CDD, Stage, Freelance, Alternance, etc.',
    created_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_jobs_created_by (created_by)
) ENGINE=InnoDB;

-- Applications (candidatures: candidate applies to a job)
CREATE TABLE applications (
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

-- Recommendations (TF-IDF + cosine similarity results)
-- Note: column named 'ranking' to avoid MySQL reserved word RANK()
CREATE TABLE recommendations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    job_id INT UNSIGNED NOT NULL,
    candidate_id INT UNSIGNED NOT NULL,
    score DECIMAL(6,5) NOT NULL,
    ranking INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_recommendations_job_candidate (job_id, candidate_id),
    INDEX idx_recommendations_job_score (job_id, score DESC),
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
    FOREIGN KEY (candidate_id) REFERENCES candidates(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Import log (track last CSV import)
CREATE TABLE import_logs (
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

-- Pipeline run log (normalization / recommendation runs)
CREATE TABLE pipeline_logs (
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

SET FOREIGN_KEY_CHECKS = 1;
