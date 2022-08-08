# [ReactPHP](http://reactphp.org/) [PSR-3](http://www.php-fig.org/psr/psr-3/) [Loggly](https://www.loggly.com/) logger

[![Linux Build Status](https://travis-ci.org/WyriHaximus/reactphp-psr-3-loggly.png)](https://travis-ci.org/WyriHaximus/reactphp-psr-3-loggly)
[![Latest Stable Version](https://poser.pugx.org/WyriHaximus/react-psr-3-loggly/v/stable.png)](https://packagist.org/packages/WyriHaximus/react-psr-3-loggly)
[![Total Downloads](https://poser.pugx.org/WyriHaximus/react-psr-3-loggly/downloads.png)](https://packagist.org/packages/WyriHaximus/react-psr-3-loggly/stats)
[![Code Coverage](https://scrutinizer-ci.com/g/WyriHaximus/reactphp-psr-3-loggly/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/WyriHaximus/reactphp-psr-3-loggly/?branch=master)
[![License](https://poser.pugx.org/WyriHaximus/react-psr-3-loggly/license.png)](https://packagist.org/packages/wyrihaximus/react-psr-3-loggly)

### Installation ###

To install via [Composer](http://getcomposer.org/), use the command below, it will automatically detect the latest version and bind it with `^`.

```
composer require wyrihaximus/react-psr-3-loggly
```

## Loggers

This package comes with two loggers:

* `LogglyLogger` - Basic logger that will send every `log` call directly to Loggly.
* `LogglyBulkLogger` - Buffering logger that will log either what max buffer size is reached or when timeout is reached.

## Usage

Creating the `LoggerlyLogger` can be done in two ways. Either create it with an [`event loop`](https://github.com/reactphp/event-loop), this will create a [`HTTP Client`](https://github.com/reactphp/http-client) internally:

```php
$token = 'abc';
$loop = createEventLoop();
$logger = LogglyLogger::create($loop, $token);
```

Or create `LoggerlyLogger` with an already create [`HTTP Client`](https://github.com/reactphp/http-client).

```php
$token = 'abc';
$httpClient = createHttpClient();
$logger = LogglyLogger::createFromHttpClient($httpClient, $token);
```

For the `LogglyBulkLogger` a third parameter can be added. The timeout parameter, represented as float, used as maximum time to wait before sending all logs in the buffer to [`Loggly`](https://www.loggly.com/). Another difference with the bulk logger is that `createFromHttpClient` also requires the event loop due to the usage of timers:

```php
$token = 'abc';
$loop = createEventLoop();
$httpClient = createHttpClient();
$logger = LogglyBulkLogger::createFromHttpClient($loop, $httpClient, $token, 12.3);
```

At this point both loggers can be used as any other ['PSR-3'](http://www.php-fig.org/psr/psr-3/) logger.

## Contributing ##

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## License ##

Copyright 2017 [Cees-Jan Kiewiet](http://wyrihaximus.net/)

Permission is hereby granted, free of charge, to any person
obtaining a copy of this software and associated documentation
files (the "Software"), to deal in the Software without
restriction, including without limitation the rights to use,
copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the
Software is furnished to do so, subject to the following
conditions:

The above copyright notice and this permission notice shall be
included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
OTHER DEALINGS IN THE SOFTWARE.
