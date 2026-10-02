<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Checkout/Contracts/ShippingRateProvider.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Checkout\Contracts\ShippingRateProvider
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-387e0f1dfb5f2ba9510b9241b6e321fa16e5f639137df9c9044c7ae6d82211a7',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Checkout\\Contracts\\ShippingRateProvider',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Checkout/Contracts/ShippingRateProvider.php',
      ),
    ),
    'namespace' => 'Modules\\Checkout\\Contracts',
    'name' => 'Modules\\Checkout\\Contracts\\ShippingRateProvider',
    'shortName' => 'ShippingRateProvider',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Nguồn phương thức giao + phí cho checkout (tag `vani.checkout.shipping_providers`).
 * Bắt buộc ≥ 1 đang bật (ADR-029). Phí cố định là plugin hệ thống `vani.shipping-flat-rate`; carrier (GHN…) là plugin.
 * Không gọi mạng đồng bộ không có timeout/cache.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 15,
    'endLine' => 23,
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
      'TAG' => 
      array (
        'declaringClassName' => 'Modules\\Checkout\\Contracts\\ShippingRateProvider',
        'implementingClassName' => 'Modules\\Checkout\\Contracts\\ShippingRateProvider',
        'name' => 'TAG',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'vani.checkout.shipping_providers\'',
          'attributes' => 
          array (
            'startLine' => 17,
            'endLine' => 17,
            'startTokenPos' => 41,
            'startFilePos' => 539,
            'endTokenPos' => 41,
            'endFilePos' => 572,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 17,
        'endLine' => 17,
        'startColumn' => 5,
        'endColumn' => 58,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
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
            'startLine' => 22,
            'endLine' => 22,
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
 * @return list<ShippingOption>
 */',
        'startLine' => 22,
        'endLine' => 22,
        'startColumn' => 5,
        'endColumn' => 59,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Checkout\\Contracts',
        'declaringClassName' => 'Modules\\Checkout\\Contracts\\ShippingRateProvider',
        'implementingClassName' => 'Modules\\Checkout\\Contracts\\ShippingRateProvider',
        'currentClassName' => 'Modules\\Checkout\\Contracts\\ShippingRateProvider',
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