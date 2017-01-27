<?php declare(strict_types=1);

namespace WyriHaximus\React\PSR3\Loggly;

use Prophecy\Argument;
use Psr\Log\Test\LoggerInterfaceTest;
use function Clue\React\Block\await;
use React\EventLoop\Factory;
use React\HttpClient\Client;
use React\HttpClient\Request;

final class LogglyLoggerTest extends LoggerInterfaceTest
{
    /**
     * @var array
     */
    private $logs = [];

    public function getLogger()
    {
        $this->logs = [];

        $request = $this->prophesize(Request::class);
        $request->end(Argument::that(function ($data) {
            $json = json_decode($data, true);
            $this->logs[] = $json['level_message'];
            return $data;
        }))->shouldBeCalled();

        $httpClient = $this->prophesize(Client::class);
        $httpClient->request(
            Argument::any(),
            Argument::any(),
            Argument::any(),
            Argument::any()
        )->shouldBeCalled()->willReturn($request->reveal());

        return LogglyLogger::createFromHttpClient($httpClient->reveal(), 'abc');
    }

    public function getLogs()
    {
        return $this->logs;
    }

    public function testImplements()
    {
        self::assertInstanceOf('Psr\Log\LoggerInterface', LogglyLogger::create(Factory::create(), 'foo.bar'));
    }

    /**
     * @expectedException \Psr\Log\InvalidArgumentException
     */
    public function testThrowsOnInvalidLevel()
    {
        LogglyLogger::create(Factory::create(), 'foo.bar')->log('invalid level', 'Foo');
    }
}
