<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Integration/Application/OrderEventReconciler.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Integration\Application\OrderEventReconciler
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-cb0c5b42ab859c12abdfbd9f7ed174f8c6b1132ca283544038e7c85d3c0ce274',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Integration\\Application\\OrderEventReconciler',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Integration/Application/OrderEventReconciler.php',
      ),
    ),
    'namespace' => 'Modules\\Integration\\Application',
    'name' => 'Modules\\Integration\\Application\\OrderEventReconciler',
    'shortName' => 'OrderEventReconciler',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Đối soát đơn ↔ event feed: bù event bị mất khi tiến trình chết giữa commit nghiệp vụ và lúc ghi feed
 * (domain event phát sau commit — xem integration-platform §11). Event bù mang `reconciled: true`.
 *
 * Trạng thái đơn → event bắt buộc:
 * - mọi đơn: `order.created`
 * - confirmed/processing/completed: `order.confirmed`
 * - cancelled: `order.cancelled` (không suy ra được đơn huỷ đã từng xác nhận hay chưa)
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 28,
    'endLine' => 125,
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
      'TYPE' => 
      array (
        'declaringClassName' => 'Modules\\Integration\\Application\\OrderEventReconciler',
        'implementingClassName' => 'Modules\\Integration\\Application\\OrderEventReconciler',
        'name' => 'TYPE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'order_events\'',
          'attributes' => 
          array (
            'startLine' => 30,
            'endLine' => 30,
            'startTokenPos' => 88,
            'startFilePos' => 1124,
            'endTokenPos' => 88,
            'endFilePos' => 1137,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 30,
        'endLine' => 30,
        'startColumn' => 5,
        'endColumn' => 39,
      ),
      'PAGE' => 
      array (
        'declaringClassName' => 'Modules\\Integration\\Application\\OrderEventReconciler',
        'implementingClassName' => 'Modules\\Integration\\Application\\OrderEventReconciler',
        'name' => 'PAGE',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '200',
          'attributes' => 
          array (
            'startLine' => 32,
            'endLine' => 32,
            'startTokenPos' => 99,
            'startFilePos' => 1166,
            'endTokenPos' => 99,
            'endFilePos' => 1168,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 32,
        'endLine' => 32,
        'startColumn' => 5,
        'endColumn' => 29,
      ),
      'MAX_DETAILS' => 
      array (
        'declaringClassName' => 'Modules\\Integration\\Application\\OrderEventReconciler',
        'implementingClassName' => 'Modules\\Integration\\Application\\OrderEventReconciler',
        'name' => 'MAX_DETAILS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '100',
          'attributes' => 
          array (
            'startLine' => 34,
            'endLine' => 34,
            'startTokenPos' => 110,
            'startFilePos' => 1204,
            'endTokenPos' => 110,
            'endFilePos' => 1206,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 34,
        'endLine' => 34,
        'startColumn' => 5,
        'endColumn' => 36,
      ),
    ),
    'immediateProperties' => 
    array (
      'orders' => 
      array (
        'declaringClassName' => 'Modules\\Integration\\Application\\OrderEventReconciler',
        'implementingClassName' => 'Modules\\Integration\\Application\\OrderEventReconciler',
        'name' => 'orders',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Modules\\Ordering\\Contracts\\OrderReader',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 37,
        'endLine' => 37,
        'startColumn' => 9,
        'endColumn' => 44,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'events' => 
      array (
        'declaringClassName' => 'Modules\\Integration\\Application\\OrderEventReconciler',
        'implementingClassName' => 'Modules\\Integration\\Application\\OrderEventReconciler',
        'name' => 'events',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Modules\\Integration\\Contracts\\IntegrationEvents',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 38,
        'endLine' => 38,
        'startColumn' => 9,
        'endColumn' => 50,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'payloads' => 
      array (
        'declaringClassName' => 'Modules\\Integration\\Application\\OrderEventReconciler',
        'implementingClassName' => 'Modules\\Integration\\Application\\OrderEventReconciler',
        'name' => 'payloads',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Modules\\Integration\\Application\\CanonicalPayloads',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 39,
        'endLine' => 39,
        'startColumn' => 9,
        'endColumn' => 52,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'context' => 
      array (
        'declaringClassName' => 'Modules\\Integration\\Application\\OrderEventReconciler',
        'implementingClassName' => 'Modules\\Integration\\Application\\OrderEventReconciler',
        'name' => 'context',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Modules\\Shared\\Context\\CurrentContext',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 40,
        'endLine' => 40,
        'startColumn' => 9,
        'endColumn' => 48,
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
          'orders' => 
          array (
            'name' => 'orders',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Ordering\\Contracts\\OrderReader',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 37,
            'endLine' => 37,
            'startColumn' => 9,
            'endColumn' => 44,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'events' => 
          array (
            'name' => 'events',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Integration\\Contracts\\IntegrationEvents',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 38,
            'endLine' => 38,
            'startColumn' => 9,
            'endColumn' => 50,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'payloads' => 
          array (
            'name' => 'payloads',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Integration\\Application\\CanonicalPayloads',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 39,
            'endLine' => 39,
            'startColumn' => 9,
            'endColumn' => 52,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'context' => 
          array (
            'name' => 'context',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Shared\\Context\\CurrentContext',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 40,
            'endLine' => 40,
            'startColumn' => 9,
            'endColumn' => 48,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 36,
        'endLine' => 41,
        'startColumn' => 5,
        'endColumn' => 8,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Integration\\Application',
        'declaringClassName' => 'Modules\\Integration\\Application\\OrderEventReconciler',
        'implementingClassName' => 'Modules\\Integration\\Application\\OrderEventReconciler',
        'currentClassName' => 'Modules\\Integration\\Application\\OrderEventReconciler',
        'aliasName' => NULL,
      ),
      'run' => 
      array (
        'name' => 'run',
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
                'name' => 'Carbon\\CarbonImmutable',
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
            'startColumn' => 25,
            'endColumn' => 45,
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
                'name' => 'Carbon\\CarbonImmutable',
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
            'startColumn' => 48,
            'endColumn' => 66,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'repair' => 
          array (
            'name' => 'repair',
            'default' => 
            array (
              'code' => 'true',
              'attributes' => 
              array (
                'startLine' => 47,
                'endLine' => 47,
                'startTokenPos' => 185,
                'startFilePos' => 1687,
                'endTokenPos' => 185,
                'endFilePos' => 1690,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'bool',
                'isIdentifier' => true,
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
            'startColumn' => 69,
            'endColumn' => 87,
            'parameterIndex' => 2,
            'isOptional' => true,
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
 * @param  bool  $repair  false = chỉ báo cáo
 * @return array{id: int, checked: int, discrepancies: int, repaired: int}
 */',
        'startLine' => 47,
        'endLine' => 100,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Integration\\Application',
        'declaringClassName' => 'Modules\\Integration\\Application\\OrderEventReconciler',
        'implementingClassName' => 'Modules\\Integration\\Application\\OrderEventReconciler',
        'currentClassName' => 'Modules\\Integration\\Application\\OrderEventReconciler',
        'aliasName' => NULL,
      ),
      'missing' => 
      array (
        'name' => 'missing',
        'parameters' => 
        array (
          'order' => 
          array (
            'name' => 'order',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Ordering\\Contracts\\Data\\OrderData',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 105,
            'endLine' => 105,
            'startColumn' => 30,
            'endColumn' => 45,
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
 * @return list<string>
 */',
        'startLine' => 105,
        'endLine' => 124,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'Modules\\Integration\\Application',
        'declaringClassName' => 'Modules\\Integration\\Application\\OrderEventReconciler',
        'implementingClassName' => 'Modules\\Integration\\Application\\OrderEventReconciler',
        'currentClassName' => 'Modules\\Integration\\Application\\OrderEventReconciler',
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