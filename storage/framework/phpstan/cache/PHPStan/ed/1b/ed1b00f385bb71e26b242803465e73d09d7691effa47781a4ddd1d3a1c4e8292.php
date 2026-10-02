<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Checkout/Testing/CheckoutValidatorContract.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Checkout\Testing\CheckoutValidatorContract
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-1cd6d6022d60bdb79a1731715db4ba85d1e7540050104d99e3022f503229ea6e',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Checkout\\Testing\\CheckoutValidatorContract',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Checkout/Testing/CheckoutValidatorContract.php',
      ),
    ),
    'namespace' => 'Modules\\Checkout\\Testing',
    'name' => 'Modules\\Checkout\\Testing\\CheckoutValidatorContract',
    'shortName' => 'CheckoutValidatorContract',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Contract test cho CheckoutValidator. Kiểm tra: mã ổn định; trả list<CheckoutIssue> có mã + thông điệp;
 * yêu cầu hợp lệ của plugin không bị chặn; yêu cầu vi phạm (nếu có) bị chặn; xác định.
 *
 *   CheckoutValidatorContract::define(\'vani.cod-province\', fn () => new CodProvinceValidator(...),
 *       invalid: fn () => new CheckoutRequest(...));   // yêu cầu mà validator phải chặn
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 19,
    'endLine' => 51,
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
            'startLine' => 26,
            'endLine' => 26,
            'startColumn' => 35,
            'endColumn' => 47,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'validator' => 
          array (
            'name' => 'validator',
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
            'startLine' => 26,
            'endLine' => 26,
            'startColumn' => 50,
            'endColumn' => 67,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'valid' => 
          array (
            'name' => 'valid',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 26,
                'endLine' => 26,
                'startTokenPos' => 72,
                'startFilePos' => 1002,
                'endTokenPos' => 72,
                'endFilePos' => 1005,
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
            'startLine' => 26,
            'endLine' => 26,
            'startColumn' => 70,
            'endColumn' => 91,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
          'invalid' => 
          array (
            'name' => 'invalid',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 26,
                'endLine' => 26,
                'startTokenPos' => 82,
                'startFilePos' => 1028,
                'endTokenPos' => 82,
                'endFilePos' => 1031,
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
            'startLine' => 26,
            'endLine' => 26,
            'startColumn' => 94,
            'endColumn' => 117,
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
 * @param  Closure(): CheckoutValidator  $validator
 * @param  (Closure(): CheckoutRequest)|null  $valid
 * @param  (Closure(): CheckoutRequest)|null  $invalid
 */',
        'startLine' => 26,
        'endLine' => 50,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Modules\\Checkout\\Testing',
        'declaringClassName' => 'Modules\\Checkout\\Testing\\CheckoutValidatorContract',
        'implementingClassName' => 'Modules\\Checkout\\Testing\\CheckoutValidatorContract',
        'currentClassName' => 'Modules\\Checkout\\Testing\\CheckoutValidatorContract',
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