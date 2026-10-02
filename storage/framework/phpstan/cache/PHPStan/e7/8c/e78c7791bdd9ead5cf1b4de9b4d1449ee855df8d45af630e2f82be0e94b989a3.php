<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Fulfillment/Application/Carriers/ManualCarrier.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Fulfillment\Application\Carriers\ManualCarrier
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-142aa46a847470c4a58e81238c025b388c654c6125c6f35c1ab718466012c4de',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Fulfillment\\Application\\Carriers\\ManualCarrier',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Fulfillment/Application/Carriers/ManualCarrier.php',
      ),
    ),
    'namespace' => 'Modules\\Fulfillment\\Application\\Carriers',
    'name' => 'Modules\\Fulfillment\\Application\\Carriers\\ManualCarrier',
    'shortName' => 'ManualCarrier',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Vận đơn thủ công: nhân viên đặt ở hãng bất kỳ (hoặc tự giao), nhập mã vận đơn và cập nhật trạng thái trong Admin.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 18,
    'endLine' => 47,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'Modules\\Fulfillment\\Contracts\\ShippingCarrier',
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
      'code' => 
      array (
        'name' => 'code',
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
        'startLine' => 20,
        'endLine' => 23,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Fulfillment\\Application\\Carriers',
        'declaringClassName' => 'Modules\\Fulfillment\\Application\\Carriers\\ManualCarrier',
        'implementingClassName' => 'Modules\\Fulfillment\\Application\\Carriers\\ManualCarrier',
        'currentClassName' => 'Modules\\Fulfillment\\Application\\Carriers\\ManualCarrier',
        'aliasName' => NULL,
      ),
      'label' => 
      array (
        'name' => 'label',
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
        'modifiers' => 1,
        'namespace' => 'Modules\\Fulfillment\\Application\\Carriers',
        'declaringClassName' => 'Modules\\Fulfillment\\Application\\Carriers\\ManualCarrier',
        'implementingClassName' => 'Modules\\Fulfillment\\Application\\Carriers\\ManualCarrier',
        'currentClassName' => 'Modules\\Fulfillment\\Application\\Carriers\\ManualCarrier',
        'aliasName' => NULL,
      ),
      'capabilities' => 
      array (
        'name' => 'capabilities',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Modules\\Fulfillment\\Contracts\\Data\\CarrierCapabilities',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 30,
        'endLine' => 33,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Fulfillment\\Application\\Carriers',
        'declaringClassName' => 'Modules\\Fulfillment\\Application\\Carriers\\ManualCarrier',
        'implementingClassName' => 'Modules\\Fulfillment\\Application\\Carriers\\ManualCarrier',
        'currentClassName' => 'Modules\\Fulfillment\\Application\\Carriers\\ManualCarrier',
        'aliasName' => NULL,
      ),
      'createShipment' => 
      array (
        'name' => 'createShipment',
        'parameters' => 
        array (
          'shipment' => 
          array (
            'name' => 'shipment',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Fulfillment\\Contracts\\Data\\ShipmentData',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 35,
            'endLine' => 35,
            'startColumn' => 36,
            'endColumn' => 57,
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
            'name' => 'Modules\\Fulfillment\\Contracts\\Data\\CarrierShipment',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 35,
        'endLine' => 39,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Fulfillment\\Application\\Carriers',
        'declaringClassName' => 'Modules\\Fulfillment\\Application\\Carriers\\ManualCarrier',
        'implementingClassName' => 'Modules\\Fulfillment\\Application\\Carriers\\ManualCarrier',
        'currentClassName' => 'Modules\\Fulfillment\\Application\\Carriers\\ManualCarrier',
        'aliasName' => NULL,
      ),
      'cancel' => 
      array (
        'name' => 'cancel',
        'parameters' => 
        array (
          'shipment' => 
          array (
            'name' => 'shipment',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Fulfillment\\Contracts\\Data\\ShipmentData',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 41,
            'endLine' => 41,
            'startColumn' => 28,
            'endColumn' => 49,
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
        'startLine' => 41,
        'endLine' => 41,
        'startColumn' => 5,
        'endColumn' => 59,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Fulfillment\\Application\\Carriers',
        'declaringClassName' => 'Modules\\Fulfillment\\Application\\Carriers\\ManualCarrier',
        'implementingClassName' => 'Modules\\Fulfillment\\Application\\Carriers\\ManualCarrier',
        'currentClassName' => 'Modules\\Fulfillment\\Application\\Carriers\\ManualCarrier',
        'aliasName' => NULL,
      ),
      'parseWebhook' => 
      array (
        'name' => 'parseWebhook',
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
                'name' => 'Illuminate\\Http\\Request',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 43,
            'endLine' => 43,
            'startColumn' => 34,
            'endColumn' => 49,
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
            'name' => 'Modules\\Fulfillment\\Contracts\\Data\\CarrierEvent',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 43,
        'endLine' => 46,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Fulfillment\\Application\\Carriers',
        'declaringClassName' => 'Modules\\Fulfillment\\Application\\Carriers\\ManualCarrier',
        'implementingClassName' => 'Modules\\Fulfillment\\Application\\Carriers\\ManualCarrier',
        'currentClassName' => 'Modules\\Fulfillment\\Application\\Carriers\\ManualCarrier',
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