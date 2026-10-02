<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Payment/Application/GatewayRegistry.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Payment\Application\GatewayRegistry
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-40613a21e15c2f61ece5753c49e32866f6714485c6cadbc958badee87c4f1788',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Payment\\Application\\GatewayRegistry',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Payment/Application/GatewayRegistry.php',
      ),
    ),
    'namespace' => 'Modules\\Payment\\Application',
    'name' => 'Modules\\Payment\\Application\\GatewayRegistry',
    'shortName' => 'GatewayRegistry',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Cổng thanh toán theo mã, từ tag `vani.payment.gateways` — chỉ cổng có hiệu lực trong phạm vi hiện tại
 * (plugin cổng tắt thì cổng không có mặt).
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 14,
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
      'TAG' => 
      array (
        'declaringClassName' => 'Modules\\Payment\\Application\\GatewayRegistry',
        'implementingClassName' => 'Modules\\Payment\\Application\\GatewayRegistry',
        'name' => 'TAG',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\\Modules\\Payment\\Contracts\\PaymentGateway::TAG',
          'attributes' => 
          array (
            'startLine' => 16,
            'endLine' => 16,
            'startTokenPos' => 43,
            'startFilePos' => 408,
            'endTokenPos' => 45,
            'endFilePos' => 426,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 16,
        'endLine' => 16,
        'startColumn' => 5,
        'endColumn' => 43,
      ),
    ),
    'immediateProperties' => 
    array (
      'extensions' => 
      array (
        'declaringClassName' => 'Modules\\Payment\\Application\\GatewayRegistry',
        'implementingClassName' => 'Modules\\Payment\\Application\\GatewayRegistry',
        'name' => 'extensions',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Modules\\Extension\\Contracts\\Extensions',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 18,
        'endLine' => 18,
        'startColumn' => 33,
        'endColumn' => 71,
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
          'extensions' => 
          array (
            'name' => 'extensions',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Extension\\Contracts\\Extensions',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 18,
            'endLine' => 18,
            'startColumn' => 33,
            'endColumn' => 71,
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
        'startLine' => 18,
        'endLine' => 18,
        'startColumn' => 5,
        'endColumn' => 75,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Payment\\Application',
        'declaringClassName' => 'Modules\\Payment\\Application\\GatewayRegistry',
        'implementingClassName' => 'Modules\\Payment\\Application\\GatewayRegistry',
        'currentClassName' => 'Modules\\Payment\\Application\\GatewayRegistry',
        'aliasName' => NULL,
      ),
      'get' => 
      array (
        'name' => 'get',
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
            'startLine' => 20,
            'endLine' => 20,
            'startColumn' => 25,
            'endColumn' => 36,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
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
                  'name' => 'Modules\\Payment\\Contracts\\PaymentGateway',
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
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 20,
        'endLine' => 23,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Payment\\Application',
        'declaringClassName' => 'Modules\\Payment\\Application\\GatewayRegistry',
        'implementingClassName' => 'Modules\\Payment\\Application\\GatewayRegistry',
        'currentClassName' => 'Modules\\Payment\\Application\\GatewayRegistry',
        'aliasName' => NULL,
      ),
      'all' => 
      array (
        'name' => 'all',
        'parameters' => 
        array (
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
 * @return array<string, PaymentGateway>
 */',
        'startLine' => 28,
        'endLine' => 31,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Payment\\Application',
        'declaringClassName' => 'Modules\\Payment\\Application\\GatewayRegistry',
        'implementingClassName' => 'Modules\\Payment\\Application\\GatewayRegistry',
        'currentClassName' => 'Modules\\Payment\\Application\\GatewayRegistry',
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