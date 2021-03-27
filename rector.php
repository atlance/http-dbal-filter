<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Core\ValueObject\PhpVersion;
use Rector\Php71\Rector\FuncCall\CountOnNullRector;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Php81\Rector\ClassMethod\NewInInitializerRector;
use Rector\Renaming\Rector\Name\RenameClassRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;

// @see https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md
return static function (RectorConfig $rectorConfig): void {
    $curDir = __DIR__ . \DIRECTORY_SEPARATOR;
    $rectorConfig->bootstrapFiles([$curDir . 'vendor/autoload.php']);
    $rectorConfig->phpVersion(PhpVersion::PHP_81);
    $rectorConfig->phpstanConfig('vendor/phpstan/phpstan-strict-rules/rules.neon');
    $rectorConfig->phpstanConfig('phpstan.neon');

    $rectorConfig->sets([
        SetList::CODE_QUALITY,
        SetList::TYPE_DECLARATION,
        SetList::CODING_STYLE,
        LevelSetList::UP_TO_PHP_81,
    ]);

    $rectorConfig->paths(['src', 'tests']);

    $rectorConfig->skip([
        $curDir . 'vendor',
        ClassPropertyAssignToConstructorPromotionRector::class,
        CountOnNullRector::class,
        NewInInitializerRector::class,
        RenameClassRector::class,
    ]);

    $rectorConfig->parallel();
};
