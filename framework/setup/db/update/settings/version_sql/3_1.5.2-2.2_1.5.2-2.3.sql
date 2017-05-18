BEGIN TRANSACTION;
UPDATE settings SET value='1.5.2-2.2' WHERE key='elastix_version_release';
COMMIT;
