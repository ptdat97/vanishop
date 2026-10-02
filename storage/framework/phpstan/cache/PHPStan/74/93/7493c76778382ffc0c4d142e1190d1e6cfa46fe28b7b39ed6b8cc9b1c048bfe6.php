<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Integration/Console/DispatchOutboxCommand.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Integration\Console\DispatchOutboxCommand
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-f62510ba8b3876fd22ed79db1f368ba89aa599103898b4af709496e81a9a1742',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Integration\\Console\\DispatchOutboxCommand',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Integration/Console/DispatchOutboxCommand.php',
      ),
    ),
    'namespace' => 'Modules\\Integration\\Console',
    'name' => 'Modules\\Integration\\Console\\DispatchOutboxCommand',
    'shortName' => 'DispatchOutboxCommand',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 10,
    'endLine' => 24,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Illuminate\\Console\\Command',
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
      'signature' => 
      array (
        'declaringClassName' => 'Modules\\Integration\\Console\\DispatchOutboxCommand',
        'implementingClassName' => 'Modules\\Integration\\Console\\DispatchOutboxCommand',
        'name' => 'signature',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'vani:integration:dispatch
        {--limit=500 : Số message tối đa mỗi lượt}
        {--work : Chạy liên tục (supervisor) thay vì một lượt}
        {--max-time=3600 : Thời gian tối đa (giây) khi --work}
        {--sleep=2 : Giây nghỉ khi hàng đợi trống}\'',
          'attributes' => 
          array (
            'startLine' => 12,
            'endLine' => 16,
            'startTokenPos' => 43,
            'startFilePos' => 235,
            'endTokenPos' => 43,
            'endFilePos' => 525,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 12,
        'endLine' => 16,
        'startColumn' => 5,
        'endColumn' => 61,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'description' => 
      array (
        'declaringClassName' => 'Modules\\Integration\\Console\\DispatchOutboxCommand',
        'implementingClassName' => 'Modules\\Integration\\Console\\DispatchOutboxCommand',
        'name' => 'description',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'Gửi message outbox tới webhook đối tác và connector (retry, backoff, dead letter).\'',
          'attributes' => 
          array (
            'startLine' => 18,
            'endLine' => 18,
            'startTokenPos' => 52,
            'startFilePos' => 558,
            'endTokenPos' => 52,
            'endFilePos' => 650,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 18,
        'endLine' => 18,
        'startColumn' => 5,
        'endColumn' => 123,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
    ),
    'immediateMethods' => 
    array (
      'handle' => 
      array (
        'name' => 'handle',
        'parameters' => 
        array (
          'worker' => 
          array (
            'name' => 'worker',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Integration\\Application\\OutboxWorker',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 20,
            'endLine' => 20,
            'startColumn' => 28,
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
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 20,
        'endLine' => 23,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Integration\\Console',
        'declaringClassName' => 'Modules\\Integration\\Console\\DispatchOutboxCommand',
        'implementingClassName' => 'Modules\\Integration\\Console\\DispatchOutboxCommand',
        'currentClassName' => 'Modules\\Integration\\Console\\DispatchOutboxCommand',
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