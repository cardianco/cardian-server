<?php
/**
 * @api graph-ql
 * @author smr
 * @package dev
 * @version 0.1.0
 * @copyright LGPLv3
 */

use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use Exception;

$schema = new Schema([
    'query' => $queryType
]);

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);
$query = $input['query'];
$variableValues = $input['variables'] ?? null;

try {
    $result = GraphQL::executeQuery($schema, $query, null, null, $variableValues);
    $output = $result->toArray();
} catch (Exception $e) {
    $output = [
        'errors' => [
            [
                'message' => $e->getMessage()
            ]
        ]
    ];
}
header('Content-Type: application/json');
echo json_encode($output);
?>