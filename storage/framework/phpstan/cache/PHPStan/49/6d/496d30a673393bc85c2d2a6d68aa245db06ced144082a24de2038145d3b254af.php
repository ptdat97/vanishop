<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Integration/Application/OutboxWorker.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Integration\Application\OutboxWorker
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-41153b00f10ea20829e922551be58a1bdce75a3c2c81475c04f7cded7458eda2',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Integration\\Application\\OutboxWorker',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Integration/Application/OutboxWorker.php',
      ),
    ),
    'namespace' => 'Modules\\Integration\\Application',
    'name' => 'Modules\\Integration\\Application\\OutboxWorker',
    'shortName' => 'OutboxWorker',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Gửi message outbox (ADR-013). Nhiều worker chạy song song an toàn:
 *
 * - Lấy message bằng `FOR UPDATE SKIP LOCKED` rồi đánh dấu `processing` trong cùng transaction.
 * - Thứ tự theo (target, aggregate): chỉ lấy message đầu hàng — message sau chờ khi message trước còn
 *   `pending`/`processing` (đang retry). Message `failed`/`dead` không chặn hàng đợi; replay khi đã sửa.
 * - `processing` quá hạn (worker chết giữa chừng) được trả về `pending` → at-least-once; phía nhận khử
 *   trùng lặp bằng `Idempotency-Key = message_id`.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 29,
    'endLine' => 153,
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
      'router' => 
      array (
        'declaringClassName' => 'Modules\\Integration\\Application\\OutboxWorker',
        'implementingClassName' => 'Modules\\Integration\\Application\\OutboxWorker',
        'name' => 'router',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Modules\\Integration\\Application\\Delivery\\MessageRouter',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 32,
        'endLine' => 32,
        'startColumn' => 9,
        'endColumn' => 46,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'retry' => 
      array (
        'declaringClassName' => 'Modules\\Integration\\Application\\OutboxWorker',
        'implementingClassName' => 'Modules\\Integration\\Application\\OutboxWorker',
        'name' => 'retry',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Modules\\Integration\\Domain\\RetryPolicy',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 33,
        'endLine' => 33,
        'startColumn' => 9,
        'endColumn' => 43,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'processingTimeoutSeconds' => 
      array (
        'declaringClassName' => 'Modules\\Integration\\Application\\OutboxWorker',
        'implementingClassName' => 'Modules\\Integration\\Application\\OutboxWorker',
        'name' => 'processingTimeoutSeconds',
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
            'startLine' => 34,
            'endLine' => 34,
            'startTokenPos' => 120,
            'startFilePos' => 1430,
            'endTokenPos' => 120,
            'endFilePos' => 1432,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 34,
        'endLine' => 34,
        'startColumn' => 9,
        'endColumn' => 60,
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
          'router' => 
          array (
            'name' => 'router',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Integration\\Application\\Delivery\\MessageRouter',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 32,
            'endLine' => 32,
            'startColumn' => 9,
            'endColumn' => 46,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'retry' => 
          array (
            'name' => 'retry',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Integration\\Domain\\RetryPolicy',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 33,
            'endLine' => 33,
            'startColumn' => 9,
            'endColumn' => 43,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'processingTimeoutSeconds' => 
          array (
            'name' => 'processingTimeoutSeconds',
            'default' => 
            array (
              'code' => '600',
              'attributes' => 
              array (
                'startLine' => 34,
                'endLine' => 34,
                'startTokenPos' => 120,
                'startFilePos' => 1430,
                'endTokenPos' => 120,
                'endFilePos' => 1432,
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
            'startLine' => 34,
            'endLine' => 34,
            'startColumn' => 9,
            'endColumn' => 60,
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
        'startLine' => 31,
        'endLine' => 35,
        'startColumn' => 5,
        'endColumn' => 8,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Integration\\Application',
        'declaringClassName' => 'Modules\\Integration\\Application\\OutboxWorker',
        'implementingClassName' => 'Modules\\Integration\\Application\\OutboxWorker',
        'currentClassName' => 'Modules\\Integration\\Application\\OutboxWorker',
        'aliasName' => NULL,
      ),
      'run' => 
      array (
        'name' => 'run',
        'parameters' => 
        array (
          'limit' => 
          array (
            'name' => 'limit',
            'default' => 
            array (
              'code' => '500',
              'attributes' => 
              array (
                'startLine' => 40,
                'endLine' => 40,
                'startTokenPos' => 142,
                'startFilePos' => 1576,
                'endTokenPos' => 142,
                'endFilePos' => 1578,
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
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 40,
            'endLine' => 40,
            'startColumn' => 25,
            'endColumn' => 40,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
          'batchSize' => 
          array (
            'name' => 'batchSize',
            'default' => 
            array (
              'code' => '50',
              'attributes' => 
              array (
                'startLine' => 40,
                'endLine' => 40,
                'startTokenPos' => 151,
                'startFilePos' => 1598,
                'endTokenPos' => 151,
                'endFilePos' => 1599,
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
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 40,
            'endLine' => 40,
            'startColumn' => 43,
            'endColumn' => 61,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
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
        'docComment' => '/**
 * @return int số message đã xử lý (thành công hay thất bại)
 */',
        'startLine' => 40,
        'endLine' => 58,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Integration\\Application',
        'declaringClassName' => 'Modules\\Integration\\Application\\OutboxWorker',
        'implementingClassName' => 'Modules\\Integration\\Application\\OutboxWorker',
        'currentClassName' => 'Modules\\Integration\\Application\\OutboxWorker',
        'aliasName' => NULL,
      ),
      'reclaimStuck' => 
      array (
        'name' => 'reclaimStuck',
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
        'startLine' => 60,
        'endLine' => 66,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Integration\\Application',
        'declaringClassName' => 'Modules\\Integration\\Application\\OutboxWorker',
        'implementingClassName' => 'Modules\\Integration\\Application\\OutboxWorker',
        'currentClassName' => 'Modules\\Integration\\Application\\OutboxWorker',
        'aliasName' => NULL,
      ),
      'claim' => 
      array (
        'name' => 'claim',
        'parameters' => 
        array (
          'size' => 
          array (
            'name' => 'size',
            'default' => NULL,
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
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 71,
            'endLine' => 71,
            'startColumn' => 28,
            'endColumn' => 36,
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
            'name' => 'Illuminate\\Support\\Collection',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return Collection<int, OutboxRecord>
 */',
        'startLine' => 71,
        'endLine' => 97,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'Modules\\Integration\\Application',
        'declaringClassName' => 'Modules\\Integration\\Application\\OutboxWorker',
        'implementingClassName' => 'Modules\\Integration\\Application\\OutboxWorker',
        'currentClassName' => 'Modules\\Integration\\Application\\OutboxWorker',
        'aliasName' => NULL,
      ),
      'deliver' => 
      array (
        'name' => 'deliver',
        'parameters' => 
        array (
          'record' => 
          array (
            'name' => 'record',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Integration\\Persistence\\Models\\OutboxRecord',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 99,
            'endLine' => 99,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 99,
        'endLine' => 122,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'Modules\\Integration\\Application',
        'declaringClassName' => 'Modules\\Integration\\Application\\OutboxWorker',
        'implementingClassName' => 'Modules\\Integration\\Application\\OutboxWorker',
        'currentClassName' => 'Modules\\Integration\\Application\\OutboxWorker',
        'aliasName' => NULL,
      ),
      'apply' => 
      array (
        'name' => 'apply',
        'parameters' => 
        array (
          'record' => 
          array (
            'name' => 'record',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Integration\\Persistence\\Models\\OutboxRecord',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 124,
            'endLine' => 124,
            'startColumn' => 28,
            'endColumn' => 47,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'result' => 
          array (
            'name' => 'result',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Integration\\Contracts\\Data\\DeliveryResult',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 124,
            'endLine' => 124,
            'startColumn' => 50,
            'endColumn' => 71,
            'parameterIndex' => 1,
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
        'startLine' => 124,
        'endLine' => 152,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'Modules\\Integration\\Application',
        'declaringClassName' => 'Modules\\Integration\\Application\\OutboxWorker',
        'implementingClassName' => 'Modules\\Integration\\Application\\OutboxWorker',
        'currentClassName' => 'Modules\\Integration\\Application\\OutboxWorker',
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