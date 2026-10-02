<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Integration/Persistence/Models/OutboxRecord.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Integration\Persistence\Models\OutboxRecord
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-205931b8a1c7beeee950da4daa8a9a069b38dc3db40ff631f72f1884d024c88e',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Integration\\Persistence\\Models\\OutboxRecord',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Integration/Persistence/Models/OutboxRecord.php',
      ),
    ),
    'namespace' => 'Modules\\Integration\\Persistence\\Models',
    'name' => 'Modules\\Integration\\Persistence\\Models\\OutboxRecord',
    'shortName' => 'OutboxRecord',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * @property int $id
 * @property string $message_id
 * @property string|null $event_id
 * @property string $target
 * @property string $message_type
 * @property string $schema_version
 * @property string $aggregate_type
 * @property string $aggregate_id
 * @property array<string, mixed> $payload
 * @property MessageStatus $status
 * @property int $attempts
 * @property Carbon|null $next_attempt_at
 * @property Carbon|null $locked_at
 * @property string|null $correlation_id
 * @property string|null $last_error
 * @property string|null $external_id
 * @property Carbon|null $created_at
 * @property Carbon|null $sent_at
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 32,
    'endLine' => 55,
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
      'UPDATED_AT' => 
      array (
        'declaringClassName' => 'Modules\\Integration\\Persistence\\Models\\OutboxRecord',
        'implementingClassName' => 'Modules\\Integration\\Persistence\\Models\\OutboxRecord',
        'name' => 'UPDATED_AT',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => 'null',
          'attributes' => 
          array (
            'startLine' => 34,
            'endLine' => 34,
            'startTokenPos' => 57,
            'startFilePos' => 961,
            'endTokenPos' => 57,
            'endFilePos' => 964,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 34,
        'endLine' => 34,
        'startColumn' => 5,
        'endColumn' => 35,
      ),
    ),
    'immediateProperties' => 
    array (
      'table' => 
      array (
        'declaringClassName' => 'Modules\\Integration\\Persistence\\Models\\OutboxRecord',
        'implementingClassName' => 'Modules\\Integration\\Persistence\\Models\\OutboxRecord',
        'name' => 'table',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'integration_outbox\'',
          'attributes' => 
          array (
            'startLine' => 36,
            'endLine' => 36,
            'startTokenPos' => 66,
            'startFilePos' => 991,
            'endTokenPos' => 66,
            'endFilePos' => 1010,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 36,
        'endLine' => 36,
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
        'declaringClassName' => 'Modules\\Integration\\Persistence\\Models\\OutboxRecord',
        'implementingClassName' => 'Modules\\Integration\\Persistence\\Models\\OutboxRecord',
        'name' => 'guarded',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[]',
          'attributes' => 
          array (
            'startLine' => 38,
            'endLine' => 38,
            'startTokenPos' => 75,
            'startFilePos' => 1039,
            'endTokenPos' => 76,
            'endFilePos' => 1040,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 38,
        'endLine' => 38,
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
        'startLine' => 40,
        'endLine' => 46,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Modules\\Integration\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Integration\\Persistence\\Models\\OutboxRecord',
        'implementingClassName' => 'Modules\\Integration\\Persistence\\Models\\OutboxRecord',
        'currentClassName' => 'Modules\\Integration\\Persistence\\Models\\OutboxRecord',
        'aliasName' => NULL,
      ),
      'toMessage' => 
      array (
        'name' => 'toMessage',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Modules\\Integration\\Contracts\\Data\\OutboxMessage',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 48,
        'endLine' => 54,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Integration\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Integration\\Persistence\\Models\\OutboxRecord',
        'implementingClassName' => 'Modules\\Integration\\Persistence\\Models\\OutboxRecord',
        'currentClassName' => 'Modules\\Integration\\Persistence\\Models\\OutboxRecord',
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