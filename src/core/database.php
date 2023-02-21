<?php
/**
 * @author SMR
 * @copyright MIT
 *
 * base connector abstract class.
 */
namespace src\core;

require_once __DIR__."/connector.php";

class database extends baseConnector {
    function __construct(string $dbname, string $user, string $pass, $ip = 'localhost', $port = 3306) {
        parent::__construct($dbname, $user, $pass, $ip, $port);
    }

    /**
     * @param string $str, Input SQL polygon as string.
     * @return array, SQL polygon as associative array.
     */
    public static function fromSqlPoly(string $polyString): array {
        $csv = trim($polyString, ")(POLYGON");
        $list = array_map(fn($v) => array_combine(['x','y'], explode(" ", $v)), explode(",", $csv));
        return $list;
    }

    /**
     * @param string $str, Input SQL polygon as string.
     * @return array, SQL polygon as associative array.
     */
    public static function toSqlPoly(array $points): string {
        return join(array_map(fn($v) => "$v[x] $v[y]", $points), ",");
    }

    /**
     * @param string $token, User's token.
     *  @return array, user ID.
     */
    public function findUserIdByToken(string $token): ?int {
        $token = $this->strEscape($token);
        $user = $this->first("SELECT id FROM `states` WHERE token=$token");
        return $user ? $user['id'] : false;
    }

    /**
     * @param string $token, session authtoken.
     * @return array, session ID and the user ID.
     *
     */
    public function findSessionIdByToken(string $token): array {
        $token = $this->strEscape($token);
        return $this->first("SELECT id, `uid` FROM `users_sessions` WHERE authtoken=$token");
    }

    /**
     * @param ?int $id, state ID, or null.
     * @return array, one state based on the input ID, or all states.
     */
    public function getStates(?int $id = null): array {
        if($id) return $this->first("SELECT * FROM `states` WHERE id=$id");
        else return $this->all("SELECT * FROM `states`");
    }

    /**
     * @param ?int $id, field ID, or null.
     * @return array, one field based on the input ID, or all fields.
     */
    public function getFields(?int $id = null): array {
        if($id) return $this->first("SELECT * FROM `fields` WHERE id=$id");
        else return $this->all("SELECT * FROM `fields`");
    }

    /**
     * @return array, All fields' IDs and names as key-value pairs.
     */
    public function getFieldsPair(): array {
        return $this->all("SELECT id, `name` FROM fields", \PDO::FETCH_KEY_PAIR);
    }

    /**
     * @param ?int $id, Device type ID, or null.
     * @return array, one device type based on the input ID, or all device types.
     */
    public function getDeviceType(?int $id = null): array {
        if($id) return $this->first("SELECT * FROM `device_type` WHERE id=$id");
        else return $this->all("SELECT * FROM `device_type`");
    }

    /**
     * @param int $uid, User ID.
     * @return array, User information as key-value pairs. (i.e. ['email' => 'example@mail.com'])
     */
    public function getUserInfo(int $uid): array {
        return $this->all("SELECT `key`,`value` from user_meta WHERE `uid`=$uid", \PDO::FETCH_KEY_PAIR);
    }

