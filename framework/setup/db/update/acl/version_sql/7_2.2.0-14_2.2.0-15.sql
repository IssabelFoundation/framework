BEGIN TRANSACTION;
CREATE TABLE acl_user_shortcut(
       id           INTEGER     NOT NULL   PRIMARY KEY,
       id_user      INTEGER     NOT NULL,
       id_resource  INTEGER     NOT NULL,
       type         VARCHAR(25) NOT NULL,
       description  VARCHAR(25)
);
COMMIT;
