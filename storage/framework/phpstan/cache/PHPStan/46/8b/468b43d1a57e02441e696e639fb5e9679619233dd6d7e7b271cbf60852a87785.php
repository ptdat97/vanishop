<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Customer/Contracts/OtpSender.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Customer\Contracts\OtpSender
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-042cddd34c2a0425270a76efc48f0fc3c19298a2b115850c997e19ee7af32966',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Customer\\Contracts\\OtpSender',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Customer/Contracts/OtpSender.php',
      ),
    ),
    'namespace' => 'Modules\\Customer\\Contracts',
    'name' => 'Modules\\Customer\\Contracts\\OtpSender',
    'shortName' => 'OtpSender',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Extension point: kênh gửi OTP. Core có `email` (khi khách có email) và `log` (chỉ dev); SMS brandname /
 * Zalo ZNS là plugin (`vani.sms-brandname`, `vani.zalo-zns`). Kênh `isAvailable()` được thử theo `priority()`
 * giảm dần; kênh ném `OtpDeliveryFailed` thì chuyển sang kênh kế tiếp.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 15,
    'endLine' => 30,
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
      'TAG' => 
      array (
        'declaringClassName' => 'Modules\\Customer\\Contracts\\OtpSender',
        'implementingClassName' => 'Modules\\Customer\\Contracts\\OtpSender',
        'name' => 'TAG',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'vani.customer.otp_senders\'',
          'attributes' => 
          array (
            'startLine' => 17,
            'endLine' => 17,
            'startTokenPos' => 41,
            'startFilePos' => 548,
            'endTokenPos' => 41,
            'endFilePos' => 574,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 17,
        'endLine' => 17,
        'startColumn' => 5,
        'endColumn' => 51,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
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
        'startLine' => 19,
        'endLine' => 19,
        'startColumn' => 5,
        'endColumn' => 38,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Customer\\Contracts',
        'declaringClassName' => 'Modules\\Customer\\Contracts\\OtpSender',
        'implementingClassName' => 'Modules\\Customer\\Contracts\\OtpSender',
        'currentClassName' => 'Modules\\Customer\\Contracts\\OtpSender',
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
        'docComment' => '/** Số lớn hơn được thử trước (SMS/ZNS nên cao hơn email). */',
        'startLine' => 22,
        'endLine' => 22,
        'startColumn' => 5,
        'endColumn' => 36,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Customer\\Contracts',
        'declaringClassName' => 'Modules\\Customer\\Contracts\\OtpSender',
        'implementingClassName' => 'Modules\\Customer\\Contracts\\OtpSender',
        'currentClassName' => 'Modules\\Customer\\Contracts\\OtpSender',
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
            'startLine' => 24,
            'endLine' => 24,
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
        'startLine' => 24,
        'endLine' => 24,
        'startColumn' => 5,
        'endColumn' => 64,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Customer\\Contracts',
        'declaringClassName' => 'Modules\\Customer\\Contracts\\OtpSender',
        'implementingClassName' => 'Modules\\Customer\\Contracts\\OtpSender',
        'currentClassName' => 'Modules\\Customer\\Contracts\\OtpSender',
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
            'startLine' => 29,
            'endLine' => 29,
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
            'startLine' => 29,
            'endLine' => 29,
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
            'startLine' => 29,
            'endLine' => 29,
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
        'docComment' => '/**
 * @throws OtpDeliveryFailed không gửi được — Core thử kênh có priority thấp hơn
 */',
        'startLine' => 29,
        'endLine' => 29,
        'startColumn' => 5,
        'endColumn' => 92,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Customer\\Contracts',
        'declaringClassName' => 'Modules\\Customer\\Contracts\\OtpSender',
        'implementingClassName' => 'Modules\\Customer\\Contracts\\OtpSender',
        'currentClassName' => 'Modules\\Customer\\Contracts\\OtpSender',
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