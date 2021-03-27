<?php

declare(strict_types=1);

namespace Atlance\HttpDbalFilter\Test\Acceptance\Filter\SQL;

use Atlance\HttpDbalFilter\Test\Acceptance\Filter\AbstractTestCase;
use Atlance\HttpDbalFilter\Test\Factory\RequestFactory;
use PHPUnit\Framework\Attributes\DataProvider;

final class OrderByTest extends AbstractTestCase
{
    #[DataProvider('dataset')]
    public function test(string $uri, string $expectedSQL): void
    {
        $dql = $this->buildQuery(RequestFactory::create($uri))->getSQL();

        self::assertTrue(false !== mb_strpos($dql, $expectedSQL));
    }

    /**
     * @return \Generator<array<string>>
     */
    public static function dataset(): \Generator
    {
        yield ['order[cards_expires_at]=asc', 'ORDER BY cards.expires_at'];
    }
}
