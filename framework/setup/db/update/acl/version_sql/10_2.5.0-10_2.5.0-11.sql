BEGIN TRANSACTION;

CREATE TABLE acl_notification
(
    id              INTEGER     NOT NULL    PRIMARY KEY,
    datetime_create DATETIME    NOT NULL,
    level           VARCHAR(32) NOT NULL    DEFAULT 'info',
    id_user         INTEGER,
    id_resource     INTEGER,
    content         TEXT,

    FOREIGN KEY (id_user) REFERENCES acl_user(id),
    FOREIGN KEY (id_resource) REFERENCES acl_resource(id)
);

COMMIT;
