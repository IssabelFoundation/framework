BEGIN TRANSACTION;
DELETE FROM acl_group_permission WHERE id_group=3;
DELETE FROM acl_group WHERE name='extension';
COMMIT;
