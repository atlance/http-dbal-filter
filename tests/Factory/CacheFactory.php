<?php

declare(strict_types=1);

namespace Atlance\HttpDbalFilter\Test\Factory;

use Atlance\HttpDbalFilter\Test\Utils\Cache\SimpleCacheBridge;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Cache\Adapter;

final class CacheFactory
{
    public static function create(): ?CacheInterface
    {
        /** @var Adapter\MemcachedAdapter|Adapter\RedisAdapter|Adapter\ApcuAdapter|null $adapter */
        $adapter = null;

        if (\extension_loaded('memcached')) {
            $memcached = new \Memcached();
            $memcached->addServer('127.0.0.1', 11211);

            if ($memcached->isPersistent()) {
                $adapter = new Adapter\MemcachedAdapter($memcached);
            }
        }

        if (null === $adapter && \extension_loaded('redis')) {
            try {
                $redis = new \Redis();
                $redis->connect('127.0.0.1');

                $adapter = new Adapter\RedisAdapter($redis);
            } catch (\RedisException) {
            }
        }

        if (null === $adapter && \extension_loaded('apcu')) {
            $adapter = new Adapter\ApcuAdapter();
        }

        return null !== $adapter ? new SimpleCacheBridge($adapter) : null;
    }
}
