<?php

declare(strict_types=1);

namespace WyriHaximus\React\Tests\PSR3\Loggly;

use PHPUnit\Framework\Attributes\Test;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LogLevel;
use React\EventLoop\Loop;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use RuntimeException;
use WyriHaximus\AsyncTestUtilities\AsyncTestCase;
use WyriHaximus\React\PSR3\Loggly\LogglyBulkLogger;

use function count;
use function explode;
use function json_decode;
use function React\Async\await;
use function str_repeat;

final class LogglyBulkLoggerTest extends AsyncTestCase
{
    #[Test]
    public function throwsOnInvalidLevel(): void
    {
        self::expectException(InvalidArgumentException::class);

        LogglyBulkLogger::create('foo.bar')->log('invalid level', 'Foo');
    }

    #[Test]
    public function createFromHttpClientReturnsLoggerWithProvidedClient(): void
    {
        $browser = new SpyBrowser();

        $logger = LogglyBulkLogger::createFromHttpClient($browser, 'test-token', 0.1);

        $logger->log(LogLevel::INFO, 'hello');

        self::assertCount(0, $browser->postCalls);
    }

    #[Test]
    public function sendBuffersDataAndSendsBulkOnTimer(): void
    {
        $browser = new SpyBrowser();

        $logger = LogglyBulkLogger::createFromHttpClient($browser, 'test-token', 0.1);

        $logger->log(LogLevel::INFO, 'first message');
        $logger->log(LogLevel::ERROR, 'second message');

        await($this->waitForPostCalls($browser, 1));

        self::assertCount(1, $browser->postCalls);
        self::assertSame(
            'https://logs-01.loggly.com/bulk/test-token',
            $browser->postCalls[0]['url'],
        );

        $body = $browser->postCalls[0]['body'];
        self::assertStringContainsString('info first message', $body);
        self::assertStringContainsString('error second message', $body);
    }

    #[Test]
    public function sendSendsBulkWhenBufferExceedsMaxBodyLength(): void
    {
        $browser = new SpyBrowser();

        $logger = LogglyBulkLogger::createFromHttpClient($browser, 'test-token', 10.0);

        $largeMessage = str_repeat('x', LogglyBulkLogger::MAX_LINE_LENGTH - 100);
        $chunkSize    = LogglyBulkLogger::MAX_BODY_LENGTH / LogglyBulkLogger::MAX_LINE_LENGTH;

        for ($i = 0; $i < $chunkSize + 1; $i++) {
            $logger->log(LogLevel::INFO, $largeMessage);
        }

        self::assertCount(1, $browser->postCalls);
        self::assertSame(
            'https://logs-01.loggly.com/bulk/test-token',
            $browser->postCalls[0]['url'],
        );
    }

    #[Test]
    public function sendSkipsDataLongerThanMaxLineLength(): void
    {
        $browser = new SpyBrowser();

        $logger = LogglyBulkLogger::createFromHttpClient($browser, 'test-token', 0.1);

        $tooLong = str_repeat('x', LogglyBulkLogger::MAX_LINE_LENGTH + 100);
        $logger->log(LogLevel::INFO, $tooLong);

        $normal = 'normal message';
        $logger->log(LogLevel::INFO, $normal);

        await($this->waitForPostCalls($browser, 1));

        self::assertCount(1, $browser->postCalls);
        self::assertStringNotContainsString($tooLong, $browser->postCalls[0]['body']);
        self::assertStringContainsString($normal, $browser->postCalls[0]['body']);
    }

    #[Test]
    public function sendBulkSendsCorrectlyFormattedBulkData(): void
    {
        $browser = new SpyBrowser();

        $logger = LogglyBulkLogger::createFromHttpClient($browser, 'test-token', 0.1);

        $logger->log(LogLevel::INFO, 'msg1');
        $logger->log(LogLevel::ERROR, 'msg2');

        await($this->waitForPostCalls($browser, 1));

        self::assertCount(1, $browser->postCalls);

        $lines = explode(LogglyBulkLogger::LF, $browser->postCalls[0]['body']);
        self::assertCount(2, $lines);

        /** @var array<string, mixed> $first */
        $first = json_decode($lines[0], true);
        /** @var array<string, mixed> $second */
        $second = json_decode($lines[1], true);

        self::assertSame(LogLevel::INFO, $first['level']);
        self::assertSame(LogLevel::ERROR, $second['level']);
    }

    #[Test]
    public function ensureTimerIsIdempotent(): void
    {
        $browser = new SpyBrowser();

        $logger = LogglyBulkLogger::createFromHttpClient($browser, 'test-token', 0.1);

        $logger->log(LogLevel::INFO, 'first');
        $logger->log(LogLevel::INFO, 'second');
        $logger->log(LogLevel::INFO, 'third');

        await($this->waitForPostCalls($browser, 1));

        self::assertCount(1, $browser->postCalls);
        $body = $browser->postCalls[0]['body'];
        self::assertStringContainsString('info first', $body);
        self::assertStringContainsString('info second', $body);
        self::assertStringContainsString('info third', $body);
    }

    /** @return PromiseInterface<null> */
    private function waitForPostCalls(SpyBrowser $browser, int $expectedCount): PromiseInterface
    {
        $deferred = new Deferred();

        if (count($browser->postCalls) >= $expectedCount) {
            $deferred->resolve(null);

            return $deferred->promise();
        }

        Loop::addTimer(2, static fn () => $deferred->reject(new RuntimeException('Timed out waiting for POST calls')));

        $check = static function () use ($browser, $expectedCount, $deferred, &$check): void {
            if (count($browser->postCalls) >= $expectedCount) {
                $deferred->resolve(null);
            } else {
                Loop::futureTick($check);
            }
        };

        Loop::futureTick($check);

        return $deferred->promise();
    }
}
