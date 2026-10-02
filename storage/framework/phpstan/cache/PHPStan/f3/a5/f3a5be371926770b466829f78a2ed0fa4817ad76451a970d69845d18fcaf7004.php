<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Inventory/Persistence/Models/StockReservation.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Inventory\Persistence\Models\StockReservation
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-2a28bd88b492220ea81be0836b10f68691a24a6b1eae1bdefb4c9496edcfb248',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Inventory\\Persistence\\Models\\StockReservation',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Inventory/Persistence/Models/StockReservation.php',
      ),
    ),
    'namespace' => 'Modules\\Inventory\\Persistence\\Models',
    'name' => 'Modules\\Inventory\\Persistence\\Models\\StockReservation',
    'shortName' => 'StockReservation',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * @property int $id
 * @property string $reservation_key
 * @property int $location_id
 * @property int $variant_id
 * @property int $quantity
 * @property ReservationStatus $status
 * @property Carbon|null $expires_at
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 20,
    'endLine' => 28,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Illuminate\\Database\\Eloquent\\Model',
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
      'fillable' => 
      array (
        'declaringClassName' => 'Modules\\Inventory\\Persistence\\Models\\StockReservation',
        'implementingClassName' => 'Modules\\Inventory\\Persistence\\Models\\StockReservation',
        'name' => 'fillable',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'reservation_key\', \'location_id\', \'variant_id\', \'quantity\', \'status\', \'expires_at\', \'release_reason\']',
          'attributes' => 
          array (
            'startLine' => 22,
            'endLine' => 22,
            'startTokenPos' => 50,
            'startFilePos' => 501,
            'endTokenPos' => 70,
            'endFilePos' => 602,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 22,
        'endLine' => 22,
        'startColumn' => 5,
        'endColumn' => 129,
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
      'casts' => 
      array (
        'name' => 'casts',
        'parameters' => 
        array (
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
        'docComment' => NULL,
        'startLine' => 24,
        'endLine' => 27,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Modules\\Inventory\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Inventory\\Persistence\\Models\\StockReservation',
        'implementingClassName' => 'Modules\\Inventory\\Persistence\\Models\\StockReservation',
        'currentClassName' => 'Modules\\Inventory\\Persistence\\Models\\StockReservation',
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