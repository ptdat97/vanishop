<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Storefront/Http/Controllers/Web/TrackOrderController.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Storefront\Http\Controllers\Web\TrackOrderController
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-9c784ed2768c8701615a5c034347dcfa92efbe978b7c466640ab4c79917ba75e',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Storefront\\Http\\Controllers\\Web\\TrackOrderController',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Storefront/Http/Controllers/Web/TrackOrderController.php',
      ),
    ),
    'namespace' => 'Modules\\Storefront\\Http\\Controllers\\Web',
    'name' => 'Modules\\Storefront\\Http\\Controllers\\Web\\TrackOrderController',
    'shortName' => 'TrackOrderController',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Tra cứu đơn cho khách vãng lai: số đơn + SĐT → thông tin đơn đã che (như GET /orders/track của API).
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 15,
    'endLine' => 32,
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
      'show' => 
      array (
        'name' => 'show',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Illuminate\\Contracts\\View\\View',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 17,
        'endLine' => 20,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Storefront\\Http\\Controllers\\Web',
        'declaringClassName' => 'Modules\\Storefront\\Http\\Controllers\\Web\\TrackOrderController',
        'implementingClassName' => 'Modules\\Storefront\\Http\\Controllers\\Web\\TrackOrderController',
        'currentClassName' => 'Modules\\Storefront\\Http\\Controllers\\Web\\TrackOrderController',
        'aliasName' => NULL,
      ),
      'search' => 
      array (
        'name' => 'search',
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
            'startLine' => 22,
            'endLine' => 22,
            'startColumn' => 28,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'orders' => 
          array (
            'name' => 'orders',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Ordering\\Contracts\\CustomerOrders',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 22,
            'endLine' => 22,
            'startColumn' => 46,
            'endColumn' => 67,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'presenter' => 
          array (
            'name' => 'presenter',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Storefront\\Application\\OrderPresenter',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 22,
            'endLine' => 22,
            'startColumn' => 70,
            'endColumn' => 94,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Illuminate\\Contracts\\View\\View',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 22,
        'endLine' => 31,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Storefront\\Http\\Controllers\\Web',
        'declaringClassName' => 'Modules\\Storefront\\Http\\Controllers\\Web\\TrackOrderController',
        'implementingClassName' => 'Modules\\Storefront\\Http\\Controllers\\Web\\TrackOrderController',
        'currentClassName' => 'Modules\\Storefront\\Http\\Controllers\\Web\\TrackOrderController',
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