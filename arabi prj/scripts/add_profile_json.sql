-- Canonical candidate profile (single source of truth for preview + PDF). Run once.
USE smartrecruit;

ALTER TABLE candidates
  ADD COLUMN profile_json LONGTEXT NULL COMMENT 'Canonical profile JSON for CV preview and PDF export'
  AFTER pretention_salaire;
