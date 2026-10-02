<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Inventory/Testing/InventoryStrategyContract.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Inventory\Testing\InventoryStrategyContract
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-dd68b72921f66ebd1746e9748708bb26df68997516804cd8c37535f51b32e5f6',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Inventory\\Testing\\InventoryStrategyContract',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Inventory/Testing/InventoryStrategyContract.php',
      ),
    ),
    'namespace' => 'Modules\\Inventory\\Testing',
    'name' => 'Modules\\Inventory\\Testing\\InventoryStrategyContract',
    'shortName' => 'InventoryStrategyContract',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Contract test cho InventoryStrategy. Kiểm tra: mã ổn định; chỉ trả variant được đưa vào; ATS nguyên, không âm,
 * KHÔNG vượt ATS chuẩn (strategy chỉ được giảm — Core cũng kẹp, nhưng strategy không được dựa vào điều đó); xác định.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 14,
    'endLine' => 36,
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
      'define' => 
      array (
        'name' => 'define',
        'parameters' => 
        array (
          'label' => 
          array (
            'name' => 'label',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 19,
            'endLine' => 19,
            'startColumn' => 35,
            'endColumn' => 47,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'strategy' => 
          array (
            'name' => 'strategy',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Closure',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 19,
            'endLine' => 19,
            'startColumn' => 50,
            'endColumn' => 66,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param  Closure(): InventoryStrategy  $strategy
 */',
        'startLine' => 19,
        'endLine' => 35,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Modules\\Inventory\\Testing',
        'declaringClassName' => 'Modules\\Inventory\\Testing\\InventoryStrategyContract',
        'implementingClassName' => 'Modules\\Inventory\\Testing\\InventoryStrategyContract',
        'currentClassName' => 'Modules\\Inventory\\Testing\\InventoryStrategyContract',
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