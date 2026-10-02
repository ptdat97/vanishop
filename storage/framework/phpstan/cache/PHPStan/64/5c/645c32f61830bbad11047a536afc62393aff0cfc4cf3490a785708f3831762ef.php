<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Checkout/Testing/TotalsCalculatorContract.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Checkout\Testing\TotalsCalculatorContract
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-c579bd885a9ea33f803ef73630d5fa1b41cf5a3498523bcbbdb58304b31a10ed',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Checkout\\Testing\\TotalsCalculatorContract',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Checkout/Testing/TotalsCalculatorContract.php',
      ),
    ),
    'namespace' => 'Modules\\Checkout\\Testing',
    'name' => 'Modules\\Checkout\\Testing\\TotalsCalculatorContract',
    'shortName' => 'TotalsCalculatorContract',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Contract test cho TotalsCalculator. Kiểm tra: mã ổn định; plugin dùng priority 300–399; không đổi dòng
 * (variant, số lượng, đơn giá) và tiền tệ; adjustment cùng tiền tệ; xác định.
 *
 *   TotalsCalculatorContract::define(\'vani.loyalty\', fn () => new PointsRedemption(...));
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 18,
    'endLine' => 49,
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
            'startLine' => 24,
            'endLine' => 24,
            'startColumn' => 35,
            'endColumn' => 47,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'calculator' => 
          array (
            'name' => 'calculator',
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
            'startLine' => 24,
            'endLine' => 24,
            'startColumn' => 50,
            'endColumn' => 68,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'context' => 
          array (
            'name' => 'context',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 24,
                'endLine' => 24,
                'startTokenPos' => 72,
                'startFilePos' => 815,
                'endTokenPos' => 72,
                'endFilePos' => 818,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
              'data' => 
              array (
                'types' => 
                array (
                  0 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'Closure',
                      'isIdentifier' => false,
                    ),
                  ),
                  1 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'null',
                      'isIdentifier' => true,
                    ),
                  ),
                ),
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
            'startColumn' => 71,
            'endColumn' => 94,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
          'plugin' => 
          array (
            'name' => 'plugin',
            'default' => 
            array (
              'code' => 'true',
              'attributes' => 
              array (
                'startLine' => 24,
                'endLine' => 24,
                'startTokenPos' => 81,
                'startFilePos' => 836,
                'endTokenPos' => 81,
                'endFilePos' => 839,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'bool',
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
            'startColumn' => 97,
            'endColumn' => 115,
            'parameterIndex' => 3,
            'isOptional' => true,
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
 * @param  Closure(): TotalsCalculator  $calculator
 * @param  (Closure(): TotalsContext)|null  $context
 */',
        'startLine' => 24,
        'endLine' => 48,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Modules\\Checkout\\Testing',
        'declaringClassName' => 'Modules\\Checkout\\Testing\\TotalsCalculatorContract',
        'implementingClassName' => 'Modules\\Checkout\\Testing\\TotalsCalculatorContract',
        'currentClassName' => 'Modules\\Checkout\\Testing\\TotalsCalculatorContract',
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