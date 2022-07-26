<?php

declare(strict_types=1);

namespace WyriHaximus\React\PSR3\Loggly;

use React\EventLoop\Loop;
use React\EventLoop\TimerInterface;
use React\Http\Browser;

use function implode;
use function strlen;

final class LogglyBulkLogger extends AbstractLogglyLogger
{
    private const DEFAULT_TIMEOUT = 0.1;
    public const LF               = "\r\n";
    public const MAX_BODY_LENGTH  = 5242880;
    public const MAX_LINE_LENGTH  = 1048576;

    private Browser $httpClient;

    private string $token;

    private float $timeout;

    /** @var string[] */
    private array $buffer = [];

    private int $bufferSize = 0;

    private ?TimerInterface $timer = null;

    private function __construct(Browser $httpClient, string $token, float $timeout)
    {
        $this->httpClient = $httpClient;
        $this->token      = $token;
        $this->timeout    = $timeout;
    }

    public static function create(string $token, float $timeout = self::DEFAULT_TIMEOUT): self
    {
        return new self(new Browser(), $token, $timeout);
    }

    public static function createFromHttpClient(
        Browser $httpClient,
        string $token,
        float $timeout = self::DEFAULT_TIMEOUT
    ): self {
        return new self($httpClient, $token, $timeout);
    }

    protected function send(string $data): void
    {
        $dataLength = strlen($data . self::LF);
        if ($dataLength > self::MAX_LINE_LENGTH) {
            return;
        }

        if ($this->bufferSize + $dataLength > self::MAX_BODY_LENGTH) {
            $this->sendBulk();
        }

        $this->buffer[]    = $data;
        $this->bufferSize += $dataLength;
        $this->ensureTimer();
    }

    private function ensureTimer(): void
    {
        if ($this->timer instanceof TimerInterface) {
            return;
        }

        $this->timer = Loop::addTimer($this->timeout, function (): void {
            $this->timer = null;
            $this->sendBulk();
        });
    }

    private function sendBulk(): void
    {
        if ($this->timer instanceof TimerInterface) {
            Loop::cancelTimer($this->timer);
            $this->timer = null;
        }

        $data = implode(self::LF, $this->buffer);

        $this->buffer     = [];
        $this->bufferSize = 0;

        /**
         * @psalm-suppress TooManyTemplateParams
         */
        $this->httpClient->post(
            'https://logs-01.loggly.com/bulk/' . $this->token,
            [
                'Content-Type' => 'application/json',
                'Content-Length' => strlen($data),
            ],
            '1.1'
        );
    }
}
