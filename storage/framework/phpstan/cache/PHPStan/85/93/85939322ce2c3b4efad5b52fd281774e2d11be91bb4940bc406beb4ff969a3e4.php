<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Inventory/Contracts/InventoryReservation.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Inventory\Contracts\InventoryReservation
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-a9116dd96ba132f9fcd497b28343459bb9ff98fa523bf58f16dfc86e9108ac72',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Inventory\\Contracts\\InventoryReservation',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Inventory/Contracts/InventoryReservation.php',
      ),
    ),
    'namespace' => 'Modules\\Inventory\\Contracts',
    'name' => 'Modules\\Inventory\\Contracts\\InventoryReservation',
    'shortName' => 'InventoryReservation',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Service contract: giữ hàng (invariant của Core). Gọi TRONG transaction đặt hàng của bên gọi.
 * Không đủ hàng → Modules\\Inventory\\Contracts\\StockUnavailable.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 14,
    'endLine' => 39,
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
      'reserve' => 
      array (
        'name' => 'reserve',
        'parameters' => 
        array (
          'request' => 
          array (
            'name' => 'request',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Inventory\\Contracts\\Data\\ReservationRequest',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 21,
            'endLine' => 21,
            'startColumn' => 29,
            'endColumn' => 55,
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
 * Idempotent theo key: key đã có reservation active → trả lại các dòng đã giữ.
 *
 * @return list<ReservedLine>
 */',
        'startLine' => 21,
        'endLine' => 21,
        'startColumn' => 5,
        'endColumn' => 64,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Inventory\\Contracts',
        'declaringClassName' => 'Modules\\Inventory\\Contracts\\InventoryReservation',
        'implementingClassName' => 'Modules\\Inventory\\Contracts\\InventoryReservation',
        'currentClassName' => 'Modules\\Inventory\\Contracts\\InventoryReservation',
        'aliasName' => NULL,
      ),
      'release' => 
      array (
        'name' => 'release',
        'parameters' => 
        array (
          'key' => 
          array (
            'name' => 'key',
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
            'startLine' => 26,
            'endLine' => 26,
            'startColumn' => 29,
            'endColumn' => 39,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'reason' => 
          array (
            'name' => 'reason',
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
            'startLine' => 26,
            'endLine' => 26,
            'startColumn' => 42,
            'endColumn' => 55,
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
        'docComment' => '/**
 * Bỏ giữ hàng (huỷ đơn, hết hạn thanh toán). Idempotent.
 */',
        'startLine' => 26,
        'endLine' => 26,
        'startColumn' => 5,
        'endColumn' => 63,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Inventory\\Contracts',
        'declaringClassName' => 'Modules\\Inventory\\Contracts\\InventoryReservation',
        'implementingClassName' => 'Modules\\Inventory\\Contracts\\InventoryReservation',
        'currentClassName' => 'Modules\\Inventory\\Contracts\\InventoryReservation',
        'aliasName' => NULL,
      ),
      'commit' => 
      array (
        'name' => 'commit',
        'parameters' => 
        array (
          'key' => 
          array (
            'name' => 'key',
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
            'startLine' => 31,
            'endLine' => 31,
            'startColumn' => 28,
            'endColumn' => 38,
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
        'docComment' => '/**
 * Xuất kho: chuyển reservation sang committed. Idempotent.
 */',
        'startLine' => 31,
        'endLine' => 31,
        'startColumn' => 5,
        'endColumn' => 46,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Inventory\\Contracts',
        'declaringClassName' => 'Modules\\Inventory\\Contracts\\InventoryReservation',
        'implementingClassName' => 'Modules\\Inventory\\Contracts\\InventoryReservation',
        'currentClassName' => 'Modules\\Inventory\\Contracts\\InventoryReservation',
        'aliasName' => NULL,
      ),
      'reservedLines' => 
      array (
        'name' => 'reservedLines',
        'parameters' => 
        array (
          'key' => 
          array (
            'name' => 'key',
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
            'startLine' => 38,
            'endLine' => 38,
            'startColumn' => 35,
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
 * Các dòng đang giữ (active) của key: location nào giữ bao nhiêu — Fulfillment dùng để tạo shipment theo kho.
 *
 * @return list<ReservedLine>
 */',
        'startLine' => 38,
        'endLine' => 38,
        'startColumn' => 5,
        'endColumn' => 54,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Inventory\\Contracts',
        'declaringClassName' => 'Modules\\Inventory\\Contracts\\InventoryReservation',
        'implementingClassName' => 'Modules\\Inventory\\Contracts\\InventoryReservation',
        'currentClassName' => 'Modules\\Inventory\\Contracts\\InventoryReservation',
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