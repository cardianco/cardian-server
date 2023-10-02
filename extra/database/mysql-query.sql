CREATE DATABASE db_cardian;
USE db_cardian;

CREATE TABLE device_type (
    id bigint NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `name` varchar(32) NOT NULL DEFAULT '',
    `desc` text NOT NULL
);

-- initial device types:
INSERT INTO db_cardian.device_type(`name`,`desc`)
VALUES ('vehicle',''),('android',''),('linux',''),('win',''),('ios','');

CREATE TABLE `states` (
    id bigint NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `name` varchar(32) NOT NULL DEFAULT '',
    `desc` text NOT NULL
);

-- initial state types:
INSERT INTO db_cardian.`states`(`name`,`desc`)
VALUES ('activated',''),('disabled',''),('suspended',''),('readonly','');

CREATE TABLE fields (
    id bigint NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `name` varchar(32) NOT NULL DEFAULT '',
    `desc` text NOT NULL
);

-- initial fields:
INSERT INTO db_cardian.fields(`name`,`desc`)
VALUES ('json',''),('temperature',''),('location','');

CREATE TABLE users (
    id bigint NOT NULL PRIMARY KEY AUTO_INCREMENT,
    stid bigint NOT NULL DEFAULT 1, -- state id
    token binary(32) NOT NULL UNIQUE, -- user token 256 bits (32 bytes)
    `utc` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP, -- user register UTC time
    FOREIGN KEY (stid) REFERENCES states(id)
);

CREATE TABLE users_sessions (
    -- note 1: recursive mode will be used to erase user_sessions.
    -- note 2: user_sessions is only removed in exceptional circumstances.
    id bigint NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `uid` bigint NOT NULL, -- user id
    tid bigint NOT NULL, -- device type id
    stid bigint NOT NULL DEFAULT 1, -- state id
    authtoken binary(32) NOT NULL UNIQUE, -- user token 256 bits (32 bytes)
    mac bigint NOT NULL, -- device mac
    register datetime NOT NULL DEFAULT CURRENT_TIMESTAMP, -- register UTC time
    access datetime NOT NULL DEFAULT CURRENT_TIMESTAMP, -- last access UTC time
    ip varchar(32) NOT NULL, -- device IP in HEX (8/32 for v4/v6)
    port smallint NOT NULL, -- device port
    FOREIGN KEY (`uid`) REFERENCES users(id),
    FOREIGN KEY (tid) REFERENCES device_type(id),
    FOREIGN KEY (stid) REFERENCES states(id)
);

CREATE TABLE boundaries (
    id bigint NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `sid` bigint NOT NULL, -- session id
    stid bigint NOT NULL DEFAULT 1, -- state id
    poly polygon NOT NULL, -- boudury polygon
    utc datetime NOT NULL DEFAULT CURRENT_TIMESTAMP, -- UTC time
    FOREIGN KEY (`sid`) REFERENCES users_sessions(id),
    FOREIGN KEY (stid) REFERENCES states(id)
);

CREATE TABLE latest_status (
    id bigint NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `uid` bigint NOT NULL UNIQUE, -- session id
    `data` json NOT NULL, -- creation date
    utc datetime NOT NULL DEFAULT CURRENT_TIMESTAMP, -- UTC time
    FOREIGN KEY (`uid`) REFERENCES users(id)
);

CREATE TABLE statuses (
    -- note 1: As data grows so fast, statuses will only last 24 hours.
    id bigint NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `sid` bigint NOT NULL, -- session id
    `fid` bigint NOT NULL, -- field id
    `data` json NOT NULL, -- status data
    utc datetime NOT NULL DEFAULT CURRENT_TIMESTAMP, -- UTC time
    accessed boolean NOT NULL DEFAULT FALSE,
    FOREIGN KEY (`fid`) REFERENCES fields(id),
    FOREIGN KEY (`sid`) REFERENCES users_sessions(id)
);

CREATE TABLE commands (
    id bigint NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `sid` bigint NOT NULL, -- session id
    `fid` bigint NOT NULL, -- field id
    `data` json NOT NULL, -- command data
    utc datetime NOT NULL DEFAULT CURRENT_TIMESTAMP, -- UTC time
    accessed boolean NOT NULL DEFAULT FALSE,
    FOREIGN KEY (`fid`) REFERENCES fields(id),
    FOREIGN KEY (`sid`) REFERENCES users_sessions(id)
);

-- optional
CREATE TABLE user_meta (
    id bigint NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `uid` bigint NOT NULL,
    `key` tinytext NOT NULL,
    `value` longtext NOT NULL,
    FOREIGN KEY (`uid`) REFERENCES users(id)
);

-- automated remove vehicle's status event.
CREATE EVENT vehicle_status_cleaner
ON SCHEDULE AT CURRENT_TIMESTAMP + INTERVAL 1 DAY
ON COMPLETION PRESERVE
DO DELETE LOW_PRIORITY FROM db_cardian.statuses WHERE utc < DATE_SUB(NOW(), INTERVAL 1 DAY);