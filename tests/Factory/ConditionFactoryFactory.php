<?php

declare(strict_types=1);

namespace Atlance\HttpDbalFilter\Test\Factory;

use Atlance\HttpDbalFilter\Query\Expression\ConditionFactory;
use Atlance\HttpDbalFilter\Query\Expression\Contract\ConditionFactoryInterface;

final class ConditionFactoryFactory
{
    public static function create(): ConditionFactoryInterface
    {
        return new ConditionFactory(CacheFactory::create());
    }
}
