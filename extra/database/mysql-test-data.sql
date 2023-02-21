--  Insert a dummy user
-- echo -n 'smr' | sha256sum
INSERT INTO db_cardian.users(token)
    VALUES(X'4f98e2c556b1abf5a58d151813182a7663b06f379863623728ab1ad74fee2bc6');

-- Insert dummy user meta
INSERT INTO user_meta (`uid`, `key`, `value`)
    VALUES(1, 'phonenumber', '01234567890');

-- Insert dummy user meta
INSERT INTO user_meta (`uid`, `key`, `value`)
    VALUES(1, 'email', 'test@test.test');

-- Insert dummy session
INSERT INTO db_cardian.users_sessions(uid, tid, authtoken, mac, ip, port)
    VALUES(1, 2, UNHEX(SHA2(RAND(), 256)), 0, '11223344', 0);

-- Insert dummy status
INSERT INTO statuses(`sid`, fid, `data`)
    VALUES(1, 3, '{"front-light":"on"}');

-- Insert another dummy status
INSERT INTO statuses(`sid`, fid, `data`)
    VALUES(1, 3, '{"engine":"on"}');

-- Insert dummy command
INSERT INTO commands(`sid`, fid, `data`)
    VALUES(1, 3, '{"engine":"off"}');

-- Insert dummy boundury
INSERT INTO boundaries(sid, poly)
    VALUES(1, ST_GeomFromText('POLYGON((0 0, 1 0, 1 1, 0 1, 0 0))'));