    /**
     * @param int $id
     * @return array, User's data.
     */
    public function getUser(int $id): array {
        return $this->first("SELECT id,stid,hex(token) as token,UNIX_TIMESTAMP(utc) as utc
                                FROM `users` WHERE id=$id");
    }

    /**
     * @param int $sessionId
     * @return array Session's data.
     */
    public function getSession(int $sessionId): array {
        return $this->first("SELECT id,`uid`,tid,stid,hex(authtoken) as authtoken,mac,UNIX_TIMESTAMP(register) as registerUTC,
                                UNIX_TIMESTAMP(access) as accessUTC,ip
                                FROM `users_sessions` WHERE id=$sessionId");
    }

    public function getUserSessions(int $userId): array {
        return $this->all("SELECT id,`uid`,tid,stid,hex(authtoken) as authtoken,mac,UNIX_TIMESTAMP(utc) as registerUTC,
                                UNIX_TIMESTAMP(ts) as accessUTC,ip
                                FROM `users_sessions` WHERE `uid`=$userId");
    }

    public function getStatusHistory(?int $sessionId, int $limit = 1, $accessed = false): ?array {
        if(is_null($sessionId)) return null;

        $aw = is_bool($accessed) ? "AND accessed=$accessed" : "";
        return $this->all("SELECT id,fid,`data`,UNIX_TIMESTAMP(utc) as utc,accessed
                                FROM `statuses` WHERE `sid`=$sessionId $aw ORDER BY id DESC LIMIT $limit");
    }

    public function getCommandHistory(int $sessionId, int $limit = 1, $accessed = false): ?array {
        if(is_null($sessionId)) return null;

        $accCnd = is_bool($accessed) ? "AND accessed=$accessed" : "";
        return $this->all("SELECT id,fid,`data`,UNIX_TIMESTAMP(utc) as utc,accessed
                                FROM `commands` WHERE `sid`=$sessionId $accCnd ORDER BY id DESC LIMIT $limit");
    }

    public function getBoundariesByUser(int $userId): array {
        $bndry = $this->all("SELECT b.id, us.id as `sid`, us.uid, ST_AsText(b.poly) as poly, UNIX_TIMESTAMP(utc) as utc
                                        FROM `boundaries` as b JOIN users_sessions as us on b.`sid`=us.id
                                        WHERE `uid`=$userId;");
        return array_map(function ($v) {
            return array_merge($v, ['poly' => database::fromSqlPoly($v['poly'])]);
        }, $bndry);
    }

    public function getBoundariesBySession(int $sessionId): array {
        $bndry = $this->all("SELECT id,`sid`,ST_AsText(b.poly) as poly, UNIX_TIMESTAMP(utc) as utc
                                FROM `boundaries` WHERE `sid`=$sessionId");
        return array_map(function ($v) {
            return array_merge($v, ['poly' => database::fromSqlPoly($v['poly'])]);
        }, $bndry);
    }

    public function getLatestStatus(int $userId): array {
        return $this->first("SELECT id,`uid`,`data`,UNIX_TIMESTAMP(utc) as utc
                                FROM `latest_status` WHERE uid=$userId");
    }

    public function touchSession(int $id): bool {
        return boolval($this->exec("UPDATE users_sessions SET access=current_timestamp()"));
    }

    /**
     * @param int $userId
     * @param string $data, JSON string.
     * @return bool|int, Number of affected rows, or false on failure.
     */
    public function updateLatestStatus(int $userId, string $data): bool|int {
        return $this->exec("INSERT INTO latest_status(`uid`, `data`) VALUES($userId, $data)
                                ON DUPLICATE KEY UPDATE `data`=JSON_MERGE_PATCH(`data`, $data);");
    }

    public function addStatus(int $sid, int $fid, string $data): int {
        $data = $this->strEscape($data);
        $result = $this->first("INSERT INTO db_cardian.statuses(`sid`, fid, `data`) VALUES($sid, $fid, $data);".
                               "SELECT LAST_INSERT_ID() as id");
        return $result['id'] ?? 0;
    }

    public function addCommand(int $sid, int $fid, string $data): int {
        $data = $this->strEscape($data);
        $res = $this->first("INSERT INTO commands(`sid`, fid, `data`) VALUES($sid, $fid, $data);".
                            "SELECT LAST_INSERT_ID() as id");
        return $res['id'] ?? 0;
    }

    public function addBoundary(int $sid, int $stid, array $pointList): ?int {
        $poly = $this->strEscape($this->toSqlPoly($pointList));
        return $this->first("INSERT INTO boundaries(`sid`, `stid`, `poly`) VALUES($sid, $stid, ST_GeomFromText($poly));".
                           "SELECT LAST_INSERT_ID() as id")['id'] ?: null;
    }

    /**
     * @param int $id
     * @param int $stid
     * @param array $pointList
     * @return bool
     * @abstract Update boundary data.
     */
    public function updateBoundary(int $id, int $stid, array $pointList): bool {
        $poly = $this->strEscape($this->toSqlPoly($pointList));
        return boolval($this->exec("UPDATE `boundaries` SET `stid`=$stid, `poly`=ST_GeomFromText($poly), `utc`=current_timestamp() WHERE `id`=$id"));
    }

    /**
     * @param int $uid
     * @param int $typeId
     * @param int $mac
     * @param string $ip
     * @param int $try
     * @return array
     */
    public function createSession(int $uid, int $typeId, int $mac, string $ip, int $try = 5): array {
        $ip = $this->strEscape($ip);
        do {
            $res = $this->first("INSERT INTO db_cardian.users_sessions(`uid`, tid, authtoken, mac, ip)
                                    VALUES($uid, $typeId, UNHEX(SHA2(RAND(), 256)), $mac, $ip);".
                                "SELECT id,`uid`,tid,stid,hex(authtoken) as authtoken,mac,UNIX_TIMESTAMP(register) as registerUTC,
                                    UNIX_TIMESTAMP(access) as accessUTC, ip
                                    From users_sessions WHERE id=LAST_INSERT_ID()");
            $try--;
        } while(empty($res) && $try);
        return $res;
    }

    public function removeCommands(array $idList): bool|int {
        $str = join($idList, ',');
        return count($idList) ? $this->exec("DELETE FROM commands WHERE `id` IN ($str)") : false;
    }

    public function removeBoundaries(array $idList): bool|int {
        $str = join($idList, ',');
        return count($idList) ? $this->exec("DELETE FROM boundaries WHERE `id` IN ($str)") : false;
    }

    /**
     *  @param array $idList
     */
    public function terminateSessions(array $idList): bool|int {
        $str = join($idList, ',');
        return count($idList) ? $this->exec("DELETE FROM users_sessions WHERE `id` IN ($str)") : false;
    }
}