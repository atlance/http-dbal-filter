<?php

declare(strict_types=1);

namespace Atlance\HttpDbalFilter\Test\ReadModel;

use Atlance\HttpDbalFilter\Filter;
use Atlance\HttpDbalFilter\Query\Cache;
use Atlance\HttpDbalFilter\Query\Configuration;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\PDO\Statement;
use Doctrine\DBAL\Query\QueryBuilder;

final class Fetcher
{
    private Filter $filter;
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;

        $this->filter = new Filter($this->connection, new Cache(new ArrayCache()));
    }

    public function getFilter(): Filter
    {
        return $this->filter;
    }

    public function getClearFilter(): Filter
    {
        return new Filter($this->connection, new Cache(new ArrayCache()));
    }

    public function createQueryBuilder(): QueryBuilder
    {
        return $this->connection->createQueryBuilder();
    }

    public function getStatement(QueryBuilder $queryBuilder): Statement
    {
        $stmt = $queryBuilder->execute();
        if ($stmt instanceof Statement) {
            return $stmt;
        }

        throw new \DomainException('this method works only with the select operator');
    }

    public function apply(QueryBuilder $qb, Configuration $configuration): QueryBuilder
    {
        return $this->filter->apply($qb, $configuration);
    }
}
