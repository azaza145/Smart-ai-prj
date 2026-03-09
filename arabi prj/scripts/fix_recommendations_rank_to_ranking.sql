-- If recommendations table was created with column 'rank' (MySQL reserved word),
-- rename it to 'ranking'. Run ONLY when the table already exists with column rank.
-- If the table does not exist, run add_recommendations_table.sql instead.
USE smartrecruit;

ALTER TABLE recommendations CHANGE COLUMN `rank` ranking INT UNSIGNED NOT NULL;
