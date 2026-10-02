<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Inventory/Contracts/InventoryStrategy.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Inventory\Contracts\InventoryStrategy
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-55ca9954d05d6e4aa93525bfd042e5053183798da75325c02a336b3e24066f0c',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Inventory\\Contracts\\InventoryStrategy',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Inventory/Contracts/InventoryStrategy.php',
      ),
    ),
    'namespace' => 'Modules\\Inventory\\Contracts',
    'name' => 'Modules\\Inventory\\Contracts\\InventoryStrategy',
    'shortName' => 'InventoryStrategy',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Extension point (tag vani.inventory.strategies): điều chỉnh ATS bán online (ví dụ chừa tồn cho sàn TMĐT).
 * Core luôn lấy min(strategy, chuẩn) — strategy chỉ được GIẢM, không được tăng ATS.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 11,
    'endLine' => 23,
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
      'TAG' => 
      array (
        'declaringClassName' => 'Modules\\Inventory\\Contracts\\InventoryStrategy',
        'implementingClassName' => 'Modules\\Inventory\\Contracts\\InventoryStrategy',
        'name' => 'TAG',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'vani.inventory.strategies\'',
          'attributes' => 
          array (
            'startLine' => 14,
            'endLine' => 14,
            'startTokenPos' => 33,
            'startFilePos' => 462,
            'endTokenPos' => 33,
            'endFilePos' => 488,
          ),
        ),
        'docComment' => '/** Tag extension point: plugin đóng góp qua `contribute(InventoryStrategy::TAG, …)`. */',
        'attributes' => 
        array (
        ),
        'startLine' => 14,
        'endLine' => 14,
        'startColumn' => 5,
        'endColumn' => 51,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'code' => 
      array (
        'name' => 'code',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 16,
        'endLine' => 16,
        'startColumn' => 5,
        'endColumn' => 35,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Inventory\\Contracts',
        'declaringClassName' => 'Modules\\Inventory\\Contracts\\InventoryStrategy',
        'implementingClassName' => 'Modules\\Inventory\\Contracts\\InventoryStrategy',
        'currentClassName' => 'Modules\\Inventory\\Contracts\\InventoryStrategy',
        'aliasName' => NULL,
      ),
      'adjust' => 
      array (
        'name' => 'adjust',
        'parameters' => 
        array (
          'standardAts' => 
          array (
            'name' => 'standardAts',
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
            'startLine' => 22,
            'endLine' => 22,
            'startColumn' => 28,
            'endColumn' => 45,
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
 * @param  array<int, int>  $standardAts  variant id => ATS chuẩn
 * @return array<int, int>
 */',
        'startLine' => 22,
        'endLine' => 22,
        'startColumn' => 5,
        'endColumn' => 54,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Inventory\\Contracts',
        'declaringClassName' => 'Modules\\Inventory\\Contracts\\InventoryStrategy',
        'implementingClassName' => 'Modules\\Inventory\\Contracts\\InventoryStrategy',
        'currentClassName' => 'Modules\\Inventory\\Contracts\\InventoryStrategy',
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