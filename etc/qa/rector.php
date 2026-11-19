<?php

declare(strict_types=1);

use WyriHaximus\RectorPHP\RectorConfig as WyriHaximusRectorConfig;

return WyriHaximusRectorConfig::configure(dirname(__DIR__, 2))
    ->withPaths([
        __DIR__ . '/../../src',
        __DIR__ . '/../../tests/AbstractLogglyLoggerTest.php',
        __DIR__ . '/../../tests/LogglyLoggerTest.php',
        __DIR__ . '/../../tests/LogglyBulkLoggerTest.php',
        __DIR__ . '/../../tests/SpyLogglyLogger.php',
    ]);
