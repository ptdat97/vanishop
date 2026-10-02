<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Payment/Application/Listeners/OrderPaymentPanel.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Payment\Application\Listeners\OrderPaymentPanel
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-1cec85f13194841478c5ee001580bb47781e9db4f93fce2e8358d16dd4efce5d',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Payment\\Application\\Listeners\\OrderPaymentPanel',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Payment/Application/Listeners/OrderPaymentPanel.php',
      ),
    ),
    'namespace' => 'Modules\\Payment\\Application\\Listeners',
    'name' => 'Modules\\Payment\\Application\\Listeners\\OrderPaymentPanel',
    'shortName' => 'OrderPaymentPanel',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Panel "Thanh toán" trên trang đơn Admin (slot vani.admin.order.sidebar).
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 15,
    'endLine' => 40,
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
      'gateways' => 
      array (
        'declaringClassName' => 'Modules\\Payment\\Application\\Listeners\\OrderPaymentPanel',
        'implementingClassName' => 'Modules\\Payment\\Application\\Listeners\\OrderPaymentPanel',
        'name' => 'gateways',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Modules\\Payment\\Application\\GatewayRegistry',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 17,
        'endLine' => 17,
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
          'gateways' => 
          array (
            'name' => 'gateways',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Payment\\Application\\GatewayRegistry',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 17,
            'endLine' => 17,
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
        'startLine' => 17,
        'endLine' => 17,
        'startColumn' => 5,
        'endColumn' => 78,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Payment\\Application\\Listeners',
        'declaringClassName' => 'Modules\\Payment\\Application\\Listeners\\OrderPaymentPanel',
        'implementingClassName' => 'Modules\\Payment\\Application\\Listeners\\OrderPaymentPanel',
        'currentClassName' => 'Modules\\Payment\\Application\\Listeners\\OrderPaymentPanel',
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
            'startLine' => 22,
            'endLine' => 22,
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
 * @return array{title: string, rows: list<array{label: string, value: string}>, link?: array{label: string, url: string}}
 */',
        'startLine' => 22,
        'endLine' => 39,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Payment\\Application\\Listeners',
        'declaringClassName' => 'Modules\\Payment\\Application\\Listeners\\OrderPaymentPanel',
        'implementingClassName' => 'Modules\\Payment\\Application\\Listeners\\OrderPaymentPanel',
        'currentClassName' => 'Modules\\Payment\\Application\\Listeners\\OrderPaymentPanel',
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