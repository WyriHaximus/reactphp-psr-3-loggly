<?php declare(strict_types=1);

namespace WyriHaximus\React\PSR3\Loggly;

use Psr\Log\AbstractLogger;
use Psr\Log\InvalidArgumentException;
use React\Dns\Resolver\Factory as ResolverFactory;
use React\EventLoop\LoopInterface;
use React\HttpClient\Client;
use React\HttpClient\Factory as HttpClientFactory;
use React\Socket\Connector;
use function WyriHaximus\PSR3\checkCorrectLogLevel;
use function WyriHaximus\PSR3\normalizeContext;
use function WyriHaximus\PSR3\processPlaceHolders;

abstract class AbstractLogglyLogger extends AbstractLogger
{
    public function log($level, $message, array $context = [])
    {
        checkCorrectLogLevel($level);
        $this->send(
            $this->format($level, $message, $context)
        );
    }

    abstract protected function send(string $data);

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

    protected static function createHttpClient(LoopInterface $loop): Client
    {
        $resolverFactory = new ResolverFactory();
        $resolver = $resolverFactory->createCached('8.8.8.8', $loop);

        if (class_exists(HttpClientFactory::class)) {
            $factory = new HttpClientFactory();

            return $factory->create($loop, $resolver);
        }

        return new Client(
            $loop,
            new Connector(
                $loop,
                [
                    'dns' => $resolver,
                ]
            )
        );
    }
}
