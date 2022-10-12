<?php
/**
 * @api web-socket
 * @author smr
 * @package dev
 * @version 0.1.0
 * @copyright LGPLv3
 */
namespace cardian\api\socket;

use cardian\logger\handler\FileHandler;
use cardian\logger\Logger;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

use Workerman\Worker;

use SplObjectStorage;
use Exception;
use Workerman\Connection\TcpConnection;

class Socket {
    /**
     * @var Worker
     */
    public $tcpWorker;
    /**
     * @var NullLogger
     */
    public LoggerInterface $logger;

    function __construct(string $tcpUrl = "tcp://0.0.0.0", int $port = 8080, int $processCount = 4, ?LoggerInterface $logger = null) {
        $logFileName = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'log' . DIRECTORY_SEPARATOR . date('Y-m-d') . '.log';

        $handler = new FileHandler($logFileName);

        $this->logger = $logger ?? new Logger($handler); // If there was no custom logger create one.
        $tcpWorker = new Worker("$tcpUrl:$port");
        $tcpWorker->count = $processCount;

        $tcpWorker->onWorkerReload = [$this, 'onWorkerReload'];
        $tcpWorker->onConnect = [$this, 'onConnect'];
        $tcpWorker->onMessage = [$this, 'onMessage'];
        $tcpWorker->onClose = [$this, 'onClose'];
        $tcpWorker->onError = [$this, 'onError'];

        $this->tcpWorker = $tcpWorker;
    }
    /**
     * Run server worker.
     */
    public static function runAll() { Worker::runAll(); }

    /**
     * @param worker TCP server worker.
     */
    function onWorkerReload($worker) {}

    /**
     * @param connection Opened connection.
     */
    function onConnect(TcpConnection $connection) {}

    /**
     * @param connection connection which sent message.
     * @param data Recived data.
     */
    function onMessage(TcpConnection $connection, $data) {
        echo $connection->getRemoteIp();
        echo $connection->getRemoteAddress();
        $connection->send('Hello;'.$data);
    }

    /**
     * @param connection Closed connection.
     */
    function onClose(TcpConnection $connection) {
        echo "Connection closed.";
        $this->logger->info("connection closed.");
    }

    /**
     * @param connection
     * @param code Error code.
     * @param msg Error message.
     */
    function onError(TcpConnection $connection, $code, string $msg) {
        $this->logger->error("Socket Error: $msg");
    }
}