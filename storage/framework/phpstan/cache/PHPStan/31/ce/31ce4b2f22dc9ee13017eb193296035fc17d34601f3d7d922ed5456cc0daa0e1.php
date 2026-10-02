<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Integration/Console/ReconcileOrdersCommand.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Integration\Console\ReconcileOrdersCommand
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-8e2817280f50a7733eda3d3b6f9f8bbe0d32e03d6450626c88c04ece1045ec9d',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Integration\\Console\\ReconcileOrdersCommand',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Integration/Console/ReconcileOrdersCommand.php',
      ),
    ),
    'namespace' => 'Modules\\Integration\\Console',
    'name' => 'Modules\\Integration\\Console\\ReconcileOrdersCommand',
    'shortName' => 'ReconcileOrdersCommand',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 11,
    'endLine' => 29,
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
        'declaringClassName' => 'Modules\\Integration\\Console\\ReconcileOrdersCommand',
        'implementingClassName' => 'Modules\\Integration\\Console\\ReconcileOrdersCommand',
        'name' => 'signature',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'vani:integration:reconcile-orders
        {--hours=25 : Đối soát đơn thay đổi trong N giờ qua}
        {--grace=5 : Bỏ qua đơn thay đổi trong N phút gần nhất (listener có thể đang chạy)}
        {--dry-run : Chỉ báo cáo, không phát event bù}\'',
          'attributes' => 
          array (
            'startLine' => 13,
            'endLine' => 16,
            'startTokenPos' => 48,
            'startFilePos' => 272,
            'endTokenPos' => 48,
            'endFilePos' => 550,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 13,
        'endLine' => 16,
        'startColumn' => 5,
        'endColumn' => 63,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'description' => 
      array (
        'declaringClassName' => 'Modules\\Integration\\Console\\ReconcileOrdersCommand',
        'implementingClassName' => 'Modules\\Integration\\Console\\ReconcileOrdersCommand',
        'name' => 'description',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'Đối soát đơn hàng ↔ event feed tích hợp; phát bù event bị thiếu.\'',
          'attributes' => 
          array (
            'startLine' => 18,
            'endLine' => 18,
            'startTokenPos' => 57,
            'startFilePos' => 583,
            'endTokenPos' => 57,
            'endFilePos' => 666,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 18,
        'endLine' => 18,
        'startColumn' => 5,
        'endColumn' => 114,
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
          'reconciler' => 
          array (
            'name' => 'reconciler',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Integration\\Application\\OrderEventReconciler',
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
            'endColumn' => 59,
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
        'endLine' => 28,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Integration\\Console',
        'declaringClassName' => 'Modules\\Integration\\Console\\ReconcileOrdersCommand',
        'implementingClassName' => 'Modules\\Integration\\Console\\ReconcileOrdersCommand',
        'currentClassName' => 'Modules\\Integration\\Console\\ReconcileOrdersCommand',
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