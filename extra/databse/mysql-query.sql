CREATE DATABASE db_cardian;
USE db_cardian;

CREATE TABLE device_type (
    id int NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `name` varchar(32) NOT NULL DEFAULT '',
    `desc` text NOT NULL
);

-- Initial device types:
INSERT INTO db_cardian.device_type(`name`,`desc`)
VALUES ('vehicle',''),('android',''),('linux',''),('win',''),('ios','');

CREATE TABLE `states` (
    id int NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `name` varchar(32) NOT NULL DEFAULT '',
    `desc` text NOT NULL
);

-- Initial user status:
INSERT INTO db_cardian.`states`(`name`,`desc`)
VALUES ('active',''),('disable',''),('suspension','');

CREATE TABLE users (
    id int NOT NULL PRIMARY KEY AUTO_INCREMENT,
    stid int NOT NULL DEFAULT 1, -- state id
    token binary(32) NOT NULL UNIQUE, -- user token 256 bits (32 Bytes)
    `utc` timestamp NOT NULL, -- user register UTC time
    FOREIGN KEY (stid) REFERENCES states(id)
);

--INSERT INTO db_cardian.users(token,`utc`) VALUES ('active',''),('disable',''),('suspension','');

CREATE TABLE users_sessions (
    -- Note 1: Recursive mode will be used to erase user_sessions.
    -- Note 2: user_sessions is only removed in exceptional circumstances.
    id bigint NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `uid` int NOT NULL, -- User id
    tid int NOT NULL, -- Device type id
    stid int NOT NULL DEFAULT 1, -- state id
    authtoken binary(32) NOT NULL UNIQUE, -- user token 256 bits (32 Bytes)
    mac bigint NOT NULL, -- Device mac
    utc timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP, -- Start UTC time
    ts timestamp NOT NULL, -- UTC Timestamp
    ip varchar(32) NOT NULL, -- Device IP in HEX (8/32 for v4/v6)
    port smallint NOT NULL, -- Device port
    `readonly` boolean NOT NULL, -- Is readonly
    FOREIGN KEY (`uid`) REFERENCES users(id),
    FOREIGN KEY (tid) REFERENCES device_type(id),
    FOREIGN KEY (stid) REFERENCES states(id)
);

CREATE TABLE boundaries (
    id int NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `sid` bigint NOT NULL, -- Session id
    stid int NOT NULL DEFAULT 1, -- state id
    poly polygon NOT NULL, -- Boudury polygon
    utc timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP, -- UTC time
    FOREIGN KEY (`sid`) REFERENCES users_sessions(id),
    FOREIGN KEY (stid) REFERENCES states(id)
);

CREATE TABLE latest_status (
    id int NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `uid` bigint NOT NULL, -- Session id
    `data` json NOT NULL,
    utc timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP, -- UTC time
    FOREIGN KEY (`uid`) REFERENCES users(id)
);

CREATE TABLE statuses (
    -- Note 1: As data grows so fast, statuses will only last 24 hours.
    id bigint NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `sid` bigint NOT NULL,
    `data` json NOT NULL,
    utc timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP, -- UTC time
    code int NOT NULL DEFAULT 0, -- Status code, indicates whether or not the status was received and confirmed by one of the user devices.
    FOREIGN KEY (`sid`) REFERENCES users_sessions(id)
);

CREATE TABLE commands (
    id bigint NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `sid` bigint NOT NULL,
    `data` json NOT NULL,
    utc timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP, -- UTC time
    code int NOT NULL DEFAULT 0, -- Status code, indicates whether or not the command was received and executed by vehicle.
    FOREIGN KEY (`sid`) REFERENCES users_sessions(id)
);

-- Automated remove vehicle status event.
CREATE EVENT vehicle_status_cleaner
ON SCHEDULE AT CURRENT_TIMESTAMP + INTERVAL 1 DAY
ON COMPLETION PRESERVE
DO DELETE LOW_PRIORITY FROM db_cardian.statuses WHERE utc < DATE_SUB(NOW(), INTERVAL 1 DAY);

/**
-- Optional
CREATE TABLE user_meta (
    id int NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `uid` int NOT NULL,
    `key` tinytext NOT NULL,
    `value` longtext NOT NULL,
    utc timestamp NOT NULL,
    FOREIGN KEY (`uid`) REFERENCES users(id)
);

CREATE TABLE user_info (
    id int NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `uid` int NOT NULL,
    username tinytext NOT NULL
);
*/