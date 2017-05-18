BEGIN TRANSACTION;
INSERT INTO "acl_action" VALUES('View the resource',2,'view');
INSERT INTO "acl_action" VALUES('Create into resource',3,'create');
INSERT INTO "acl_action" VALUES('Delete in resource',4,'delete');
INSERT INTO "acl_action" VALUES('Update into resource',5,'update');
COMMIT;
