<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Pricing/Contracts/PriceResolver.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Pricing\Contracts\PriceResolver
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-8049d20c3ec3a27c3bab026ffb6a43946e3785cb7ae7d07e8c76c5a830601739',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Pricing\\Contracts\\PriceResolver',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Pricing/Contracts/PriceResolver.php',
      ),
    ),
    'namespace' => 'Modules\\Pricing\\Contracts',
    'name' => 'Modules\\Pricing\\Contracts\\PriceResolver',
    'shortName' => 'PriceResolver',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Service contract: giá hiệu lực của variant theo kênh (Storefront, Cart, Checkout dùng).
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 13,
    'endLine' => 20,
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
      'forVariants' => 
      array (
        'name' => 'forVariants',
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
            'startLine' => 19,
            'endLine' => 19,
            'startColumn' => 33,
            'endColumn' => 49,
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
            'startLine' => 19,
            'endLine' => 19,
            'startColumn' => 52,
            'endColumn' => 74,
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
        'docComment' => '/**
 * @param  list<int>  $variantIds
 * @return array<int, ResolvedPrice>
 */',
        'startLine' => 19,
        'endLine' => 19,
        'startColumn' => 5,
        'endColumn' => 83,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Pricing\\Contracts',
        'declaringClassName' => 'Modules\\Pricing\\Contracts\\PriceResolver',
        'implementingClassName' => 'Modules\\Pricing\\Contracts\\PriceResolver',
        'currentClassName' => 'Modules\\Pricing\\Contracts\\PriceResolver',
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