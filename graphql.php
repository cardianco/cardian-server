<?php declare(strict_types=1);

require_once __DIR__.'/vendor/autoload.php';
require_once __DIR__.'/src/core/database.php';
require_once __DIR__.'/src/api/graphql/resolver.php';

use GraphQL\GraphQL;
use GraphQL\Utils\BuildSchema;
use \src\core\database;
use \src\api\resolver;

$sessionToken = $_SERVER['stoken'] ?? "";
$userToken = $_SERVER['utoken'] ?? "";
$ip = $_SERVER['REMOTE_ADDR'] ?? "";

try {
    // assert($sessionToken || $userToken, 'No session or user token provided.');

    $cdb = new database("db_cardian", "root", "");

    $userId = 1;//$cdb->findUserIdByToken($userToken);
    $sessionId = 1;//$cdb->findSessionIdByToken($sessionToken);

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