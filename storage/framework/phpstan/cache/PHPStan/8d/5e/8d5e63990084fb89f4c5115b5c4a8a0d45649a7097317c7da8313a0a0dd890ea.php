<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/custom/plugin/BankTransfer/Infrastructure/ManualBankTransferGateway.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Plugin\BankTransfer\Infrastructure\ManualBankTransferGateway
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-ea18c6d9e475300c44374115fd51d8d6d7b922687dfab226898cd8486d27f21b',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Plugin\\BankTransfer\\Infrastructure\\ManualBankTransferGateway',
        'filename' => '/Users/dat/Ecommerce/vanishop/custom/plugin/BankTransfer/Infrastructure/ManualBankTransferGateway.php',
      ),
    ),
    'namespace' => 'Plugin\\BankTransfer\\Infrastructure',
    'name' => 'Plugin\\BankTransfer\\Infrastructure\\ManualBankTransferGateway',
    'shortName' => 'ManualBankTransferGateway',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Chuyển khoản thủ công: hiển thị tài khoản của cửa hàng + nội dung = số đơn; nhân viên xác nhận đã nhận tiền
 * (Core: `manualConfirmation`). Tự xác nhận theo sao kê là plugin `vani.vietqr`.
 * Mã `manual_bank_transfer` giữ nguyên từ khi còn nằm trong Core — đơn cũ không đổi.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 26,
    'endLine' => 93,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'Modules\\Payment\\Contracts\\PaymentGateway',
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
      'FIELDS' => 
      array (
        'declaringClassName' => 'Plugin\\BankTransfer\\Infrastructure\\ManualBankTransferGateway',
        'implementingClassName' => 'Plugin\\BankTransfer\\Infrastructure\\ManualBankTransferGateway',
        'name' => 'FIELDS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'bank\', \'account_number\', \'account_name\']',
          'attributes' => 
          array (
            'startLine' => 28,
            'endLine' => 28,
            'startTokenPos' => 102,
            'startFilePos' => 1137,
            'endTokenPos' => 110,
            'endFilePos' => 1178,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 28,
        'endLine' => 28,
        'startColumn' => 5,
        'endColumn' => 70,
      ),
    ),
    'immediateProperties' => 
    array (
      'settings' => 
      array (
        'declaringClassName' => 'Plugin\\BankTransfer\\Infrastructure\\ManualBankTransferGateway',
        'implementingClassName' => 'Plugin\\BankTransfer\\Infrastructure\\ManualBankTransferGateway',
        'name' => 'settings',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Modules\\Tenancy\\Contracts\\Settings',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 30,
        'endLine' => 30,
        'startColumn' => 33,
        'endColumn' => 67,
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
          'settings' => 
          array (
            'name' => 'settings',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Tenancy\\Contracts\\Settings',
                'isIdentifier' => false,
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
            'startColumn' => 33,
            'endColumn' => 67,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 30,
        'endLine' => 30,
        'startColumn' => 5,
        'endColumn' => 71,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Plugin\\BankTransfer\\Infrastructure',
        'declaringClassName' => 'Plugin\\BankTransfer\\Infrastructure\\ManualBankTransferGateway',
        'implementingClassName' => 'Plugin\\BankTransfer\\Infrastructure\\ManualBankTransferGateway',
        'currentClassName' => 'Plugin\\BankTransfer\\Infrastructure\\ManualBankTransferGateway',
        'aliasName' => NULL,
      ),
      'code' => 
      array (
        'name' => 'code',
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
        'startLine' => 32,
        'endLine' => 35,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Plugin\\BankTransfer\\Infrastructure',
        'declaringClassName' => 'Plugin\\BankTransfer\\Infrastructure\\ManualBankTransferGateway',
        'implementingClassName' => 'Plugin\\BankTransfer\\Infrastructure\\ManualBankTransferGateway',
        'currentClassName' => 'Plugin\\BankTransfer\\Infrastructure\\ManualBankTransferGateway',
        'aliasName' => NULL,
      ),
      'label' => 
      array (
        'name' => 'label',
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
        'startLine' => 37,
        'endLine' => 40,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Plugin\\BankTransfer\\Infrastructure',
        'declaringClassName' => 'Plugin\\BankTransfer\\Infrastructure\\ManualBankTransferGateway',
        'implementingClassName' => 'Plugin\\BankTransfer\\Infrastructure\\ManualBankTransferGateway',
        'currentClassName' => 'Plugin\\BankTransfer\\Infrastructure\\ManualBankTransferGateway',
        'aliasName' => NULL,
      ),
      'capabilities' => 
      array (
        'name' => 'capabilities',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Modules\\Payment\\Contracts\\Data\\GatewayCapabilities',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 42,
        'endLine' => 45,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Plugin\\BankTransfer\\Infrastructure',
        'declaringClassName' => 'Plugin\\BankTransfer\\Infrastructure\\ManualBankTransferGateway',
        'implementingClassName' => 'Plugin\\BankTransfer\\Infrastructure\\ManualBankTransferGateway',
        'currentClassName' => 'Plugin\\BankTransfer\\Infrastructure\\ManualBankTransferGateway',
        'aliasName' => NULL,
      ),
      'isAvailable' => 
      array (
        'name' => 'isAvailable',
        'parameters' => 
        array (
          'context' => 
          array (
            'name' => 'context',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Payment\\Contracts\\Data\\PaymentContext',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 47,
            'endLine' => 47,
            'startColumn' => 33,
            'endColumn' => 55,
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
        'startLine' => 47,
        'endLine' => 50,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Plugin\\BankTransfer\\Infrastructure',
        'declaringClassName' => 'Plugin\\BankTransfer\\Infrastructure\\ManualBankTransferGateway',
        'implementingClassName' => 'Plugin\\BankTransfer\\Infrastructure\\ManualBankTransferGateway',
        'currentClassName' => 'Plugin\\BankTransfer\\Infrastructure\\ManualBankTransferGateway',
        'aliasName' => NULL,
      ),
      'initiate' => 
      array (
        'name' => 'initiate',
        'parameters' => 
        array (
          'payment' => 
          array (
            'name' => 'payment',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Payment\\Contracts\\Data\\PaymentData',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 52,
            'endLine' => 52,
            'startColumn' => 30,
            'endColumn' => 49,
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
            'name' => 'Modules\\Payment\\Contracts\\Data\\PaymentInitiation',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 52,
        'endLine' => 61,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Plugin\\BankTransfer\\Infrastructure',
        'declaringClassName' => 'Plugin\\BankTransfer\\Infrastructure\\ManualBankTransferGateway',
        'implementingClassName' => 'Plugin\\BankTransfer\\Infrastructure\\ManualBankTransferGateway',
        'currentClassName' => 'Plugin\\BankTransfer\\Infrastructure\\ManualBankTransferGateway',
        'aliasName' => NULL,
      ),
      'verifyCallback' => 
      array (
        'name' => 'verifyCallback',
        'parameters' => 
        array (
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
            'startLine' => 63,
            'endLine' => 63,
            'startColumn' => 36,
            'endColumn' => 51,
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
            'name' => 'Modules\\Payment\\Contracts\\Data\\GatewayCallback',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 63,
        'endLine' => 66,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Plugin\\BankTransfer\\Infrastructure',
        'declaringClassName' => 'Plugin\\BankTransfer\\Infrastructure\\ManualBankTransferGateway',
        'implementingClassName' => 'Plugin\\BankTransfer\\Infrastructure\\ManualBankTransferGateway',
        'currentClassName' => 'Plugin\\BankTransfer\\Infrastructure\\ManualBankTransferGateway',
        'aliasName' => NULL,
      ),
      'query' => 
      array (
        'name' => 'query',
        'parameters' => 
        array (
          'payment' => 
          array (
            'name' => 'payment',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Payment\\Contracts\\Data\\PaymentData',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 68,
            'endLine' => 68,
            'startColumn' => 27,
            'endColumn' => 46,
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
            'name' => 'Modules\\Payment\\Contracts\\Data\\GatewayStatus',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 68,
        'endLine' => 71,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Plugin\\BankTransfer\\Infrastructure',
        'declaringClassName' => 'Plugin\\BankTransfer\\Infrastructure\\ManualBankTransferGateway',
        'implementingClassName' => 'Plugin\\BankTransfer\\Infrastructure\\ManualBankTransferGateway',
        'currentClassName' => 'Plugin\\BankTransfer\\Infrastructure\\ManualBankTransferGateway',
        'aliasName' => NULL,
      ),
      'refund' => 
      array (
        'name' => 'refund',
        'parameters' => 
        array (
          'payment' => 
          array (
            'name' => 'payment',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Payment\\Contracts\\Data\\PaymentData',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 73,
            'endLine' => 73,
            'startColumn' => 28,
            'endColumn' => 47,
            'parameterIndex' => 0,
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
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 73,
            'endLine' => 73,
            'startColumn' => 50,
            'endColumn' => 62,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'idempotencyKey' => 
          array (
            'name' => 'idempotencyKey',
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
            'startLine' => 73,
            'endLine' => 73,
            'startColumn' => 65,
            'endColumn' => 86,
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
            'name' => 'Modules\\Payment\\Contracts\\Data\\GatewayResult',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 73,
        'endLine' => 76,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Plugin\\BankTransfer\\Infrastructure',
        'declaringClassName' => 'Plugin\\BankTransfer\\Infrastructure\\ManualBankTransferGateway',
        'implementingClassName' => 'Plugin\\BankTransfer\\Infrastructure\\ManualBankTransferGateway',
        'currentClassName' => 'Plugin\\BankTransfer\\Infrastructure\\ManualBankTransferGateway',
        'aliasName' => NULL,
      ),
      'account' => 
      array (
        'name' => 'account',
        'parameters' => 
        array (
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
                  'name' => 'array',
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
        'docComment' => '/**
 * Tài khoản nhận tiền: cấu hình trong Admin, thiếu thì theo .env.
 *
 * @return array{bank: string, account_number: string, account_name: string}|null
 */',
        'startLine' => 83,
        'endLine' => 92,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'Plugin\\BankTransfer\\Infrastructure',
        'declaringClassName' => 'Plugin\\BankTransfer\\Infrastructure\\ManualBankTransferGateway',
        'implementingClassName' => 'Plugin\\BankTransfer\\Infrastructure\\ManualBankTransferGateway',
        'currentClassName' => 'Plugin\\BankTransfer\\Infrastructure\\ManualBankTransferGateway',
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