<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Integration/Persistence/Models/ClientKey.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Integration\Persistence\Models\ClientKey
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-6ab46eb476f017af3ce330e846f4ecf4ca1f13bede65c23f222af94c6b63eb6e',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Integration\\Persistence\\Models\\ClientKey',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Integration/Persistence/Models/ClientKey.php',
      ),
    ),
    'namespace' => 'Modules\\Integration\\Persistence\\Models',
    'name' => 'Modules\\Integration\\Persistence\\Models\\ClientKey',
    'shortName' => 'ClientKey',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * @property int $id
 * @property int $client_id
 * @property string $key_id
 * @property string $secret
 * @property Carbon|null $expires_at
 * @property Carbon|null $revoked_at
 * @property Carbon|null $last_used_at
 * @property-read IntegrationClient $client
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 21,
    'endLine' => 48,
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
        'declaringClassName' => 'Modules\\Integration\\Persistence\\Models\\ClientKey',
        'implementingClassName' => 'Modules\\Integration\\Persistence\\Models\\ClientKey',
        'name' => 'UPDATED_AT',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => 'null',
          'attributes' => 
          array (
            'startLine' => 23,
            'endLine' => 23,
            'startTokenPos' => 52,
            'startFilePos' => 548,
            'endTokenPos' => 52,
            'endFilePos' => 551,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 23,
        'endLine' => 23,
        'startColumn' => 5,
        'endColumn' => 35,
      ),
    ),
    'immediateProperties' => 
    array (
      'table' => 
      array (
        'declaringClassName' => 'Modules\\Integration\\Persistence\\Models\\ClientKey',
        'implementingClassName' => 'Modules\\Integration\\Persistence\\Models\\ClientKey',
        'name' => 'table',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'integration_client_keys\'',
          'attributes' => 
          array (
            'startLine' => 25,
            'endLine' => 25,
            'startTokenPos' => 61,
            'startFilePos' => 578,
            'endTokenPos' => 61,
            'endFilePos' => 602,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 25,
        'endLine' => 25,
        'startColumn' => 5,
        'endColumn' => 49,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'fillable' => 
      array (
        'declaringClassName' => 'Modules\\Integration\\Persistence\\Models\\ClientKey',
        'implementingClassName' => 'Modules\\Integration\\Persistence\\Models\\ClientKey',
        'name' => 'fillable',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'client_id\', \'key_id\', \'secret\', \'expires_at\', \'revoked_at\', \'last_used_at\']',
          'attributes' => 
          array (
            'startLine' => 27,
            'endLine' => 27,
            'startTokenPos' => 70,
            'startFilePos' => 632,
            'endTokenPos' => 87,
            'endFilePos' => 708,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 27,
        'endLine' => 27,
        'startColumn' => 5,
        'endColumn' => 104,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'hidden' => 
      array (
        'declaringClassName' => 'Modules\\Integration\\Persistence\\Models\\ClientKey',
        'implementingClassName' => 'Modules\\Integration\\Persistence\\Models\\ClientKey',
        'name' => 'hidden',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'secret\']',
          'attributes' => 
          array (
            'startLine' => 29,
            'endLine' => 29,
            'startTokenPos' => 96,
            'startFilePos' => 736,
            'endTokenPos' => 98,
            'endFilePos' => 745,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 29,
        'endLine' => 29,
        'startColumn' => 5,
        'endColumn' => 35,
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
        'declaringClassName' => 'Modules\\Integration\\Persistence\\Models\\ClientKey',
        'implementingClassName' => 'Modules\\Integration\\Persistence\\Models\\ClientKey',
        'currentClassName' => 'Modules\\Integration\\Persistence\\Models\\ClientKey',
        'aliasName' => NULL,
      ),
      'client' => 
      array (
        'name' => 'client',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Illuminate\\Database\\Eloquent\\Relations\\BelongsTo',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return BelongsTo<IntegrationClient, $this>
 */',
        'startLine' => 39,
        'endLine' => 42,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Integration\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Integration\\Persistence\\Models\\ClientKey',
        'implementingClassName' => 'Modules\\Integration\\Persistence\\Models\\ClientKey',
        'currentClassName' => 'Modules\\Integration\\Persistence\\Models\\ClientKey',
        'aliasName' => NULL,
      ),
      'isUsable' => 
      array (
        'name' => 'isUsable',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 44,
        'endLine' => 47,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Integration\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Integration\\Persistence\\Models\\ClientKey',
        'implementingClassName' => 'Modules\\Integration\\Persistence\\Models\\ClientKey',
        'currentClassName' => 'Modules\\Integration\\Persistence\\Models\\ClientKey',
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