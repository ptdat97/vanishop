<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Checkout/Application/Listeners/UndoCancelledOrder.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Checkout\Application\Listeners\UndoCancelledOrder
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-2e607b7c20ed20e26de372a3d424a8057d3d101c4acde1258ba472490e76b8ab',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Checkout\\Application\\Listeners\\UndoCancelledOrder',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Checkout/Application/Listeners/UndoCancelledOrder.php',
      ),
    ),
    'namespace' => 'Modules\\Checkout\\Application\\Listeners',
    'name' => 'Modules\\Checkout\\Application\\Listeners\\UndoCancelledOrder',
    'shortName' => 'UndoCancelledOrder',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Đơn huỷ → nhả giữ hàng và hoàn lượt khuyến mãi. Cả hai thao tác idempotent (event có thể đến lại).
 * Checkout điều phối vì Checkout là nơi đã giữ hàng và ghi lượt khi đặt.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 15,
    'endLine' => 27,
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
      'inventory' => 
      array (
        'declaringClassName' => 'Modules\\Checkout\\Application\\Listeners\\UndoCancelledOrder',
        'implementingClassName' => 'Modules\\Checkout\\Application\\Listeners\\UndoCancelledOrder',
        'name' => 'inventory',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Modules\\Inventory\\Contracts\\InventoryReservation',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 18,
        'endLine' => 18,
        'startColumn' => 9,
        'endColumn' => 56,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'promotions' => 
      array (
        'declaringClassName' => 'Modules\\Checkout\\Application\\Listeners\\UndoCancelledOrder',
        'implementingClassName' => 'Modules\\Checkout\\Application\\Listeners\\UndoCancelledOrder',
        'name' => 'promotions',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Modules\\Promotion\\Contracts\\PromotionEngine',
            'isIdentifier' => false,
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
        'endColumn' => 52,
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
          'inventory' => 
          array (
            'name' => 'inventory',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Inventory\\Contracts\\InventoryReservation',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 18,
            'endLine' => 18,
            'startColumn' => 9,
            'endColumn' => 56,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'promotions' => 
          array (
            'name' => 'promotions',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Promotion\\Contracts\\PromotionEngine',
                'isIdentifier' => false,
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
            'endColumn' => 52,
            'parameterIndex' => 1,
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
        'endLine' => 20,
        'startColumn' => 5,
        'endColumn' => 8,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Checkout\\Application\\Listeners',
        'declaringClassName' => 'Modules\\Checkout\\Application\\Listeners\\UndoCancelledOrder',
        'implementingClassName' => 'Modules\\Checkout\\Application\\Listeners\\UndoCancelledOrder',
        'currentClassName' => 'Modules\\Checkout\\Application\\Listeners\\UndoCancelledOrder',
        'aliasName' => NULL,
      ),
      'handle' => 
      array (
        'name' => 'handle',
        'parameters' => 
        array (
          'event' => 
          array (
            'name' => 'event',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Ordering\\Events\\OrderCancelled',
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
            'startColumn' => 28,
            'endColumn' => 48,
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
        'startLine' => 22,
        'endLine' => 26,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Checkout\\Application\\Listeners',
        'declaringClassName' => 'Modules\\Checkout\\Application\\Listeners\\UndoCancelledOrder',
        'implementingClassName' => 'Modules\\Checkout\\Application\\Listeners\\UndoCancelledOrder',
        'currentClassName' => 'Modules\\Checkout\\Application\\Listeners\\UndoCancelledOrder',
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