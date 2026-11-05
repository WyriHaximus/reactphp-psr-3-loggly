<?php

declare(strict_types=1);

namespace WyriHaximus\React\Tests\PSR3\Loggly;

use WyriHaximus\React\PSR3\Loggly\AbstractLogglyLogger;

final class SpyLogglyLogger extends AbstractLogglyLogger
{
    private string $sentData = '';

    public function getSentData(): string
    {
        return $this->sentData;
    }

    protected function send(string $data): void
    {
        $this->sentData = $data;
    }
}
