<?php

declare(strict_types=1);

namespace Atlance\HttpDbalFilter\Query;

use Webmozart\Assert\Assert;

final class Field
{
    /**
     * Expression.
     */
    private string $exprMethod;

    /**
     * Snake case DQL expression.
     */
    private string $snakeCaseExprMethod;

    /**
     * Table alias in current instance DBAL\QueryBuilder.
     */
    private string $tableAlias;

    /**
     * The column name. Optional. Defaults to the field name.
     */
    private string $columnName;

    /**
     * Is LIKE operator?
     */
    private bool $isLike;

    /** @var array<mixed> */
    private array $values = [];

    public function __construct(string $snakeCaseExprMethod, string $tableAlias, string $columnName)
    {
        Assert::oneOf($snakeCaseExprMethod, Builder::SUPPORTED_EXPRESSIONS);
        $this->snakeCaseExprMethod = $snakeCaseExprMethod;
        $this->isLike = \in_array($snakeCaseExprMethod, ['like', 'not_like', 'ilike'], true);
        $exprMethod = lcfirst(str_replace('_', '', ucwords($snakeCaseExprMethod, '_')));
        Assert::methodExists(Builder::class, $exprMethod, "method \"{$exprMethod}\" not allowed");
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

    public function getValues(): array
    {
        return $this->values;
    }

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
        return ":{$this->getTableAlias()}_{$this->getColumnName()}_{$i}";
    }

    public function getPropertyPath(): string
    {
        return "{$this->getTableAlias()}.{$this->getColumnName()}";
    }

    public function isLike(): bool
    {
        return $this->isLike;
    }
}
