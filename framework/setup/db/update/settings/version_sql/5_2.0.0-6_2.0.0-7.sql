BEGIN TRANSACTION;
UPDATE settings SET value='2.0.0' WHERE key='elastix_version_release';
UPDATE settings SET value='elastixblue' WHERE key='theme';
COMMIT;
