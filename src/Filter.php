<?php

declare(strict_types=1);

namespace Atlance\HttpDbalFilter;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;

final class Filter
{
    public const CACHE_FIELD = 'http_dbal_filter_field';
    private QueryBuilder $currentQueryBuilder;
    private Connection $connection;
    private Query\Cache $cacher;

    public function __construct(Connection $connection, Query\Cache $cacher)
    {
        $this->connection = $connection;
        $this->cacher = $cacher->setNamespace(self::CACHE_FIELD);
        $this->currentQueryBuilder = $this->createQueryBuilder();
    }

    public function getConnection(): Connection
    {
        return $this->connection;
    }

    public function createQueryBuilder(): QueryBuilder
    {
        return new QueryBuilder($this->getConnection());
    }

    public function apply(QueryBuilder $qb, Query\Configuration $configuration): QueryBuilder
    {
        return $this->setCurrentQueryBuilder($qb)
            ->select($configuration->filter)
            ->order($configuration->order)
            ->getCurrentQueryBuilder();
    }

    private function select(array $conditions): self
    {
        /**
         * @var string $expr
         * @var array  $aliases
         */
        foreach ($conditions as $expr => $aliases) {
            /**
             * @var string $alias
             * @var array  $values
             */
            foreach ($aliases as $alias => $values) {
                /** @var string $cacheKey */
                [$cacheKey,] = $this->cacher->generateCacheKeys(
                    self::CACHE_FIELD,
                    $this->currentQueryBuilder->getSQL(),
                    ['query' => "[{$expr}][{$alias}]"]
                );
                /** @var null| Query\Field $field */
                $field = $this->cacher->fetchCache($cacheKey);
                if (false === $field instanceof Query\Field) {
                    /** @var Query\Field $field */
                    $field = $this->createField($alias, $expr);
                }

                $this->createQuery($field, $values, $cacheKey);
            }
        }

        return $this;
    }

    private function order(array $conditions): self
    {
        /**
         * @var string $alias
         * @var string $value
         */
        foreach ($conditions as $alias => $value) {
            $snakeCaseExprMethod = 'order_by';
            /** @var string $cacheKey */
            [$cacheKey,] = $this->cacher->generateCacheKeys(
                self::CACHE_FIELD,
                $this->currentQueryBuilder->getSQL(),
                ['query' => "[{$snakeCaseExprMethod}][{$alias}]"]
            );
            /** @var null| Query\Field $field */
            $field = $this->cacher->fetchCache($cacheKey);
            if (false === $field instanceof Query\Field) {
                /** @var Query\Field $field */
                $field = $this->createField($alias, $snakeCaseExprMethod);
            }

            $this->createQuery($field, [$value], $cacheKey);
        }

        return $this;
    }

    private function createQuery(Query\Field $field, array $values, string $cacheKey): void
    {
        (new Query\Builder($this->currentQueryBuilder))->andWhere($field->setValues($values));
        $this->cacher->saveCache($cacheKey, $field);
    }

    private function createField(string $tableAliasAndColumnName, string $expr): Query\Field
    {
        /**
         * @var string $tableName
         * @var string $alias
         */
        foreach ($this->getTableAliases() as $tableName => $alias) {
            if (0 === strncasecmp($tableAliasAndColumnName, $alias . '_', mb_strlen($alias . '_'))) {
                $columnName = mb_substr($tableAliasAndColumnName, mb_strlen($alias . '_'));
                $columns = $this->getColumnsNamesByTable($tableName);
                if (!\in_array($columnName, $columns, true)) {
                    throw new \InvalidArgumentException("{$columnName} not exist in {$tableName}.");
                }

                return new Query\Field($expr, $alias, $columnName);
            }
        }

        throw new \InvalidArgumentException($tableAliasAndColumnName . ' not allowed');
    }

    private function getTableAliases(): array
    {
        $tableAliases = [];

        /** @var array $from */
        $from = $this->currentQueryBuilder->getQueryPart('from');
        /**
         * @var int   $i
         * @var array $item
         */
        foreach ($from as $i => $item) {
            if (!\in_array($item['alias'], $tableAliases, true)) {
                /** @var string $table */
                $table = $item['table'];
                /** @var string $alias */
                $alias = $item['alias'];

                $tableAliases[$table] = $alias ;
            }
        }

        /** @var array $joins */
        $joins = $this->currentQueryBuilder->getQueryPart('join');
        /**
         * @var int   $i
         * @var array $items
         */
        foreach ($joins as $i => $items) {
            /** @var array $item */
            foreach ($items as $item) {
                /** @var string $table */
                $table = $item['joinTable'];
                /** @var string $alias */
                $alias = $item['joinAlias'];

                $tableAliases[$table] = $alias;
            }
        }

        return $tableAliases;
    }

    private function getCurrentQueryBuilder(): QueryBuilder
    {
        return $this->currentQueryBuilder;
    }

    private function setCurrentQueryBuilder(QueryBuilder $qb): self
    {
        $this->currentQueryBuilder = $qb;

        return $this;
    }

    private function getColumnsNamesByTable(string $tableName): array
    {
        return array_keys($this->getConnection()->getSchemaManager()->listTableColumns($tableName));
    }
}
