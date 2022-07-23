<?php

declare(strict_types=1);

namespace WyriHaximus\React\PSR3\Loggly;

use React\Http\Browser;

use function strlen;

final class LogglyLogger extends AbstractLogglyLogger
{
    private Browser $httpClient;

    private string $token;

    private function __construct(Browser $httpClient, string $token)
    {
        $this->httpClient = $httpClient;
        $this->token      = $token;
    }

    public static function create(string $token): self
    {
        return new self(new Browser(), $token);
    }

    public static function createFromHttpClient(Browser $httpClient, string $token): self
    {
        return new self($httpClient, $token);
    }

    /**
     * @psalm-suppress TooManyTemplateParams
     */
    protected function send(string $data): void
    {
        $this->httpClient->post(
            'https://logs-01.loggly.com/inputs/' . $this->token,
            [
                'Content-Type' => 'application/json',
                'Content-Length' => strlen($data),
            ],
            '1.1'
        );
    }
}
