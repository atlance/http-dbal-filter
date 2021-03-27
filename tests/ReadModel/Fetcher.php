<?php

declare(strict_types=1);

namespace Atlance\HttpDbalFilter\Test\ReadModel;

use Atlance\HttpDbalFilter\Filter;
use Atlance\HttpDbalFilter\Query\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ForwardCompatibility\Result;
use Doctrine\DBAL\Query\QueryBuilder;

final class Fetcher
{
    public function __construct(private readonly Connection $connection, readonly private Filter $filter)
    {
    }

    /**
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public function fetch(Configuration $conditions): mixed
    {
        return $this->executeQuery($this->buildQuery($conditions))->fetchOne();
    }

    public function buildQuery(Configuration $conditions): QueryBuilder
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
