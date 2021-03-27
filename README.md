# Doctrine DBAL filter.

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/atlance/http-doctrine-dbal-filter/badges/quality-score.png?b=main)](https://scrutinizer-ci.com/g/atlance/http-doctrine-dbal-filter/?branch=main)
[![Code Coverage](https://scrutinizer-ci.com/g/atlance/http-doctrine-dbal-filter/badges/coverage.png?b=main)](https://scrutinizer-ci.com/g/atlance/http-doctrine-dbal-filter/?branch=main)
[![Build Status](https://scrutinizer-ci.com/g/atlance/http-doctrine-dbal-filter/badges/build.png?b=main)](https://scrutinizer-ci.com/g/atlance/http-doctrine-dbal-filter/build-status/main)
[![Code Intelligence Status](https://scrutinizer-ci.com/g/atlance/http-doctrine-dbal-filter/badges/code-intelligence.svg?b=main)](https://scrutinizer-ci.com/code-intelligence)
![GitHub](https://img.shields.io/badge/PHPStan-level%20max-brightgreen.svg?style=flat)
[![Maintainability](https://api.codeclimate.com/v1/badges/7a09def47eb0df6bebb8/maintainability)](https://codeclimate.com/github/atlance/http-doctrine-dbal-filter/maintainability)
![Psalm coverage](https://shepherd.dev/github/atlance/http-doctrine-dbal-filter/coverage.svg)
[![composer.lock](http://poser.pugx.org/phpunit/phpunit/composerlock)](https://packagist.org/packages/phpunit/phpunit)
[![PHP analyze & tests](https://github.com/atlance/http-doctrine-dbal-filter/actions/workflows/php-analyze.yml/badge.svg)](https://github.com/atlance/http-doctrine-dbal-filter/actions/workflows/php-analyze.yml)

Analogue of [atlance/http-doctrine-orm-filter](https://github.com/atlance/http-doctrine-orm-filter) for `DBAL` 
and of course without validation.

Simple example:
```php
<?php # src/Fetcher.php

declare(strict_types=1);

namespace App;

use Atlance\HttpDbalFilter\Filter;
use Atlance\HttpDbalFilter\Query\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ForwardCompatibility\Result;
use Doctrine\DBAL\Query\QueryBuilder;

final class Fetcher
{
    public function __construct(
        private readonly Connection $connection,
        private readonly Filter $filter
    ) {
    }

    /**
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public function fetch(Configuration $conditions): mixed
    {
        return $this->executeQuery($this->buildQuery($conditions))->fetchOne();
    }

    private function buildQuery(Configuration $conditions): QueryBuilder
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->select('COUNT(DISTINCT(users.id))')
            ->from('users', 'users')
            ->leftJoin('users', 'users_cards', 'uc', 'uc.user_id = users.id')
            ->leftJoin('uc', 'banking_cards', 'cards', 'uc.card_id = cards.id')
            ->leftJoin('users', 'phones', 'phones', 'phones.user_id = users.id')
            ->leftJoin('users', 'passports', 'passport', 'passport.user_id = users.id');

        $this->filter->apply($qb, $conditions);

        return $qb;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    private function executeQuery(QueryBuilder $qb): Result
    {
        $stmt = $qb->execute();
        if ($stmt instanceof Result) {
            return $stmt;
        }

        throw new \DomainException('this method works only with the select operator');
    }
}

```

```php
<?php # src/FetcherFactory.php

declare(strict_types=1);

namespace App;

use Atlance\HttpDbalFilter\Filter;
use Atlance\HttpDbalFilter\Query\Expression\ConditionFactory;
use Doctrine\DBAL\DriverManager;
use Psr\SimpleCache\CacheInterface;

final class FetcherFactory
{
    public static function create(?CacheInterface $cache = null): Fetcher
    {
        return new Fetcher(
            DriverManager::getConnection([
                'driver' => 'pdo_sqlite',
                'path' => \dirname(__DIR__) . '/storage/db.sqlite',
            ]),
            new Filter(new ConditionFactory($cache))
        );
    }
}

```

```php
<?php # public/index.php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Atlance\HttpDbalFilter\Query\Configuration;
use App\FetcherFactory;

// as example, from http query parameter: ?filter[eq][users_id]=1
$result = FetcherFactory::create()->fetch(
    Configuration::fromArray([
        'filter' => [
            'eq' => [
                'users_id' => 1,
            ],
        ],
    ])
);

var_dump($result);

```

More examples in [tests](/tests).
