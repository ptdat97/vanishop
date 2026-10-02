<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Inventory/Domain/Allocation.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Inventory\Domain\Allocation
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-07bd6a12f474676ea2d93d5d4fdcbc98794c48092f954a3c0522809a4de46f70',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Inventory\\Domain\\Allocation',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Inventory/Domain/Allocation.php',
      ),
    ),
    'namespace' => 'Modules\\Inventory\\Domain',
    'name' => 'Modules\\Inventory\\Domain\\Allocation',
    'shortName' => 'Allocation',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Phân bổ số lượng cần giữ của một variant vào các location theo thứ tự ưu tiên cho sẵn:
 * lấy tối đa ATS của location đầu, phần còn thiếu sang location kế tiếp. Thiếu tổng → InsufficientStock.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 11,
    'endLine' => 39,
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
      'allocate' => 
      array (
        'name' => 'allocate',
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
            'startLine' => 17,
            'endLine' => 17,
            'startColumn' => 37,
            'endColumn' => 50,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'quantity' => 
          array (
            'name' => 'quantity',
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
            'startLine' => 17,
            'endLine' => 17,
            'startColumn' => 53,
            'endColumn' => 65,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'levels' => 
          array (
            'name' => 'levels',
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
            'startLine' => 17,
            'endLine' => 17,
            'startColumn' => 68,
            'endColumn' => 80,
            'parameterIndex' => 2,
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
 * @param  list<StockLevel>  $levels  theo thứ tự ưu tiên
 * @return array<int, int> location id => số lượng
 */',
        'startLine' => 17,
        'endLine' => 38,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Modules\\Inventory\\Domain',
        'declaringClassName' => 'Modules\\Inventory\\Domain\\Allocation',
        'implementingClassName' => 'Modules\\Inventory\\Domain\\Allocation',
        'currentClassName' => 'Modules\\Inventory\\Domain\\Allocation',
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