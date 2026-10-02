<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Checkout/Application/TotalsPipeline.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Checkout\Application\TotalsPipeline
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-15663c7ed9246fe062fd17d2fb668b72dea3ee97151b03600a41d840aab33ec8',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Checkout\\Application\\TotalsPipeline',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Checkout/Application/TotalsPipeline.php',
      ),
    ),
    'namespace' => 'Modules\\Checkout\\Application',
    'name' => 'Modules\\Checkout\\Application\\TotalsPipeline',
    'shortName' => 'TotalsPipeline',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Chạy các TotalsCalculator theo priority (docs/03-domains/cart-checkout.md §2). Cùng một pipeline cho
 * xem tổng (quote) và PlaceOrder.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 18,
    'endLine' => 47,
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
      'extensions' => 
      array (
        'declaringClassName' => 'Modules\\Checkout\\Application\\TotalsPipeline',
        'implementingClassName' => 'Modules\\Checkout\\Application\\TotalsPipeline',
        'name' => 'extensions',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Modules\\Extension\\Contracts\\Extensions',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 20,
        'endLine' => 20,
        'startColumn' => 33,
        'endColumn' => 71,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
    ),
    'immediateMethods' => 
    array (
      '__construct' => 
      array (
        'name' => '__construct',
        'parameters' => 
        array (
          'extensions' => 
          array (
            'name' => 'extensions',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Extension\\Contracts\\Extensions',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 20,
            'endLine' => 20,
            'startColumn' => 33,
            'endColumn' => 71,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 20,
        'endLine' => 20,
        'startColumn' => 5,
        'endColumn' => 75,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Checkout\\Application',
        'declaringClassName' => 'Modules\\Checkout\\Application\\TotalsPipeline',
        'implementingClassName' => 'Modules\\Checkout\\Application\\TotalsPipeline',
        'currentClassName' => 'Modules\\Checkout\\Application\\TotalsPipeline',
        'aliasName' => NULL,
      ),
      'run' => 
      array (
        'name' => 'run',
        'parameters' => 
        array (
          'context' => 
          array (
            'name' => 'context',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Checkout\\Contracts\\Data\\TotalsContext',
                'isIdentifier' => false,
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
            'startColumn' => 25,
            'endColumn' => 46,
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
            'name' => 'Modules\\Checkout\\Contracts\\Data\\Totals',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 22,
        'endLine' => 46,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Checkout\\Application',
        'declaringClassName' => 'Modules\\Checkout\\Application\\TotalsPipeline',
        'implementingClassName' => 'Modules\\Checkout\\Application\\TotalsPipeline',
        'currentClassName' => 'Modules\\Checkout\\Application\\TotalsPipeline',
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