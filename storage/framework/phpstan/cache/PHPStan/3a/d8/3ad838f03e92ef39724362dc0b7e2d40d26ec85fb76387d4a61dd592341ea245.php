<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Integration/Console/ProcessInboxCommand.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Integration\Console\ProcessInboxCommand
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-a2bd23be8d7ed9005a2ead2294de3ffe87220597fc35e5b59094e9bea59473bd',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Integration\\Console\\ProcessInboxCommand',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Integration/Console/ProcessInboxCommand.php',
      ),
    ),
    'namespace' => 'Modules\\Integration\\Console',
    'name' => 'Modules\\Integration\\Console\\ProcessInboxCommand',
    'shortName' => 'ProcessInboxCommand',
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
        'declaringClassName' => 'Modules\\Integration\\Console\\ProcessInboxCommand',
        'implementingClassName' => 'Modules\\Integration\\Console\\ProcessInboxCommand',
        'name' => 'signature',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'vani:integration:process-inbox
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
            'endFilePos' => 530,
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
        'declaringClassName' => 'Modules\\Integration\\Console\\ProcessInboxCommand',
        'implementingClassName' => 'Modules\\Integration\\Console\\ProcessInboxCommand',
        'name' => 'description',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'Xử lý message inbox bằng InboundHandler của plugin.\'',
          'attributes' => 
          array (
            'startLine' => 18,
            'endLine' => 18,
            'startTokenPos' => 52,
            'startFilePos' => 563,
            'endTokenPos' => 52,
            'endFilePos' => 622,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 18,
        'endLine' => 18,
        'startColumn' => 5,
        'endColumn' => 90,
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
          'processor' => 
          array (
            'name' => 'processor',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Integration\\Application\\InboxProcessor',
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
        'declaringClassName' => 'Modules\\Integration\\Console\\ProcessInboxCommand',
        'implementingClassName' => 'Modules\\Integration\\Console\\ProcessInboxCommand',
        'currentClassName' => 'Modules\\Integration\\Console\\ProcessInboxCommand',
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