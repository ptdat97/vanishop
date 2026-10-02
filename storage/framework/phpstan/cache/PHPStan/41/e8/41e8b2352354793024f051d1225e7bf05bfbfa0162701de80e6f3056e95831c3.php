<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Ordering/OrderingServiceProvider.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Ordering\OrderingServiceProvider
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-7bed3d55a06b0d6480e889c506accf03b263aff7e3af0b3e4d28c62f2394ba94',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Ordering\\OrderingServiceProvider',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Ordering/OrderingServiceProvider.php',
      ),
    ),
    'namespace' => 'Modules\\Ordering',
    'name' => 'Modules\\Ordering\\OrderingServiceProvider',
    'shortName' => 'OrderingServiceProvider',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Slice 6: tạo đơn (snapshot, số đơn, order_events). Slice 7: state machine + OrderTransitions (Payment cần).
 * Slice 8: Admin đơn, tra cứu/huỷ cho khách (CustomerOrders), đổi địa chỉ, ghi chú.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 23,
    'endLine' => 50,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Modules\\Shared\\Support\\ModuleServiceProvider',
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
      'moduleName' => 
      array (
        'name' => 'moduleName',
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
        'startLine' => 25,
        'endLine' => 28,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Modules\\Ordering',
        'declaringClassName' => 'Modules\\Ordering\\OrderingServiceProvider',
        'implementingClassName' => 'Modules\\Ordering\\OrderingServiceProvider',
        'currentClassName' => 'Modules\\Ordering\\OrderingServiceProvider',
        'aliasName' => NULL,
      ),
      'register' => 
      array (
        'name' => 'register',
        'parameters' => 
        array (
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
        'startLine' => 30,
        'endLine' => 36,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Ordering',
        'declaringClassName' => 'Modules\\Ordering\\OrderingServiceProvider',
        'implementingClassName' => 'Modules\\Ordering\\OrderingServiceProvider',
        'currentClassName' => 'Modules\\Ordering\\OrderingServiceProvider',
        'aliasName' => NULL,
      ),
      'boot' => 
      array (
        'name' => 'boot',
        'parameters' => 
        array (
          'permissions' => 
          array (
            'name' => 'permissions',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Identity\\Application\\PermissionRegistry',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 38,
            'endLine' => 38,
            'startColumn' => 26,
            'endColumn' => 56,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'navigation' => 
          array (
            'name' => 'navigation',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Extension\\Application\\Admin\\AdminNavigation',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 38,
            'endLine' => 38,
            'startColumn' => 59,
            'endColumn' => 85,
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
        'startLine' => 38,
        'endLine' => 49,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Ordering',
        'declaringClassName' => 'Modules\\Ordering\\OrderingServiceProvider',
        'implementingClassName' => 'Modules\\Ordering\\OrderingServiceProvider',
        'currentClassName' => 'Modules\\Ordering\\OrderingServiceProvider',
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