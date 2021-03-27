<?php

declare(strict_types=1);

namespace Atlance\HttpDbalFilter\Query;

use Atlance\HttpDbalFilter\Query\Expression\Condition;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Doctrine\DBAL\Query\QueryBuilder;
use Webmozart\Assert\Assert;

final class Builder
{
    /** @var string[] */
    public const SUPPORTED_EXPRESSIONS = [
        'eq',
        'neq',
        'gt',
        'gte',
        'ilike',
        'between',
        'in',
        'not_in',
        'is_null',
        'is_not_null',
        'like',
        'not_like',
        'lt',
        'lte',
        'order_by',
    ];

    public function __construct(private readonly QueryBuilder $qb)
    {
    }

    public function andWhere(Condition $condition): void
    {
        $this->{$condition->getExprMethod()}($condition);
    }

    private function andWhereAndX(Condition $condition): void
    {
        $this->andWhereComposite($condition, CompositeExpression::TYPE_AND);
    }

    /**
     * @psalm-suppress MixedAssignment
     * @psalm-suppress MixedArgumentTypeCoercion
     */
    private function andWhereComposite(Condition $condition, string $type): void
    {
        Assert::inArray($type, [CompositeExpression::TYPE_AND, CompositeExpression::TYPE_OR]);
        $parts = [];

        foreach ($condition->getValues() as $i => $value) {
            /** @var string $sql */
            $sql = $this->qb->expr()->{$condition->getExprMethod()}(
                $condition->getPropertyPath(),
                $condition->generateParameter($i)
            );

            $parts[] = $sql;
            if ($condition->isLike()) {
                $this->qb->setParameter($condition->generateParameter($i), sprintf('%%%s%%', (string) $value));

                continue;
            }

            $this->qb->setParameter($condition->generateParameter($i), $value);
        }

        $this->qb->andWhere(new CompositeExpression($type, $parts));
    }

    private function andWhereOrX(Condition $condition): void
    {
        $this->andWhereComposite($condition, CompositeExpression::TYPE_OR);
    }

    private function between(Condition $condition): void
    {
        Assert::eq($condition->countValues(), 2, 'Invalid format for between, expected "min|max"');
        [$min, $max] = $condition->getValues();
        Assert::lessThan($min, $max, 'Invalid values for between, expected min < max');

        $from = $condition->generateParameter('from');
        $to = $condition->generateParameter('to');
        $this->qb->andWhere(sprintf('%s BETWEEN %s AND %s', $condition->getPropertyPath(), $from, $to))
            ->setParameter($from, $min)
            ->setParameter($to, $max);
    }

    private function eq(Condition $condition): void
    {
        $this->andWhereOrX($condition);
    }

    private function gt(Condition $condition): void
    {
        Assert::eq($condition->countValues(), 1, 'expected single value');
        $this->qb->andWhere($this->qb->expr()->gt($condition->getPropertyPath(), $condition->generateParameter('gt')))
            ->setParameter($condition->generateParameter('gt'), $condition->getValues()[0]);
    }

    private function gte(Condition $condition): void
    {
        Assert::eq($condition->countValues(), 1, 'expected single value');
        $this->qb->andWhere($this->qb->expr()->gte($condition->getPropertyPath(), $condition->generateParameter('gte')))
            ->setParameter($condition->generateParameter('gte'), $condition->getValues()[0]);
    }

    private function ilike(Condition $condition): void
    {
        $parts = [];

        foreach ($condition->getValues() as $i => $value) {
            $parts[] = $this->qb->expr()->like(
                sprintf('LOWER(%s)', $condition->getPropertyPath()),
                sprintf('LOWER(%s)', $condition->generateParameter($i))
            );

            $this->qb->setParameter(
                $condition->generateParameter($i),
                mb_strtolower(sprintf('%%%s%%', (string) $value))
            );
        }

        $this->qb->andWhere(new CompositeExpression(CompositeExpression::TYPE_OR, $parts));
    }

    private function in(Condition $condition): void
    {
        Assert::greaterThanEq(
            $condition->countValues(),
            2,
            'expression "in" expected multiple value. Use "eq" for single value.'
        );

        $this->qb->andWhere($this->qb->expr()->in($condition->getPropertyPath(), $condition->generateParameter('in')))
            ->setParameter(
                $condition->generateParameter('in'),
                $condition->getValues(),
                \is_string($condition->getValues()[0])
                    ? Connection::PARAM_STR_ARRAY
                    : Connection::PARAM_INT_ARRAY
            );
    }

    private function isNotNull(Condition $condition): void
    {
        $this->qb->andWhere($this->qb->expr()->isNotNull($condition->getPropertyPath()));
    }

    private function isNull(Condition $condition): void
    {
        $this->qb->andWhere($this->qb->expr()->isNull($condition->getPropertyPath()));
    }

    private function like(Condition $condition): void
    {
        $this->andWhereOrX($condition);
    }

    private function lt(Condition $condition): void
    {
        Assert::eq($condition->countValues(), 1, 'expected single value');
        $this->qb->andWhere($this->qb->expr()->lt($condition->getPropertyPath(), $condition->generateParameter('lt')))
            ->setParameter($condition->generateParameter('lt'), $condition->getValues()[0]);
    }

    private function lte(Condition $condition): void
    {
        Assert::eq($condition->countValues(), 1, 'expected single value');
        $this->qb->andWhere($this->qb->expr()->lte($condition->getPropertyPath(), $condition->generateParameter('lte')))
            ->setParameter($condition->generateParameter('lte'), $condition->getValues()[0]);
    }

    private function neq(Condition $condition): void
    {
        $this->andWhereAndX($condition);
    }

    private function notIn(Condition $condition): void
    {
        Assert::greaterThanEq(
            $condition->countValues(),
            2,
            'expression "not_in" expected multiple value. Use "eq" for single value.'
        );

        $this->qb->andWhere($this->qb->expr()->notIn($condition->getPropertyPath(), $condition->generateParameter('not_in')))
            ->setParameter(
                $condition->generateParameter('not_in'),
                $condition->getValues(),
                \is_string($condition->getValues()[0])
                    ? Connection::PARAM_STR_ARRAY
                    : Connection::PARAM_INT_ARRAY
            );
    }

    private function notLike(Condition $condition): void
    {
        $this->andWhereAndX($condition);
    }

    private function orderBy(Condition $condition): void
    {
        Assert::eq($condition->countValues(), 1, 'expected single value');
        $order = $condition->getValues()[0];
        Assert::true(\is_string($order));
        $order = mb_strtolower($order);
        Assert::true('asc' === $order || 'desc' === $order);
        $this->qb->addOrderBy($condition->getPropertyPath(), (string) $condition->getValues()[0]);
    }
}
