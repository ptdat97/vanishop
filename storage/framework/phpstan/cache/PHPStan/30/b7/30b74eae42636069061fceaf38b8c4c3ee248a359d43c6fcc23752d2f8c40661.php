<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Customer/Domain/OtpCode.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Customer\Domain\OtpCode
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-cbc500ef5442092621594d3dd5725c1bae4d07dd9b24d64bfcca345e9c8a09c1',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Customer\\Domain\\OtpCode',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Customer/Domain/OtpCode.php',
      ),
    ),
    'namespace' => 'Modules\\Customer\\Domain',
    'name' => 'Modules\\Customer\\Domain\\OtpCode',
    'shortName' => 'OtpCode',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * OTP 6 số, TTL 5 phút, tối đa 5 lần thử (docs/03-domains/customer.md §4).
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 10,
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
      'LENGTH' => 
      array (
        'declaringClassName' => 'Modules\\Customer\\Domain\\OtpCode',
        'implementingClassName' => 'Modules\\Customer\\Domain\\OtpCode',
        'name' => 'LENGTH',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '6',
          'attributes' => 
          array (
            'startLine' => 12,
            'endLine' => 12,
            'startTokenPos' => 33,
            'startFilePos' => 212,
            'endTokenPos' => 33,
            'endFilePos' => 212,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 12,
        'endLine' => 12,
        'startColumn' => 5,
        'endColumn' => 28,
      ),
      'TTL_SECONDS' => 
      array (
        'declaringClassName' => 'Modules\\Customer\\Domain\\OtpCode',
        'implementingClassName' => 'Modules\\Customer\\Domain\\OtpCode',
        'name' => 'TTL_SECONDS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '300',
          'attributes' => 
          array (
            'startLine' => 14,
            'endLine' => 14,
            'startTokenPos' => 44,
            'startFilePos' => 247,
            'endTokenPos' => 44,
            'endFilePos' => 249,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 14,
        'endLine' => 14,
        'startColumn' => 5,
        'endColumn' => 35,
      ),
      'MAX_ATTEMPTS' => 
      array (
        'declaringClassName' => 'Modules\\Customer\\Domain\\OtpCode',
        'implementingClassName' => 'Modules\\Customer\\Domain\\OtpCode',
        'name' => 'MAX_ATTEMPTS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '5',
          'attributes' => 
          array (
            'startLine' => 16,
            'endLine' => 16,
            'startTokenPos' => 55,
            'startFilePos' => 285,
            'endTokenPos' => 55,
            'endFilePos' => 285,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 16,
        'endLine' => 16,
        'startColumn' => 5,
        'endColumn' => 34,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'generate' => 
      array (
        'name' => 'generate',
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
        'startLine' => 18,
        'endLine' => 21,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Modules\\Customer\\Domain',
        'declaringClassName' => 'Modules\\Customer\\Domain\\OtpCode',
        'implementingClassName' => 'Modules\\Customer\\Domain\\OtpCode',
        'currentClassName' => 'Modules\\Customer\\Domain\\OtpCode',
        'aliasName' => NULL,
      ),
      'hash' => 
      array (
        'name' => 'hash',
        'parameters' => 
        array (
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
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 26,
            'endLine' => 26,
            'startColumn' => 33,
            'endColumn' => 46,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'phone' => 
          array (
            'name' => 'phone',
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
            'startColumn' => 49,
            'endColumn' => 61,
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
            'startLine' => 26,
            'endLine' => 26,
            'startColumn' => 64,
            'endColumn' => 75,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * HMAC với khoá bí mật của ứng dụng: lộ bảng OTP không đủ để dò mã 6 số.
 */',
        'startLine' => 26,
        'endLine' => 29,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Modules\\Customer\\Domain',
        'declaringClassName' => 'Modules\\Customer\\Domain\\OtpCode',
        'implementingClassName' => 'Modules\\Customer\\Domain\\OtpCode',
        'currentClassName' => 'Modules\\Customer\\Domain\\OtpCode',
        'aliasName' => NULL,
      ),
      'isWellFormed' => 
      array (
        'name' => 'isWellFormed',
        'parameters' => 
        array (
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
            'startLine' => 31,
            'endLine' => 31,
            'startColumn' => 41,
            'endColumn' => 52,
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
        'startLine' => 31,
        'endLine' => 34,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Modules\\Customer\\Domain',
        'declaringClassName' => 'Modules\\Customer\\Domain\\OtpCode',
        'implementingClassName' => 'Modules\\Customer\\Domain\\OtpCode',
        'currentClassName' => 'Modules\\Customer\\Domain\\OtpCode',
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