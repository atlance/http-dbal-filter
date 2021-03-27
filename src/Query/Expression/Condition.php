<?php

declare(strict_types=1);

namespace Atlance\HttpDbalFilter\Query\Expression;

use Atlance\HttpDbalFilter\Query\Builder;
use Webmozart\Assert\Assert;

final class Condition
{
    /**
     * Expression.
     */
    private readonly string $exprMethod;

    /**
     * Table alias in current instance DBAL\QueryBuilder.
     */
    private readonly string $tableAlias;

    /**
     * The column name. Optional. Defaults to the field name.
     */
    private readonly string $columnName;

    /**
     * Is LIKE operator?
     */
    private readonly bool $isLike;

    /**
     * @var array<int,string|int|float|\Stringable>
     */
    private array $values = [];

    public function __construct(string $snakeCaseExprMethod, string $tableAlias, string $columnName)
    {
        Assert::oneOf($snakeCaseExprMethod, Builder::SUPPORTED_EXPRESSIONS);
        $this->isLike = \in_array($snakeCaseExprMethod, ['like', 'not_like', 'ilike'], true);
        $exprMethod = lcfirst(str_replace('_', '', ucwords($snakeCaseExprMethod, '_')));
        Assert::methodExists(Builder::class, $exprMethod, sprintf('method "%s" not allowed', $exprMethod));
        $this->exprMethod = $exprMethod;
        $this->tableAlias = $tableAlias;
        $this->columnName = $columnName;
    }

    public function getExprMethod(): string
    {
        return $this->exprMethod;
    }

    public function getTableAlias(): string
    {
        return $this->tableAlias;
    }

    public function getColumnName(): string
    {
        return $this->columnName;
    }

    /**
     * @return array<int,string|int|float|\Stringable>
     */
    public function getValues(): array
    {
        return $this->values;
    }

    /**
     * @param array<int,string|int|float|\Stringable> $values
     *
     * @return $this
     */
    public function setValues(array $values): self
    {
        $this->values = $values;

        return $this;
    }

    public function countValues(): int
    {
        return \count($this->values);
    }

    public function generateParameter(string | int $i): string
    {
        return sprintf(':%s_%s_%s', $this->getTableAlias(), $this->getColumnName(), $i);
    }

    public function getPropertyPath(): string
    {
        return sprintf('%s.%s', $this->getTableAlias(), $this->getColumnName());
    }

    public function isLike(): bool
    {
        return $this->isLike;
    }
}
