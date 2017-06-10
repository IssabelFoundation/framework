BEGIN TRANSACTION;
UPDATE settings SET value='1.1-4' WHERE key='issabel_version_release';
COMMIT;
