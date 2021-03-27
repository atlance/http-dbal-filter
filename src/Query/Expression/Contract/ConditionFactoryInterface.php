<?php

declare(strict_types=1);

namespace Atlance\HttpDbalFilter\Query\Expression\Contract;

use Atlance\HttpDbalFilter\Query\Expression\Condition;
use Doctrine\DBAL\Query\QueryBuilder;

interface ConditionFactoryInterface
{
    public function create(QueryBuilder $qb, string $tableAliasAndColumnName, string $expr): Condition;
}
