BEGIN TRANSACTION;
CREATE TABLE attribute 
(
       id                INTEGER    PRIMARY KEY,
       type              VARCHAR(20),
       qualify           VARCHAR(20),
       insecure          VARCHAR(20),
       host              VARCHAR(20),
       fromuser          VARCHAR(20),
       fromdomain        VARCHAR(20),
       dtmfmode          VARCHAR(20),
       disallow          VARCHAR(20),
       context           VARCHAR(20),
       allow             VARCHAR(20),
       trustrpid         VARCHAR(20),
       sendrpid          VARCHAR(20),
       canreinvite       VARCHAR(20),
       id_provider       INTEGER,
       FOREIGN KEY(id_provider) REFERENCES provider(id)
);
CREATE TABLE provider
(
       id                INTEGER    PRIMARY KEY,
       name              VARCHAR(20),
       domain            VARCHAR(20),
       type_trunk        VARCHAR(20),
       description       VARCHAR(20)
);
CREATE TABLE provider_account
(
       id                INTEGER       PRIMARY KEY,
       account_name      VARCHAR(40),
       username          VARCHAR(40),
       password          VARCHAR(40),
       callerID          VARCHAR(40)   DEFAULT '',
       type              VARCHAR(20),
       qualify           VARCHAR(20),
       insecure          VARCHAR(20),
       host              VARCHAR(20),
       fromuser          VARCHAR(20),
       fromdomain        VARCHAR(20),
       dtmfmode          VARCHAR(20),
       disallow          VARCHAR(20),
       context           VARCHAR(20),
       allow             VARCHAR(20),
       trustrpid         VARCHAR(20),
       sendrpid          VARCHAR(20),
       canreinvite       VARCHAR(20),
       type_trunk        VARCHAR(20),
       status            VARCHAR(20)   DEFAULT 'activate',
       id_provider       INTEGER,
       FOREIGN KEY(id_provider) REFERENCES provider(id)
);

INSERT INTO "attribute" VALUES(1, 'peer', 'yes', 'very', 'ippbx.net2phone.com', '', '', '', 'all', 'from-pstn', 'alaw&ulaw', '', '', 'no', 1);
INSERT INTO "attribute" VALUES(2, 'friend', 'yes', 'very', 'sip.camundanet.com', '', 'camundanet.com', 'rfc2833', 'all', 'from-pstn', 'gsm', '', '', 'no', 2);
INSERT INTO "attribute" VALUES(3, 'peer', 'yes', '', 'outbound1.vitelity.net', '', '', '', '', 'from-trunk', '', 'yes', 'yes;', 'no', 3);
INSERT INTO "attribute" VALUES(4, 'friend', 'yes', 'very', 'sip1.starvox.com', '', '', 'rfc2833', '', 'from-pstn', '', '', '', '', 4);
INSERT INTO "attribute" VALUES(6, 'peer', 'yes', 'very', 'freephonie.net', '', 'freephonie.net', 'auto', 'all', 'from-trunk', 'alaw', '', '', 'no', 6);
INSERT INTO "attribute" VALUES(7, 'peer', 'yes', 'very', 'sip.ovh.net', '', 'sip.ovh.net', 'auto', 'all', 'from-trunk', 'alaw', '', '', 'no', 7);
INSERT INTO "attribute" VALUES(8, 'peer', 'yes', '', 'sip.voipdiscount.com', '', '', 'rfc2833', 'all', 'from-trunk', 'alaw', '', '', 'no', 8);
INSERT INTO "attribute" VALUES(9, 'peer', 'yes', 'very', 'gateway.circuitid.com', NULL, NULL, 'rfc2833', 'all', 'from-pstn', 'alaw&ulaw&gsm', 'no', 'no', 'no', 9);

INSERT INTO "provider" VALUES(1, 'Net2Phone', '', 'SIP', 'trunk type SIP');
INSERT INTO "provider" VALUES(2, 'CamundaNET', '', 'SIP', 'trunk type SIP');
INSERT INTO "provider" VALUES(3, 'Vitelity', '', 'SIP', 'trunk type SIP');
INSERT INTO "provider" VALUES(4, 'StarVox', '', 'SIP', 'trunk type SIP');
INSERT INTO "provider" VALUES(6, 'Freephonie', 'freephonie.net', 'SIP', 'trunk type SIP');
INSERT INTO "provider" VALUES(7, 'OVH', 'sip.ovh.net', 'SIP', 'trunk type SIP');
INSERT INTO "provider" VALUES(8, 'VoIPDiscount', 'sip.voipdiscount.com', 'SIP', 'trunk type SIP');
INSERT INTO "provider" VALUES(9, 'CircuitID', '', 'SIP', 'trunk type SIP');

COMMIT;


