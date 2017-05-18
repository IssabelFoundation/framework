BEGIN TRANSACTION;
UPDATE settings SET value='elastixneo' WHERE key='theme';
COMMIT;