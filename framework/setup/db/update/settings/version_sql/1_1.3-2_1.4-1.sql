BEGIN TRANSACTION;
UPDATE settings SET value='1.4-1' WHERE key='elastix_version_release';
COMMIT;
