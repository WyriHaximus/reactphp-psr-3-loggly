<?php declare(strict_types=1);

namespace WyriHaximus\React\PSR3\Loggly;

use React\EventLoop\LoopInterface;
use React\EventLoop\Timer\TimerInterface;
use React\HttpClient\Client;

final class LogglyBulkLogger extends AbstractLogglyLogger
{
    const LF = "\r\n";
    const MAX_BODY_LENGTH = 5242880;
    const MAX_LINE_LENGTH = 1048576;

    /**
     * @var LoopInterface
     */
    private $loop;

    /**
     * @var Client
     */
    private $httpClient;

    /**
     * @var string
     */
    private $token;

    /**
     * @var float
     */
    private $timeout;

    /**
     * @var string[]
     */
    private $buffer = [];

    /**
     * @var int
     */
    private $bufferSize = 0;

    /**
     * @var TimerInterface
     */
    private $timer;

    private function __construct(LoopInterface $loop, Client $httpClient, string $token, float $timeout)
    {
        $this->loop = $loop;
        $this->httpClient = $httpClient;
        $this->token = $token;
        $this->timeout = $timeout;
    }

    public static function create(LoopInterface $loop, string $token, float $timeout = 5.3): self
    {
        $httpClient = self::createHttpClient($loop);

        return new self($loop, $httpClient, $token, $timeout);
    }

    public static function createFromHttpClient(
        LoopInterface $loop,
        Client $httpClient,
        string $token,
        float $timeout = 5.3
    ): self {
        return new self($loop, $httpClient, $token, $timeout);
    }

    protected function send(string $data)
    {
        $dataLength = strlen($data . self::LF);
        if ($dataLength > self::MAX_LINE_LENGTH) {
            return;
        }

        if ($this->bufferSize + $dataLength > self::MAX_BODY_LENGTH) {
            $this->sendBulk();
        }

        $this->buffer[] = $data;
        $this->bufferSize += $dataLength;
        $this->ensureTimer();
    }

    private function ensureTimer()
    {
        if ($this->timer instanceof TimerInterface) {
            return;
        }

        $this->timer = $this->loop->addTimer($this->timeout, function () {
            $this->timer = null;
            $this->sendBulk();
        });
    }

    private function sendBulk()
    {
        if ($this->timer instanceof TimerInterface) {
            $this->timer->cancel();
            $this->timer = null;
        }

        $data = implode(self::LF, $this->buffer);

        $this->buffer = [];
        $this->bufferSize = 0;

        $this->httpClient->request(
            'POST',
            'https://logs-01.loggly.com/bulk/' . $this->token,
            [
                'Content-Type' => 'application/json',
                'Content-Length' => strlen($data),
            ],
            '1.1'
        )->end($data);
    }
}
