BEGIN TRANSACTION;
ALTER TABLE contact ADD COLUMN last_name varchar(35);
ALTER TABLE contact ADD COLUMN iduser int;
COMMIT;
