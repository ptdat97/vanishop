<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/custom/plugin/Ghn/Infrastructure/GhnCarrier.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Plugin\Ghn\Infrastructure\GhnCarrier
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-cc4d3caf866e4cddf1aac372e022f891556a2c64888889b901577b38ed5614f4',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Plugin\\Ghn\\Infrastructure\\GhnCarrier',
        'filename' => '/Users/dat/Ecommerce/vanishop/custom/plugin/Ghn/Infrastructure/GhnCarrier.php',
      ),
    ),
    'namespace' => 'Plugin\\Ghn\\Infrastructure',
    'name' => 'Plugin\\Ghn\\Infrastructure\\GhnCarrier',
    'shortName' => 'GhnCarrier',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Tích hợp đối tác vận chuyển Giao Hàng Nhanh (GHN).
 * Thực thi ShippingCarrier (tạo đơn, webhook cập nhật hành trình)
 * và ShippingRateProvider (báo giá cước GHN ở checkout).
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 25,
    'endLine' => 151,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'Modules\\Fulfillment\\Contracts\\ShippingCarrier',
      1 => 'Modules\\Checkout\\Contracts\\ShippingRateProvider',
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
      'CODE' => 
      array (
        'declaringClassName' => 'Plugin\\Ghn\\Infrastructure\\GhnCarrier',
        'implementingClassName' => 'Plugin\\Ghn\\Infrastructure\\GhnCarrier',
        'name' => 'CODE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'ghn\'',
          'attributes' => 
          array (
            'startLine' => 27,
            'endLine' => 27,
            'startTokenPos' => 100,
            'startFilePos' => 964,
            'endTokenPos' => 100,
            'endFilePos' => 968,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 27,
        'endLine' => 27,
        'startColumn' => 5,
        'endColumn' => 30,
      ),
    ),
    'immediateProperties' => 
    array (
      'mockOrders' => 
      array (
        'declaringClassName' => 'Plugin\\Ghn\\Infrastructure\\GhnCarrier',
        'implementingClassName' => 'Plugin\\Ghn\\Infrastructure\\GhnCarrier',
        'name' => 'mockOrders',
        'modifiers' => 17,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'default' => 
        array (
          'code' => '[]',
          'attributes' => 
          array (
            'startLine' => 30,
            'endLine' => 30,
            'startTokenPos' => 115,
            'startFilePos' => 1078,
            'endTokenPos' => 116,
            'endFilePos' => 1079,
          ),
        ),
        'docComment' => '/** @var array<string, string> publicId => GHN tracking code */',
        'attributes' => 
        array (
        ),
        'startLine' => 30,
        'endLine' => 30,
        'startColumn' => 5,
        'endColumn' => 41,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'token' => 
      array (
        'declaringClassName' => 'Plugin\\Ghn\\Infrastructure\\GhnCarrier',
        'implementingClassName' => 'Plugin\\Ghn\\Infrastructure\\GhnCarrier',
        'name' => 'token',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'default' => 
        array (
          'code' => '\'test-ghn-token\'',
          'attributes' => 
          array (
            'startLine' => 33,
            'endLine' => 33,
            'startTokenPos' => 136,
            'startFilePos' => 1157,
            'endTokenPos' => 136,
            'endFilePos' => 1172,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 33,
        'endLine' => 33,
        'startColumn' => 9,
        'endColumn' => 57,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'shopId' => 
      array (
        'declaringClassName' => 'Plugin\\Ghn\\Infrastructure\\GhnCarrier',
        'implementingClassName' => 'Plugin\\Ghn\\Infrastructure\\GhnCarrier',
        'name' => 'shopId',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'default' => 
        array (
          'code' => '\'123456\'',
          'attributes' => 
          array (
            'startLine' => 34,
            'endLine' => 34,
            'startTokenPos' => 149,
            'startFilePos' => 1217,
            'endTokenPos' => 149,
            'endFilePos' => 1224,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 34,
        'endLine' => 34,
        'startColumn' => 9,
        'endColumn' => 50,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'webhookSecret' => 
      array (
        'declaringClassName' => 'Plugin\\Ghn\\Infrastructure\\GhnCarrier',
        'implementingClassName' => 'Plugin\\Ghn\\Infrastructure\\GhnCarrier',
        'name' => 'webhookSecret',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'default' => 
        array (
          'code' => '\'ghn-webhook-secret\'',
          'attributes' => 
          array (
            'startLine' => 35,
            'endLine' => 35,
            'startTokenPos' => 162,
            'startFilePos' => 1276,
            'endTokenPos' => 162,
            'endFilePos' => 1295,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 35,
        'endLine' => 35,
        'startColumn' => 9,
        'endColumn' => 69,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'services' => 
      array (
        'declaringClassName' => 'Plugin\\Ghn\\Infrastructure\\GhnCarrier',
        'implementingClassName' => 'Plugin\\Ghn\\Infrastructure\\GhnCarrier',
        'name' => 'services',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'default' => 
        array (
          'code' => '[]',
          'attributes' => 
          array (
            'startLine' => 37,
            'endLine' => 37,
            'startTokenPos' => 177,
            'startFilePos' => 1407,
            'endTokenPos' => 178,
            'endFilePos' => 1408,
          ),
        ),
        'docComment' => '/** @var array<string, array{label: string, fee: int}> */',
        'attributes' => 
        array (
        ),
        'startLine' => 37,
        'endLine' => 37,
        'startColumn' => 9,
        'endColumn' => 45,
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
          'token' => 
          array (
            'name' => 'token',
            'default' => 
            array (
              'code' => '\'test-ghn-token\'',
              'attributes' => 
              array (
                'startLine' => 33,
                'endLine' => 33,
                'startTokenPos' => 136,
                'startFilePos' => 1157,
                'endTokenPos' => 136,
                'endFilePos' => 1172,
              ),
            ),
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
            'startLine' => 33,
            'endLine' => 33,
            'startColumn' => 9,
            'endColumn' => 57,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
          'shopId' => 
          array (
            'name' => 'shopId',
            'default' => 
            array (
              'code' => '\'123456\'',
              'attributes' => 
              array (
                'startLine' => 34,
                'endLine' => 34,
                'startTokenPos' => 149,
                'startFilePos' => 1217,
                'endTokenPos' => 149,
                'endFilePos' => 1224,
              ),
            ),
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
            'startLine' => 34,
            'endLine' => 34,
            'startColumn' => 9,
            'endColumn' => 50,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'webhookSecret' => 
          array (
            'name' => 'webhookSecret',
            'default' => 
            array (
              'code' => '\'ghn-webhook-secret\'',
              'attributes' => 
              array (
                'startLine' => 35,
                'endLine' => 35,
                'startTokenPos' => 162,
                'startFilePos' => 1276,
                'endTokenPos' => 162,
                'endFilePos' => 1295,
              ),
            ),
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
            'startLine' => 35,
            'endLine' => 35,
            'startColumn' => 9,
            'endColumn' => 69,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
          'services' => 
          array (
            'name' => 'services',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 37,
                'endLine' => 37,
                'startTokenPos' => 177,
                'startFilePos' => 1407,
                'endTokenPos' => 178,
                'endFilePos' => 1408,
              ),
            ),
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 37,
            'endLine' => 37,
            'startColumn' => 9,
            'endColumn' => 45,
            'parameterIndex' => 3,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 32,
        'endLine' => 38,
        'startColumn' => 5,
        'endColumn' => 8,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Plugin\\Ghn\\Infrastructure',
        'declaringClassName' => 'Plugin\\Ghn\\Infrastructure\\GhnCarrier',
        'implementingClassName' => 'Plugin\\Ghn\\Infrastructure\\GhnCarrier',
        'currentClassName' => 'Plugin\\Ghn\\Infrastructure\\GhnCarrier',
        'aliasName' => NULL,
      ),
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
        'startLine' => 40,
        'endLine' => 43,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Plugin\\Ghn\\Infrastructure',
        'declaringClassName' => 'Plugin\\Ghn\\Infrastructure\\GhnCarrier',
        'implementingClassName' => 'Plugin\\Ghn\\Infrastructure\\GhnCarrier',
        'currentClassName' => 'Plugin\\Ghn\\Infrastructure\\GhnCarrier',
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
        'startLine' => 45,
        'endLine' => 48,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Plugin\\Ghn\\Infrastructure',
        'declaringClassName' => 'Plugin\\Ghn\\Infrastructure\\GhnCarrier',
        'implementingClassName' => 'Plugin\\Ghn\\Infrastructure\\GhnCarrier',
        'currentClassName' => 'Plugin\\Ghn\\Infrastructure\\GhnCarrier',
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
        'startLine' => 50,
        'endLine' => 57,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Plugin\\Ghn\\Infrastructure',
        'declaringClassName' => 'Plugin\\Ghn\\Infrastructure\\GhnCarrier',
        'implementingClassName' => 'Plugin\\Ghn\\Infrastructure\\GhnCarrier',
        'currentClassName' => 'Plugin\\Ghn\\Infrastructure\\GhnCarrier',
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
            'startLine' => 59,
            'endLine' => 59,
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
        'startLine' => 59,
        'endLine' => 70,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Plugin\\Ghn\\Infrastructure',
        'declaringClassName' => 'Plugin\\Ghn\\Infrastructure\\GhnCarrier',
        'implementingClassName' => 'Plugin\\Ghn\\Infrastructure\\GhnCarrier',
        'currentClassName' => 'Plugin\\Ghn\\Infrastructure\\GhnCarrier',
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
            'startLine' => 72,
            'endLine' => 72,
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
        'startLine' => 72,
        'endLine' => 75,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Plugin\\Ghn\\Infrastructure',
        'declaringClassName' => 'Plugin\\Ghn\\Infrastructure\\GhnCarrier',
        'implementingClassName' => 'Plugin\\Ghn\\Infrastructure\\GhnCarrier',
        'currentClassName' => 'Plugin\\Ghn\\Infrastructure\\GhnCarrier',
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
            'startLine' => 77,
            'endLine' => 77,
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
        'startLine' => 77,
        'endLine' => 105,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Plugin\\Ghn\\Infrastructure',
        'declaringClassName' => 'Plugin\\Ghn\\Infrastructure\\GhnCarrier',
        'implementingClassName' => 'Plugin\\Ghn\\Infrastructure\\GhnCarrier',
        'currentClassName' => 'Plugin\\Ghn\\Infrastructure\\GhnCarrier',
        'aliasName' => NULL,
      ),
      'options' => 
      array (
        'name' => 'options',
        'parameters' => 
        array (
          'context' => 
          array (
            'name' => 'context',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Checkout\\Contracts\\Data\\TotalsContext',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 112,
            'endLine' => 112,
            'startColumn' => 29,
            'endColumn' => 50,
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
 * ShippingRateProvider: tính phí vận chuyển GHN tại checkout.
 *
 * @return list<ShippingOption>
 */',
        'startLine' => 112,
        'endLine' => 129,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Plugin\\Ghn\\Infrastructure',
        'declaringClassName' => 'Plugin\\Ghn\\Infrastructure\\GhnCarrier',
        'implementingClassName' => 'Plugin\\Ghn\\Infrastructure\\GhnCarrier',
        'currentClassName' => 'Plugin\\Ghn\\Infrastructure\\GhnCarrier',
        'aliasName' => NULL,
      ),
      'mapGhnStatus' => 
      array (
        'name' => 'mapGhnStatus',
        'parameters' => 
        array (
          'status' => 
          array (
            'name' => 'status',
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
            'startLine' => 136,
            'endLine' => 136,
            'startColumn' => 34,
            'endColumn' => 47,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Map trạng thái vận đơn GHN sang giá trị chuẩn của `ShipmentStatus` trong Core
 * (created, picked_up, in_transit, out_for_delivery, failed_attempt, delivered, returning, returned, cancelled).
 * Plugin chỉ dùng giá trị chuẩn dạng chuỗi để không phụ thuộc tầng Domain của Core (rule R5).
 */',
        'startLine' => 136,
        'endLine' => 150,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Plugin\\Ghn\\Infrastructure',
        'declaringClassName' => 'Plugin\\Ghn\\Infrastructure\\GhnCarrier',
        'implementingClassName' => 'Plugin\\Ghn\\Infrastructure\\GhnCarrier',
        'currentClassName' => 'Plugin\\Ghn\\Infrastructure\\GhnCarrier',
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