<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Payment/Http/Controllers/Api/GatewayCallbackController.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Payment\Http\Controllers\Api\GatewayCallbackController
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-23a52232be6fedc4316b4fdb26459fa53146911ada6437b6e89cd6d78460533f',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Payment\\Http\\Controllers\\Api\\GatewayCallbackController',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Payment/Http/Controllers/Api/GatewayCallbackController.php',
      ),
    ),
    'namespace' => 'Modules\\Payment\\Http\\Controllers\\Api',
    'name' => 'Modules\\Payment\\Http\\Controllers\\Api\\GatewayCallbackController',
    'shortName' => 'GatewayCallbackController',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * IPN/webhook chung cho mọi cổng: /api/payments/{gateway}/callback. Cổng xác minh chữ ký; Core ghi nhận.
 * Return URL của khách KHÔNG xác nhận thanh toán — chỉ callback đã xác minh mới được.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 21,
    'endLine' => 46,
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
      '__invoke' => 
      array (
        'name' => '__invoke',
        'parameters' => 
        array (
          'gateway' => 
          array (
            'name' => 'gateway',
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
            'startColumn' => 30,
            'endColumn' => 44,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'request' => 
          array (
            'name' => 'request',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Http\\Request',
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
            'startColumn' => 47,
            'endColumn' => 62,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'gateways' => 
          array (
            'name' => 'gateways',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Payment\\Application\\GatewayRegistry',
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
            'startColumn' => 65,
            'endColumn' => 89,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'payments' => 
          array (
            'name' => 'payments',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Payment\\Application\\PaymentService',
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
            'startColumn' => 92,
            'endColumn' => 115,
            'parameterIndex' => 3,
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
                'name' => 'Modules\\Shared\\Context\\CurrentContext',
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
            'startColumn' => 118,
            'endColumn' => 140,
            'parameterIndex' => 4,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Illuminate\\Http\\JsonResponse',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 23,
        'endLine' => 45,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Payment\\Http\\Controllers\\Api',
        'declaringClassName' => 'Modules\\Payment\\Http\\Controllers\\Api\\GatewayCallbackController',
        'implementingClassName' => 'Modules\\Payment\\Http\\Controllers\\Api\\GatewayCallbackController',
        'currentClassName' => 'Modules\\Payment\\Http\\Controllers\\Api\\GatewayCallbackController',
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