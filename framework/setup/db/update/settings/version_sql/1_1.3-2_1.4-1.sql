BEGIN TRANSACTION;
UPDATE settings SET value='1.4-1' WHERE key='issabel_version_release';
COMMIT;
