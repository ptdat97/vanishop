<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Checkout/Application/PaymentMethods.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Checkout\Application\PaymentMethods
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-ce633d6140f0d4ccd40505257e35c36c4ffea16440c363d437a0d2f667e79553',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Checkout\\Application\\PaymentMethods',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Checkout/Application/PaymentMethods.php',
      ),
    ),
    'namespace' => 'Modules\\Checkout\\Application',
    'name' => 'Modules\\Checkout\\Application\\PaymentMethods',
    'shortName' => 'PaymentMethods',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Phương thức thanh toán khả dụng cho đơn: các PaymentGateway có isAvailable() + filter `vani.checkout.payment_methods`.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 14,
    'endLine' => 34,
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
      'payments' => 
      array (
        'declaringClassName' => 'Modules\\Checkout\\Application\\PaymentMethods',
        'implementingClassName' => 'Modules\\Checkout\\Application\\PaymentMethods',
        'name' => 'payments',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Modules\\Payment\\Contracts\\Payments',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 16,
        'endLine' => 16,
        'startColumn' => 33,
        'endColumn' => 67,
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
          'payments' => 
          array (
            'name' => 'payments',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Payment\\Contracts\\Payments',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 16,
            'endLine' => 16,
            'startColumn' => 33,
            'endColumn' => 67,
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
        'startLine' => 16,
        'endLine' => 16,
        'startColumn' => 5,
        'endColumn' => 71,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Checkout\\Application',
        'declaringClassName' => 'Modules\\Checkout\\Application\\PaymentMethods',
        'implementingClassName' => 'Modules\\Checkout\\Application\\PaymentMethods',
        'currentClassName' => 'Modules\\Checkout\\Application\\PaymentMethods',
        'aliasName' => NULL,
      ),
      'available' => 
      array (
        'name' => 'available',
        'parameters' => 
        array (
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
            'startLine' => 21,
            'endLine' => 21,
            'startColumn' => 31,
            'endColumn' => 44,
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
 * @return list<array{code: string, label: string}>
 */',
        'startLine' => 21,
        'endLine' => 28,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Checkout\\Application',
        'declaringClassName' => 'Modules\\Checkout\\Application\\PaymentMethods',
        'implementingClassName' => 'Modules\\Checkout\\Application\\PaymentMethods',
        'currentClassName' => 'Modules\\Checkout\\Application\\PaymentMethods',
        'aliasName' => NULL,
      ),
      'initialPaymentStatus' => 
      array (
        'name' => 'initialPaymentStatus',
        'parameters' => 
        array (
          'code' => 
          array (
            'name' => 'code',
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
            'startLine' => 30,
            'endLine' => 30,
            'startColumn' => 42,
            'endColumn' => 53,
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
        'namespace' => 'Modules\\Checkout\\Application',
        'declaringClassName' => 'Modules\\Checkout\\Application\\PaymentMethods',
        'implementingClassName' => 'Modules\\Checkout\\Application\\PaymentMethods',
        'currentClassName' => 'Modules\\Checkout\\Application\\PaymentMethods',
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