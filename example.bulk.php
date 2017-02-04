<?php declare(strict_types=1);

require 'vendor/autoload.php';

use React\EventLoop\Factory;
use WyriHaximus\React\PSR3\Loggly\LogglyBulkLogger;

$loop = Factory::create();

$logger = LogglyBulkLogger::create($loop, require 'token.php', 10);

$func = function () use ($argv, $logger) {
    $logger->log($argv[1], $argv[2]);
};

for ($i = 1; $i < 25; $i++) {
    $loop->addTimer($i, $func);
}

$loop->run();
