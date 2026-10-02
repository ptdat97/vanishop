<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Inventory/Contracts/InventorySync.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Inventory\Contracts\InventorySync
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-7adf35e2fd6e3873c4787c9b95aa074f8d164fb61a2502d136a397b940f30961',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Inventory\\Contracts\\InventorySync',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Inventory/Contracts/InventorySync.php',
      ),
    ),
    'namespace' => 'Modules\\Inventory\\Contracts',
    'name' => 'Modules\\Inventory\\Contracts\\InventorySync',
    'shortName' => 'InventorySync',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Service contract: nhận tồn vật lý (số tuyệt đối + version) từ authority ngoài (ERP/POS/ODO) của một location.
 * Chỉ authority của location (`locations.stock_authority`) được ghi; bản cũ hơn `sync_version` bị bỏ qua.
 *
 * @see docs/08-inventory/inventory.md §7
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 15,
    'endLine' => 28,
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
      'assertAuthority' => 
      array (
        'name' => 'assertAuthority',
        'parameters' => 
        array (
          'authority' => 
          array (
            'name' => 'authority',
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
            'startColumn' => 37,
            'endColumn' => 53,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'locationCodes' => 
          array (
            'name' => 'locationCodes',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
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
            'startColumn' => 56,
            'endColumn' => 75,
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
 * @param  list<string>  $locationCodes
 *
 * @throws NotStockAuthority location đầu tiên không tồn tại hoặc không do $authority quản lý tồn
 */',
        'startLine' => 22,
        'endLine' => 22,
        'startColumn' => 5,
        'endColumn' => 83,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Inventory\\Contracts',
        'declaringClassName' => 'Modules\\Inventory\\Contracts\\InventorySync',
        'implementingClassName' => 'Modules\\Inventory\\Contracts\\InventorySync',
        'currentClassName' => 'Modules\\Inventory\\Contracts\\InventorySync',
        'aliasName' => NULL,
      ),
      'syncOnHand' => 
      array (
        'name' => 'syncOnHand',
        'parameters' => 
        array (
          'authority' => 
          array (
            'name' => 'authority',
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
            'startLine' => 27,
            'endLine' => 27,
            'startColumn' => 32,
            'endColumn' => 48,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'locationCode' => 
          array (
            'name' => 'locationCode',
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
            'startLine' => 27,
            'endLine' => 27,
            'startColumn' => 51,
            'endColumn' => 70,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'variantId' => 
          array (
            'name' => 'variantId',
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
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 27,
            'endLine' => 27,
            'startColumn' => 73,
            'endColumn' => 86,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'onHand' => 
          array (
            'name' => 'onHand',
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
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 27,
            'endLine' => 27,
            'startColumn' => 89,
            'endColumn' => 99,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'version' => 
          array (
            'name' => 'version',
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
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 27,
            'endLine' => 27,
            'startColumn' => 102,
            'endColumn' => 113,
            'parameterIndex' => 4,
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
            'startLine' => 27,
            'endLine' => 27,
            'startColumn' => 116,
            'endColumn' => 129,
            'parameterIndex' => 5,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Modules\\Inventory\\Contracts\\Data\\SyncOutcome',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @throws NotStockAuthority location không tồn tại hoặc không do $authority quản lý tồn
 */',
        'startLine' => 27,
        'endLine' => 27,
        'startColumn' => 5,
        'endColumn' => 144,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Inventory\\Contracts',
        'declaringClassName' => 'Modules\\Inventory\\Contracts\\InventorySync',
        'implementingClassName' => 'Modules\\Inventory\\Contracts\\InventorySync',
        'currentClassName' => 'Modules\\Inventory\\Contracts\\InventorySync',
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