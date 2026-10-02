<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Customer/Persistence/Models/CustomerToken.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Customer\Persistence\Models\CustomerToken
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-062693c175a257c2f8a6eee101eb8e9b6f1af0686776b2317634b40b878e6dbc',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Customer\\Persistence\\Models\\CustomerToken',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Customer/Persistence/Models/CustomerToken.php',
      ),
    ),
    'namespace' => 'Modules\\Customer\\Persistence\\Models',
    'name' => 'Modules\\Customer\\Persistence\\Models\\CustomerToken',
    'shortName' => 'CustomerToken',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * @property int $id
 * @property int $customer_id
 * @property string $token_hash
 * @property string|null $name
 * @property Carbon|null $last_used_at
 * @property Carbon $expires_at
 * @property-read Customer $customer
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 20,
    'endLine' => 38,
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
        'declaringClassName' => 'Modules\\Customer\\Persistence\\Models\\CustomerToken',
        'implementingClassName' => 'Modules\\Customer\\Persistence\\Models\\CustomerToken',
        'name' => 'UPDATED_AT',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => 'null',
          'attributes' => 
          array (
            'startLine' => 22,
            'endLine' => 22,
            'startTokenPos' => 52,
            'startFilePos' => 509,
            'endTokenPos' => 52,
            'endFilePos' => 512,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 22,
        'endLine' => 22,
        'startColumn' => 5,
        'endColumn' => 35,
      ),
    ),
    'immediateProperties' => 
    array (
      'fillable' => 
      array (
        'declaringClassName' => 'Modules\\Customer\\Persistence\\Models\\CustomerToken',
        'implementingClassName' => 'Modules\\Customer\\Persistence\\Models\\CustomerToken',
        'name' => 'fillable',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'customer_id\', \'token_hash\', \'name\', \'last_used_at\', \'expires_at\']',
          'attributes' => 
          array (
            'startLine' => 24,
            'endLine' => 24,
            'startTokenPos' => 61,
            'startFilePos' => 542,
            'endTokenPos' => 75,
            'endFilePos' => 608,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 24,
        'endLine' => 24,
        'startColumn' => 5,
        'endColumn' => 94,
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
        'startLine' => 26,
        'endLine' => 29,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Modules\\Customer\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Customer\\Persistence\\Models\\CustomerToken',
        'implementingClassName' => 'Modules\\Customer\\Persistence\\Models\\CustomerToken',
        'currentClassName' => 'Modules\\Customer\\Persistence\\Models\\CustomerToken',
        'aliasName' => NULL,
      ),
      'customer' => 
      array (
        'name' => 'customer',
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
 * @return BelongsTo<Customer, $this>
 */',
        'startLine' => 34,
        'endLine' => 37,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Customer\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Customer\\Persistence\\Models\\CustomerToken',
        'implementingClassName' => 'Modules\\Customer\\Persistence\\Models\\CustomerToken',
        'currentClassName' => 'Modules\\Customer\\Persistence\\Models\\CustomerToken',
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