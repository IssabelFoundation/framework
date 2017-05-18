PRAGMA foreign_keys=OFF;
BEGIN TRANSACTION;
CREATE TABLE register(
       id                   integer     primary key,
       contact_name         varchar(25),
       email                varchar(25),
       phone                varchar(20),
       company              varchar(25),
       address              varchar(100),
       city                 varchar(25),
       country              varchar(25),
       idPartner            varchar(25)
);
COMMIT;
