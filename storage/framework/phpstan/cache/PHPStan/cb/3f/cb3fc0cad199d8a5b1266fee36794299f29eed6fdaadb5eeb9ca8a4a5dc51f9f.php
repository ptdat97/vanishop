<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Integration/Contracts/Connector.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Integration\Contracts\Connector
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-528efe9a6afe08e16d379c34752d366a0863280daa8226967f1d3c0532900cfe',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Integration\\Contracts\\Connector',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Integration/Contracts/Connector.php',
      ),
    ),
    'namespace' => 'Modules\\Integration\\Contracts',
    'name' => 'Modules\\Integration\\Contracts\\Connector',
    'shortName' => 'Connector',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Extension point (mô hình B): VaniShop chủ động gửi message ra hệ thống ngoài (ERP, HĐĐT, sàn…).
 * Plugin đóng góp qua `contribute(Connector::TAG, …)`; chỉ có hiệu lực khi plugin bật.
 *
 * Mỗi event của feed được fan-out thành một message outbox cho mọi connector `supports()` loại đó;
 * worker gọi `send()` sau commit, có retry/backoff/dead letter. Connector phải gửi header
 * `Idempotency-Key: <messageId>` để phía nhận khử trùng lặp khi retry/replay.
 *
 * @see docs/11-integration/integration-platform.md §6
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 21,
    'endLine' => 37,
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
        'declaringClassName' => 'Modules\\Integration\\Contracts\\Connector',
        'implementingClassName' => 'Modules\\Integration\\Contracts\\Connector',
        'name' => 'TAG',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'vani.integration.connectors\'',
          'attributes' => 
          array (
            'startLine' => 23,
            'endLine' => 23,
            'startTokenPos' => 46,
            'startFilePos' => 878,
            'endTokenPos' => 46,
            'endFilePos' => 906,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 23,
        'endLine' => 23,
        'startColumn' => 5,
        'endColumn' => 53,
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
        'docComment' => '/** Mã hệ thống, cũng là `target` của message outbox và `system` của external_references. */',
        'startLine' => 26,
        'endLine' => 26,
        'startColumn' => 5,
        'endColumn' => 37,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Integration\\Contracts',
        'declaringClassName' => 'Modules\\Integration\\Contracts\\Connector',
        'implementingClassName' => 'Modules\\Integration\\Contracts\\Connector',
        'currentClassName' => 'Modules\\Integration\\Contracts\\Connector',
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
            'startLine' => 28,
            'endLine' => 28,
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
        'startLine' => 28,
        'endLine' => 28,
        'startColumn' => 5,
        'endColumn' => 56,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Integration\\Contracts',
        'declaringClassName' => 'Modules\\Integration\\Contracts\\Connector',
        'implementingClassName' => 'Modules\\Integration\\Contracts\\Connector',
        'currentClassName' => 'Modules\\Integration\\Contracts\\Connector',
        'aliasName' => NULL,
      ),
      'send' => 
      array (
        'name' => 'send',
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
                'name' => 'Modules\\Integration\\Contracts\\Data\\OutboxMessage',
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
            'startColumn' => 26,
            'endColumn' => 47,
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
        'docComment' => '/**
 * Không ném exception cho lỗi dự kiến: trả `DeliveryResult::retryable()` (timeout, 5xx, 429)
 * hoặc `permanent()` (4xx do dữ liệu, mapping thiếu). Exception bất ngờ được coi là retryable.
 */',
        'startLine' => 34,
        'endLine' => 34,
        'startColumn' => 5,
        'endColumn' => 65,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Integration\\Contracts',
        'declaringClassName' => 'Modules\\Integration\\Contracts\\Connector',
        'implementingClassName' => 'Modules\\Integration\\Contracts\\Connector',
        'currentClassName' => 'Modules\\Integration\\Contracts\\Connector',
        'aliasName' => NULL,
      ),
      'healthCheck' => 
      array (
        'name' => 'healthCheck',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Modules\\Integration\\Contracts\\Data\\HealthStatus',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 36,
        'endLine' => 36,
        'startColumn' => 5,
        'endColumn' => 48,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Integration\\Contracts',
        'declaringClassName' => 'Modules\\Integration\\Contracts\\Connector',
        'implementingClassName' => 'Modules\\Integration\\Contracts\\Connector',
        'currentClassName' => 'Modules\\Integration\\Contracts\\Connector',
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