<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/custom/plugin/ZaloZns/Infrastructure/ZnsOtpSender.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Plugin\ZaloZns\Infrastructure\ZnsOtpSender
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-c04670e0003b2cde8d904882a88a26a9e65f9729af1aea8c5e3f3cd26f44fd3d',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Plugin\\ZaloZns\\Infrastructure\\ZnsOtpSender',
        'filename' => '/Users/dat/Ecommerce/vanishop/custom/plugin/ZaloZns/Infrastructure/ZnsOtpSender.php',
      ),
    ),
    'namespace' => 'Plugin\\ZaloZns\\Infrastructure',
    'name' => 'Plugin\\ZaloZns\\Infrastructure\\ZnsOtpSender',
    'shortName' => 'ZnsOtpSender',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * OTP qua ZNS (rẻ hơn SMS nên priority cao hơn). SĐT không dùng Zalo → OtpDeliveryFailed, Core chuyển sang SMS.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 16,
    'endLine' => 46,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'Modules\\Customer\\Contracts\\OtpSender',
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
      'client' => 
      array (
        'declaringClassName' => 'Plugin\\ZaloZns\\Infrastructure\\ZnsOtpSender',
        'implementingClassName' => 'Plugin\\ZaloZns\\Infrastructure\\ZnsOtpSender',
        'name' => 'client',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Plugin\\ZaloZns\\Infrastructure\\ZnsClient',
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
        'startColumn' => 9,
        'endColumn' => 42,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'templateId' => 
      array (
        'declaringClassName' => 'Plugin\\ZaloZns\\Infrastructure\\ZnsOtpSender',
        'implementingClassName' => 'Plugin\\ZaloZns\\Infrastructure\\ZnsOtpSender',
        'name' => 'templateId',
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
        'startLine' => 20,
        'endLine' => 20,
        'startColumn' => 9,
        'endColumn' => 43,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'param' => 
      array (
        'declaringClassName' => 'Plugin\\ZaloZns\\Infrastructure\\ZnsOtpSender',
        'implementingClassName' => 'Plugin\\ZaloZns\\Infrastructure\\ZnsOtpSender',
        'name' => 'param',
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
          'code' => '\'otp\'',
          'attributes' => 
          array (
            'startLine' => 21,
            'endLine' => 21,
            'startTokenPos' => 89,
            'startFilePos' => 641,
            'endTokenPos' => 89,
            'endFilePos' => 645,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 21,
        'endLine' => 21,
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
          'client' => 
          array (
            'name' => 'client',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Plugin\\ZaloZns\\Infrastructure\\ZnsClient',
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
            'startColumn' => 9,
            'endColumn' => 42,
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 20,
            'endLine' => 20,
            'startColumn' => 9,
            'endColumn' => 43,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'param' => 
          array (
            'name' => 'param',
            'default' => 
            array (
              'code' => '\'otp\'',
              'attributes' => 
              array (
                'startLine' => 21,
                'endLine' => 21,
                'startTokenPos' => 89,
                'startFilePos' => 641,
                'endTokenPos' => 89,
                'endFilePos' => 645,
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
            'startLine' => 21,
            'endLine' => 21,
            'startColumn' => 9,
            'endColumn' => 46,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 18,
        'endLine' => 22,
        'startColumn' => 5,
        'endColumn' => 8,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Plugin\\ZaloZns\\Infrastructure',
        'declaringClassName' => 'Plugin\\ZaloZns\\Infrastructure\\ZnsOtpSender',
        'implementingClassName' => 'Plugin\\ZaloZns\\Infrastructure\\ZnsOtpSender',
        'currentClassName' => 'Plugin\\ZaloZns\\Infrastructure\\ZnsOtpSender',
        'aliasName' => NULL,
      ),
      'channel' => 
      array (
        'name' => 'channel',
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
        'startLine' => 24,
        'endLine' => 27,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Plugin\\ZaloZns\\Infrastructure',
        'declaringClassName' => 'Plugin\\ZaloZns\\Infrastructure\\ZnsOtpSender',
        'implementingClassName' => 'Plugin\\ZaloZns\\Infrastructure\\ZnsOtpSender',
        'currentClassName' => 'Plugin\\ZaloZns\\Infrastructure\\ZnsOtpSender',
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
        'startLine' => 29,
        'endLine' => 32,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Plugin\\ZaloZns\\Infrastructure',
        'declaringClassName' => 'Plugin\\ZaloZns\\Infrastructure\\ZnsOtpSender',
        'implementingClassName' => 'Plugin\\ZaloZns\\Infrastructure\\ZnsOtpSender',
        'currentClassName' => 'Plugin\\ZaloZns\\Infrastructure\\ZnsOtpSender',
        'aliasName' => NULL,
      ),
      'isAvailable' => 
      array (
        'name' => 'isAvailable',
        'parameters' => 
        array (
          'contact' => 
          array (
            'name' => 'contact',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Customer\\Contracts\\Data\\CustomerContact',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 34,
            'endLine' => 34,
            'startColumn' => 33,
            'endColumn' => 56,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 34,
        'endLine' => 37,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Plugin\\ZaloZns\\Infrastructure',
        'declaringClassName' => 'Plugin\\ZaloZns\\Infrastructure\\ZnsOtpSender',
        'implementingClassName' => 'Plugin\\ZaloZns\\Infrastructure\\ZnsOtpSender',
        'currentClassName' => 'Plugin\\ZaloZns\\Infrastructure\\ZnsOtpSender',
        'aliasName' => NULL,
      ),
      'send' => 
      array (
        'name' => 'send',
        'parameters' => 
        array (
          'contact' => 
          array (
            'name' => 'contact',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Customer\\Contracts\\Data\\CustomerContact',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 39,
            'endLine' => 39,
            'startColumn' => 26,
            'endColumn' => 49,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'code' => 
          array (
            'name' => 'code',
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
            'startLine' => 39,
            'endLine' => 39,
            'startColumn' => 52,
            'endColumn' => 63,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'purpose' => 
          array (
            'name' => 'purpose',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Customer\\Contracts\\Data\\OtpPurpose',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 39,
            'endLine' => 39,
            'startColumn' => 66,
            'endColumn' => 84,
            'parameterIndex' => 2,
            'isOptional' => false,
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
        'docComment' => NULL,
        'startLine' => 39,
        'endLine' => 45,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Plugin\\ZaloZns\\Infrastructure',
        'declaringClassName' => 'Plugin\\ZaloZns\\Infrastructure\\ZnsOtpSender',
        'implementingClassName' => 'Plugin\\ZaloZns\\Infrastructure\\ZnsOtpSender',
        'currentClassName' => 'Plugin\\ZaloZns\\Infrastructure\\ZnsOtpSender',
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