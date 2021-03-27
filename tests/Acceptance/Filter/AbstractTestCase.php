<?php

declare(strict_types=1);

namespace Atlance\HttpDbalFilter\Test\Acceptance\Filter;

use Atlance\HttpDbalFilter\Query\Configuration;
use Atlance\HttpDbalFilter\Test\Factory\FetcherFactory;
use Atlance\HttpDbalFilter\Test\Factory\InvalidArgumentExceptionFactory;
use Atlance\HttpDbalFilter\Test\Factory\RequestFactory;
use Atlance\HttpDbalFilter\Test\ReadModel\Fetcher;
use Doctrine\DBAL\Query\QueryBuilder;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class AbstractTestCase extends BaseTestCase
{
    public const EQ = 'filter[eq]';
    public const NEQ = 'filter[neq]';
    public const GT = 'filter[gt]';
    public const GTE = 'filter[gte]';
    public const ILIKE = 'filter[ilike]';
    public const IN = 'filter[in]';
    public const NOT_IN = 'filter[not_in]';
    public const IS_NULL = 'filter[is_null]';
    public const IS_NOT_NULL = 'filter[is_not_null]';
    public const LIKE = 'filter[like]';
    public const NOT_LIKE = 'filter[not_like]';
    public const LT = 'filter[lt]';
    public const LTE = 'filter[lte]';
    public const BETWEEN = 'filter[between]';

    private ?Fetcher $fetcher;

    private static Fetcher $staticFetcher;

    public function fetch(string $uri): mixed
    {
        return $this->fetcher()->fetch(RequestFactory::create($uri));
    }

    public function buildQuery(Configuration $conditions): QueryBuilder
    {
        return $this->fetcher()->buildQuery($conditions);
    }

    public function fetcher(): Fetcher
    {
        if (null === $this->fetcher) {
            throw InvalidArgumentExceptionFactory::create(Fetcher::class);
        }

        return $this->fetcher;
    }

    protected function assertCountByHttpQuery(string $uri, int $expectedCount): void
    {
        self::assertEquals($expectedCount, $this->fetch($uri));
    }

    public static function setUpBeforeClass(): void
    {
        self::$staticFetcher = FetcherFactory::create();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->fetcher = self::$staticFetcher;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->fetcher = null;
    }
}
