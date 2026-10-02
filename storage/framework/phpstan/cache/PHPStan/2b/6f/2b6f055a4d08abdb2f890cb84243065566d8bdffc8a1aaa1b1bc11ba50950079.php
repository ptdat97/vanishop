<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Ordering/Domain/OrderStateMachine.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Ordering\Domain\OrderStateMachine
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-164a97d77b0f5c55366433fdd278f38c46689336f9e773a93a65c11c73ee02a6',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Ordering\\Domain\\OrderStateMachine',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Ordering/Domain/OrderStateMachine.php',
      ),
    ),
    'namespace' => 'Modules\\Ordering\\Domain',
    'name' => 'Modules\\Ordering\\Domain\\OrderStateMachine',
    'shortName' => 'OrderStateMachine',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Bảng chuyển trạng thái đơn (docs/09-order/order.md §3). Cố định — không mở rộng bằng plugin.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 12,
    'endLine' => 34,
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
      'TRANSITIONS' => 
      array (
        'declaringClassName' => 'Modules\\Ordering\\Domain\\OrderStateMachine',
        'implementingClassName' => 'Modules\\Ordering\\Domain\\OrderStateMachine',
        'name' => 'TRANSITIONS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'pending\' => [\'confirmed\', \'cancelled\'], \'confirmed\' => [\'processing\', \'cancelled\'], \'processing\' => [\'completed\', \'cancelled\'], \'completed\' => [], \'cancelled\' => []]',
          'attributes' => 
          array (
            'startLine' => 14,
            'endLine' => 20,
            'startTokenPos' => 38,
            'startFilePos' => 311,
            'endTokenPos' => 92,
            'endFilePos' => 524,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 14,
        'endLine' => 20,
        'startColumn' => 5,
        'endColumn' => 6,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'can' => 
      array (
        'name' => 'can',
        'parameters' => 
        array (
          'from' => 
          array (
            'name' => 'from',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Ordering\\Contracts\\Data\\OrderStatus',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 22,
            'endLine' => 22,
            'startColumn' => 32,
            'endColumn' => 48,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'to' => 
          array (
            'name' => 'to',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Ordering\\Contracts\\Data\\OrderStatus',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 22,
            'endLine' => 22,
            'startColumn' => 51,
            'endColumn' => 65,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 22,
        'endLine' => 25,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Modules\\Ordering\\Domain',
        'declaringClassName' => 'Modules\\Ordering\\Domain\\OrderStateMachine',
        'implementingClassName' => 'Modules\\Ordering\\Domain\\OrderStateMachine',
        'currentClassName' => 'Modules\\Ordering\\Domain\\OrderStateMachine',
        'aliasName' => NULL,
      ),
      'next' => 
      array (
        'name' => 'next',
        'parameters' => 
        array (
          'from' => 
          array (
            'name' => 'from',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Ordering\\Contracts\\Data\\OrderStatus',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 30,
            'endLine' => 30,
            'startColumn' => 33,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return list<OrderStatus>
 */',
        'startLine' => 30,
        'endLine' => 33,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Modules\\Ordering\\Domain',
        'declaringClassName' => 'Modules\\Ordering\\Domain\\OrderStateMachine',
        'implementingClassName' => 'Modules\\Ordering\\Domain\\OrderStateMachine',
        'currentClassName' => 'Modules\\Ordering\\Domain\\OrderStateMachine',
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