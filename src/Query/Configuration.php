<?php

declare(strict_types=1);

namespace Atlance\HttpDbalFilter\Query;

use Atlance\HttpDbalFilter\Dto\AbstractCommand;
use Atlance\HttpDbalFilter\Utils\JsonNormalizer;
use Webmozart\Assert\Assert;

final class Configuration extends AbstractCommand
{
    public int $page;

    public int $limit;

    /**
     * @var array<string,array<string,array<int,string|int|float|\Stringable>>>
     */
    public array $filter = [];

    /**
     * @var array<string,string>
     */
    public array $order = [];

    /**
     * @psalm-suppress MixedArrayAssignment
     * @psalm-suppress MixedPropertyTypeCoercion
     *
     * @param non-empty-array<string,array<string,string|int|float>> $conditions
     *
     * @return Configuration
     *
     * @throws \JsonException
     */
    public function setFilter(array $conditions): self
    {
        foreach ($conditions as $expr => $aliases) {
            Assert::oneOf($expr, Builder::SUPPORTED_EXPRESSIONS);

            foreach ($aliases as $alias => $values) {
                if (!\array_key_exists($expr, $this->filter)) {
                    $this->filter[$expr] = [];
                }

                if (\is_string($values) && (bool) preg_match('#\|#', $values)) {
                    $values = JsonNormalizer::normalize(explode('|', $values));
                }

                $this->filter[$expr][$alias] = \is_array($values) ? $values : [$values];
            }
        }

        return $this;
    }

    public function setOrder(array $conditions): self
    {
        /**
         * @var string $alias
         * @var string $direction
         */
        foreach ($conditions as $alias => $direction) {
            Assert::oneOf($direction, ['asc', 'desc']);
            $this->order[$alias] = $direction;
        }

        return $this;
    }
}
