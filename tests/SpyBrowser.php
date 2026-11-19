<?php

declare(strict_types=1);

namespace WyriHaximus\React\Tests\PSR3\Loggly;

use React\Http\Browser;
use React\Promise\PromiseInterface;

use function React\Promise\resolve;

final class SpyBrowser extends Browser
{
    /** @var list<array{url: string, headers: array<string, string>, body: string}> */
    public array $postCalls = [];

    /**
     * @param array<string, string> $headers
     *
     * @return PromiseInterface<null>
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint
    public function post($url, array $headers = [], $body = ''): PromiseInterface
    {
        $this->postCalls[] = [
            'url' => $url,
            'headers' => $headers,
            'body' => $body,
        ];

        return resolve(null);
    }
}
