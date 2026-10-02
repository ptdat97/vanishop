<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Inventory/Application/StockQueries.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Inventory\Application\StockQueries
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-578c62d3233a05edc7fbe2c12d4babe7cdc30484b78a611f636e71d32e6f8504',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Inventory\\Application\\StockQueries',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Inventory/Application/StockQueries.php',
      ),
    ),
    'namespace' => 'Modules\\Inventory\\Application',
    'name' => 'Modules\\Inventory\\Application\\StockQueries',
    'shortName' => 'StockQueries',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Truy vấn đọc cho màn hình tồn kho trong Admin.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 14,
    'endLine' => 66,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
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
    ),
    'immediateMethods' => 
    array (
      'locations' => 
      array (
        'name' => 'locations',
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
 * Mọi location (kể cả không phục vụ online — cửa hàng vẫn có tồn).
 *
 * @return list<Location>
 */',
        'startLine' => 21,
        'endLine' => 26,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Inventory\\Application',
        'declaringClassName' => 'Modules\\Inventory\\Application\\StockQueries',
        'implementingClassName' => 'Modules\\Inventory\\Application\\StockQueries',
        'currentClassName' => 'Modules\\Inventory\\Application\\StockQueries',
        'aliasName' => NULL,
      ),
      'levels' => 
      array (
        'name' => 'levels',
        'parameters' => 
        array (
          'variantIds' => 
          array (
            'name' => 'variantIds',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 32,
            'endLine' => 32,
            'startColumn' => 28,
            'endColumn' => 44,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param  list<int>  $variantIds
 * @return array<string, array{on_hand: int, reserved: int, safety_stock: int, available: int}> "loc:variant" => số liệu
 */',
        'startLine' => 32,
        'endLine' => 42,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Inventory\\Application',
        'declaringClassName' => 'Modules\\Inventory\\Application\\StockQueries',
        'implementingClassName' => 'Modules\\Inventory\\Application\\StockQueries',
        'currentClassName' => 'Modules\\Inventory\\Application\\StockQueries',
        'aliasName' => NULL,
      ),
      'movements' => 
      array (
        'name' => 'movements',
        'parameters' => 
        array (
          'variantId' => 
          array (
            'name' => 'variantId',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'int',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 47,
            'endLine' => 47,
            'startColumn' => 31,
            'endColumn' => 44,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'limit' => 
          array (
            'name' => 'limit',
            'default' => 
            array (
              'code' => '100',
              'attributes' => 
              array (
                'startLine' => 47,
                'endLine' => 47,
                'startTokenPos' => 247,
                'startFilePos' => 1475,
                'endTokenPos' => 247,
                'endFilePos' => 1477,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'int',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 47,
            'endLine' => 47,
            'startColumn' => 47,
            'endColumn' => 62,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
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
 * @return list<array<string, mixed>>
 */',
        'startLine' => 47,
        'endLine' => 65,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Inventory\\Application',
        'declaringClassName' => 'Modules\\Inventory\\Application\\StockQueries',
        'implementingClassName' => 'Modules\\Inventory\\Application\\StockQueries',
        'currentClassName' => 'Modules\\Inventory\\Application\\StockQueries',
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