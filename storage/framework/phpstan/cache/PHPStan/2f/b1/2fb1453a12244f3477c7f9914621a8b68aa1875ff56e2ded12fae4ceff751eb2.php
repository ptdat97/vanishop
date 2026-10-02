<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Fulfillment/Application/BookShipmentJob.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Fulfillment\Application\BookShipmentJob
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-aa462dbabbdf3366ebcae127203f2ef332cf508318740a11a1e13a723dfa3b15',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Fulfillment\\Application\\BookShipmentJob',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Fulfillment/Application/BookShipmentJob.php',
      ),
    ),
    'namespace' => 'Modules\\Fulfillment\\Application',
    'name' => 'Modules\\Fulfillment\\Application\\BookShipmentJob',
    'shortName' => 'BookShipmentJob',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Đặt vận đơn qua API hãng sau commit. Idempotent: shipment đã có mã thì bỏ qua.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 17,
    'endLine' => 35,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'Illuminate\\Contracts\\Queue\\ShouldQueue',
    ),
    'traitClassNames' => 
    array (
      0 => 'Illuminate\\Foundation\\Bus\\Dispatchable',
      1 => 'Illuminate\\Queue\\InteractsWithQueue',
      2 => 'Illuminate\\Bus\\Queueable',
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
      'tries' => 
      array (
        'declaringClassName' => 'Modules\\Fulfillment\\Application\\BookShipmentJob',
        'implementingClassName' => 'Modules\\Fulfillment\\Application\\BookShipmentJob',
        'name' => 'tries',
        'modifiers' => 1,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'default' => 
        array (
          'code' => '3',
          'attributes' => 
          array (
            'startLine' => 21,
            'endLine' => 21,
            'startTokenPos' => 78,
            'startFilePos' => 557,
            'endTokenPos' => 78,
            'endFilePos' => 557,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 21,
        'endLine' => 21,
        'startColumn' => 5,
        'endColumn' => 26,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'backoff' => 
      array (
        'declaringClassName' => 'Modules\\Fulfillment\\Application\\BookShipmentJob',
        'implementingClassName' => 'Modules\\Fulfillment\\Application\\BookShipmentJob',
        'name' => 'backoff',
        'modifiers' => 1,
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
          'code' => '[30, 120, 600]',
          'attributes' => 
          array (
            'startLine' => 24,
            'endLine' => 24,
            'startTokenPos' => 91,
            'startFilePos' => 615,
            'endTokenPos' => 99,
            'endFilePos' => 628,
          ),
        ),
        'docComment' => '/** @var list<int> */',
        'attributes' => 
        array (
        ),
        'startLine' => 24,
        'endLine' => 24,
        'startColumn' => 5,
        'endColumn' => 43,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'shipmentId' => 
      array (
        'declaringClassName' => 'Modules\\Fulfillment\\Application\\BookShipmentJob',
        'implementingClassName' => 'Modules\\Fulfillment\\Application\\BookShipmentJob',
        'name' => 'shipmentId',
        'modifiers' => 2177,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 26,
        'endLine' => 26,
        'startColumn' => 33,
        'endColumn' => 63,
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
          'shipmentId' => 
          array (
            'name' => 'shipmentId',
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 26,
            'endLine' => 26,
            'startColumn' => 33,
            'endColumn' => 63,
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
        'startLine' => 26,
        'endLine' => 29,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Fulfillment\\Application',
        'declaringClassName' => 'Modules\\Fulfillment\\Application\\BookShipmentJob',
        'implementingClassName' => 'Modules\\Fulfillment\\Application\\BookShipmentJob',
        'currentClassName' => 'Modules\\Fulfillment\\Application\\BookShipmentJob',
        'aliasName' => NULL,
      ),
      'handle' => 
      array (
        'name' => 'handle',
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
            'startLine' => 31,
            'endLine' => 31,
            'startColumn' => 28,
            'endColumn' => 50,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'fulfillment' => 
          array (
            'name' => 'fulfillment',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Fulfillment\\Application\\FulfillmentService',
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
            'startColumn' => 53,
            'endColumn' => 83,
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
        'docComment' => NULL,
        'startLine' => 31,
        'endLine' => 34,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Fulfillment\\Application',
        'declaringClassName' => 'Modules\\Fulfillment\\Application\\BookShipmentJob',
        'implementingClassName' => 'Modules\\Fulfillment\\Application\\BookShipmentJob',
        'currentClassName' => 'Modules\\Fulfillment\\Application\\BookShipmentJob',
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