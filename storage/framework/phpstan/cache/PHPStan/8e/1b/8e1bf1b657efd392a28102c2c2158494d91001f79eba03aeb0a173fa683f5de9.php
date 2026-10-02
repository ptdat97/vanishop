<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Checkout/Application/Calculators/ShippingCalculator.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Checkout\Application\Calculators\ShippingCalculator
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-286478adfbe3042c8efd62d0e49f4c2c5c6383259b7385925a7098eceda9ebd1',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Checkout\\Application\\Calculators\\ShippingCalculator',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Checkout/Application/Calculators/ShippingCalculator.php',
      ),
    ),
    'namespace' => 'Modules\\Checkout\\Application\\Calculators',
    'name' => 'Modules\\Checkout\\Application\\Calculators\\ShippingCalculator',
    'shortName' => 'ShippingCalculator',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Chọn phương thức giao theo mã khách gửi (không gửi → phương thức đầu tiên). Mã không hợp lệ → không có
 * phí giao, validator `shipping` sẽ chặn đặt hàng.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 15,
    'endLine' => 38,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'Modules\\Checkout\\Contracts\\TotalsCalculator',
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
      'options' => 
      array (
        'declaringClassName' => 'Modules\\Checkout\\Application\\Calculators\\ShippingCalculator',
        'implementingClassName' => 'Modules\\Checkout\\Application\\Calculators\\ShippingCalculator',
        'name' => 'options',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Modules\\Checkout\\Application\\ShippingOptions',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 17,
        'endLine' => 17,
        'startColumn' => 33,
        'endColumn' => 73,
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
          'options' => 
          array (
            'name' => 'options',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Checkout\\Application\\ShippingOptions',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 17,
            'endLine' => 17,
            'startColumn' => 33,
            'endColumn' => 73,
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
        'startLine' => 17,
        'endLine' => 17,
        'startColumn' => 5,
        'endColumn' => 77,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Checkout\\Application\\Calculators',
        'declaringClassName' => 'Modules\\Checkout\\Application\\Calculators\\ShippingCalculator',
        'implementingClassName' => 'Modules\\Checkout\\Application\\Calculators\\ShippingCalculator',
        'currentClassName' => 'Modules\\Checkout\\Application\\Calculators\\ShippingCalculator',
        'aliasName' => NULL,
      ),
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
        'startLine' => 19,
        'endLine' => 22,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Checkout\\Application\\Calculators',
        'declaringClassName' => 'Modules\\Checkout\\Application\\Calculators\\ShippingCalculator',
        'implementingClassName' => 'Modules\\Checkout\\Application\\Calculators\\ShippingCalculator',
        'currentClassName' => 'Modules\\Checkout\\Application\\Calculators\\ShippingCalculator',
        'aliasName' => NULL,
      ),
      'priority' => 
      array (
        'name' => 'priority',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 24,
        'endLine' => 27,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Checkout\\Application\\Calculators',
        'declaringClassName' => 'Modules\\Checkout\\Application\\Calculators\\ShippingCalculator',
        'implementingClassName' => 'Modules\\Checkout\\Application\\Calculators\\ShippingCalculator',
        'currentClassName' => 'Modules\\Checkout\\Application\\Calculators\\ShippingCalculator',
        'aliasName' => NULL,
      ),
      'calculate' => 
      array (
        'name' => 'calculate',
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
            'startLine' => 29,
            'endLine' => 29,
            'startColumn' => 31,
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
            'name' => 'Modules\\Checkout\\Contracts\\Data\\TotalsContext',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 29,
        'endLine' => 37,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Checkout\\Application\\Calculators',
        'declaringClassName' => 'Modules\\Checkout\\Application\\Calculators\\ShippingCalculator',
        'implementingClassName' => 'Modules\\Checkout\\Application\\Calculators\\ShippingCalculator',
        'currentClassName' => 'Modules\\Checkout\\Application\\Calculators\\ShippingCalculator',
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