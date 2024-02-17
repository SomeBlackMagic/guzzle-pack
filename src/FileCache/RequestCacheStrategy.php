<?php

declare(strict_types=1);

namespace SomeBlackMagic\GuzzlePack\FileCache;

use DateTime;
use Kevinrob\GuzzleCache\CacheEntry;
use Kevinrob\GuzzleCache\Storage\CacheStorageInterface;
use Kevinrob\GuzzleCache\Storage\VolatileRuntimeStorage;
use Kevinrob\GuzzleCache\Strategy\CacheStrategyInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Class RequestCacheStrategy.
 */
class RequestCacheStrategy implements CacheStrategyInterface
{
    private const CACHE_TIME = '+1000 days';

    /**
     * @var CacheStorageInterface
     */
    protected CacheStorageInterface $storage;

    /**
     * @var int[]
     */
    protected array $statusAccepted = [
        200 => 200,
        203 => 203,
        204 => 204,
        300 => 300,
        301 => 301,
        401 => 401,
        404 => 404,
        405 => 405,
        410 => 410,
        414 => 414,
        418 => 418,
        501 => 501,
    ];

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * RequestCacheStrategy constructor.
     *
     * @param LoggerInterface|null       $logger
     * @param CacheStorageInterface|null $cache
     */
    public function __construct(?LoggerInterface $logger = null, ?CacheStorageInterface $cache = null)
    {
        $this->storage = $cache ?? new VolatileRuntimeStorage();
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * @param ResponseInterface $response
     *
     * @return CacheEntry|null entry to save, null if can't cache it
     */
    protected function getCacheObject(ResponseInterface $response): ?CacheEntry
    {
        if (!isset($this->statusAccepted[$response->getStatusCode()])) {
            // Don't cache it
            return null;
        }

        return new CacheEntry($response, new DateTime(self::CACHE_TIME));
    }

    /**
     * @param RequestInterface $request
     *
     * @throws \JsonException
     *
     * @return string
     */
    protected function getCacheKey(RequestInterface $request): string
    {
        return sha1(
            $request->getMethod().
            $request->getUri().
            json_encode($request->getHeaders(), JSON_THROW_ON_ERROR).
            json_encode($request->getBody(), JSON_THROW_ON_ERROR)
        );
    }

    /**
     * Return a CacheEntry or null if no cache.
     *
     * @param RequestInterface $request
     *
     * @throws \JsonException
     *
     * @return CacheEntry|null
     */
    public function fetch(RequestInterface $request)
    {
        $result = $this->storage->fetch($this->getCacheKey($request));

        if (null !== $result) {
            $this->logger->warning('Get from cache: '.(string) $request->getUri());
        }

        return $result;
    }

    /**
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     *
     * @throws \JsonException
     *
     * @return bool true if success
     */
    public function cache(RequestInterface $request, ResponseInterface $response)
    {
        $cacheObject = $this->getCacheObject($response);

        if (null !== $cacheObject) {
            return $this->storage->save(
                $this->getCacheKey($request),
                $cacheObject
            );
        }

        return false;
    }
}
