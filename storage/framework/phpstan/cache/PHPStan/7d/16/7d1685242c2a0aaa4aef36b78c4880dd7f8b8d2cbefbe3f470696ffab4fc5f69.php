<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Checkout/Contracts/CheckoutValidator.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Checkout\Contracts\CheckoutValidator
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-f82a5828830fd22bcba37f8ad06d37ab58505cddd77c2f93e0ae54a058e818e4',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Checkout\\Contracts\\CheckoutValidator',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Checkout/Contracts/CheckoutValidator.php',
      ),
    ),
    'namespace' => 'Modules\\Checkout\\Contracts',
    'name' => 'Modules\\Checkout\\Contracts\\CheckoutValidator',
    'shortName' => 'CheckoutValidator',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Extension point (tag `vani.checkout.validators`). Chạy trong transaction PlaceOrder, chỉ đọc.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 14,
    'endLine' => 25,
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
        'declaringClassName' => 'Modules\\Checkout\\Contracts\\CheckoutValidator',
        'implementingClassName' => 'Modules\\Checkout\\Contracts\\CheckoutValidator',
        'name' => 'TAG',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'vani.checkout.validators\'',
          'attributes' => 
          array (
            'startLine' => 17,
            'endLine' => 17,
            'startTokenPos' => 48,
            'startFilePos' => 484,
            'endTokenPos' => 48,
            'endFilePos' => 509,
          ),
        ),
        'docComment' => '/** Tag extension point: plugin đóng góp qua `contribute(CheckoutValidator::TAG, …)`. */',
        'attributes' => 
        array (
        ),
        'startLine' => 17,
        'endLine' => 17,
        'startColumn' => 5,
        'endColumn' => 50,
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
        'startLine' => 19,
        'endLine' => 19,
        'startColumn' => 5,
        'endColumn' => 35,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Checkout\\Contracts',
        'declaringClassName' => 'Modules\\Checkout\\Contracts\\CheckoutValidator',
        'implementingClassName' => 'Modules\\Checkout\\Contracts\\CheckoutValidator',
        'currentClassName' => 'Modules\\Checkout\\Contracts\\CheckoutValidator',
        'aliasName' => NULL,
      ),
      'validate' => 
      array (
        'name' => 'validate',
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
                'name' => 'Modules\\Checkout\\Contracts\\Data\\CheckoutRequest',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 24,
            'endLine' => 24,
            'startColumn' => 30,
            'endColumn' => 53,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'totals' => 
          array (
            'name' => 'totals',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Checkout\\Contracts\\Data\\Totals',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 24,
            'endLine' => 24,
            'startColumn' => 56,
            'endColumn' => 69,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'cartReady' => 
          array (
            'name' => 'cartReady',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'bool',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 24,
            'endLine' => 24,
            'startColumn' => 72,
            'endColumn' => 86,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return list<CheckoutIssue>
 */',
        'startLine' => 24,
        'endLine' => 24,
        'startColumn' => 5,
        'endColumn' => 95,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Checkout\\Contracts',
        'declaringClassName' => 'Modules\\Checkout\\Contracts\\CheckoutValidator',
        'implementingClassName' => 'Modules\\Checkout\\Contracts\\CheckoutValidator',
        'currentClassName' => 'Modules\\Checkout\\Contracts\\CheckoutValidator',
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