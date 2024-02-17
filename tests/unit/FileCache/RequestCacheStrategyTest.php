<?php

declare(strict_types=1);

namespace SomeBlackMagic\GuzzlePack\Tests\Unit\FileCache;

use SomeBlackMagic\GuzzlePack\FileCache\RequestCacheStrategy;
use DateTime;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Kevinrob\GuzzleCache\CacheEntry;
use Kevinrob\GuzzleCache\Storage\VolatileRuntimeStorage;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\Test\TestLogger;

class RequestCacheStrategyTest extends TestCase
{
    /**
     * @covers \SomeBlackMagic\GuzzlePack\FileCache\RequestCacheStrategy
     */
    public function testFetchNegative(): void
    {
        $storage = new VolatileRuntimeStorage();
        $logger = new TestLogger();
        $request = $this->getRequestMock('GET', 'http://google.com', ['foo' => 'bar'], ['yyy' => 'eee']);
        $strategy = new RequestCacheStrategy($logger, $storage);
        $result = $strategy->fetch($request);
        self::assertNull($result);
        self::assertFalse($logger->hasRecords('warning'));
    }

    /**
     * @covers \SomeBlackMagic\GuzzlePack\FileCache\RequestCacheStrategy
     */
    public function testFetchPositive(): void
    {
        $cacheObject = new CacheEntry($this->getResponseMock(200, ['content-type' => 'fake'], 'test'), new DateTime('+100 days'));
        $storage = new VolatileRuntimeStorage();
        $logger = new TestLogger();
        $storage->save('ca316e1ae718dffb7112c21a015d7ba2b75ae0a6', $cacheObject);
        $request = $this->getRequestMock('GET', 'http://google.com', ['foo' => 'bar'], ['yyy' => 'eee']);
        $strategy = new RequestCacheStrategy($logger, $storage);
        $result = $strategy->fetch($request);
        self::assertEquals($cacheObject, $result);
        self::assertTrue($logger->hasRecord('Get from cache: http://google.com', 'warning'));
    }

    /**
     * @covers \SomeBlackMagic\GuzzlePack\FileCache\RequestCacheStrategy
     */
    public function testCachePositive(): void
    {
        $request = $this->getRequestMock('GET', 'http://google.com', ['foo' => 'bar'], ['yyy' => 'eee']);
        $response = $this->getResponseMock(200, ['foo' => 'bar'], 'qweqe');

        $storage = new VolatileRuntimeStorage();
        $logger = new TestLogger();
        $strategy = new RequestCacheStrategy($logger, $storage);
        self::assertTrue($strategy->cache($request, $response));
    }

    /**
     * @covers \SomeBlackMagic\GuzzlePack\FileCache\RequestCacheStrategy
     */
    public function testCacheNegative(): void
    {
        $request = $this->getRequestMock('GET', 'http://google.com', ['foo' => 'bar'], ['yyy' => 'eee']);
        $response = $this->getResponseMock(404, ['foo' => 'bar'], 'qweqe');

        $storage = new VolatileRuntimeStorage();
        $logger = new TestLogger();
        $strategy = new RequestCacheStrategy($logger, $storage);
        self::assertTrue($strategy->cache($request, $response));
    }

    /**
     * @param string  $method
     * @param string  $url
     * @param mixed[] $headers
     * @param mixed[] $body
     *
     * @return MockInterface|RequestInterface
     */
    public function getRequestMock(string $method, string $url, array $headers, array $body): MockInterface
    {
        $mock = Mockery::mock(Request::class);

        $mock
            ->shouldReceive('getMethod')
            ->zeroOrMoreTimes()
            ->andReturn($method);
        $mock
            ->shouldReceive('getUri')
            ->zeroOrMoreTimes()
            ->andReturn($url);
        $mock
            ->shouldReceive('getHeaders')
            ->zeroOrMoreTimes()
            ->andReturn($headers);

        $mock
            ->shouldReceive('getBody')
            ->zeroOrMoreTimes()
            ->andReturn($body);

        return $mock;
    }

    /**
     * @param int     $statusCode
     * @param mixed[] $headers
     * @param string  $body
     *
     * @return MockInterface|ResponseInterface
     */
    public function getResponseMock(int $statusCode, array $headers, string $body): MockInterface
    {
        $mock = Mockery::mock(Response::class);

        $mock
            ->shouldReceive('getHeaders')
            ->zeroOrMoreTimes()
            ->andReturn($headers);

        $mock
            ->shouldReceive('getStatusCode')
            ->zeroOrMoreTimes()
            ->andReturn($statusCode);

        $mock
            ->shouldReceive('getBody')
            ->zeroOrMoreTimes()
            ->andReturn($body);

        $mock
            ->shouldReceive('getHeader')
            ->zeroOrMoreTimes()
            ->andReturn(['', '', '', '', '']);

        return $mock;
    }
}
