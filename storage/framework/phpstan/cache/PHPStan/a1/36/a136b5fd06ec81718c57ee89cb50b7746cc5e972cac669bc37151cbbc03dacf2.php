<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Ordering/Domain/OrderPolicy.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Ordering\Domain\OrderPolicy
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-c8444ac692119b422142dfc37c645cf597aac4dd2d8c25adad14a4a282be654d',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Ordering\\Domain\\OrderPolicy',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Ordering/Domain/OrderPolicy.php',
      ),
    ),
    'namespace' => 'Modules\\Ordering\\Domain',
    'name' => 'Modules\\Ordering\\Domain\\OrderPolicy',
    'shortName' => 'OrderPolicy',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Quy tắc thao tác trên đơn ngoài bảng chuyển trạng thái.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 12,
    'endLine' => 33,
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
      'customerCanCancel' => 
      array (
        'name' => 'customerCanCancel',
        'parameters' => 
        array (
          'status' => 
          array (
            'name' => 'status',
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
            'startLine' => 15,
            'endLine' => 15,
            'startColumn' => 46,
            'endColumn' => 64,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'fulfillmentStatus' => 
          array (
            'name' => 'fulfillmentStatus',
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
            'startLine' => 15,
            'endLine' => 15,
            'startColumn' => 67,
            'endColumn' => 91,
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
        'docComment' => '/** Khách tự huỷ: hàng chưa rời kho (vận đơn đã tạo nhưng chưa lấy hàng vẫn huỷ được). */',
        'startLine' => 15,
        'endLine' => 19,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Modules\\Ordering\\Domain',
        'declaringClassName' => 'Modules\\Ordering\\Domain\\OrderPolicy',
        'implementingClassName' => 'Modules\\Ordering\\Domain\\OrderPolicy',
        'currentClassName' => 'Modules\\Ordering\\Domain\\OrderPolicy',
        'aliasName' => NULL,
      ),
      'staffCanCancel' => 
      array (
        'name' => 'staffCanCancel',
        'parameters' => 
        array (
          'status' => 
          array (
            'name' => 'status',
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
            'startColumn' => 43,
            'endColumn' => 61,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'fulfillmentStatus' => 
          array (
            'name' => 'fulfillmentStatus',
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
            'startLine' => 22,
            'endLine' => 22,
            'startColumn' => 64,
            'endColumn' => 88,
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
        'docComment' => '/** Nhân viên huỷ: theo state machine, khi hàng chưa rời kho hoặc đã bị hoàn về. */',
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
        'declaringClassName' => 'Modules\\Ordering\\Domain\\OrderPolicy',
        'implementingClassName' => 'Modules\\Ordering\\Domain\\OrderPolicy',
        'currentClassName' => 'Modules\\Ordering\\Domain\\OrderPolicy',
        'aliasName' => NULL,
      ),
      'canChangeAddress' => 
      array (
        'name' => 'canChangeAddress',
        'parameters' => 
        array (
          'status' => 
          array (
            'name' => 'status',
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
            'startLine' => 28,
            'endLine' => 28,
            'startColumn' => 45,
            'endColumn' => 63,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'fulfillmentStatus' => 
          array (
            'name' => 'fulfillmentStatus',
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
            'startColumn' => 66,
            'endColumn' => 90,
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
        'docComment' => '/** Đổi địa chỉ giao: hàng chưa rời kho, đơn còn mở. Vận đơn đã đặt ở hãng thì plugin hãng phải cập nhật lại. */',
        'startLine' => 28,
        'endLine' => 32,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Modules\\Ordering\\Domain',
        'declaringClassName' => 'Modules\\Ordering\\Domain\\OrderPolicy',
        'implementingClassName' => 'Modules\\Ordering\\Domain\\OrderPolicy',
        'currentClassName' => 'Modules\\Ordering\\Domain\\OrderPolicy',
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