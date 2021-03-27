<?php

declare(strict_types=1);

namespace Atlance\HttpDbalFilter\Query\Expression;

use Atlance\HttpDbalFilter\Utils\CacheKeyGenerator;
use Doctrine\DBAL\Query\QueryBuilder;
use Psr\SimpleCache\CacheInterface;

final class ConditionFactory implements Contract\ConditionFactoryInterface
{
    private const CACHE_KEY = 'http_dbal_filter_condition';

    public function __construct(private readonly ?CacheInterface $cache = null)
    {
    }

    public function create(QueryBuilder $qb, string $tableAliasAndColumnName, string $expr): Condition
    {
        [$cacheKey,] = CacheKeyGenerator::generate(self::CACHE_KEY, $tableAliasAndColumnName, ['expr' => $expr]);
        /** @var Condition|null $condition */
        $condition = $this->cache?->get($cacheKey);

        if ($condition instanceof Condition) {
            return $condition;
        }

        foreach ($this->getTableAliases($qb) as $tableName => $alias) {
            if (0 === strncasecmp($tableAliasAndColumnName, $alias . '_', mb_strlen($alias . '_'))) {
                $columnName = mb_substr($tableAliasAndColumnName, mb_strlen($alias . '_'));
                $columns = $this->getColumnsNamesByTable($qb, $tableName);
                if (!\in_array($columnName, $columns, true)) {
                    throw new \InvalidArgumentException(sprintf('%s not exist in %s.', $columnName, $tableName));
                }

                $condition = new Condition($expr, $alias, $columnName);
                $this->cache?->set($cacheKey, $condition);

                return $condition;
            }
        }

        throw new \InvalidArgumentException($tableAliasAndColumnName . ' not allowed');
    }

    /**
     * @return array<string,string>
     */
    private function getTableAliases(QueryBuilder $qb): array
    {
        $aliases = [];

        /** @var array<int,array{alias:string,table:string}> $from */
        $from = $qb->getQueryPart('from');
        foreach ($from as $item) {
            if (!\in_array($item['alias'], $aliases, true)) {
                $aliases[$item['table']] = $item['alias'];
            }
        }

        /** @var array<int,array<int,array{joinTable:string,joinAlias:string}>> $joins */
        $joins = $qb->getQueryPart('join');
        foreach ($joins as $items) {
            foreach ($items as $item) {
                $aliases[$item['joinTable']] = $item['joinAlias'];
            }
        }

        return $aliases;
    }

    private function getColumnsNamesByTable(QueryBuilder $qb, string $tableName): array
    {
        return array_keys($qb->getConnection()->getSchemaManager()->listTableColumns($tableName));
    }
}
