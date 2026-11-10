<?php

declare(strict_types=1);

namespace WyriHaximus\React\Tests\PSR3\Loggly;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LogLevel;
use Stringable;
use WyriHaximus\AsyncTestUtilities\AsyncTestCase;

use function json_decode;
use function tmpfile;

final class AbstractLogglyLoggerTest extends AsyncTestCase
{
    #[Test]
    public function logDelegatesFormattedJsonToSend(): void
    {
        $logger = new SpyLogglyLogger();

        $logger->log(LogLevel::INFO, 'hello');

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($logger->getSentData(), true);
        self::assertArrayHasKey('level', $decoded);
        self::assertArrayHasKey('message', $decoded);
        self::assertArrayHasKey('context', $decoded);
    }

    #[Test]
    public function formatProducesCorrectJsonStructure(): void
    {
        $logger = new SpyLogglyLogger();

        $logger->log(LogLevel::INFO, 'hello', ['key' => 'value']);

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($logger->getSentData(), true);
        self::assertSame(LogLevel::INFO, $decoded['level']);
        self::assertSame('info hello', $decoded['message']);
        self::assertSame(['key' => 'value'], $decoded['context']);
    }

    #[Test]
    public function formatCastsStringableMessageToString(): void
    {
        $logger = new SpyLogglyLogger();

        $message = new class implements Stringable {
            public function __toString(): string
            {
                return 'stringable message';
            }
        };

        $logger->log(LogLevel::DEBUG, $message);

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($logger->getSentData(), true);
        self::assertSame('debug stringable message', $decoded['message']);
    }

    #[Test]
    public function formatProcessesPlaceholders(): void
    {
        $logger = new SpyLogglyLogger();

        $logger->log(LogLevel::INFO, 'Hello {name}', ['name' => 'Alice']);

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($logger->getSentData(), true);
        self::assertSame('info Hello Alice', $decoded['message']);
    }

    #[Test]
    public function formatIncludesLevelPrefixInMessageField(): void
    {
        $logger = new SpyLogglyLogger();

        $logger->log(LogLevel::ERROR, 'something broke');

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($logger->getSentData(), true);
        self::assertSame('error something broke', $decoded['message']);
    }

    #[Test]
    #[DataProvider('provideAllLevels')]
    public function logPassesAllPsr3LevelsThrough(string $level): void
    {
        $logger = new SpyLogglyLogger();

        /** @phpstan-ignore psr3.interpolated */
        $logger->log($level, 'test message');

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($logger->getSentData(), true);
        self::assertSame($level, $decoded['level']);
        self::assertSame(
            $level . ' test message',
            $decoded['message'],
        );
    }

    /** @return list<array{string}> */
    public static function provideAllLevels(): array
    {
        return [
            [LogLevel::DEBUG],
            [LogLevel::INFO],
            [LogLevel::NOTICE],
            [LogLevel::WARNING],
            [LogLevel::ERROR],
            [LogLevel::CRITICAL],
            [LogLevel::ALERT],
            [LogLevel::EMERGENCY],
        ];
    }

    #[Test]
    public function logThrowsInvalidArgumentExceptionOnInvalidLevel(): void
    {
        $logger = new SpyLogglyLogger();

        self::expectException(InvalidArgumentException::class);

        $logger->log('invalid', 'test');
    }

    #[Test]
    public function formatNormalizesContext(): void
    {
        $logger = new SpyLogglyLogger();

        $resource = tmpfile();
        $logger->log(LogLevel::INFO, 'test', ['resource' => $resource, 'name' => 'test']);

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($logger->getSentData(), true);
        /** @var array<string, mixed> $context */
        $context = $decoded['context'];
        self::assertIsString($context['resource']);
        self::assertStringContainsString('[resource]', $context['resource']);
        self::assertSame('test', $context['name']);
    }

    #[Test]
    public function logWithEmptyContext(): void
    {
        $logger = new SpyLogglyLogger();

        $logger->log(LogLevel::INFO, 'hello', []);

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($logger->getSentData(), true);
        self::assertSame([], $decoded['context']);
    }

    #[Test]
    public function formatWithUnmatchedPlaceholders(): void
    {
        $logger = new SpyLogglyLogger();

        $logger->log(LogLevel::INFO, 'Hello {unknown}', []);

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($logger->getSentData(), true);
        self::assertSame('info Hello {unknown}', $decoded['message']);
    }

    #[Test]
    public function debugConvenienceMethodDelegatesToLog(): void
    {
        $logger = new SpyLogglyLogger();

        $logger->debug('debug msg');

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($logger->getSentData(), true);
        self::assertSame(LogLevel::DEBUG, $decoded['level']);
    }

    #[Test]
    public function infoConvenienceMethodDelegatesToLog(): void
    {
        $logger = new SpyLogglyLogger();

        $logger->info('info msg');

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($logger->getSentData(), true);
        self::assertSame(LogLevel::INFO, $decoded['level']);
    }

    #[Test]
    public function noticeConvenienceMethodDelegatesToLog(): void
    {
        $logger = new SpyLogglyLogger();

        $logger->notice('notice msg');

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($logger->getSentData(), true);
        self::assertSame(LogLevel::NOTICE, $decoded['level']);
    }

    #[Test]
    public function warningConvenienceMethodDelegatesToLog(): void
    {
        $logger = new SpyLogglyLogger();

        $logger->warning('warning msg');

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($logger->getSentData(), true);
        self::assertSame(LogLevel::WARNING, $decoded['level']);
    }

    #[Test]
    public function errorConvenienceMethodDelegatesToLog(): void
    {
        $logger = new SpyLogglyLogger();

        $logger->error('error msg');

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($logger->getSentData(), true);
        self::assertSame(LogLevel::ERROR, $decoded['level']);
    }

    #[Test]
    public function criticalConvenienceMethodDelegatesToLog(): void
    {
        $logger = new SpyLogglyLogger();

        $logger->critical('critical msg');

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($logger->getSentData(), true);
        self::assertSame(LogLevel::CRITICAL, $decoded['level']);
    }

    #[Test]
    public function alertConvenienceMethodDelegatesToLog(): void
    {
        $logger = new SpyLogglyLogger();

        $logger->alert('alert msg');

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($logger->getSentData(), true);
        self::assertSame(LogLevel::ALERT, $decoded['level']);
    }

    #[Test]
    public function emergencyConvenienceMethodDelegatesToLog(): void
    {
        $logger = new SpyLogglyLogger();

        $logger->emergency('emergency msg');

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($logger->getSentData(), true);
        self::assertSame(LogLevel::EMERGENCY, $decoded['level']);
    }
}
