<?php

declare(strict_types=1);

namespace WyriHaximus\React\PSR3\Loggly;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Stringable;
use WyriHaximus\PSR3\Utils;

abstract class AbstractLogglyLogger implements LoggerInterface
{
    use LoggerTrait;

    /**
     * @param string               $level
     * @param array<string, mixed> $context
     *
     * @phpstan-ignore typeCoverage.paramTypeCoverage
     */
    final public function log($level, string|Stringable $message, array $context = []): void // phpcs:disabled
    {
        Utils::checkCorrectLogLevel($level);
        $this->send(
            $this->format($level, $message, $context)
        );
    }

    abstract protected function send(string $data): void;

    /**
     * @param array<string, mixed> $context
     */
    final protected function format(string $level, string|Stringable $message, array $context): string
    {
        $message = (string) $message;
        /**
         * @psalm-suppress MixedArgumentTypeCoercion
         */
        $context = Utils::normalizeContext($context);
        $message = Utils::processPlaceHolders($message, $context);
        $json = json_encode([
            'level'   => $level,
            'message' => $level . ' ' . $message,
            'context' => $context,
        ]);

        if ($json === false) {
            throw new \RuntimeException('Failed to encode JSON');
        }

        return $json;
    }
}
