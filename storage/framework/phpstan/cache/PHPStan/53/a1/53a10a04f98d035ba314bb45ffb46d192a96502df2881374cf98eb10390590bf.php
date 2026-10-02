<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Returns/Events/ReturnResolved.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Returns\Events\ReturnResolved
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-72572c904e8cb825b2802a8db718aeafc98bba69af3aa06377975468c1d17e85',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Returns\\Events\\ReturnResolved',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Returns/Events/ReturnResolved.php',
      ),
    ),
    'namespace' => 'Modules\\Returns\\Events',
    'name' => 'Modules\\Returns\\Events\\ReturnResolved',
    'shortName' => 'ReturnResolved',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 65568,
    'docComment' => '/**
 * Đã hoàn tất đổi/trả (đã nhập kho hàng bán được, đã tạo hoàn tiền). Listener: Integration, hoá đơn điều chỉnh (plugin).
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 13,
    'endLine' => 18,
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
      'returnId' => 
      array (
        'declaringClassName' => 'Modules\\Returns\\Events\\ReturnResolved',
        'implementingClassName' => 'Modules\\Returns\\Events\\ReturnResolved',
        'name' => 'returnId',
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
        'startLine' => 17,
        'endLine' => 17,
        'startColumn' => 33,
        'endColumn' => 52,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'orderId' => 
      array (
        'declaringClassName' => 'Modules\\Returns\\Events\\ReturnResolved',
        'implementingClassName' => 'Modules\\Returns\\Events\\ReturnResolved',
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
        'startLine' => 17,
        'endLine' => 17,
        'startColumn' => 55,
        'endColumn' => 73,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'refundedAmount' => 
      array (
        'declaringClassName' => 'Modules\\Returns\\Events\\ReturnResolved',
        'implementingClassName' => 'Modules\\Returns\\Events\\ReturnResolved',
        'name' => 'refundedAmount',
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
        'startLine' => 17,
        'endLine' => 17,
        'startColumn' => 76,
        'endColumn' => 101,
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
          'returnId' => 
          array (
            'name' => 'returnId',
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
            'startLine' => 17,
            'endLine' => 17,
            'startColumn' => 33,
            'endColumn' => 52,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startLine' => 17,
            'endLine' => 17,
            'startColumn' => 55,
            'endColumn' => 73,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'refundedAmount' => 
          array (
            'name' => 'refundedAmount',
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
            'startLine' => 17,
            'endLine' => 17,
            'startColumn' => 76,
            'endColumn' => 101,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 17,
        'endLine' => 17,
        'startColumn' => 5,
        'endColumn' => 105,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Returns\\Events',
        'declaringClassName' => 'Modules\\Returns\\Events\\ReturnResolved',
        'implementingClassName' => 'Modules\\Returns\\Events\\ReturnResolved',
        'currentClassName' => 'Modules\\Returns\\Events\\ReturnResolved',
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