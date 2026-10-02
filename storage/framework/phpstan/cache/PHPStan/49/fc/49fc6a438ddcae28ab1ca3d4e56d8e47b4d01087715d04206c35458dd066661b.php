<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Ordering/Domain/PaymentStatus.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Ordering\Domain\PaymentStatus
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-4f7512efa8c6ab7cbeb194184ec83103ebbe7d82babe59b4d7d04ab2a38990d1',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Ordering\\Domain\\PaymentStatus',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Ordering/Domain/PaymentStatus.php',
      ),
    ),
    'namespace' => 'Modules\\Ordering\\Domain',
    'name' => 'Modules\\Ordering\\Domain\\PaymentStatus',
    'shortName' => 'PaymentStatus',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Giá trị hợp lệ của orders.payment_status (chiều thanh toán, do Payment cập nhật).
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 10,
    'endLine' => 18,
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
      'VALUES' => 
      array (
        'declaringClassName' => 'Modules\\Ordering\\Domain\\PaymentStatus',
        'implementingClassName' => 'Modules\\Ordering\\Domain\\PaymentStatus',
        'name' => 'VALUES',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'unpaid\', \'authorized\', \'paid\', \'partially_refunded\', \'refunded\', \'cod_pending\', \'cod_collected\', \'failed\']',
          'attributes' => 
          array (
            'startLine' => 12,
            'endLine' => 12,
            'startTokenPos' => 33,
            'startFilePos' => 232,
            'endTokenPos' => 56,
            'endFilePos' => 339,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 12,
        'endLine' => 12,
        'startColumn' => 5,
        'endColumn' => 135,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'isValid' => 
      array (
        'name' => 'isValid',
        'parameters' => 
        array (
          'status' => 
          array (
            'name' => 'status',
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
            'startLine' => 14,
            'endLine' => 14,
            'startColumn' => 36,
            'endColumn' => 49,
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
        'docComment' => NULL,
        'startLine' => 14,
        'endLine' => 17,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Modules\\Ordering\\Domain',
        'declaringClassName' => 'Modules\\Ordering\\Domain\\PaymentStatus',
        'implementingClassName' => 'Modules\\Ordering\\Domain\\PaymentStatus',
        'currentClassName' => 'Modules\\Ordering\\Domain\\PaymentStatus',
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