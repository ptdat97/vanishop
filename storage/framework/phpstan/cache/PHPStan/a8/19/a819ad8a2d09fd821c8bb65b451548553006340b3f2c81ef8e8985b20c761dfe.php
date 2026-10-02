<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Pricing/Application/PriceListPriorityStrategy.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Pricing\Application\PriceListPriorityStrategy
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-a501d13c451e721d3c7041ad4b503f955d36d5bccf49047e0abe4d2617d21a0d',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Pricing\\Application\\PriceListPriorityStrategy',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Pricing/Application/PriceListPriorityStrategy.php',
      ),
    ),
    'namespace' => 'Modules\\Pricing\\Application',
    'name' => 'Modules\\Pricing\\Application\\PriceListPriorityStrategy',
    'shortName' => 'PriceListPriorityStrategy',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Chiến lược mặc định: bảng giá đang hiệu lực của kênh → PriceSelection (priority, giá thấp nhất, giá gốc).
 * 1 truy vấn cho cả lô variant (dùng được cho trang danh sách).
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 21,
    'endLine' => 65,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'Modules\\Pricing\\Contracts\\PricingStrategy',
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
        'startLine' => 23,
        'endLine' => 26,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Pricing\\Application',
        'declaringClassName' => 'Modules\\Pricing\\Application\\PriceListPriorityStrategy',
        'implementingClassName' => 'Modules\\Pricing\\Application\\PriceListPriorityStrategy',
        'currentClassName' => 'Modules\\Pricing\\Application\\PriceListPriorityStrategy',
        'aliasName' => NULL,
      ),
      'resolve' => 
      array (
        'name' => 'resolve',
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
            'startLine' => 28,
            'endLine' => 28,
            'startColumn' => 29,
            'endColumn' => 45,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'context' => 
          array (
            'name' => 'context',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Pricing\\Contracts\\Data\\PricingContext',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 28,
            'endLine' => 28,
            'startColumn' => 48,
            'endColumn' => 70,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 28,
        'endLine' => 64,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Pricing\\Application',
        'declaringClassName' => 'Modules\\Pricing\\Application\\PriceListPriorityStrategy',
        'implementingClassName' => 'Modules\\Pricing\\Application\\PriceListPriorityStrategy',
        'currentClassName' => 'Modules\\Pricing\\Application\\PriceListPriorityStrategy',
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