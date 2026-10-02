<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Fulfillment/Domain/FulfillmentProgress.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Fulfillment\Domain\FulfillmentProgress
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-0fc338519cf6761c52990ca89431c6ec574920a8d2ed3e5f8b4e3c45fa29fd86',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Fulfillment\\Domain\\FulfillmentProgress',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Fulfillment/Domain/FulfillmentProgress.php',
      ),
    ),
    'namespace' => 'Modules\\Fulfillment\\Domain',
    'name' => 'Modules\\Fulfillment\\Domain\\FulfillmentProgress',
    'shortName' => 'FulfillmentProgress',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Tổng hợp orders.fulfillment_status từ các shipment của đơn.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 10,
    'endLine' => 45,
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
      'orderStatus' => 
      array (
        'name' => 'orderStatus',
        'parameters' => 
        array (
          'statuses' => 
          array (
            'name' => 'statuses',
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
            'startLine' => 15,
            'endLine' => 15,
            'startColumn' => 40,
            'endColumn' => 54,
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
 * @param  list<ShipmentStatus>  $statuses
 */',
        'startLine' => 15,
        'endLine' => 32,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Modules\\Fulfillment\\Domain',
        'declaringClassName' => 'Modules\\Fulfillment\\Domain\\FulfillmentProgress',
        'implementingClassName' => 'Modules\\Fulfillment\\Domain\\FulfillmentProgress',
        'currentClassName' => 'Modules\\Fulfillment\\Domain\\FulfillmentProgress',
        'aliasName' => NULL,
      ),
      'allLeftWarehouse' => 
      array (
        'name' => 'allLeftWarehouse',
        'parameters' => 
        array (
          'statuses' => 
          array (
            'name' => 'statuses',
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
            'startLine' => 39,
            'endLine' => 39,
            'startColumn' => 45,
            'endColumn' => 59,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Mọi shipment còn hiệu lực đã rời kho → commit giữ hàng (trừ on_hand).
 *
 * @param  list<ShipmentStatus>  $statuses
 */',
        'startLine' => 39,
        'endLine' => 44,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Modules\\Fulfillment\\Domain',
        'declaringClassName' => 'Modules\\Fulfillment\\Domain\\FulfillmentProgress',
        'implementingClassName' => 'Modules\\Fulfillment\\Domain\\FulfillmentProgress',
        'currentClassName' => 'Modules\\Fulfillment\\Domain\\FulfillmentProgress',
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