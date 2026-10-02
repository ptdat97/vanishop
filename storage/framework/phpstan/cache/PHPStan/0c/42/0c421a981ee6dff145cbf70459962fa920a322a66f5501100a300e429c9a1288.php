<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Fulfillment/Application/Listeners/OrderShipmentPanel.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Fulfillment\Application\Listeners\OrderShipmentPanel
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-ff6cf95c23e97fcda0f1953eae6fbbd5b65710a7ce7ce87035373e3e98565432',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Fulfillment\\Application\\Listeners\\OrderShipmentPanel',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Fulfillment/Application/Listeners/OrderShipmentPanel.php',
      ),
    ),
    'namespace' => 'Modules\\Fulfillment\\Application\\Listeners',
    'name' => 'Modules\\Fulfillment\\Application\\Listeners\\OrderShipmentPanel',
    'shortName' => 'OrderShipmentPanel',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Panel "Giao hàng" trên trang đơn Admin (slot vani.admin.order.sidebar).
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 13,
    'endLine' => 33,
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
      'shipments' => 
      array (
        'declaringClassName' => 'Modules\\Fulfillment\\Application\\Listeners\\OrderShipmentPanel',
        'implementingClassName' => 'Modules\\Fulfillment\\Application\\Listeners\\OrderShipmentPanel',
        'name' => 'shipments',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Modules\\Fulfillment\\Contracts\\ShipmentReader',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 15,
        'endLine' => 15,
        'startColumn' => 33,
        'endColumn' => 74,
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
          'shipments' => 
          array (
            'name' => 'shipments',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Fulfillment\\Contracts\\ShipmentReader',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 15,
            'endLine' => 15,
            'startColumn' => 33,
            'endColumn' => 74,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 15,
        'endLine' => 15,
        'startColumn' => 5,
        'endColumn' => 78,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Fulfillment\\Application\\Listeners',
        'declaringClassName' => 'Modules\\Fulfillment\\Application\\Listeners\\OrderShipmentPanel',
        'implementingClassName' => 'Modules\\Fulfillment\\Application\\Listeners\\OrderShipmentPanel',
        'currentClassName' => 'Modules\\Fulfillment\\Application\\Listeners\\OrderShipmentPanel',
        'aliasName' => NULL,
      ),
      '__invoke' => 
      array (
        'name' => '__invoke',
        'parameters' => 
        array (
          'order' => 
          array (
            'name' => 'order',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Ordering\\Contracts\\Data\\OrderDetail',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 20,
            'endLine' => 20,
            'startColumn' => 30,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return array{title: string, rows: list<array{label: string, value: string}>, link: array{label: string, url: string}}
 */',
        'startLine' => 20,
        'endLine' => 32,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Fulfillment\\Application\\Listeners',
        'declaringClassName' => 'Modules\\Fulfillment\\Application\\Listeners\\OrderShipmentPanel',
        'implementingClassName' => 'Modules\\Fulfillment\\Application\\Listeners\\OrderShipmentPanel',
        'currentClassName' => 'Modules\\Fulfillment\\Application\\Listeners\\OrderShipmentPanel',
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