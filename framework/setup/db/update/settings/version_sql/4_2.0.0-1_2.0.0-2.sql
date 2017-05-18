BEGIN TRANSACTION;
UPDATE settings SET value='elastixwine' WHERE key='theme';
COMMIT;
