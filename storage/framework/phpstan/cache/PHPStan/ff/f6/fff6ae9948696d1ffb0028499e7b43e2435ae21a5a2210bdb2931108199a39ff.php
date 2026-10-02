<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Cart/Persistence/Models/Cart.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Cart\Persistence\Models\Cart
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-ca3a5e7a2edc849cfb159dc628a3204d4442b6c4df51ecf0914531f3bbfd8ae3',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Cart\\Persistence\\Models\\Cart',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Cart/Persistence/Models/Cart.php',
      ),
    ),
    'namespace' => 'Modules\\Cart\\Persistence\\Models',
    'name' => 'Modules\\Cart\\Persistence\\Models\\Cart',
    'shortName' => 'Cart',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Giỏ của cửa hàng; chứa sản phẩm của mọi brand.
 *
 * @property int $id
 * @property string $public_id
 * @property string $token_hash
 * @property int|null $customer_id
 * @property string $currency_code
 * @property CartStatus $status
 * @property int $lock_version
 * @property CarbonImmutable $last_activity_at
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 24,
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
      'fillable' => 
      array (
        'declaringClassName' => 'Modules\\Cart\\Persistence\\Models\\Cart',
        'implementingClassName' => 'Modules\\Cart\\Persistence\\Models\\Cart',
        'name' => 'fillable',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'public_id\', \'token_hash\', \'customer_id\', \'currency_code\', \'status\', \'meta\', \'lock_version\', \'last_activity_at\']',
          'attributes' => 
          array (
            'startLine' => 26,
            'endLine' => 26,
            'startTokenPos' => 55,
            'startFilePos' => 635,
            'endTokenPos' => 78,
            'endFilePos' => 747,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 26,
        'endLine' => 26,
        'startColumn' => 5,
        'endColumn' => 140,
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
        'startLine' => 28,
        'endLine' => 38,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Modules\\Cart\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Cart\\Persistence\\Models\\Cart',
        'implementingClassName' => 'Modules\\Cart\\Persistence\\Models\\Cart',
        'currentClassName' => 'Modules\\Cart\\Persistence\\Models\\Cart',
        'aliasName' => NULL,
      ),
      'lines' => 
      array (
        'name' => 'lines',
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
 * @return HasMany<CartLine, $this>
 */',
        'startLine' => 43,
        'endLine' => 46,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Cart\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Cart\\Persistence\\Models\\Cart',
        'implementingClassName' => 'Modules\\Cart\\Persistence\\Models\\Cart',
        'currentClassName' => 'Modules\\Cart\\Persistence\\Models\\Cart',
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