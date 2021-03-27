<?php

declare(strict_types=1);

namespace Atlance\HttpDbalFilter;

use Atlance\HttpDbalFilter\Query\Expression\ConditionFactory;
use Atlance\HttpDbalFilter\Query\Expression\Contract\ConditionFactoryInterface;
use Doctrine\DBAL\Query\QueryBuilder;

final class Filter
{
    public function __construct(private readonly ConditionFactoryInterface $factory = new ConditionFactory())
    {
    }

    public function apply(QueryBuilder $qb, Query\Configuration $configuration): void
    {
        $this->select($qb, $configuration->filter)->order($qb, $configuration->order);
    }

    /**
     * @param array<string,array<string,array<int,string|int|float|\Stringable>>> $conditions
     */
    private function select(QueryBuilder $qb, array $conditions): self
    {
        foreach ($conditions as $expr => $aliases) {
            foreach ($aliases as $alias => $values) {
                $this->andWhere($qb, $this->factory->create($qb, $alias, $expr), $values);
            }
        }

        return $this;
    }

    /**
     * @param array<string,string> $conditions
     */
    private function order(QueryBuilder $qb, array $conditions): void
    {
        foreach ($conditions as $alias => $value) {
            $this->andWhere($qb, $this->factory->create($qb, $alias, 'order_by'), [$value]);
        }
    }

    /**
     * @param array<int,string|int|float|\Stringable> $values
     */
    private function andWhere(QueryBuilder $qb, Query\Expression\Condition $condition, array $values): void
    {
        (new Query\Builder($qb))->andWhere($condition->setValues($values));
    }
}
