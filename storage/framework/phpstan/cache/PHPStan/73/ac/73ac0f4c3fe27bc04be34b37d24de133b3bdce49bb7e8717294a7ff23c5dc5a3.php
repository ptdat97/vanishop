<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Fulfillment/Persistence/Models/Shipment.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Fulfillment\Persistence\Models\Shipment
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-26d5de998d412f079b37e7138161a738a0f29f72f611c81294faa4e235f7fbaa',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Fulfillment\\Persistence\\Models\\Shipment',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Fulfillment/Persistence/Models/Shipment.php',
      ),
    ),
    'namespace' => 'Modules\\Fulfillment\\Persistence\\Models',
    'name' => 'Modules\\Fulfillment\\Persistence\\Models\\Shipment',
    'shortName' => 'Shipment',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * @property int $id
 * @property string $public_id
 * @property int $order_id
 * @property int $location_id
 * @property string $carrier_code
 * @property string|null $service_code
 * @property string|null $tracking_number
 * @property int $cod_amount
 * @property string $currency_code
 * @property ShipmentStatus $status
 * @property int $booking_attempts
 * @property int $lock_version
 * @property CarbonImmutable|null $delivered_at
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 27,
    'endLine' => 46,
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
      'guarded' => 
      array (
        'declaringClassName' => 'Modules\\Fulfillment\\Persistence\\Models\\Shipment',
        'implementingClassName' => 'Modules\\Fulfillment\\Persistence\\Models\\Shipment',
        'name' => 'guarded',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'id\']',
          'attributes' => 
          array (
            'startLine' => 29,
            'endLine' => 29,
            'startTokenPos' => 55,
            'startFilePos' => 760,
            'endTokenPos' => 57,
            'endFilePos' => 765,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 29,
        'endLine' => 29,
        'startColumn' => 5,
        'endColumn' => 32,
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
        'startLine' => 31,
        'endLine' => 37,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Modules\\Fulfillment\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Fulfillment\\Persistence\\Models\\Shipment',
        'implementingClassName' => 'Modules\\Fulfillment\\Persistence\\Models\\Shipment',
        'currentClassName' => 'Modules\\Fulfillment\\Persistence\\Models\\Shipment',
        'aliasName' => NULL,
      ),
      'lines' => 
      array (
        'name' => 'lines',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Illuminate\\Database\\Eloquent\\Relations\\HasMany',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return HasMany<ShipmentLine, $this>
 */',
        'startLine' => 42,
        'endLine' => 45,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Fulfillment\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Fulfillment\\Persistence\\Models\\Shipment',
        'implementingClassName' => 'Modules\\Fulfillment\\Persistence\\Models\\Shipment',
        'currentClassName' => 'Modules\\Fulfillment\\Persistence\\Models\\Shipment',
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