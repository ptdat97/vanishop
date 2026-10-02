<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Integration/Persistence/Models/InboxRecord.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Integration\Persistence\Models\InboxRecord
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-1a230e38011ba6ed3d148c3341952ef9fc18653681cece0f606b33238798741c',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Integration\\Persistence\\Models\\InboxRecord',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Integration/Persistence/Models/InboxRecord.php',
      ),
    ),
    'namespace' => 'Modules\\Integration\\Persistence\\Models',
    'name' => 'Modules\\Integration\\Persistence\\Models\\InboxRecord',
    'shortName' => 'InboxRecord',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * @property int $id
 * @property string $system
 * @property string $external_event_id
 * @property string $message_type
 * @property array<string, mixed> $payload
 * @property MessageStatus $status
 * @property int $attempts
 * @property Carbon|null $next_attempt_at
 * @property Carbon|null $locked_at
 * @property string|null $correlation_id
 * @property string|null $last_error
 * @property Carbon $received_at
 * @property Carbon|null $processed_at
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 27,
    'endLine' => 47,
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
        'declaringClassName' => 'Modules\\Integration\\Persistence\\Models\\InboxRecord',
        'implementingClassName' => 'Modules\\Integration\\Persistence\\Models\\InboxRecord',
        'name' => 'timestamps',
        'modifiers' => 1,
        'type' => NULL,
        'default' => 
        array (
          'code' => 'false',
          'attributes' => 
          array (
            'startLine' => 29,
            'endLine' => 29,
            'startTokenPos' => 55,
            'startFilePos' => 783,
            'endTokenPos' => 55,
            'endFilePos' => 787,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 29,
        'endLine' => 29,
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
        'declaringClassName' => 'Modules\\Integration\\Persistence\\Models\\InboxRecord',
        'implementingClassName' => 'Modules\\Integration\\Persistence\\Models\\InboxRecord',
        'name' => 'table',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'integration_inbox\'',
          'attributes' => 
          array (
            'startLine' => 31,
            'endLine' => 31,
            'startTokenPos' => 64,
            'startFilePos' => 814,
            'endTokenPos' => 64,
            'endFilePos' => 832,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 31,
        'endLine' => 31,
        'startColumn' => 5,
        'endColumn' => 43,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'guarded' => 
      array (
        'declaringClassName' => 'Modules\\Integration\\Persistence\\Models\\InboxRecord',
        'implementingClassName' => 'Modules\\Integration\\Persistence\\Models\\InboxRecord',
        'name' => 'guarded',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[]',
          'attributes' => 
          array (
            'startLine' => 33,
            'endLine' => 33,
            'startTokenPos' => 73,
            'startFilePos' => 861,
            'endTokenPos' => 74,
            'endFilePos' => 862,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 33,
        'endLine' => 33,
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
        'startLine' => 35,
        'endLine' => 41,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Modules\\Integration\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Integration\\Persistence\\Models\\InboxRecord',
        'implementingClassName' => 'Modules\\Integration\\Persistence\\Models\\InboxRecord',
        'currentClassName' => 'Modules\\Integration\\Persistence\\Models\\InboxRecord',
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
            'name' => 'Modules\\Integration\\Contracts\\Data\\InboxMessage',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 43,
        'endLine' => 46,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Integration\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Integration\\Persistence\\Models\\InboxRecord',
        'implementingClassName' => 'Modules\\Integration\\Persistence\\Models\\InboxRecord',
        'currentClassName' => 'Modules\\Integration\\Persistence\\Models\\InboxRecord',
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