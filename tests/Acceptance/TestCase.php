<?php

declare(strict_types=1);

namespace Atlance\HttpDbalFilter\Test\Acceptance;

use Atlance\HttpDbalFilter\Filter;
use Atlance\HttpDbalFilter\Query\Configuration;
use Atlance\HttpDbalFilter\Test\ReadModel\Fetcher;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Query\QueryBuilder;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    private Fetcher $fetcher;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
    }

    protected function assertCountByHttpQuery(string $uri, int $expectedCount)
    {
        $qb = $this->prepareQueryBuilderQuery();

        parse_str($uri, $args);
        $configuration = new Configuration($args);

        $qb = $this->fetcher->apply($qb, $configuration);

        $stmt = $this->fetcher->getStatement($qb);

        $this->assertEquals($expectedCount, $stmt->fetchOne());
    }

    protected function prepareQueryBuilderQuery(): QueryBuilder
    {
        $qb = $this->fetcher->createQueryBuilder();
        $qb->select('COUNT(DISTINCT(users.id))')
            ->from('users', 'users')
            ->leftJoin('users', 'users_cards', 'uc', 'uc.user_id = users.id')
            ->leftJoin('uc', 'banking_cards', 'cards', 'uc.card_id = cards.id')
            ->leftJoin('users', 'phones', 'phones', 'phones.user_id = users.id')
            ->leftJoin('users', 'passports', 'passport', 'passport.user_id = users.id');

        return $qb;
    }

    protected function createClearFilter(): Filter
    {
        return $this->fetcher->getClearFilter();
    }

    protected function getByUri(string $uri)
    {
        parse_str($uri, $args);

        var_dump($args);die;

        return $this->fetcher->apply(new Configuration($args));
    }

    protected function createHttpConfiguration(string $uri)
    {
        parse_str($uri, $args);

        return new Configuration($args);
    }

    final protected function setUp(): void
    {
        $this->fetcher = new Fetcher(
            DriverManager::getConnection([
                'driver' => 'pdo_sqlite',
                'path' => \dirname(__DIR__) . '/DB/db.sqlite',
            ])
        );
    }
}
