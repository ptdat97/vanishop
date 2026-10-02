<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Integration/Contracts/InboundHandler.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Integration\Contracts\InboundHandler
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-2a608b347c70002994db089d2ed19681562be03079fa00b66123be1d497b46c0',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Integration\\Contracts\\InboundHandler',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Integration/Contracts/InboundHandler.php',
      ),
    ),
    'namespace' => 'Modules\\Integration\\Contracts',
    'name' => 'Modules\\Integration\\Contracts\\InboundHandler',
    'shortName' => 'InboundHandler',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Extension point: xử lý message vào đã lưu inbox (webhook của SaaS, event của ERP…), bất đồng bộ.
 * Handler dịch message sang lời gọi Service contract của context sở hữu — không ghi bảng của module khác.
 * Phải idempotent (R18): cùng message có thể được xử lý lại khi retry/replay.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 15,
    'endLine' => 24,
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
        'declaringClassName' => 'Modules\\Integration\\Contracts\\InboundHandler',
        'implementingClassName' => 'Modules\\Integration\\Contracts\\InboundHandler',
        'name' => 'TAG',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'vani.integration.inbound\'',
          'attributes' => 
          array (
            'startLine' => 17,
            'endLine' => 17,
            'startTokenPos' => 41,
            'startFilePos' => 583,
            'endTokenPos' => 41,
            'endFilePos' => 608,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 17,
        'endLine' => 17,
        'startColumn' => 5,
        'endColumn' => 50,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'system' => 
      array (
        'name' => 'system',
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
        'endColumn' => 37,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Integration\\Contracts',
        'declaringClassName' => 'Modules\\Integration\\Contracts\\InboundHandler',
        'implementingClassName' => 'Modules\\Integration\\Contracts\\InboundHandler',
        'currentClassName' => 'Modules\\Integration\\Contracts\\InboundHandler',
        'aliasName' => NULL,
      ),
      'supports' => 
      array (
        'name' => 'supports',
        'parameters' => 
        array (
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
            'startLine' => 21,
            'endLine' => 21,
            'startColumn' => 30,
            'endColumn' => 48,
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
        'startLine' => 21,
        'endLine' => 21,
        'startColumn' => 5,
        'endColumn' => 56,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Integration\\Contracts',
        'declaringClassName' => 'Modules\\Integration\\Contracts\\InboundHandler',
        'implementingClassName' => 'Modules\\Integration\\Contracts\\InboundHandler',
        'currentClassName' => 'Modules\\Integration\\Contracts\\InboundHandler',
        'aliasName' => NULL,
      ),
      'handle' => 
      array (
        'name' => 'handle',
        'parameters' => 
        array (
          'message' => 
          array (
            'name' => 'message',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Integration\\Contracts\\Data\\InboxMessage',
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
            'startColumn' => 28,
            'endColumn' => 48,
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
            'name' => 'Modules\\Integration\\Contracts\\Data\\DeliveryResult',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 23,
        'endLine' => 23,
        'startColumn' => 5,
        'endColumn' => 66,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Integration\\Contracts',
        'declaringClassName' => 'Modules\\Integration\\Contracts\\InboundHandler',
        'implementingClassName' => 'Modules\\Integration\\Contracts\\InboundHandler',
        'currentClassName' => 'Modules\\Integration\\Contracts\\InboundHandler',
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