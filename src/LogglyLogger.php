<?php declare(strict_types=1);

namespace WyriHaximus\React\PSR3\Loggly;

use Psr\Log\AbstractLogger;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LogLevel;
use React\EventLoop\LoopInterface;
use React\Dns\Resolver\Factory as ResolverFactory;
use React\HttpClient\Factory as HttpClientFactory;
use React\HttpClient\Client;

final class LogglyLogger extends AbstractLogger
{
    /**
     * @var Client
     */
    private $httpClient;

    /**
     * @var string
     */
    private $token;

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

    public static function create(LoopInterface $loop, string $token): self
    {
        $resolverFactory = new ResolverFactory();
        $resolver = $resolverFactory->create('8.8.8.8', $loop);

        $factory = new HttpClientFactory();
        $httpClient = $factory->create($loop, $resolver);

        return new self($httpClient, $token);
    }

    public static function createFromHttpClient(Client $httpClient, string $token): self
    {
        return new self($httpClient, $token);
    }

    private function __construct(Client $httpClient, string $token)
    {
        $this->httpClient = $httpClient;
        $this->token = $token;
    }

    public function log($level, $message, array $context = [])
    {
        if (!isset(self::LOG_LEVELS[$level])) {
            throw new InvalidArgumentException(
                'Level "'.$level.'" is not defined, use one of: '.implode(', ', array_keys(self::LOG_LEVELS))
            );
        }

        $data = $this->format($level, $message, $context);
        $this->send($data);
    }

    private function format($level, $message, array $context): string
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

    private function send(string $data)
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
