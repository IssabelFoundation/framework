BEGIN TRANSACTION;
UPDATE menu SET order_no=41 WHERE id='userlist';
UPDATE menu SET order_no=42 WHERE id='grouplist';
UPDATE menu SET order_no=43 WHERE id='group_permission';
COMMIT;
