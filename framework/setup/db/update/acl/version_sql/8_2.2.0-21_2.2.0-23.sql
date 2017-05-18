BEGIN TRANSACTION;
CREATE TABLE sticky_note(
       id           INTEGER   NOT NULL   PRIMARY KEY,
       id_user      INTEGER   NOT NULL,
       id_resource  INTEGER   NOT NULL,
       date_edit    DATETIME  NOT NULL,
       description  TEXT
);
COMMIT;