<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Checkout/Testing/TaxCalculatorContract.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Checkout\Testing\TaxCalculatorContract
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-8c01d9b8acd9776740f92daec945cb5478a5cbe6df2ccbe273e96521bf7b65dd',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Checkout\\Testing\\TaxCalculatorContract',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Checkout/Testing/TaxCalculatorContract.php',
      ),
    ),
    'namespace' => 'Modules\\Checkout\\Testing',
    'name' => 'Modules\\Checkout\\Testing\\TaxCalculatorContract',
    'shortName' => 'TaxCalculatorContract',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Contract test cho TaxCalculator. Kiểm tra: mã ổn định; chỉ trả thuế cho dòng có trong giỏ; thuế suất 0–100%;
 * tiền thuế nguyên, không âm, không vượt thành tiền dòng; cùng đầu vào cho cùng kết quả.
 *
 *   TaxCalculatorContract::define(\'vani.tax-xyz\', fn () => new XyzTax(...));
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 17,
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
            'startLine' => 23,
            'endLine' => 23,
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
            'startLine' => 23,
            'endLine' => 23,
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
                'startLine' => 23,
                'endLine' => 23,
                'startTokenPos' => 67,
                'startFilePos' => 773,
                'endTokenPos' => 67,
                'endFilePos' => 776,
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
            'startLine' => 23,
            'endLine' => 23,
            'startColumn' => 71,
            'endColumn' => 94,
            'parameterIndex' => 2,
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
 * @param  Closure(): TaxCalculator  $calculator
 * @param  (Closure(): TotalsContext)|null  $context
 */',
        'startLine' => 23,
        'endLine' => 46,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Modules\\Checkout\\Testing',
        'declaringClassName' => 'Modules\\Checkout\\Testing\\TaxCalculatorContract',
        'implementingClassName' => 'Modules\\Checkout\\Testing\\TaxCalculatorContract',
        'currentClassName' => 'Modules\\Checkout\\Testing\\TaxCalculatorContract',
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