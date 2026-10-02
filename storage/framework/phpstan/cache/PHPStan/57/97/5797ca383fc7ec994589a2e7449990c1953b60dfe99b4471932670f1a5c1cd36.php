<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Customer/Persistence/Models/Customer.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Customer\Persistence\Models\Customer
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-8046aa576d40bea693716aad9667395463d545d5a1503285ab9d04e653c47991',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Customer\\Persistence\\Models\\Customer',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Customer/Persistence/Models/Customer.php',
      ),
    ),
    'namespace' => 'Modules\\Customer\\Persistence\\Models',
    'name' => 'Modules\\Customer\\Persistence\\Models\\Customer',
    'shortName' => 'Customer',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Khách hàng của cửa hàng. registered_at = null → profile ẩn của khách vãng lai.
 *
 * @property int $id
 * @property string $public_id
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $full_name
 * @property Carbon|null $birth_date
 * @property string|null $gender
 * @property CustomerStatus $status
 * @property Carbon|null $registered_at
 * @property Carbon|null $phone_verified_at
 * @property string|null $password
 * @property int|null $merged_into_id
 * @property array<string, mixed>|null $meta
 * @property Carbon|null $last_login_at
 * @property Carbon|null $created_at
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 31,
    'endLine' => 66,
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
      'fillable' => 
      array (
        'declaringClassName' => 'Modules\\Customer\\Persistence\\Models\\Customer',
        'implementingClassName' => 'Modules\\Customer\\Persistence\\Models\\Customer',
        'name' => 'fillable',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'public_id\', \'phone\', \'email\', \'full_name\', \'birth_date\', \'gender\', \'status\', \'registered_at\', \'phone_verified_at\', \'password\', \'merged_into_id\', \'meta\', \'last_login_at\', \'orders_count\', \'total_spent\', \'first_order_at\', \'last_order_at\']',
          'attributes' => 
          array (
            'startLine' => 33,
            'endLine' => 36,
            'startTokenPos' => 55,
            'startFilePos' => 957,
            'endTokenPos' => 108,
            'endFilePos' => 1216,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 33,
        'endLine' => 36,
        'startColumn' => 5,
        'endColumn' => 6,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'hidden' => 
      array (
        'declaringClassName' => 'Modules\\Customer\\Persistence\\Models\\Customer',
        'implementingClassName' => 'Modules\\Customer\\Persistence\\Models\\Customer',
        'name' => 'hidden',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'password\']',
          'attributes' => 
          array (
            'startLine' => 38,
            'endLine' => 38,
            'startTokenPos' => 117,
            'startFilePos' => 1244,
            'endTokenPos' => 119,
            'endFilePos' => 1255,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 38,
        'endLine' => 38,
        'startColumn' => 5,
        'endColumn' => 37,
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
        'endLine' => 47,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Modules\\Customer\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Customer\\Persistence\\Models\\Customer',
        'implementingClassName' => 'Modules\\Customer\\Persistence\\Models\\Customer',
        'currentClassName' => 'Modules\\Customer\\Persistence\\Models\\Customer',
        'aliasName' => NULL,
      ),
      'addresses' => 
      array (
        'name' => 'addresses',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Illuminate\\Database\\Eloquent\\Relations\\HasMany',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return HasMany<CustomerAddress, $this>
 */',
        'startLine' => 52,
        'endLine' => 55,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Customer\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Customer\\Persistence\\Models\\Customer',
        'implementingClassName' => 'Modules\\Customer\\Persistence\\Models\\Customer',
        'currentClassName' => 'Modules\\Customer\\Persistence\\Models\\Customer',
        'aliasName' => NULL,
      ),
      'isActive' => 
      array (
        'name' => 'isActive',
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
        'startLine' => 57,
        'endLine' => 60,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Customer\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Customer\\Persistence\\Models\\Customer',
        'implementingClassName' => 'Modules\\Customer\\Persistence\\Models\\Customer',
        'currentClassName' => 'Modules\\Customer\\Persistence\\Models\\Customer',
        'aliasName' => NULL,
      ),
      'isRegistered' => 
      array (
        'name' => 'isRegistered',
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
        'startLine' => 62,
        'endLine' => 65,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Customer\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Customer\\Persistence\\Models\\Customer',
        'implementingClassName' => 'Modules\\Customer\\Persistence\\Models\\Customer',
        'currentClassName' => 'Modules\\Customer\\Persistence\\Models\\Customer',
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