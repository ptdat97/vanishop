<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Fulfillment/Contracts/ShippingCarrier.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Fulfillment\Contracts\ShippingCarrier
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-ba93c6bd5a8d583f51a9e8eece7d7084462374f822eeaa2320ea44c9d400d6bb',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Fulfillment\\Contracts\\ShippingCarrier',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Fulfillment/Contracts/ShippingCarrier.php',
      ),
    ),
    'namespace' => 'Modules\\Fulfillment\\Contracts',
    'name' => 'Modules\\Fulfillment\\Contracts\\ShippingCarrier',
    'shortName' => 'ShippingCarrier',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Extension point (tag `vani.shipping.carriers`). Core: `manual`. Plugin: GHN, GHTK, Viettel Post…
 * Phí giao ở checkout là `ShippingRateProvider` của Checkout (plugin hãng thường cài cả hai).
 * Bộ contract test: Modules\\Fulfillment\\Testing\\ShippingCarrierContract.
 *
 * - createShipment() chạy trong job SAU commit, idempotent theo $shipment->publicId.
 * - parseWebhook() xác minh + chuẩn hoá; Core ghi nhận (khử trùng, không cho lùi trạng thái).
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 21,
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
      'CARRIERS_TAG' => 
      array (
        'declaringClassName' => 'Modules\\Fulfillment\\Contracts\\ShippingCarrier',
        'implementingClassName' => 'Modules\\Fulfillment\\Contracts\\ShippingCarrier',
        'name' => 'CARRIERS_TAG',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'vani.shipping.carriers\'',
          'attributes' => 
          array (
            'startLine' => 23,
            'endLine' => 23,
            'startTokenPos' => 56,
            'startFilePos' => 881,
            'endTokenPos' => 56,
            'endFilePos' => 904,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 23,
        'endLine' => 23,
        'startColumn' => 5,
        'endColumn' => 57,
      ),
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
        'startLine' => 25,
        'endLine' => 25,
        'startColumn' => 5,
        'endColumn' => 35,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Fulfillment\\Contracts',
        'declaringClassName' => 'Modules\\Fulfillment\\Contracts\\ShippingCarrier',
        'implementingClassName' => 'Modules\\Fulfillment\\Contracts\\ShippingCarrier',
        'currentClassName' => 'Modules\\Fulfillment\\Contracts\\ShippingCarrier',
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
        'startLine' => 27,
        'endLine' => 27,
        'startColumn' => 5,
        'endColumn' => 36,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Fulfillment\\Contracts',
        'declaringClassName' => 'Modules\\Fulfillment\\Contracts\\ShippingCarrier',
        'implementingClassName' => 'Modules\\Fulfillment\\Contracts\\ShippingCarrier',
        'currentClassName' => 'Modules\\Fulfillment\\Contracts\\ShippingCarrier',
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
        'startLine' => 29,
        'endLine' => 29,
        'startColumn' => 5,
        'endColumn' => 56,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Fulfillment\\Contracts',
        'declaringClassName' => 'Modules\\Fulfillment\\Contracts\\ShippingCarrier',
        'implementingClassName' => 'Modules\\Fulfillment\\Contracts\\ShippingCarrier',
        'currentClassName' => 'Modules\\Fulfillment\\Contracts\\ShippingCarrier',
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
            'startLine' => 31,
            'endLine' => 31,
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
        'startLine' => 31,
        'endLine' => 31,
        'startColumn' => 5,
        'endColumn' => 76,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Fulfillment\\Contracts',
        'declaringClassName' => 'Modules\\Fulfillment\\Contracts\\ShippingCarrier',
        'implementingClassName' => 'Modules\\Fulfillment\\Contracts\\ShippingCarrier',
        'currentClassName' => 'Modules\\Fulfillment\\Contracts\\ShippingCarrier',
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
            'startLine' => 33,
            'endLine' => 33,
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
        'startLine' => 33,
        'endLine' => 33,
        'startColumn' => 5,
        'endColumn' => 57,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Fulfillment\\Contracts',
        'declaringClassName' => 'Modules\\Fulfillment\\Contracts\\ShippingCarrier',
        'implementingClassName' => 'Modules\\Fulfillment\\Contracts\\ShippingCarrier',
        'currentClassName' => 'Modules\\Fulfillment\\Contracts\\ShippingCarrier',
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
            'startLine' => 38,
            'endLine' => 38,
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
        'docComment' => '/**
 * @throws InvalidCarrierEvent
 */',
        'startLine' => 38,
        'endLine' => 38,
        'startColumn' => 5,
        'endColumn' => 65,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Fulfillment\\Contracts',
        'declaringClassName' => 'Modules\\Fulfillment\\Contracts\\ShippingCarrier',
        'implementingClassName' => 'Modules\\Fulfillment\\Contracts\\ShippingCarrier',
        'currentClassName' => 'Modules\\Fulfillment\\Contracts\\ShippingCarrier',
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