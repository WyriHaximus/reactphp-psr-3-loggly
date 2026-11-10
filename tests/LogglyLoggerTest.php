<?php

declare(strict_types=1);

namespace WyriHaximus\React\Tests\PSR3\Loggly;

use PHPUnit\Framework\Attributes\Test;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LogLevel;
use WyriHaximus\AsyncTestUtilities\AsyncTestCase;
use WyriHaximus\React\PSR3\Loggly\LogglyLogger;

use function json_decode;
use function strlen;

final class LogglyLoggerTest extends AsyncTestCase
{
    #[Test]
    public function throwsOnInvalidLevel(): void
    {
        self::expectException(InvalidArgumentException::class);

        LogglyLogger::create('foo.bar')->log('invalid level', 'Foo');
    }

    #[Test]
    public function createFromHttpClientReturnsLoggerWithProvidedClient(): void
    {
        $browser = new SpyBrowser();

        $logger = LogglyLogger::createFromHttpClient($browser, 'test-token');

        $logger->log(LogLevel::INFO, 'hello');

        /** @var array{url: string, headers: array<string, string>, body: string} $call */
        $call = $browser->postCalls[0];

        self::assertCount(1, $browser->postCalls);
        self::assertSame(
            'https://logs-01.loggly.com/inputs/test-token',
            $call['url'],
        );
        self::assertSame(
            'application/json',
            $call['headers']['Content-Type'],
        );
        self::assertSame(
            strlen($call['body']),
            (int) $call['headers']['Content-Length'],
        );

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($call['body'], true);
        self::assertSame(LogLevel::INFO, $decoded['level']);
        self::assertSame('info hello', $decoded['message']);
    }

    #[Test]
    public function sendPostsCorrectJsonToLogglyEndpoint(): void
    {
        $browser = new SpyBrowser();

        $logger = LogglyLogger::createFromHttpClient($browser, 'my-token');

        $logger->log(LogLevel::ERROR, 'something broke', ['key' => 'value']);

        self::assertCount(1, $browser->postCalls);
        self::assertSame(
            'https://logs-01.loggly.com/inputs/my-token',
            $browser->postCalls[0]['url'],
        );

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($browser->postCalls[0]['body'], true);
        self::assertSame(LogLevel::ERROR, $decoded['level']);
        self::assertSame('error something broke', $decoded['message']);
        self::assertSame(['key' => 'value'], $decoded['context']);
    }
}
