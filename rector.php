<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveUselessParamTagRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUselessReturnTagRector;
use Rector\Php81\Rector\Property\ReadOnlyPropertyRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ]);

    // Define what rule sets will be applied
    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_81,
        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
        SetList::EARLY_RETURN,
        SetList::TYPE_DECLARATION,
        SetList::PRIVATIZATION,
        SetList::INSTANCEOF,
    ]);

    // Skip certain directories and files
    $rectorConfig->skip([
        __DIR__ . '/vendor',
        __DIR__ . '/build',
        __DIR__ . '/stubs',
        __DIR__ . '/src/Templates',
        __DIR__ . '/tests/Fixtures',
        // Skip specific rules that might conflict with Laravel patterns
        ReadOnlyPropertyRector::class,
    ]);

    // Individual rules
    $rectorConfig->rule(InlineConstructorDefaultToPropertyRector::class);
    $rectorConfig->rule(AddVoidReturnTypeWhereNoReturnRector::class);
    $rectorConfig->rule(RemoveUselessParamTagRector::class);
    $rectorConfig->rule(RemoveUselessReturnTagRector::class);

    // Configure parallel processing
    $rectorConfig->parallel();

    // Import names
    $rectorConfig->importNames();
    $rectorConfig->importShortClasses();

    // Cache directory
    $rectorConfig->cacheDirectory(__DIR__ . '/build/rector');
};