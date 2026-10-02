<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Ordering/Persistence/Models/Order.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Ordering\Persistence\Models\Order
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-0eb7eb973494460bc14e0238290a7fb4c5fe3a086596265591876bcebecf385c',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Ordering\\Persistence\\Models\\Order',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Ordering/Persistence/Models/Order.php',
      ),
    ),
    'namespace' => 'Modules\\Ordering\\Persistence\\Models',
    'name' => 'Modules\\Ordering\\Persistence\\Models\\Order',
    'shortName' => 'Order',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * @property int $id
 * @property string $public_id
 * @property string $number
 * @property string $source
 * @property OrderStatus $order_status
 * @property string $payment_status
 * @property string $currency_code
 * @property int $total_amount
 * @property array<string, mixed> $customer_snapshot
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 22,
    'endLine' => 51,
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
      'guarded' => 
      array (
        'declaringClassName' => 'Modules\\Ordering\\Persistence\\Models\\Order',
        'implementingClassName' => 'Modules\\Ordering\\Persistence\\Models\\Order',
        'name' => 'guarded',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'id\']',
          'attributes' => 
          array (
            'startLine' => 24,
            'endLine' => 24,
            'startTokenPos' => 50,
            'startFilePos' => 592,
            'endTokenPos' => 52,
            'endFilePos' => 597,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 24,
        'endLine' => 24,
        'startColumn' => 5,
        'endColumn' => 32,
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
        'endLine' => 34,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Modules\\Ordering\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Ordering\\Persistence\\Models\\Order',
        'implementingClassName' => 'Modules\\Ordering\\Persistence\\Models\\Order',
        'currentClassName' => 'Modules\\Ordering\\Persistence\\Models\\Order',
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
 * @return HasMany<OrderLine, $this>
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
        'namespace' => 'Modules\\Ordering\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Ordering\\Persistence\\Models\\Order',
        'implementingClassName' => 'Modules\\Ordering\\Persistence\\Models\\Order',
        'currentClassName' => 'Modules\\Ordering\\Persistence\\Models\\Order',
        'aliasName' => NULL,
      ),
      'adjustments' => 
      array (
        'name' => 'adjustments',
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
 * @return HasMany<OrderAdjustment, $this>
 */',
        'startLine' => 47,
        'endLine' => 50,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Ordering\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Ordering\\Persistence\\Models\\Order',
        'implementingClassName' => 'Modules\\Ordering\\Persistence\\Models\\Order',
        'currentClassName' => 'Modules\\Ordering\\Persistence\\Models\\Order',
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