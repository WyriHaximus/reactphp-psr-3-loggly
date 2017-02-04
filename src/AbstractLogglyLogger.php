<?php declare(strict_types=1);

namespace WyriHaximus\React\PSR3\Loggly;

use Psr\Log\AbstractLogger;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LogLevel;

abstract class AbstractLogglyLogger extends AbstractLogger
{
    abstract protected function send(string $data);

    /**
     * Logging levels PSR-3 LogLevel enum
     *
     * @var array $levels Logging levels
     */
    const LOG_LEVELS = [
        LogLevel::DEBUG     => 'DEBUG',
        LogLevel::INFO      => 'INFO',
        LogLevel::NOTICE    => 'NOTICE',
        LogLevel::WARNING   => 'WARNING',
        LogLevel::ERROR     => 'ERROR',
        LogLevel::CRITICAL  => 'CRITICAL',
        LogLevel::ALERT     => 'ALERT',
        LogLevel::EMERGENCY => 'EMERGENCY',
    ];

    public function log($level, $message, array $context = [])
    {
        $levels = self::LOG_LEVELS;
        if (!isset($levels[$level])) {
            throw new InvalidArgumentException(
                'Level "'.$level.'" is not defined, use one of: '.implode(', ', array_keys(self::LOG_LEVELS))
            );
        }

        $data = $this->format($level, $message, $context);
        $this->send($data);
    }

    protected function format($level, $message, array $context): string
    {
        $message = (string)$message;
        $context = $this->normalizeContext($context);
        $message = $this->processPlaceHolders($message, $context);
        $json = json_encode([
            'level'   => $level,
            'message' => $message,
            'level_message' => $level . ' ' . $message,
            'context' => $context,
        ]);

        if ($json === false) {
            throw new InvalidArgumentException(json_last_error_msg());
        }

        return $json;
    }

    /**
     * @param string $message
     * @param array $context
     * @return string
     *
     * Method copied from: https://github.com/Seldaek/monolog/blob/6e6586257d9fb231bf039563632e626cdef594e5/src/Monolog/Processor/PsrLogMessageProcessor.php
     */
    private function processPlaceHolders(string $message, array $context): string
    {
        if (false === strpos($message, '{')) {
            return $message;
        }

        $replacements = array();
        foreach ($context as $key => $val) {
            if (is_null($val) || is_scalar($val) || (is_object($val) && method_exists($val, '__toString'))) {
                $replacements['{'.$key.'}'] = $val;
            } elseif (is_object($val)) {
                $replacements['{'.$key.'}'] = '[object '.get_class($val).']';
            } else {
                $replacements['{'.$key.'}'] = '['.gettype($val).']';
            }
        }

        return strtr($message, $replacements);
    }

    private function normalizeContext(array $context): array
    {
        foreach ($context as $index => $value) {
            if (is_array($value)) {
                $context[$index] = $this->normalizeContext($value);
                continue;
            }

            if (is_resource($value)) {
                $context[$index] = sprintf('[resource] (%s)', get_resource_type($value));
                continue;
            }
        }
        return $context;
    }
}
