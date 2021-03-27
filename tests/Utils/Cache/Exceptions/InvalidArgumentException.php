<?php

declare(strict_types=1);

namespace Atlance\HttpDbalFilter\Test\Utils\Cache\Exceptions;

class InvalidArgumentException extends \Exception implements \Psr\SimpleCache\InvalidArgumentException
{
}
