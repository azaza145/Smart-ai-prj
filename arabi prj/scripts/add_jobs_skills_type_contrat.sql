-- Add skills and contract type to jobs. Run once.
USE smartrecruit;

ALTER TABLE jobs
  ADD COLUMN skills_raw VARCHAR(500) NULL COMMENT 'Compétences recherchées (virgules ou tags)',
  ADD COLUMN type_contrat VARCHAR(50) NULL COMMENT 'CDI, CDD, Stage, Freelance, Alternance, etc.';
