<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Fulfillment/Testing/ShippingCarrierContract.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Fulfillment\Testing\ShippingCarrierContract
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-ef6b59b42b1601cfecf82bb7ab02eeea243b63536ebe10b536976486e90eaa5d',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Fulfillment\\Testing\\ShippingCarrierContract',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Fulfillment/Testing/ShippingCarrierContract.php',
      ),
    ),
    'namespace' => 'Modules\\Fulfillment\\Testing',
    'name' => 'Modules\\Fulfillment\\Testing\\ShippingCarrierContract',
    'shortName' => 'ShippingCarrierContract',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Bộ contract test cho mọi ShippingCarrier (Core và plugin hãng vận chuyển):
 *
 *   ShippingCarrierContract::define(\'vani.ghn\', fn () => new GhnCarrier(...),
 *       validWebhook: fn (string $tracking) => Request::create(...),
 *       tamperedWebhook: fn (string $tracking) => Request::create(...));
 *
 * Kiểm tra: mã hợp lệ; createShipment idempotent theo mã shipment; webhook đúng chữ ký → trạng thái chuẩn hoá
 * (một giá trị của ShipmentStatus) cho đúng mã vận đơn; webhook bị sửa → InvalidCarrierEvent; không hỗ trợ webhook → luôn từ chối.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 27,
    'endLine' => 82,
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
      'define' => 
      array (
        'name' => 'define',
        'parameters' => 
        array (
          'label' => 
          array (
            'name' => 'label',
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
            'startLine' => 34,
            'endLine' => 34,
            'startColumn' => 35,
            'endColumn' => 47,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'carrier' => 
          array (
            'name' => 'carrier',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Closure',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 34,
            'endLine' => 34,
            'startColumn' => 50,
            'endColumn' => 65,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'validWebhook' => 
          array (
            'name' => 'validWebhook',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 34,
                'endLine' => 34,
                'startTokenPos' => 97,
                'startFilePos' => 1416,
                'endTokenPos' => 97,
                'endFilePos' => 1419,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
              'data' => 
              array (
                'types' => 
                array (
                  0 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'Closure',
                      'isIdentifier' => false,
                    ),
                  ),
                  1 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'null',
                      'isIdentifier' => true,
                    ),
                  ),
                ),
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 34,
            'endLine' => 34,
            'startColumn' => 68,
            'endColumn' => 96,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
          'tamperedWebhook' => 
          array (
            'name' => 'tamperedWebhook',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 34,
                'endLine' => 34,
                'startTokenPos' => 107,
                'startFilePos' => 1450,
                'endTokenPos' => 107,
                'endFilePos' => 1453,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
              'data' => 
              array (
                'types' => 
                array (
                  0 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'Closure',
                      'isIdentifier' => false,
                    ),
                  ),
                  1 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'null',
                      'isIdentifier' => true,
                    ),
                  ),
                ),
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 34,
            'endLine' => 34,
            'startColumn' => 99,
            'endColumn' => 130,
            'parameterIndex' => 3,
            'isOptional' => true,
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
 * @param  Closure(): ShippingCarrier  $carrier
 * @param  (Closure(string): Request)|null  $validWebhook
 * @param  (Closure(string): Request)|null  $tamperedWebhook
 */',
        'startLine' => 34,
        'endLine' => 81,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Modules\\Fulfillment\\Testing',
        'declaringClassName' => 'Modules\\Fulfillment\\Testing\\ShippingCarrierContract',
        'implementingClassName' => 'Modules\\Fulfillment\\Testing\\ShippingCarrierContract',
        'currentClassName' => 'Modules\\Fulfillment\\Testing\\ShippingCarrierContract',
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