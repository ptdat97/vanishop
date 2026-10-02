<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Payment/Contracts/Data/GatewayCallback.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Payment\Contracts\Data\GatewayCallback
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-1f21441dd33b1f8df5622ae7a4265cb8cd6025c9f74aa1ec107dccaf4befde04',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Payment\\Contracts\\Data\\GatewayCallback',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Payment/Contracts/Data/GatewayCallback.php',
      ),
    ),
    'namespace' => 'Modules\\Payment\\Contracts\\Data',
    'name' => 'Modules\\Payment\\Contracts\\Data\\GatewayCallback',
    'shortName' => 'GatewayCallback',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 65568,
    'docComment' => '/**
 * Callback/IPN đã xác minh chữ ký, chuẩn hoá. `acknowledgement` là phản hồi cổng mong đợi (JSON).
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 12,
    'endLine' => 35,
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
      'PAID' => 
      array (
        'declaringClassName' => 'Modules\\Payment\\Contracts\\Data\\GatewayCallback',
        'implementingClassName' => 'Modules\\Payment\\Contracts\\Data\\GatewayCallback',
        'name' => 'PAID',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'paid\'',
          'attributes' => 
          array (
            'startLine' => 14,
            'endLine' => 14,
            'startTokenPos' => 40,
            'startFilePos' => 305,
            'endTokenPos' => 40,
            'endFilePos' => 310,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 14,
        'endLine' => 14,
        'startColumn' => 5,
        'endColumn' => 31,
      ),
      'FAILED' => 
      array (
        'declaringClassName' => 'Modules\\Payment\\Contracts\\Data\\GatewayCallback',
        'implementingClassName' => 'Modules\\Payment\\Contracts\\Data\\GatewayCallback',
        'name' => 'FAILED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'failed\'',
          'attributes' => 
          array (
            'startLine' => 16,
            'endLine' => 16,
            'startTokenPos' => 51,
            'startFilePos' => 340,
            'endTokenPos' => 51,
            'endFilePos' => 347,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 16,
        'endLine' => 16,
        'startColumn' => 5,
        'endColumn' => 35,
      ),
      'PENDING' => 
      array (
        'declaringClassName' => 'Modules\\Payment\\Contracts\\Data\\GatewayCallback',
        'implementingClassName' => 'Modules\\Payment\\Contracts\\Data\\GatewayCallback',
        'name' => 'PENDING',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'pending\'',
          'attributes' => 
          array (
            'startLine' => 18,
            'endLine' => 18,
            'startTokenPos' => 62,
            'startFilePos' => 378,
            'endTokenPos' => 62,
            'endFilePos' => 386,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 18,
        'endLine' => 18,
        'startColumn' => 5,
        'endColumn' => 37,
      ),
      'AUTHORIZED' => 
      array (
        'declaringClassName' => 'Modules\\Payment\\Contracts\\Data\\GatewayCallback',
        'implementingClassName' => 'Modules\\Payment\\Contracts\\Data\\GatewayCallback',
        'name' => 'AUTHORIZED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'authorized\'',
          'attributes' => 
          array (
            'startLine' => 21,
            'endLine' => 21,
            'startTokenPos' => 75,
            'startFilePos' => 490,
            'endTokenPos' => 75,
            'endFilePos' => 501,
          ),
        ),
        'docComment' => '/** Đã giữ tiền, chưa thu (chỉ cổng CapturesLater). */',
        'attributes' => 
        array (
        ),
        'startLine' => 21,
        'endLine' => 21,
        'startColumn' => 5,
        'endColumn' => 43,
      ),
    ),
    'immediateProperties' => 
    array (
      'paymentPublicId' => 
      array (
        'declaringClassName' => 'Modules\\Payment\\Contracts\\Data\\GatewayCallback',
        'implementingClassName' => 'Modules\\Payment\\Contracts\\Data\\GatewayCallback',
        'name' => 'paymentPublicId',
        'modifiers' => 2049,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 28,
        'endLine' => 28,
        'startColumn' => 9,
        'endColumn' => 38,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'gatewayTransactionId' => 
      array (
        'declaringClassName' => 'Modules\\Payment\\Contracts\\Data\\GatewayCallback',
        'implementingClassName' => 'Modules\\Payment\\Contracts\\Data\\GatewayCallback',
        'name' => 'gatewayTransactionId',
        'modifiers' => 2049,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 29,
        'endLine' => 29,
        'startColumn' => 9,
        'endColumn' => 43,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'status' => 
      array (
        'declaringClassName' => 'Modules\\Payment\\Contracts\\Data\\GatewayCallback',
        'implementingClassName' => 'Modules\\Payment\\Contracts\\Data\\GatewayCallback',
        'name' => 'status',
        'modifiers' => 2049,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 30,
        'endLine' => 30,
        'startColumn' => 9,
        'endColumn' => 29,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'amount' => 
      array (
        'declaringClassName' => 'Modules\\Payment\\Contracts\\Data\\GatewayCallback',
        'implementingClassName' => 'Modules\\Payment\\Contracts\\Data\\GatewayCallback',
        'name' => 'amount',
        'modifiers' => 2049,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Modules\\Shared\\Domain\\Money\\Money',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 31,
        'endLine' => 31,
        'startColumn' => 9,
        'endColumn' => 28,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'maskedPayload' => 
      array (
        'declaringClassName' => 'Modules\\Payment\\Contracts\\Data\\GatewayCallback',
        'implementingClassName' => 'Modules\\Payment\\Contracts\\Data\\GatewayCallback',
        'name' => 'maskedPayload',
        'modifiers' => 2049,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'default' => 
        array (
          'code' => '[]',
          'attributes' => 
          array (
            'startLine' => 32,
            'endLine' => 32,
            'startTokenPos' => 123,
            'startFilePos' => 905,
            'endTokenPos' => 124,
            'endFilePos' => 906,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 32,
        'endLine' => 32,
        'startColumn' => 9,
        'endColumn' => 40,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'acknowledgement' => 
      array (
        'declaringClassName' => 'Modules\\Payment\\Contracts\\Data\\GatewayCallback',
        'implementingClassName' => 'Modules\\Payment\\Contracts\\Data\\GatewayCallback',
        'name' => 'acknowledgement',
        'modifiers' => 2049,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'default' => 
        array (
          'code' => '[\'ok\' => true]',
          'attributes' => 
          array (
            'startLine' => 33,
            'endLine' => 33,
            'startTokenPos' => 135,
            'startFilePos' => 949,
            'endTokenPos' => 141,
            'endFilePos' => 962,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 33,
        'endLine' => 33,
        'startColumn' => 9,
        'endColumn' => 54,
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
          'paymentPublicId' => 
          array (
            'name' => 'paymentPublicId',
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 28,
            'endLine' => 28,
            'startColumn' => 9,
            'endColumn' => 38,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'gatewayTransactionId' => 
          array (
            'name' => 'gatewayTransactionId',
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 29,
            'endLine' => 29,
            'startColumn' => 9,
            'endColumn' => 43,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'status' => 
          array (
            'name' => 'status',
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 30,
            'endLine' => 30,
            'startColumn' => 9,
            'endColumn' => 29,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'amount' => 
          array (
            'name' => 'amount',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Shared\\Domain\\Money\\Money',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 31,
            'endLine' => 31,
            'startColumn' => 9,
            'endColumn' => 28,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'maskedPayload' => 
          array (
            'name' => 'maskedPayload',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 32,
                'endLine' => 32,
                'startTokenPos' => 123,
                'startFilePos' => 905,
                'endTokenPos' => 124,
                'endFilePos' => 906,
              ),
            ),
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 32,
            'endLine' => 32,
            'startColumn' => 9,
            'endColumn' => 40,
            'parameterIndex' => 4,
            'isOptional' => true,
          ),
          'acknowledgement' => 
          array (
            'name' => 'acknowledgement',
            'default' => 
            array (
              'code' => '[\'ok\' => true]',
              'attributes' => 
              array (
                'startLine' => 33,
                'endLine' => 33,
                'startTokenPos' => 135,
                'startFilePos' => 949,
                'endTokenPos' => 141,
                'endFilePos' => 962,
              ),
            ),
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 33,
            'endLine' => 33,
            'startColumn' => 9,
            'endColumn' => 54,
            'parameterIndex' => 5,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param  array<string, mixed>  $maskedPayload  payload đã che dữ liệu nhạy cảm, để lưu vết
 * @param  array<string, mixed>  $acknowledgement
 */',
        'startLine' => 27,
        'endLine' => 34,
        'startColumn' => 5,
        'endColumn' => 8,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Payment\\Contracts\\Data',
        'declaringClassName' => 'Modules\\Payment\\Contracts\\Data\\GatewayCallback',
        'implementingClassName' => 'Modules\\Payment\\Contracts\\Data\\GatewayCallback',
        'currentClassName' => 'Modules\\Payment\\Contracts\\Data\\GatewayCallback',
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