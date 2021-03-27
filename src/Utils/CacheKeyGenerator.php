<?php

declare(strict_types=1);

namespace Atlance\HttpDbalFilter\Utils;

final class CacheKeyGenerator
{
    /** @return array<int, string> */
    public static function generate(string $key, string $query, array $params = []): array
    {
        $realCacheKey = $key . 'query=' . $query . '&params=' . hash('sha256', serialize($params));

        return [sha1($realCacheKey), $realCacheKey];
    }
}
