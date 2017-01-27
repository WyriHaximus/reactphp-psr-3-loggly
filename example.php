<?php declare(strict_types=1);

require 'vendor/autoload.php';

use React\EventLoop\Factory;
use WyriHaximus\React\PSR3\Loggly\LogglyLogger;

$loop = Factory::create();

LogglyLogger::create($loop, require 'token.php')->log($argv[1], $argv[2]);

$loop->run();
