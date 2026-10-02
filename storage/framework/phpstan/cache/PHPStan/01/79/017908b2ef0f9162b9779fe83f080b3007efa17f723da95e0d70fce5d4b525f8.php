<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Integration/Testing/ConnectorContract.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Integration\Testing\ConnectorContract
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-6a127b967b49d637b3c813ccc391397e971e683a9da687bba6e37df04d8a2b44',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Integration\\Testing\\ConnectorContract',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Integration/Testing/ConnectorContract.php',
      ),
    ),
    'namespace' => 'Modules\\Integration\\Testing',
    'name' => 'Modules\\Integration\\Testing\\ConnectorContract',
    'shortName' => 'ConnectorContract',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Contract test cho Connector (mô hình B). Plugin cung cấp message mẫu nó hỗ trợ và `prepare` giả lập hệ thống
 * ngoài: thành công / lỗi tạm thời (5xx, timeout) / lỗi dữ liệu (4xx). Kiểm tra: system ổn định; supports đúng;
 * phân loại ok/retryable/permanent và KHÔNG ném exception cho lỗi dự kiến; healthCheck trả HealthStatus.
 * `$sentRequests` (tuỳ chọn) trả các request đã gửi để kiểm tra header `Idempotency-Key = messageId`.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 19,
    'endLine' => 56,
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
      'define' => 
      array (
        'name' => 'define',
        'parameters' => 
        array (
          'label' => 
          array (
            'name' => 'label',
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
            'startLine' => 27,
            'endLine' => 27,
            'startColumn' => 35,
            'endColumn' => 47,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'connector' => 
          array (
            'name' => 'connector',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Closure',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 27,
            'endLine' => 27,
            'startColumn' => 50,
            'endColumn' => 67,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Closure',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 27,
            'endLine' => 27,
            'startColumn' => 70,
            'endColumn' => 85,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'scenarios' => 
          array (
            'name' => 'scenarios',
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
            'startLine' => 27,
            'endLine' => 27,
            'startColumn' => 88,
            'endColumn' => 103,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'sentHeaders' => 
          array (
            'name' => 'sentHeaders',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 27,
                'endLine' => 27,
                'startTokenPos' => 87,
                'startFilePos' => 1314,
                'endTokenPos' => 87,
                'endFilePos' => 1317,
              ),
            ),
            'type' => 
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
                      'name' => 'Closure',
                      'isIdentifier' => false,
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
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 27,
            'endLine' => 27,
            'startColumn' => 106,
            'endColumn' => 133,
            'parameterIndex' => 4,
            'isOptional' => true,
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
 * @param  Closure(): Connector  $connector
 * @param  Closure(): OutboxMessage  $message
 * @param  array{ok: Closure(): void, retryable: Closure(): void, permanent: Closure(): void}  $scenarios
 * @param  (Closure(): list<array<string, list<string>>>)|null  $sentHeaders  header của các request đã gửi
 */',
        'startLine' => 27,
        'endLine' => 55,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Modules\\Integration\\Testing',
        'declaringClassName' => 'Modules\\Integration\\Testing\\ConnectorContract',
        'implementingClassName' => 'Modules\\Integration\\Testing\\ConnectorContract',
        'currentClassName' => 'Modules\\Integration\\Testing\\ConnectorContract',
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