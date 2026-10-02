<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Payment/Testing/PaymentGatewayContract.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Payment\Testing\PaymentGatewayContract
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-9a1992ae92109a4d75c62683fdf04a68391dcf23b1fd9e48ed32ff0b64adf44d',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Payment\\Testing\\PaymentGatewayContract',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Payment/Testing/PaymentGatewayContract.php',
      ),
    ),
    'namespace' => 'Modules\\Payment\\Testing',
    'name' => 'Modules\\Payment\\Testing\\PaymentGatewayContract',
    'shortName' => 'PaymentGatewayContract',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Bộ contract test cho mọi PaymentGateway (Core và plugin). Trong file test Pest của plugin:
 *
 *   PaymentGatewayContract::define(\'vani.vietqr\', fn () => new VietQrGateway(...),
 *       validCallback: fn (PaymentData $p) => Request::create(...),       // callback ký đúng
 *       tamperedCallback: fn (PaymentData $p) => Request::create(...));   // callback bị sửa
 *
 * Kiểm tra: mã hợp lệ; initiate idempotent; callback đúng chữ ký được chấp nhận và khớp payment/số tiền;
 * callback sai bị từ chối; cổng không có callback luôn từ chối; query trả trạng thái; refund idempotent theo key;
 * cổng giữ tiền (CapturesLater): capture/void idempotent theo key.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 31,
    'endLine' => 129,
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
            'startLine' => 38,
            'endLine' => 38,
            'startColumn' => 35,
            'endColumn' => 47,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'gateway' => 
          array (
            'name' => 'gateway',
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
            'startLine' => 38,
            'endLine' => 38,
            'startColumn' => 50,
            'endColumn' => 65,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'validCallback' => 
          array (
            'name' => 'validCallback',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 38,
                'endLine' => 38,
                'startTokenPos' => 112,
                'startFilePos' => 1639,
                'endTokenPos' => 112,
                'endFilePos' => 1642,
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
            'startLine' => 38,
            'endLine' => 38,
            'startColumn' => 68,
            'endColumn' => 97,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
          'tamperedCallback' => 
          array (
            'name' => 'tamperedCallback',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 38,
                'endLine' => 38,
                'startTokenPos' => 122,
                'startFilePos' => 1674,
                'endTokenPos' => 122,
                'endFilePos' => 1677,
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
            'startLine' => 38,
            'endLine' => 38,
            'startColumn' => 100,
            'endColumn' => 132,
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
 * @param  Closure(): PaymentGateway  $gateway
 * @param  (Closure(PaymentData): Request)|null  $validCallback
 * @param  (Closure(PaymentData): Request)|null  $tamperedCallback
 */',
        'startLine' => 38,
        'endLine' => 128,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Modules\\Payment\\Testing',
        'declaringClassName' => 'Modules\\Payment\\Testing\\PaymentGatewayContract',
        'implementingClassName' => 'Modules\\Payment\\Testing\\PaymentGatewayContract',
        'currentClassName' => 'Modules\\Payment\\Testing\\PaymentGatewayContract',
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