-- Migration v2: Enhanced recommendations + CV extraction tracking
-- Run after completing code changes. Execute statements one by one; skip any that fail (column/index may already exist).
-- jobs.skills_raw and type_contrat may exist from add_jobs_skills_type_contrat.sql; candidates.formations_json/experiences_json from add_formations_experiences_documents.sql.

-- 1. Add score_pct and breakdown to recommendations
ALTER TABLE recommendations ADD COLUMN score_pct DECIMAL(5,2) DEFAULT NULL COMMENT 'Score 0-100 for display';
ALTER TABLE recommendations ADD COLUMN score_breakdown JSON DEFAULT NULL COMMENT 'Sub-scores: tfidf, skills, experience';

-- 2. Add extraction_attempted_at to cvs (prevent retry storms)
ALTER TABLE cvs ADD COLUMN extraction_attempted_at TIMESTAMP NULL DEFAULT NULL;

-- 3. Ensure jobs has skills_raw and type_contrat columns (skip if already exist)
ALTER TABLE jobs ADD COLUMN skills_raw VARCHAR(500) DEFAULT NULL COMMENT 'Required skills for AI matching';
ALTER TABLE jobs ADD COLUMN type_contrat VARCHAR(50) DEFAULT NULL COMMENT 'CDI, CDD, Stage, Freelance, Alternance';

-- 4. Add index for faster recommendation queries
CREATE INDEX idx_recommendations_job_score ON recommendations(job_id, score DESC);

-- 5. Candidates: add formations_json and experiences_json for structured data
ALTER TABLE candidates ADD COLUMN formations_json JSON DEFAULT NULL COMMENT 'Structured education entries';
ALTER TABLE candidates ADD COLUMN experiences_json JSON DEFAULT NULL COMMENT 'Structured experience entries';
