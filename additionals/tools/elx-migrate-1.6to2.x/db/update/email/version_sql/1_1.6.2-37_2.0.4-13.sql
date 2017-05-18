BEGIN TRANSACTION;
CREATE TABLE email_relay (
        id          INTEGER PRIMARY KEY,
        name        varchar (80),
        value       varchar (100)
);


CREATE TABLE member_list (
        id              INTEGER PRIMARY KEY,
        mailmember      varchar (50),
        id_emaillist    INTEGER,
        FOREIGN KEY(id_emaillist) REFERENCES email_list(id)
);

CREATE TABLE virtual (
        id          integer not null primary key,
        alias       varchar(255) not null,
        username    varchar(50) not null
);
COMMIT;
