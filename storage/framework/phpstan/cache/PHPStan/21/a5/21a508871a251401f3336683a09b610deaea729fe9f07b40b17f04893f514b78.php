<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Inventory/Console/ReleaseExpiredReservationsCommand.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Inventory\Console\ReleaseExpiredReservationsCommand
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-65f57f9c317eb1cfdca702729b43ce7b775f28dc72bb4e9ac4712fcb2644b761',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Inventory\\Console\\ReleaseExpiredReservationsCommand',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Inventory/Console/ReleaseExpiredReservationsCommand.php',
      ),
    ),
    'namespace' => 'Modules\\Inventory\\Console',
    'name' => 'Modules\\Inventory\\Console\\ReleaseExpiredReservationsCommand',
    'shortName' => 'ReleaseExpiredReservationsCommand',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Giải phóng reservation quá hạn (chạy mỗi phút). Idempotent.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 17,
    'endLine' => 43,
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
        'declaringClassName' => 'Modules\\Inventory\\Console\\ReleaseExpiredReservationsCommand',
        'implementingClassName' => 'Modules\\Inventory\\Console\\ReleaseExpiredReservationsCommand',
        'name' => 'signature',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'vani:inventory:release-expired {--limit=500}\'',
          'attributes' => 
          array (
            'startLine' => 19,
            'endLine' => 19,
            'startTokenPos' => 65,
            'startFilePos' => 522,
            'endTokenPos' => 65,
            'endFilePos' => 567,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 19,
        'endLine' => 19,
        'startColumn' => 5,
        'endColumn' => 74,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'description' => 
      array (
        'declaringClassName' => 'Modules\\Inventory\\Console\\ReleaseExpiredReservationsCommand',
        'implementingClassName' => 'Modules\\Inventory\\Console\\ReleaseExpiredReservationsCommand',
        'name' => 'description',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'Giải phóng giữ hàng đã hết hạn\'',
          'attributes' => 
          array (
            'startLine' => 21,
            'endLine' => 21,
            'startTokenPos' => 74,
            'startFilePos' => 600,
            'endTokenPos' => 74,
            'endFilePos' => 643,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 21,
        'endLine' => 21,
        'startColumn' => 5,
        'endColumn' => 74,
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
          'reservations' => 
          array (
            'name' => 'reservations',
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
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 23,
            'endLine' => 23,
            'startColumn' => 28,
            'endColumn' => 61,
            'parameterIndex' => 0,
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
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 23,
            'endLine' => 23,
            'startColumn' => 64,
            'endColumn' => 86,
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
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 23,
        'endLine' => 42,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Inventory\\Console',
        'declaringClassName' => 'Modules\\Inventory\\Console\\ReleaseExpiredReservationsCommand',
        'implementingClassName' => 'Modules\\Inventory\\Console\\ReleaseExpiredReservationsCommand',
        'currentClassName' => 'Modules\\Inventory\\Console\\ReleaseExpiredReservationsCommand',
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