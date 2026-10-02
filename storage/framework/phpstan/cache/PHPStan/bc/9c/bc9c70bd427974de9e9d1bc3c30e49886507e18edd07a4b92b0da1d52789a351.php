<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/custom/plugin/ZaloZns/Infrastructure/ZnsClient.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Plugin\ZaloZns\Infrastructure\ZnsClient
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-ca498abd0ab8d90504a7c1918cf2b825aa042e01cc6448d680b334b4c6042062',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Plugin\\ZaloZns\\Infrastructure\\ZnsClient',
        'filename' => '/Users/dat/Ecommerce/vanishop/custom/plugin/ZaloZns/Infrastructure/ZnsClient.php',
      ),
    ),
    'namespace' => 'Plugin\\ZaloZns\\Infrastructure',
    'name' => 'Plugin\\ZaloZns\\Infrastructure\\ZnsClient',
    'shortName' => 'ZnsClient',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Gửi tin ZNS theo template đã duyệt. Access token (~25h) làm mới bằng refresh token; refresh token xoay
 * vòng mỗi lần làm mới nên luôn lưu bản mới nhất. Token hết hạn giữa chừng (lỗi -124) → làm mới rồi gửi lại một lần.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 16,
    'endLine' => 142,
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
      'TOKEN_ERRORS' => 
      array (
        'declaringClassName' => 'Plugin\\ZaloZns\\Infrastructure\\ZnsClient',
        'implementingClassName' => 'Plugin\\ZaloZns\\Infrastructure\\ZnsClient',
        'name' => 'TOKEN_ERRORS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[-124, -216]',
          'attributes' => 
          array (
            'startLine' => 19,
            'endLine' => 19,
            'startTokenPos' => 55,
            'startFilePos' => 654,
            'endTokenPos' => 62,
            'endFilePos' => 665,
          ),
        ),
        'docComment' => '/** Lỗi token (hết hạn/không hợp lệ). */',
        'attributes' => 
        array (
        ),
        'startLine' => 19,
        'endLine' => 19,
        'startColumn' => 5,
        'endColumn' => 46,
      ),
      'RETRYABLE' => 
      array (
        'declaringClassName' => 'Plugin\\ZaloZns\\Infrastructure\\ZnsClient',
        'implementingClassName' => 'Plugin\\ZaloZns\\Infrastructure\\ZnsClient',
        'name' => 'RETRYABLE',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[-32, -1, -1000]',
          'attributes' => 
          array (
            'startLine' => 22,
            'endLine' => 22,
            'startTokenPos' => 75,
            'startFilePos' => 777,
            'endTokenPos' => 86,
            'endFilePos' => 792,
          ),
        ),
        'docComment' => '/** Lỗi tạm thời: vượt hạn mức gọi, hệ thống bận. */',
        'attributes' => 
        array (
        ),
        'startLine' => 22,
        'endLine' => 22,
        'startColumn' => 5,
        'endColumn' => 47,
      ),
      'ACCESS_KEY' => 
      array (
        'declaringClassName' => 'Plugin\\ZaloZns\\Infrastructure\\ZnsClient',
        'implementingClassName' => 'Plugin\\ZaloZns\\Infrastructure\\ZnsClient',
        'name' => 'ACCESS_KEY',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'vani.zalo-zns.access_token\'',
          'attributes' => 
          array (
            'startLine' => 24,
            'endLine' => 24,
            'startTokenPos' => 97,
            'startFilePos' => 827,
            'endTokenPos' => 97,
            'endFilePos' => 854,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 24,
        'endLine' => 24,
        'startColumn' => 5,
        'endColumn' => 60,
      ),
      'REFRESH_KEY' => 
      array (
        'declaringClassName' => 'Plugin\\ZaloZns\\Infrastructure\\ZnsClient',
        'implementingClassName' => 'Plugin\\ZaloZns\\Infrastructure\\ZnsClient',
        'name' => 'REFRESH_KEY',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'vani.zalo-zns.refresh_token\'',
          'attributes' => 
          array (
            'startLine' => 26,
            'endLine' => 26,
            'startTokenPos' => 108,
            'startFilePos' => 890,
            'endTokenPos' => 108,
            'endFilePos' => 918,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 26,
        'endLine' => 26,
        'startColumn' => 5,
        'endColumn' => 62,
      ),
    ),
    'immediateProperties' => 
    array (
      'cache' => 
      array (
        'declaringClassName' => 'Plugin\\ZaloZns\\Infrastructure\\ZnsClient',
        'implementingClassName' => 'Plugin\\ZaloZns\\Infrastructure\\ZnsClient',
        'name' => 'cache',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Illuminate\\Contracts\\Cache\\Repository',
            'isIdentifier' => false,
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
        'endColumn' => 42,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'apiBase' => 
      array (
        'declaringClassName' => 'Plugin\\ZaloZns\\Infrastructure\\ZnsClient',
        'implementingClassName' => 'Plugin\\ZaloZns\\Infrastructure\\ZnsClient',
        'name' => 'apiBase',
        'modifiers' => 132,
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
        'endColumn' => 40,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'oauthBase' => 
      array (
        'declaringClassName' => 'Plugin\\ZaloZns\\Infrastructure\\ZnsClient',
        'implementingClassName' => 'Plugin\\ZaloZns\\Infrastructure\\ZnsClient',
        'name' => 'oauthBase',
        'modifiers' => 132,
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
        'startLine' => 31,
        'endLine' => 31,
        'startColumn' => 9,
        'endColumn' => 42,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'appId' => 
      array (
        'declaringClassName' => 'Plugin\\ZaloZns\\Infrastructure\\ZnsClient',
        'implementingClassName' => 'Plugin\\ZaloZns\\Infrastructure\\ZnsClient',
        'name' => 'appId',
        'modifiers' => 132,
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
        'startLine' => 32,
        'endLine' => 32,
        'startColumn' => 9,
        'endColumn' => 38,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'appSecret' => 
      array (
        'declaringClassName' => 'Plugin\\ZaloZns\\Infrastructure\\ZnsClient',
        'implementingClassName' => 'Plugin\\ZaloZns\\Infrastructure\\ZnsClient',
        'name' => 'appSecret',
        'modifiers' => 132,
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
        'startLine' => 33,
        'endLine' => 33,
        'startColumn' => 9,
        'endColumn' => 42,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'initialRefreshToken' => 
      array (
        'declaringClassName' => 'Plugin\\ZaloZns\\Infrastructure\\ZnsClient',
        'implementingClassName' => 'Plugin\\ZaloZns\\Infrastructure\\ZnsClient',
        'name' => 'initialRefreshToken',
        'modifiers' => 132,
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
        'startLine' => 34,
        'endLine' => 34,
        'startColumn' => 9,
        'endColumn' => 52,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'mode' => 
      array (
        'declaringClassName' => 'Plugin\\ZaloZns\\Infrastructure\\ZnsClient',
        'implementingClassName' => 'Plugin\\ZaloZns\\Infrastructure\\ZnsClient',
        'name' => 'mode',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'default' => 
        array (
          'code' => '\'production\'',
          'attributes' => 
          array (
            'startLine' => 35,
            'endLine' => 35,
            'startTokenPos' => 182,
            'startFilePos' => 1263,
            'endTokenPos' => 182,
            'endFilePos' => 1274,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 35,
        'endLine' => 35,
        'startColumn' => 9,
        'endColumn' => 52,
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
          'cache' => 
          array (
            'name' => 'cache',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Contracts\\Cache\\Repository',
                'isIdentifier' => false,
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
            'endColumn' => 42,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'apiBase' => 
          array (
            'name' => 'apiBase',
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
            'endColumn' => 40,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'oauthBase' => 
          array (
            'name' => 'oauthBase',
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
            'startLine' => 31,
            'endLine' => 31,
            'startColumn' => 9,
            'endColumn' => 42,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'appId' => 
          array (
            'name' => 'appId',
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
            'startLine' => 32,
            'endLine' => 32,
            'startColumn' => 9,
            'endColumn' => 38,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'appSecret' => 
          array (
            'name' => 'appSecret',
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
            'startLine' => 33,
            'endLine' => 33,
            'startColumn' => 9,
            'endColumn' => 42,
            'parameterIndex' => 4,
            'isOptional' => false,
          ),
          'initialRefreshToken' => 
          array (
            'name' => 'initialRefreshToken',
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
            'startLine' => 34,
            'endLine' => 34,
            'startColumn' => 9,
            'endColumn' => 52,
            'parameterIndex' => 5,
            'isOptional' => false,
          ),
          'mode' => 
          array (
            'name' => 'mode',
            'default' => 
            array (
              'code' => '\'production\'',
              'attributes' => 
              array (
                'startLine' => 35,
                'endLine' => 35,
                'startTokenPos' => 182,
                'startFilePos' => 1263,
                'endTokenPos' => 182,
                'endFilePos' => 1274,
              ),
            ),
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
            'startLine' => 35,
            'endLine' => 35,
            'startColumn' => 9,
            'endColumn' => 52,
            'parameterIndex' => 6,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 28,
        'endLine' => 36,
        'startColumn' => 5,
        'endColumn' => 8,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Plugin\\ZaloZns\\Infrastructure',
        'declaringClassName' => 'Plugin\\ZaloZns\\Infrastructure\\ZnsClient',
        'implementingClassName' => 'Plugin\\ZaloZns\\Infrastructure\\ZnsClient',
        'currentClassName' => 'Plugin\\ZaloZns\\Infrastructure\\ZnsClient',
        'aliasName' => NULL,
      ),
      'configured' => 
      array (
        'name' => 'configured',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 38,
        'endLine' => 41,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Plugin\\ZaloZns\\Infrastructure',
        'declaringClassName' => 'Plugin\\ZaloZns\\Infrastructure\\ZnsClient',
        'implementingClassName' => 'Plugin\\ZaloZns\\Infrastructure\\ZnsClient',
        'currentClassName' => 'Plugin\\ZaloZns\\Infrastructure\\ZnsClient',
        'aliasName' => NULL,
      ),
      'send' => 
      array (
        'name' => 'send',
        'parameters' => 
        array (
          'e164' => 
          array (
            'name' => 'e164',
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
            'startLine' => 46,
            'endLine' => 46,
            'startColumn' => 26,
            'endColumn' => 37,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'templateId' => 
          array (
            'name' => 'templateId',
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
            'startLine' => 46,
            'endLine' => 46,
            'startColumn' => 40,
            'endColumn' => 57,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'params' => 
          array (
            'name' => 'params',
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
            'startLine' => 46,
            'endLine' => 46,
            'startColumn' => 60,
            'endColumn' => 72,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'trackingId' => 
          array (
            'name' => 'trackingId',
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
            'startLine' => 46,
            'endLine' => 46,
            'startColumn' => 75,
            'endColumn' => 92,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Modules\\Notification\\Contracts\\Data\\SendResult',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param  array<string, scalar|null>  $params
 */',
        'startLine' => 46,
        'endLine' => 58,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Plugin\\ZaloZns\\Infrastructure',
        'declaringClassName' => 'Plugin\\ZaloZns\\Infrastructure\\ZnsClient',
        'implementingClassName' => 'Plugin\\ZaloZns\\Infrastructure\\ZnsClient',
        'currentClassName' => 'Plugin\\ZaloZns\\Infrastructure\\ZnsClient',
        'aliasName' => NULL,
      ),
      'attempt' => 
      array (
        'name' => 'attempt',
        'parameters' => 
        array (
          'e164' => 
          array (
            'name' => 'e164',
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
            'startLine' => 63,
            'endLine' => 63,
            'startColumn' => 30,
            'endColumn' => 41,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'templateId' => 
          array (
            'name' => 'templateId',
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
            'startLine' => 63,
            'endLine' => 63,
            'startColumn' => 44,
            'endColumn' => 61,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'params' => 
          array (
            'name' => 'params',
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
            'startLine' => 63,
            'endLine' => 63,
            'startColumn' => 64,
            'endColumn' => 76,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'trackingId' => 
          array (
            'name' => 'trackingId',
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
            'startLine' => 63,
            'endLine' => 63,
            'startColumn' => 79,
            'endColumn' => 96,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'forceRefresh' => 
          array (
            'name' => 'forceRefresh',
            'default' => NULL,
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
            'startLine' => 63,
            'endLine' => 63,
            'startColumn' => 99,
            'endColumn' => 116,
            'parameterIndex' => 4,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
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
                  'name' => 'Modules\\Notification\\Contracts\\Data\\SendResult',
                  'isIdentifier' => false,
                ),
              ),
              1 => 
              array (
                'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                'data' => 
                array (
                  'name' => 'string',
                  'isIdentifier' => true,
                ),
              ),
            ),
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param  array<string, scalar|null>  $params
 */',
        'startLine' => 63,
        'endLine' => 102,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'Plugin\\ZaloZns\\Infrastructure',
        'declaringClassName' => 'Plugin\\ZaloZns\\Infrastructure\\ZnsClient',
        'implementingClassName' => 'Plugin\\ZaloZns\\Infrastructure\\ZnsClient',
        'currentClassName' => 'Plugin\\ZaloZns\\Infrastructure\\ZnsClient',
        'aliasName' => NULL,
      ),
      'accessToken' => 
      array (
        'name' => 'accessToken',
        'parameters' => 
        array (
          'forceRefresh' => 
          array (
            'name' => 'forceRefresh',
            'default' => NULL,
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
            'startLine' => 104,
            'endLine' => 104,
            'startColumn' => 34,
            'endColumn' => 51,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
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
                  'name' => 'string',
                  'isIdentifier' => true,
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
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 104,
        'endLine' => 141,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'Plugin\\ZaloZns\\Infrastructure',
        'declaringClassName' => 'Plugin\\ZaloZns\\Infrastructure\\ZnsClient',
        'implementingClassName' => 'Plugin\\ZaloZns\\Infrastructure\\ZnsClient',
        'currentClassName' => 'Plugin\\ZaloZns\\Infrastructure\\ZnsClient',
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