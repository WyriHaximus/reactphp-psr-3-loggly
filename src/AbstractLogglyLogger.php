<?php

declare(strict_types=1);

namespace WyriHaximus\React\PSR3\Loggly;

use Psr\Log\AbstractLogger;
use Stringable;

use function WyriHaximus\PSR3\checkCorrectLogLevel;
use function WyriHaximus\PSR3\normalizeContext;
use function WyriHaximus\PSR3\processPlaceHolders;

abstract class AbstractLogglyLogger extends AbstractLogger
{
    /**
     * @param string       $level
     * @param array<mixed> $context
     *
     * @phpstan-ignore-next-line
     * @psalm-suppress MoreSpecificImplementedParamType
     */
    final public function log($level, string|Stringable $message, array $context = []): void // phpcs:disabled
    {
        checkCorrectLogLevel($level);
        $this->send(
            $this->format($level, $message, $context)
        );
    }

    abstract protected function send(string $data): void;

    /**
     * @param array<mixed> $context
     */
    final protected function format(string $level, string|Stringable $message, array $context): string
    {
        $message = (string) $message;
        /**
         * @psalm-suppress MixedArgumentTypeCoercion
         */
        $context = normalizeContext($context);
        $message = processPlaceHolders($message, $context);
        return \Safe\json_encode([
            'level'   => $level,
            'message' => $level . ' ' . $message,
            'context' => $context,
        ]);
    }
}
