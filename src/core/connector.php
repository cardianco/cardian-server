<?php
/**
 * @author SMR
 * @copyright MIT
 *
 * base connector abstract class.
 */
namespace src\core;

abstract class baseConnector {
    protected $connection;
    protected $DBerror;
    /** @readonly */
    public string $dbname;

    function __construct(string $dbname, string $user, string $pass, $host = 'localhost', $port = 3306) {
        try {
            $this->dbname = $dbname;
            $this->connection = new \PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
            $this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        } catch (\PDOException $e) {
            print "Error!: " . $e->getMessage() . "<br/>";
            $this->abort(500); //die and print contact message
        }
    }

    /**
     * @abstract Direct call to pdo::exec function
     * @return int|bool number of rows affected or false
     */
    public function exec(string $query = ""): int|bool {
        return $this->connection->exec($query);
    }

    /**
     * @abstract Fetch and return all row of executed query
     * @param string query, The SQL query to execute
     */
    public function all(string $query = "", int $mode = \PDO::FETCH_ASSOC): array {
        $result = $this->connection->query($query);
        return $result->fetchAll($mode);
    }

    /**
     * @abstract Fetch and return first row of executed query
     * @param string query, The SQL query to execute
     */
    public function first(string $query = ""): array {
        $result = $this->connection->query($query);
        return $result->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * @abstract Return rows and first row key as key
     * @param string $query
     */
    public function unique(string $query = "", ?string $key = null): array {
        $rows = $this->all($query);
        if(!is_null($rows)) {
            $k = $key ?? array_keys($rows[0] ?? ['id' => ''])[0];
            return array_reduce($rows, fn($c, $row) => array_merge($c, [$row[$k] => $row]), []);
        }
        return [];
    }

    public function strEscape(string $input): string {
        return $this->connection->quote($input);
    }

    public static function abort($code = 0, string $message = "") {
        die(json_encode([
            "status" => -1,
            "code" => $code,
            "message" => $message,
            "info" => "Please contact website administrator."
        ]));
    }
}
?>