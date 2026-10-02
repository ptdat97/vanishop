<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Inventory/Persistence/Models/Location.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Inventory\Persistence\Models\Location
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-1dc06dc1f1219063c9a51389e0c7f5de1cb2964b46a90b0777be58f0b6e74c81',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Inventory\\Persistence\\Models\\Location',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Inventory/Persistence/Models/Location.php',
      ),
    ),
    'namespace' => 'Modules\\Inventory\\Persistence\\Models',
    'name' => 'Modules\\Inventory\\Persistence\\Models\\Location',
    'shortName' => 'Location',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Kho / cửa hàng / điểm ảo của cửa hàng.
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property LocationType $type
 * @property bool $ships_online_orders
 * @property bool $allows_pickup
 * @property bool $accepts_returns
 * @property string $stock_authority
 * @property int $priority
 * @property string $status
 * @property int $lock_version
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 25,
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
      'AUTHORITY_VANISHOP' => 
      array (
        'declaringClassName' => 'Modules\\Inventory\\Persistence\\Models\\Location',
        'implementingClassName' => 'Modules\\Inventory\\Persistence\\Models\\Location',
        'name' => 'AUTHORITY_VANISHOP',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'vanishop\'',
          'attributes' => 
          array (
            'startLine' => 27,
            'endLine' => 27,
            'startTokenPos' => 47,
            'startFilePos' => 642,
            'endTokenPos' => 47,
            'endFilePos' => 651,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 27,
        'endLine' => 27,
        'startColumn' => 5,
        'endColumn' => 49,
      ),
    ),
    'immediateProperties' => 
    array (
      'fillable' => 
      array (
        'declaringClassName' => 'Modules\\Inventory\\Persistence\\Models\\Location',
        'implementingClassName' => 'Modules\\Inventory\\Persistence\\Models\\Location',
        'name' => 'fillable',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'code\', \'name\', \'type\', \'address\', \'province_code\', \'ships_online_orders\', \'allows_pickup\', \'accepts_returns\', \'stock_authority\', \'priority\', \'status\', \'lock_version\']',
          'attributes' => 
          array (
            'startLine' => 29,
            'endLine' => 29,
            'startTokenPos' => 56,
            'startFilePos' => 681,
            'endTokenPos' => 91,
            'endFilePos' => 848,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 29,
        'endLine' => 29,
        'startColumn' => 5,
        'endColumn' => 195,
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
        'endLine' => 41,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Modules\\Inventory\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Inventory\\Persistence\\Models\\Location',
        'implementingClassName' => 'Modules\\Inventory\\Persistence\\Models\\Location',
        'currentClassName' => 'Modules\\Inventory\\Persistence\\Models\\Location',
        'aliasName' => NULL,
      ),
      'managesOnHand' => 
      array (
        'name' => 'managesOnHand',
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
        'startLine' => 43,
        'endLine' => 46,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Inventory\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Inventory\\Persistence\\Models\\Location',
        'implementingClassName' => 'Modules\\Inventory\\Persistence\\Models\\Location',
        'currentClassName' => 'Modules\\Inventory\\Persistence\\Models\\Location',
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