<?php declare(strict_types=1);

namespace WyriHaximus\React\PSR3\Loggly;

use Psr\Log\AbstractLogger;
use Psr\Log\InvalidArgumentException;
use function WyriHaximus\PSR3\checkCorrectLogLevel;
use function WyriHaximus\PSR3\normalizeContext;
use function WyriHaximus\PSR3\processPlaceHolders;

abstract class AbstractLogglyLogger extends AbstractLogger
{
    abstract protected function send(string $data);

    public function log($level, $message, array $context = [])
    {
        checkCorrectLogLevel($level);
        $data = $this->format($level, $message, $context);
        $this->send($data);
    }

    protected function format($level, $message, array $context): string
    {
        $message = (string)$message;
        $context = normalizeContext($context);
        $message = processPlaceHolders($message, $context);
        $json = json_encode([
            'level'   => $level,
            'message' => $level . ' ' . $message,
            'context' => $context,
        ]);

        if ($json === false) {
            throw new InvalidArgumentException(json_last_error_msg());
        }

        return $json;
    }
}
