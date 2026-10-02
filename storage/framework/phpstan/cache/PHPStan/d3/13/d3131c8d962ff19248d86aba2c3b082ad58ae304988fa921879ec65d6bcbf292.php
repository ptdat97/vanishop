<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Integration/Contracts/Inbox.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Integration\Contracts\Inbox
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-c97785c2318a33cb4342072f39f79c8d32b25d7922b8409936d28ee0e17bbb3b',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Integration\\Contracts\\Inbox',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Integration/Contracts/Inbox.php',
      ),
    ),
    'namespace' => 'Modules\\Integration\\Contracts',
    'name' => 'Modules\\Integration\\Contracts\\Inbox',
    'shortName' => 'Inbox',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Service contract: lưu message vào TRƯỚC khi xử lý (ADR-013). Endpoint webhook của plugin: xác thực chữ ký →
 * `receive()` → trả 2xx ngay; xử lý bất đồng bộ qua InboundHandler.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 11,
    'endLine' => 18,
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
      'receive' => 
      array (
        'name' => 'receive',
        'parameters' => 
        array (
          'system' => 
          array (
            'name' => 'system',
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
            'startLine' => 17,
            'endLine' => 17,
            'startColumn' => 29,
            'endColumn' => 42,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'externalEventId' => 
          array (
            'name' => 'externalEventId',
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
            'startLine' => 17,
            'endLine' => 17,
            'startColumn' => 45,
            'endColumn' => 67,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'messageType' => 
          array (
            'name' => 'messageType',
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
            'startLine' => 17,
            'endLine' => 17,
            'startColumn' => 70,
            'endColumn' => 88,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'payload' => 
          array (
            'name' => 'payload',
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
            'startLine' => 17,
            'endLine' => 17,
            'startColumn' => 91,
            'endColumn' => 104,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param  array<string, mixed>  $payload
 * @return bool false = trùng (system, external_event_id) đã nhận trước đó — vẫn trả 2xx cho bên gửi
 */',
        'startLine' => 17,
        'endLine' => 17,
        'startColumn' => 5,
        'endColumn' => 112,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Integration\\Contracts',
        'declaringClassName' => 'Modules\\Integration\\Contracts\\Inbox',
        'implementingClassName' => 'Modules\\Integration\\Contracts\\Inbox',
        'currentClassName' => 'Modules\\Integration\\Contracts\\Inbox',
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