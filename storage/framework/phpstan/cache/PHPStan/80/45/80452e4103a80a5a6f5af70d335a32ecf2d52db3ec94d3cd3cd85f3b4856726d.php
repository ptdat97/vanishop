<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/custom/plugin/ShippingFlatRate/Infrastructure/FlatRateShipping.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Plugin\ShippingFlatRate\Infrastructure\FlatRateShipping
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-133cc33729226731cde9735ba73b0096770f81ccf5d736a21c6f0919dd26d26c',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Plugin\\ShippingFlatRate\\Infrastructure\\FlatRateShipping',
        'filename' => '/Users/dat/Ecommerce/vanishop/custom/plugin/ShippingFlatRate/Infrastructure/FlatRateShipping.php',
      ),
    ),
    'namespace' => 'Plugin\\ShippingFlatRate\\Infrastructure',
    'name' => 'Plugin\\ShippingFlatRate\\Infrastructure\\FlatRateShipping',
    'shortName' => 'FlatRateShipping',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Phí giao cố định, miễn phí khi tiền hàng (sau giảm giá) đạt ngưỡng. Mã phương thức `standard` giữ nguyên
 * từ khi còn nằm trong Core.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 17,
    'endLine' => 29,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'Modules\\Checkout\\Contracts\\ShippingRateProvider',
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
      'settings' => 
      array (
        'declaringClassName' => 'Plugin\\ShippingFlatRate\\Infrastructure\\FlatRateShipping',
        'implementingClassName' => 'Plugin\\ShippingFlatRate\\Infrastructure\\FlatRateShipping',
        'name' => 'settings',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Modules\\Tenancy\\Contracts\\Settings',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 19,
        'endLine' => 19,
        'startColumn' => 33,
        'endColumn' => 67,
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
          'settings' => 
          array (
            'name' => 'settings',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Tenancy\\Contracts\\Settings',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 19,
            'endLine' => 19,
            'startColumn' => 33,
            'endColumn' => 67,
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
        'startLine' => 19,
        'endLine' => 19,
        'startColumn' => 5,
        'endColumn' => 71,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Plugin\\ShippingFlatRate\\Infrastructure',
        'declaringClassName' => 'Plugin\\ShippingFlatRate\\Infrastructure\\FlatRateShipping',
        'implementingClassName' => 'Plugin\\ShippingFlatRate\\Infrastructure\\FlatRateShipping',
        'currentClassName' => 'Plugin\\ShippingFlatRate\\Infrastructure\\FlatRateShipping',
        'aliasName' => NULL,
      ),
      'options' => 
      array (
        'name' => 'options',
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
            'startLine' => 21,
            'endLine' => 21,
            'startColumn' => 29,
            'endColumn' => 50,
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
        'docComment' => NULL,
        'startLine' => 21,
        'endLine' => 28,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Plugin\\ShippingFlatRate\\Infrastructure',
        'declaringClassName' => 'Plugin\\ShippingFlatRate\\Infrastructure\\FlatRateShipping',
        'implementingClassName' => 'Plugin\\ShippingFlatRate\\Infrastructure\\FlatRateShipping',
        'currentClassName' => 'Plugin\\ShippingFlatRate\\Infrastructure\\FlatRateShipping',
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