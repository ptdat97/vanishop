<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Ordering/Events/OrderCompleted.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Ordering\Events\OrderCompleted
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-16f036ff21cc63504dd07dc4c3ee17e1d2e672ace2f8d3e7aeb16acfd2da41a4',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Ordering\\Events\\OrderCompleted',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Ordering/Events/OrderCompleted.php',
      ),
    ),
    'namespace' => 'Modules\\Ordering\\Events',
    'name' => 'Modules\\Ordering\\Events\\OrderCompleted',
    'shortName' => 'OrderCompleted',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 65568,
    'docComment' => '/**
 * Đơn hoàn tất: đã giao hết và quá hạn đổi trả (`vani:fulfillment:complete-orders`). Từ đây doanh thu không còn
 * bị đảo do trả hàng thông thường — loyalty (điểm khả dụng), hoá đơn điện tử, hoa hồng creator lắng nghe.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 14,
    'endLine' => 26,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'Illuminate\\Contracts\\Events\\ShouldDispatchAfterCommit',
    ),
    'traitClassNames' => 
    array (
      0 => 'Illuminate\\Foundation\\Events\\Dispatchable',
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
      'orderId' => 
      array (
        'declaringClassName' => 'Modules\\Ordering\\Events\\OrderCompleted',
        'implementingClassName' => 'Modules\\Ordering\\Events\\OrderCompleted',
        'name' => 'orderId',
        'modifiers' => 2049,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 19,
        'endLine' => 19,
        'startColumn' => 9,
        'endColumn' => 27,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'publicId' => 
      array (
        'declaringClassName' => 'Modules\\Ordering\\Events\\OrderCompleted',
        'implementingClassName' => 'Modules\\Ordering\\Events\\OrderCompleted',
        'name' => 'publicId',
        'modifiers' => 2049,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 20,
        'endLine' => 20,
        'startColumn' => 9,
        'endColumn' => 31,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'number' => 
      array (
        'declaringClassName' => 'Modules\\Ordering\\Events\\OrderCompleted',
        'implementingClassName' => 'Modules\\Ordering\\Events\\OrderCompleted',
        'name' => 'number',
        'modifiers' => 2049,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 21,
        'endLine' => 21,
        'startColumn' => 9,
        'endColumn' => 29,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'customerId' => 
      array (
        'declaringClassName' => 'Modules\\Ordering\\Events\\OrderCompleted',
        'implementingClassName' => 'Modules\\Ordering\\Events\\OrderCompleted',
        'name' => 'customerId',
        'modifiers' => 2049,
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
                  'name' => 'int',
                  'isIdentifier' => true,
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
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 22,
        'endLine' => 22,
        'startColumn' => 9,
        'endColumn' => 31,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'total' => 
      array (
        'declaringClassName' => 'Modules\\Ordering\\Events\\OrderCompleted',
        'implementingClassName' => 'Modules\\Ordering\\Events\\OrderCompleted',
        'name' => 'total',
        'modifiers' => 2049,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 23,
        'endLine' => 23,
        'startColumn' => 9,
        'endColumn' => 25,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'currency' => 
      array (
        'declaringClassName' => 'Modules\\Ordering\\Events\\OrderCompleted',
        'implementingClassName' => 'Modules\\Ordering\\Events\\OrderCompleted',
        'name' => 'currency',
        'modifiers' => 2049,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 24,
        'endLine' => 24,
        'startColumn' => 9,
        'endColumn' => 31,
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
          'orderId' => 
          array (
            'name' => 'orderId',
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 19,
            'endLine' => 19,
            'startColumn' => 9,
            'endColumn' => 27,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'publicId' => 
          array (
            'name' => 'publicId',
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 20,
            'endLine' => 20,
            'startColumn' => 9,
            'endColumn' => 31,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'number' => 
          array (
            'name' => 'number',
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 21,
            'endLine' => 21,
            'startColumn' => 9,
            'endColumn' => 29,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'customerId' => 
          array (
            'name' => 'customerId',
            'default' => NULL,
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
                      'name' => 'int',
                      'isIdentifier' => true,
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 22,
            'endLine' => 22,
            'startColumn' => 9,
            'endColumn' => 31,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'total' => 
          array (
            'name' => 'total',
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 23,
            'endLine' => 23,
            'startColumn' => 9,
            'endColumn' => 25,
            'parameterIndex' => 4,
            'isOptional' => false,
          ),
          'currency' => 
          array (
            'name' => 'currency',
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 24,
            'endLine' => 24,
            'startColumn' => 9,
            'endColumn' => 31,
            'parameterIndex' => 5,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 18,
        'endLine' => 25,
        'startColumn' => 5,
        'endColumn' => 8,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Ordering\\Events',
        'declaringClassName' => 'Modules\\Ordering\\Events\\OrderCompleted',
        'implementingClassName' => 'Modules\\Ordering\\Events\\OrderCompleted',
        'currentClassName' => 'Modules\\Ordering\\Events\\OrderCompleted',
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