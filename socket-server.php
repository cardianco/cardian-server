<?php
/**
 * @author smr
 * @package dev
 * @version 0.1.0
 * @copyright MIT
 */
if(file_exists(dirname(__FILE__).'/vendor/autoload.php')) {
	require_once dirname(__FILE__).'/vendor/autoload.php';
}

use cardian\api\socket\Socket;

if(class_exists('cardian\\api\\socket\\Socket')) {
    $socket = new Socket();
    $socket->runAll(); // Start tcp worker
}
?>