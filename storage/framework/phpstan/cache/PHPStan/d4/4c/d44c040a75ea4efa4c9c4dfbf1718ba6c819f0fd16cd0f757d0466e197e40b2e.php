<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Integration/Persistence/Models/IntegrationEventRecord.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Integration\Persistence\Models\IntegrationEventRecord
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-3615743e11680107ed87b56602f4a076f03f6fe6e28935e1859def15ddf6ab56',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Integration\\Persistence\\Models\\IntegrationEventRecord',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Integration/Persistence/Models/IntegrationEventRecord.php',
      ),
    ),
    'namespace' => 'Modules\\Integration\\Persistence\\Models',
    'name' => 'Modules\\Integration\\Persistence\\Models\\IntegrationEventRecord',
    'shortName' => 'IntegrationEventRecord',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Event feed (append-only).
 *
 * @property int $id
 * @property string $event_id
 * @property string $event_type
 * @property string $schema_version
 * @property string $aggregate_type
 * @property string $aggregate_id
 * @property array<string, mixed> $payload
 * @property string|null $correlation_id
 * @property Carbon $occurred_at
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 23,
    'endLine' => 52,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Illuminate\\Database\\Eloquent\\Model',
    'implementsClassNames' => 
    array (
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
      'timestamps' => 
      array (
        'declaringClassName' => 'Modules\\Integration\\Persistence\\Models\\IntegrationEventRecord',
        'implementingClassName' => 'Modules\\Integration\\Persistence\\Models\\IntegrationEventRecord',
        'name' => 'timestamps',
        'modifiers' => 1,
        'type' => NULL,
        'default' => 
        array (
          'code' => 'false',
          'attributes' => 
          array (
            'startLine' => 25,
            'endLine' => 25,
            'startTokenPos' => 45,
            'startFilePos' => 578,
            'endTokenPos' => 45,
            'endFilePos' => 582,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 25,
        'endLine' => 25,
        'startColumn' => 5,
        'endColumn' => 31,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'table' => 
      array (
        'declaringClassName' => 'Modules\\Integration\\Persistence\\Models\\IntegrationEventRecord',
        'implementingClassName' => 'Modules\\Integration\\Persistence\\Models\\IntegrationEventRecord',
        'name' => 'table',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'integration_events\'',
          'attributes' => 
          array (
            'startLine' => 27,
            'endLine' => 27,
            'startTokenPos' => 54,
            'startFilePos' => 609,
            'endTokenPos' => 54,
            'endFilePos' => 628,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 27,
        'endLine' => 27,
        'startColumn' => 5,
        'endColumn' => 44,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'guarded' => 
      array (
        'declaringClassName' => 'Modules\\Integration\\Persistence\\Models\\IntegrationEventRecord',
        'implementingClassName' => 'Modules\\Integration\\Persistence\\Models\\IntegrationEventRecord',
        'name' => 'guarded',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[]',
          'attributes' => 
          array (
            'startLine' => 29,
            'endLine' => 29,
            'startTokenPos' => 63,
            'startFilePos' => 657,
            'endTokenPos' => 64,
            'endFilePos' => 658,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 29,
        'endLine' => 29,
        'startColumn' => 5,
        'endColumn' => 28,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
    ),
    'immediateMethods' => 
    array (
      'casts' => 
      array (
        'name' => 'casts',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 31,
        'endLine' => 34,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Modules\\Integration\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Integration\\Persistence\\Models\\IntegrationEventRecord',
        'implementingClassName' => 'Modules\\Integration\\Persistence\\Models\\IntegrationEventRecord',
        'currentClassName' => 'Modules\\Integration\\Persistence\\Models\\IntegrationEventRecord',
        'aliasName' => NULL,
      ),
      'envelope' => 
      array (
        'name' => 'envelope',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return array<string, mixed>
 */',
        'startLine' => 39,
        'endLine' => 51,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Integration\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Integration\\Persistence\\Models\\IntegrationEventRecord',
        'implementingClassName' => 'Modules\\Integration\\Persistence\\Models\\IntegrationEventRecord',
        'currentClassName' => 'Modules\\Integration\\Persistence\\Models\\IntegrationEventRecord',
        'aliasName' => NULL,
      ),
    ),
    'traitsData' => 
    array (
      'aliases' => 
      array (
      ),
      'modifiers' => 
      array (
      ),
      'precedences' => 
      array (
      ),
      'hashes' => 
      array (
      ),
    ),
  ),
));