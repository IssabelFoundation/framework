PRAGMA foreign_keys=OFF;
BEGIN TRANSACTION;
CREATE TABLE acl_action (description varchar(200), id INTEGER PRIMARY KEY, name varchar(10));
INSERT INTO "acl_action" VALUES('Access the resource',1,'access');
CREATE TABLE acl_group (description TEXT, id INTEGER PRIMARY KEY, name varchar(200));
INSERT INTO "acl_group" VALUES('total access',1,'administrator');
INSERT INTO "acl_group" VALUES('extension user',3,'extension');
CREATE TABLE acl_membership (
  id INTEGER  NOT NULL   PRIMARY KEY,
  id_user INTEGER  NOT NULL default '0',
  id_group INTEGER   NOT NULL default '0'
);
INSERT INTO "acl_membership" VALUES(1,1,1);
CREATE TABLE acl_user (id INTEGER PRIMARY KEY, name varchar(50), description varchar(180), md5_password varchar(50), extension varchar(20));
INSERT INTO "acl_user" VALUES(1,'admin',NULL,'7a5210c173ea40c03205a5de7dcd4cb0',NULL);
CREATE TABLE acl_user_permission (id INTEGER PRIMARY KEY, id_action int(11), id_user int(11), id_resource int(11));
CREATE TABLE acl_resource (id INTEGER PRIMARY KEY, name varchar(50), description varchar(180));
INSERT INTO "acl_resource" VALUES(1,'sysinfo','System Info');
INSERT INTO "acl_resource" VALUES(2,'usermgr','User Management');
INSERT INTO "acl_resource" VALUES(3,'grouplist','Group List');
INSERT INTO "acl_resource" VALUES(4,'userlist','User List');
INSERT INTO "acl_resource" VALUES(5,'group_permission','Group Permission');
INSERT INTO "acl_resource" VALUES(6,'load_module','Load Module');
INSERT INTO "acl_resource" VALUES(16,'preferences','Preferences');
INSERT INTO "acl_resource" VALUES(17,'language','Language');
INSERT INTO "acl_resource" VALUES(18,'themes_system','Themes');
INSERT INTO "acl_resource" VALUES(19,'time_config','Date/Time');
INSERT INTO "acl_resource" VALUES(20,'example','Example');
CREATE TABLE acl_group_permission (id INTEGER NOT NULL PRIMARY KEY,  id_action INTEGER NOT NULL, id_group INTEGER NOT NULL, id_resource INTEGER NOT NULL);
INSERT INTO "acl_group_permission" VALUES(1,1,1,1);
INSERT INTO "acl_group_permission" VALUES(2,1,1,2);
INSERT INTO "acl_group_permission" VALUES(3,1,1,3);
INSERT INTO "acl_group_permission" VALUES(4,1,1,4);
INSERT INTO "acl_group_permission" VALUES(5,1,1,5);
INSERT INTO "acl_group_permission" VALUES(6,1,1,6);
INSERT INTO "acl_group_permission" VALUES(7,1,1,16);
INSERT INTO "acl_group_permission" VALUES(8,1,1,17);
INSERT INTO "acl_group_permission" VALUES(9,1,1,18);
INSERT INTO "acl_group_permission" VALUES(10,1,1,19);
INSERT INTO "acl_group_permission" VALUES(11,1,3,1);
INSERT INTO "acl_group_permission" VALUES(12,1,3,16);
INSERT INTO "acl_group_permission" VALUES(13,1,3,17);
INSERT INTO "acl_group_permission" VALUES(14,1,3,18);
INSERT INTO "acl_group_permission" VALUES(15,1,3,19);
INSERT INTO "acl_group_permission" VALUES(16,1,1,20);
COMMIT;
