<?php declare(strict_types=1);

namespace WyriHaximus\React\PSR3\Loggly;

use React\EventLoop\LoopInterface;
use React\HttpClient\Client;

final class LogglyLogger extends AbstractLogglyLogger
{
    /**
     * @var Client
     */
    private $httpClient;

    /**
     * @var string
     */
    private $token;

    private function __construct(Client $httpClient, string $token)
    {
        $this->httpClient = $httpClient;
        $this->token = $token;
    }

    public static function create(LoopInterface $loop, string $token): self
    {
        $httpClient = self::createHttpClient($loop);

        return new self($httpClient, $token);
    }

    public static function createFromHttpClient(Client $httpClient, string $token): self
    {
        return new self($httpClient, $token);
    }

    protected function send(string $data)
    {
        $this->httpClient->request(
            'POST',
            'https://logs-01.loggly.com/inputs/' . $this->token,
            [
                'Content-Type' => 'application/json',
                'Content-Length' => strlen($data),
            ],
            '1.1'
        )->end($data);
    }
}
