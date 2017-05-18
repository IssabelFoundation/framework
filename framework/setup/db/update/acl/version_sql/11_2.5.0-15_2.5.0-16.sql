BEGIN TRANSACTION;

CREATE TABLE acl_module_privileges (
    id              INTEGER     NOT NULL    PRIMARY KEY,
    id_resource     INTEGER     NOT NULL,
    privilege       VARCHAR(32) NOT NULL,
    desc_privilege  TEXT,

    FOREIGN KEY (id_resource) REFERENCES acl_resource(id)
);

CREATE TABLE acl_module_user_permissions (
    id                  INTEGER     NOT NULL    PRIMARY KEY,
    id_user             INTEGER     NOT NULL,
    id_module_privilege INTEGER     NOT NULL,

    FOREIGN KEY (id_user) REFERENCES acl_user(id),
    FOREIGN KEY (id_module_privilege) REFERENCES acl_module_privileges(id)
);

CREATE TABLE acl_module_group_permissions (
    id                  INTEGER     NOT NULL    PRIMARY KEY,
    id_group            INTEGER     NOT NULL,
    id_module_privilege INTEGER     NOT NULL,

    FOREIGN KEY (id_group) REFERENCES acl_group(id),
    FOREIGN KEY (id_module_privilege) REFERENCES acl_module_privileges(id)
);

COMMIT;

