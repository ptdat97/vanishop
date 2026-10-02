<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Pricing/Contracts/PricingStrategy.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Pricing\Contracts\PricingStrategy
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-ec65ff0bfe1efd273ef7c524e37597912c8500e712f083829c4d549587e75e57',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Pricing\\Contracts\\PricingStrategy',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Pricing/Contracts/PricingStrategy.php',
      ),
    ),
    'namespace' => 'Modules\\Pricing\\Contracts',
    'name' => 'Modules\\Pricing\\Contracts\\PricingStrategy',
    'shortName' => 'PricingStrategy',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Extension point: cách chọn giá của variant (tag vani.pricing.strategies). Mặc định: price_list_priority.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 13,
    'endLine' => 25,
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
        'declaringClassName' => 'Modules\\Pricing\\Contracts\\PricingStrategy',
        'implementingClassName' => 'Modules\\Pricing\\Contracts\\PricingStrategy',
        'name' => 'TAG',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'vani.pricing.strategies\'',
          'attributes' => 
          array (
            'startLine' => 16,
            'endLine' => 16,
            'startTokenPos' => 43,
            'startFilePos' => 447,
            'endTokenPos' => 43,
            'endFilePos' => 471,
          ),
        ),
        'docComment' => '/** Tag extension point: plugin đóng góp qua `contribute(PricingStrategy::TAG, …)`. */',
        'attributes' => 
        array (
        ),
        'startLine' => 16,
        'endLine' => 16,
        'startColumn' => 5,
        'endColumn' => 49,
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
        'startLine' => 18,
        'endLine' => 18,
        'startColumn' => 5,
        'endColumn' => 35,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Pricing\\Contracts',
        'declaringClassName' => 'Modules\\Pricing\\Contracts\\PricingStrategy',
        'implementingClassName' => 'Modules\\Pricing\\Contracts\\PricingStrategy',
        'currentClassName' => 'Modules\\Pricing\\Contracts\\PricingStrategy',
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
            'startLine' => 24,
            'endLine' => 24,
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
            'startLine' => 24,
            'endLine' => 24,
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
        'docComment' => '/**
 * @param  list<int>  $variantIds
 * @return array<int, ResolvedPrice> variant id => giá (variant không có giá thì không có mặt)
 */',
        'startLine' => 24,
        'endLine' => 24,
        'startColumn' => 5,
        'endColumn' => 79,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Pricing\\Contracts',
        'declaringClassName' => 'Modules\\Pricing\\Contracts\\PricingStrategy',
        'implementingClassName' => 'Modules\\Pricing\\Contracts\\PricingStrategy',
        'currentClassName' => 'Modules\\Pricing\\Contracts\\PricingStrategy',
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