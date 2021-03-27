<?php

declare(strict_types=1);

namespace Atlance\HttpDbalFilter\Test\Factory;

use Atlance\HttpDbalFilter\Filter;

final class FilterFactory
{
    public static function create(): Filter
    {
        return new Filter(ConditionFactoryFactory::create());
    }
}
