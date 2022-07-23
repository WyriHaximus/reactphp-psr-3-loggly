<?php

declare(strict_types=1);

namespace WyriHaximus\React\Tests\PSR3\Loggly;

use Psr\Log\InvalidArgumentException;
use WyriHaximus\AsyncTestUtilities\AsyncTestCase;
use WyriHaximus\React\PSR3\Loggly\LogglyBulkLogger;

final class LogglyBulkLoggerTest extends AsyncTestCase
{
    public function testThrowsOnInvalidLevel(): void
    {
        self::expectException(InvalidArgumentException::class);

        LogglyBulkLogger::create('foo.bar')->log('invalid level', 'Foo');
    }
}
