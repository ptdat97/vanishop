<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Customer/Application/OtpService.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Customer\Application\OtpService
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-eacb91a5c0b2318463c8976cded859e08ae2f46514b11269eceb56ab317a603a',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Customer\\Application\\OtpService',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Customer/Application/OtpService.php',
      ),
    ),
    'namespace' => 'Modules\\Customer\\Application',
    'name' => 'Modules\\Customer\\Application\\OtpService',
    'shortName' => 'OtpService',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * OTP qua kênh `OtpSender`. Chống SMS pumping/brute-force: giới hạn yêu cầu theo SĐT và IP, tối đa 5 lần
 * nhập sai cho mỗi mã, mã mới vô hiệu mã cũ.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 22,
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
      'extensions' => 
      array (
        'declaringClassName' => 'Modules\\Customer\\Application\\OtpService',
        'implementingClassName' => 'Modules\\Customer\\Application\\OtpService',
        'name' => 'extensions',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Modules\\Extension\\Contracts\\Extensions',
            'isIdentifier' => false,
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
        'endColumn' => 47,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'customers' => 
      array (
        'declaringClassName' => 'Modules\\Customer\\Application\\OtpService',
        'implementingClassName' => 'Modules\\Customer\\Application\\OtpService',
        'name' => 'customers',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Modules\\Customer\\Application\\CustomerService',
            'isIdentifier' => false,
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
        'endColumn' => 51,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'secret' => 
      array (
        'declaringClassName' => 'Modules\\Customer\\Application\\OtpService',
        'implementingClassName' => 'Modules\\Customer\\Application\\OtpService',
        'name' => 'secret',
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
        'endColumn' => 39,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'perPhone' => 
      array (
        'declaringClassName' => 'Modules\\Customer\\Application\\OtpService',
        'implementingClassName' => 'Modules\\Customer\\Application\\OtpService',
        'name' => 'perPhone',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'default' => 
        array (
          'code' => '3',
          'attributes' => 
          array (
            'startLine' => 28,
            'endLine' => 28,
            'startTokenPos' => 119,
            'startFilePos' => 945,
            'endTokenPos' => 119,
            'endFilePos' => 945,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 28,
        'endLine' => 28,
        'startColumn' => 9,
        'endColumn' => 42,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'perIp' => 
      array (
        'declaringClassName' => 'Modules\\Customer\\Application\\OtpService',
        'implementingClassName' => 'Modules\\Customer\\Application\\OtpService',
        'name' => 'perIp',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'default' => 
        array (
          'code' => '10',
          'attributes' => 
          array (
            'startLine' => 29,
            'endLine' => 29,
            'startTokenPos' => 132,
            'startFilePos' => 986,
            'endTokenPos' => 132,
            'endFilePos' => 987,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 29,
        'endLine' => 29,
        'startColumn' => 9,
        'endColumn' => 40,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'windowSeconds' => 
      array (
        'declaringClassName' => 'Modules\\Customer\\Application\\OtpService',
        'implementingClassName' => 'Modules\\Customer\\Application\\OtpService',
        'name' => 'windowSeconds',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'default' => 
        array (
          'code' => '600',
          'attributes' => 
          array (
            'startLine' => 30,
            'endLine' => 30,
            'startTokenPos' => 145,
            'startFilePos' => 1036,
            'endTokenPos' => 145,
            'endFilePos' => 1038,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 30,
        'endLine' => 30,
        'startColumn' => 9,
        'endColumn' => 49,
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
          'extensions' => 
          array (
            'name' => 'extensions',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Extension\\Contracts\\Extensions',
                'isIdentifier' => false,
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
            'endColumn' => 47,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'customers' => 
          array (
            'name' => 'customers',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Customer\\Application\\CustomerService',
                'isIdentifier' => false,
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
            'endColumn' => 51,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'secret' => 
          array (
            'name' => 'secret',
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
            'endColumn' => 39,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'perPhone' => 
          array (
            'name' => 'perPhone',
            'default' => 
            array (
              'code' => '3',
              'attributes' => 
              array (
                'startLine' => 28,
                'endLine' => 28,
                'startTokenPos' => 119,
                'startFilePos' => 945,
                'endTokenPos' => 119,
                'endFilePos' => 945,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'int',
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
            'endColumn' => 42,
            'parameterIndex' => 3,
            'isOptional' => true,
          ),
          'perIp' => 
          array (
            'name' => 'perIp',
            'default' => 
            array (
              'code' => '10',
              'attributes' => 
              array (
                'startLine' => 29,
                'endLine' => 29,
                'startTokenPos' => 132,
                'startFilePos' => 986,
                'endTokenPos' => 132,
                'endFilePos' => 987,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'int',
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
            'endColumn' => 40,
            'parameterIndex' => 4,
            'isOptional' => true,
          ),
          'windowSeconds' => 
          array (
            'name' => 'windowSeconds',
            'default' => 
            array (
              'code' => '600',
              'attributes' => 
              array (
                'startLine' => 30,
                'endLine' => 30,
                'startTokenPos' => 145,
                'startFilePos' => 1036,
                'endTokenPos' => 145,
                'endFilePos' => 1038,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'int',
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
            'endColumn' => 49,
            'parameterIndex' => 5,
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
        'endLine' => 31,
        'startColumn' => 5,
        'endColumn' => 8,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Customer\\Application',
        'declaringClassName' => 'Modules\\Customer\\Application\\OtpService',
        'implementingClassName' => 'Modules\\Customer\\Application\\OtpService',
        'currentClassName' => 'Modules\\Customer\\Application\\OtpService',
        'aliasName' => NULL,
      ),
      'request' => 
      array (
        'name' => 'request',
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
            'startColumn' => 29,
            'endColumn' => 40,
            'parameterIndex' => 0,
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
            'startLine' => 36,
            'endLine' => 36,
            'startColumn' => 43,
            'endColumn' => 61,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'ip' => 
          array (
            'name' => 'ip',
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
            'startColumn' => 64,
            'endColumn' => 73,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return array{channel: string, expires_in: int}
 */',
        'startLine' => 36,
        'endLine' => 80,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Customer\\Application',
        'declaringClassName' => 'Modules\\Customer\\Application\\OtpService',
        'implementingClassName' => 'Modules\\Customer\\Application\\OtpService',
        'currentClassName' => 'Modules\\Customer\\Application\\OtpService',
        'aliasName' => NULL,
      ),
      'verify' => 
      array (
        'name' => 'verify',
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
            'startLine' => 87,
            'endLine' => 87,
            'startColumn' => 28,
            'endColumn' => 39,
            'parameterIndex' => 0,
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
            'startLine' => 87,
            'endLine' => 87,
            'startColumn' => 42,
            'endColumn' => 60,
            'parameterIndex' => 1,
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
            'startLine' => 87,
            'endLine' => 87,
            'startColumn' => 63,
            'endColumn' => 74,
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
        'docComment' => '/**
 * Dùng mã (một lần). Sai → tăng số lần thử; quá 5 lần mã bị huỷ.
 *
 * @throws CustomerRejected otp_invalid
 */',
        'startLine' => 87,
        'endLine' => 114,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Customer\\Application',
        'declaringClassName' => 'Modules\\Customer\\Application\\OtpService',
        'implementingClassName' => 'Modules\\Customer\\Application\\OtpService',
        'currentClassName' => 'Modules\\Customer\\Application\\OtpService',
        'aliasName' => NULL,
      ),
      'senders' => 
      array (
        'name' => 'senders',
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
            'startLine' => 119,
            'endLine' => 119,
            'startColumn' => 30,
            'endColumn' => 53,
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
        'docComment' => '/**
 * @return list<OtpSender> kênh dùng được, priority giảm dần
 */',
        'startLine' => 119,
        'endLine' => 128,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'Modules\\Customer\\Application',
        'declaringClassName' => 'Modules\\Customer\\Application\\OtpService',
        'implementingClassName' => 'Modules\\Customer\\Application\\OtpService',
        'currentClassName' => 'Modules\\Customer\\Application\\OtpService',
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