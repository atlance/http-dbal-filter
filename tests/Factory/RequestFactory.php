<?php

declare(strict_types=1);

namespace Atlance\HttpDbalFilter\Test\Factory;

use Atlance\HttpDbalFilter\Query\Configuration;
use Atlance\HttpDbalFilter\Utils\JsonNormalizer;

final class RequestFactory
{
    public static function create(string $uri): Configuration
    {
        parse_str($uri, $args);
        /* @var array<string,mixed> $args */
        return Configuration::fromArray(JsonNormalizer::normalize($args));
    }
}
