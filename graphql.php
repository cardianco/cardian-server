<?php declare(strict_types=1);

require_once __DIR__.'/vendor/autoload.php';
require_once __DIR__.'/src/core/database.php';
require_once __DIR__.'/src/api/graphql/resolver.php';
require_once __DIR__.'/config.php';

use GraphQL\GraphQL;
use GraphQL\Utils\BuildSchema;
use \src\core\database;
use \src\api\resolver;

if (!function_exists('getallheaders')) {
    function getallheaders() {
        $headers = [];
        $mapKeys = array(
            'CONTENT_TYPE'   => 'Content-Type',
            'CONTENT_LENGTH' => 'Content-Length',
            'CONTENT_MD5'    => 'Content-Md5',
        );

        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headers[str_replace(['_',' '], '-', substr($key, 5))] = $value;
            } elseif (isset($copy_server[$key])) {
                $headers[$mapKeys[$key]] = $value;
            }
        }
        return array_change_key_case($headers, CASE_LOWER);
    }
}

$sessionToken = getallheaders()['stoken'] ?? "";
$userToken = getallheaders()['utoken'] ?? "";
$ip = $_SERVER['REMOTE_ADDR'] ?? "";

try {
    assert($sessionToken || $userToken, 'No session or user token provided.');
    // $userId = $sessionId = 1;

    $cdb = new database(constant('DB_NAME'), constant('DB_USER'), constant('DB_PASS'));

    if($sessionToken) {
        $result = $cdb->findSessionIdByToken($sessionToken) ?? [];
        $userId = $result['uid'] ?? -1;
        $sessionId = $result['id'] ?? -1;

        assert(!empty($result), 'No user found with the provided session token. Please create a session first.');
    } else if($userToken) {
        $result = $cdb->findUserIdByToken($userToken) ?? [];
        $userId = $result['uid'] ?? -1;
        $sessionId = -1;

        assert(!empty($result), 'No user was found with the provided user token. Please register an account or contact the website admin.');
    }

    $schema = BuildSchema::build(file_get_contents(__DIR__."/src/api/graphql/schema.graphql"));
    $rootValue = resolver::values($cdb);

    $rootValue['uid'] = $userId;
    $rootValue['sid'] = $sessionId;
    $rootValue['ip'] = $ip;

    $rawInput = file_get_contents('php://input');

    assert($rawInput !== false, 'Failed to get php://input');

    $input = json_decode($rawInput, true);
    $query = $input['query'] ?? "";
    $variables = $input['variables'] ?? null;

    $result = GraphQL::executeQuery($schema, $query, $rootValue, null, $variables);
} catch (Throwable $e) {
    $result = ['error' => [
        'message' => $e->getMessage()
    ]];
}

header('Content-Type: application/json; charset=UTF-8');
echo json_encode($result);
?>