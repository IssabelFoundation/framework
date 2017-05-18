BEGIN TRANSACTION;
UPDATE settings SET value='elastixwave' WHERE key='theme';
COMMIT;
