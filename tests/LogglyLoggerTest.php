<?php

declare(strict_types=1);

namespace WyriHaximus\React\Tests\PSR3\Loggly;

use Psr\Log\InvalidArgumentException;
use WyriHaximus\AsyncTestUtilities\AsyncTestCase;
use WyriHaximus\React\PSR3\Loggly\LogglyLogger;

final class LogglyLoggerTest extends AsyncTestCase
{
    public function testThrowsOnInvalidLevel(): void
    {
        self::expectException(InvalidArgumentException::class);

        LogglyLogger::create('foo.bar')->log('invalid level', 'Foo');
    }
}
