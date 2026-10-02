<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Inventory/Persistence/Models/StockLevelRecord.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Inventory\Persistence\Models\StockLevelRecord
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-44a21ff1269643b70a676f6d8c7b422289a68a490f0c25338f87bbcc577ac308',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Inventory\\Persistence\\Models\\StockLevelRecord',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Inventory/Persistence/Models/StockLevelRecord.php',
      ),
    ),
    'namespace' => 'Modules\\Inventory\\Persistence\\Models',
    'name' => 'Modules\\Inventory\\Persistence\\Models\\StockLevelRecord',
    'shortName' => 'StockLevelRecord',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * @property int $id
 * @property int $location_id
 * @property int $variant_id
 * @property int $on_hand
 * @property int $reserved
 * @property int $safety_stock
 * @property int|null $sync_version
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 19,
    'endLine' => 46,
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
      'table' => 
      array (
        'declaringClassName' => 'Modules\\Inventory\\Persistence\\Models\\StockLevelRecord',
        'implementingClassName' => 'Modules\\Inventory\\Persistence\\Models\\StockLevelRecord',
        'name' => 'table',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'stock_levels\'',
          'attributes' => 
          array (
            'startLine' => 21,
            'endLine' => 21,
            'startTokenPos' => 45,
            'startFilePos' => 440,
            'endTokenPos' => 45,
            'endFilePos' => 453,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 21,
        'endLine' => 21,
        'startColumn' => 5,
        'endColumn' => 38,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'fillable' => 
      array (
        'declaringClassName' => 'Modules\\Inventory\\Persistence\\Models\\StockLevelRecord',
        'implementingClassName' => 'Modules\\Inventory\\Persistence\\Models\\StockLevelRecord',
        'name' => 'fillable',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'location_id\', \'variant_id\', \'on_hand\', \'reserved\', \'safety_stock\', \'sync_version\']',
          'attributes' => 
          array (
            'startLine' => 23,
            'endLine' => 23,
            'startTokenPos' => 54,
            'startFilePos' => 483,
            'endTokenPos' => 71,
            'endFilePos' => 566,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 23,
        'endLine' => 23,
        'startColumn' => 5,
        'endColumn' => 111,
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
        'startLine' => 25,
        'endLine' => 28,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Modules\\Inventory\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Inventory\\Persistence\\Models\\StockLevelRecord',
        'implementingClassName' => 'Modules\\Inventory\\Persistence\\Models\\StockLevelRecord',
        'currentClassName' => 'Modules\\Inventory\\Persistence\\Models\\StockLevelRecord',
        'aliasName' => NULL,
      ),
      'toDomain' => 
      array (
        'name' => 'toDomain',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Modules\\Inventory\\Domain\\StockLevel',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 30,
        'endLine' => 33,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Inventory\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Inventory\\Persistence\\Models\\StockLevelRecord',
        'implementingClassName' => 'Modules\\Inventory\\Persistence\\Models\\StockLevelRecord',
        'currentClassName' => 'Modules\\Inventory\\Persistence\\Models\\StockLevelRecord',
        'aliasName' => NULL,
      ),
      'fillFromDomain' => 
      array (
        'name' => 'fillFromDomain',
        'parameters' => 
        array (
          'level' => 
          array (
            'name' => 'level',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Inventory\\Domain\\StockLevel',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 35,
            'endLine' => 35,
            'startColumn' => 36,
            'endColumn' => 52,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'self',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 35,
        'endLine' => 45,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Inventory\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Inventory\\Persistence\\Models\\StockLevelRecord',
        'implementingClassName' => 'Modules\\Inventory\\Persistence\\Models\\StockLevelRecord',
        'currentClassName' => 'Modules\\Inventory\\Persistence\\Models\\StockLevelRecord',
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