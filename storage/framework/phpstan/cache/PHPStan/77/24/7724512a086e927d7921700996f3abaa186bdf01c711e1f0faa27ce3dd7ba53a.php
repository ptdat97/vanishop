<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/custom/plugin/SmsBrandname/Infrastructure/EsmsClient.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Plugin\SmsBrandname\Infrastructure\EsmsClient
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-a8689b856bfbc50882426d4cabbe32f628a36029f4027ee81d615318f0b1c455',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Plugin\\SmsBrandname\\Infrastructure\\EsmsClient',
        'filename' => '/Users/dat/Ecommerce/vanishop/custom/plugin/SmsBrandname/Infrastructure/EsmsClient.php',
      ),
    ),
    'namespace' => 'Plugin\\SmsBrandname\\Infrastructure',
    'name' => 'Plugin\\SmsBrandname\\Infrastructure\\EsmsClient',
    'shortName' => 'EsmsClient',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Gửi SMS brandname qua eSMS (SmsType 2 = chăm sóc khách hàng/giao dịch). Nội dung bỏ dấu (tin không dấu,
 * 160 ký tự/tin). `RequestId` = khoá idempotency để eSMS chặn gửi trùng khi retry.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 17,
    'endLine' => 71,
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
      'OK' => 
      array (
        'declaringClassName' => 'Plugin\\SmsBrandname\\Infrastructure\\EsmsClient',
        'implementingClassName' => 'Plugin\\SmsBrandname\\Infrastructure\\EsmsClient',
        'name' => 'OK',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'100\'',
          'attributes' => 
          array (
            'startLine' => 19,
            'endLine' => 19,
            'startTokenPos' => 58,
            'startFilePos' => 585,
            'endTokenPos' => 58,
            'endFilePos' => 589,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 19,
        'endLine' => 19,
        'startColumn' => 5,
        'endColumn' => 28,
      ),
      'RETRYABLE' => 
      array (
        'declaringClassName' => 'Plugin\\SmsBrandname\\Infrastructure\\EsmsClient',
        'implementingClassName' => 'Plugin\\SmsBrandname\\Infrastructure\\EsmsClient',
        'name' => 'RETRYABLE',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'103\', \'99\']',
          'attributes' => 
          array (
            'startLine' => 22,
            'endLine' => 22,
            'startTokenPos' => 71,
            'startFilePos' => 711,
            'endTokenPos' => 76,
            'endFilePos' => 723,
          ),
        ),
        'docComment' => '/** Mã lỗi tạm thời: hết tiền trong tài khoản, hệ thống bận. */',
        'attributes' => 
        array (
        ),
        'startLine' => 22,
        'endLine' => 22,
        'startColumn' => 5,
        'endColumn' => 44,
      ),
    ),
    'immediateProperties' => 
    array (
      'apiBase' => 
      array (
        'declaringClassName' => 'Plugin\\SmsBrandname\\Infrastructure\\EsmsClient',
        'implementingClassName' => 'Plugin\\SmsBrandname\\Infrastructure\\EsmsClient',
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
        'startLine' => 25,
        'endLine' => 25,
        'startColumn' => 9,
        'endColumn' => 40,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'apiKey' => 
      array (
        'declaringClassName' => 'Plugin\\SmsBrandname\\Infrastructure\\EsmsClient',
        'implementingClassName' => 'Plugin\\SmsBrandname\\Infrastructure\\EsmsClient',
        'name' => 'apiKey',
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
        'startLine' => 26,
        'endLine' => 26,
        'startColumn' => 9,
        'endColumn' => 39,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'secretKey' => 
      array (
        'declaringClassName' => 'Plugin\\SmsBrandname\\Infrastructure\\EsmsClient',
        'implementingClassName' => 'Plugin\\SmsBrandname\\Infrastructure\\EsmsClient',
        'name' => 'secretKey',
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
        'startLine' => 27,
        'endLine' => 27,
        'startColumn' => 9,
        'endColumn' => 42,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'sandbox' => 
      array (
        'declaringClassName' => 'Plugin\\SmsBrandname\\Infrastructure\\EsmsClient',
        'implementingClassName' => 'Plugin\\SmsBrandname\\Infrastructure\\EsmsClient',
        'name' => 'sandbox',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'default' => 
        array (
          'code' => 'false',
          'attributes' => 
          array (
            'startLine' => 28,
            'endLine' => 28,
            'startTokenPos' => 123,
            'startFilePos' => 928,
            'endTokenPos' => 123,
            'endFilePos' => 932,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 28,
        'endLine' => 28,
        'startColumn' => 9,
        'endColumn' => 46,
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
            'startLine' => 25,
            'endLine' => 25,
            'startColumn' => 9,
            'endColumn' => 40,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'apiKey' => 
          array (
            'name' => 'apiKey',
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
            'startLine' => 26,
            'endLine' => 26,
            'startColumn' => 9,
            'endColumn' => 39,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'secretKey' => 
          array (
            'name' => 'secretKey',
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
            'startLine' => 27,
            'endLine' => 27,
            'startColumn' => 9,
            'endColumn' => 42,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'sandbox' => 
          array (
            'name' => 'sandbox',
            'default' => 
            array (
              'code' => 'false',
              'attributes' => 
              array (
                'startLine' => 28,
                'endLine' => 28,
                'startTokenPos' => 123,
                'startFilePos' => 928,
                'endTokenPos' => 123,
                'endFilePos' => 932,
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 28,
            'endLine' => 28,
            'startColumn' => 9,
            'endColumn' => 46,
            'parameterIndex' => 3,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 24,
        'endLine' => 29,
        'startColumn' => 5,
        'endColumn' => 8,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Plugin\\SmsBrandname\\Infrastructure',
        'declaringClassName' => 'Plugin\\SmsBrandname\\Infrastructure\\EsmsClient',
        'implementingClassName' => 'Plugin\\SmsBrandname\\Infrastructure\\EsmsClient',
        'currentClassName' => 'Plugin\\SmsBrandname\\Infrastructure\\EsmsClient',
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
        'startLine' => 31,
        'endLine' => 34,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Plugin\\SmsBrandname\\Infrastructure',
        'declaringClassName' => 'Plugin\\SmsBrandname\\Infrastructure\\EsmsClient',
        'implementingClassName' => 'Plugin\\SmsBrandname\\Infrastructure\\EsmsClient',
        'currentClassName' => 'Plugin\\SmsBrandname\\Infrastructure\\EsmsClient',
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
            'startLine' => 36,
            'endLine' => 36,
            'startColumn' => 26,
            'endColumn' => 37,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'content' => 
          array (
            'name' => 'content',
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
            'startLine' => 36,
            'endLine' => 36,
            'startColumn' => 40,
            'endColumn' => 54,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'brandname' => 
          array (
            'name' => 'brandname',
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
            'startLine' => 36,
            'endLine' => 36,
            'startColumn' => 57,
            'endColumn' => 73,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'requestId' => 
          array (
            'name' => 'requestId',
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
            'startLine' => 36,
            'endLine' => 36,
            'startColumn' => 76,
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
        'docComment' => NULL,
        'startLine' => 36,
        'endLine' => 70,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Plugin\\SmsBrandname\\Infrastructure',
        'declaringClassName' => 'Plugin\\SmsBrandname\\Infrastructure\\EsmsClient',
        'implementingClassName' => 'Plugin\\SmsBrandname\\Infrastructure\\EsmsClient',
        'currentClassName' => 'Plugin\\SmsBrandname\\Infrastructure\\EsmsClient',
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