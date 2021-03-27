<?php

declare(strict_types=1);

namespace Atlance\HttpDbalFilter\Test\Factory;

use Atlance\HttpDbalFilter\Test\ReadModel\Fetcher;
use Doctrine\DBAL\DriverManager;

final class FetcherFactory
{
    public static function create(): Fetcher
    {
        return new Fetcher(
            DriverManager::getConnection([
                'driver' => 'pdo_sqlite',
                'path' => \dirname(__DIR__, 2) . '/storage/db.sqlite',
            ]),
            FilterFactory::create()
        );
    }
}
