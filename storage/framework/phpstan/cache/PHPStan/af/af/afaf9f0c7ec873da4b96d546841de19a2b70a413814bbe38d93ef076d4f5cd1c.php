<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Payment/Domain/RefundRules.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Payment\Domain\RefundRules
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-6bc1a0079f66fc28837ffdab36be24894d5fcac5103a474bab428d65e426c309',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Payment\\Domain\\RefundRules',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Payment/Domain/RefundRules.php',
      ),
    ),
    'namespace' => 'Modules\\Payment\\Domain',
    'name' => 'Modules\\Payment\\Domain\\RefundRules',
    'shortName' => 'RefundRules',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Tổng hoàn không vượt số đã thu (docs/10-payment/payment.md §3).
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 10,
    'endLine' => 21,
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
      'refundable' => 
      array (
        'name' => 'refundable',
        'parameters' => 
        array (
          'collected' => 
          array (
            'name' => 'collected',
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
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 12,
            'endLine' => 12,
            'startColumn' => 39,
            'endColumn' => 52,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'alreadyRefunded' => 
          array (
            'name' => 'alreadyRefunded',
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
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 12,
            'endLine' => 12,
            'startColumn' => 55,
            'endColumn' => 74,
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
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 12,
        'endLine' => 15,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Modules\\Payment\\Domain',
        'declaringClassName' => 'Modules\\Payment\\Domain\\RefundRules',
        'implementingClassName' => 'Modules\\Payment\\Domain\\RefundRules',
        'currentClassName' => 'Modules\\Payment\\Domain\\RefundRules',
        'aliasName' => NULL,
      ),
      'statusAfterRefund' => 
      array (
        'name' => 'statusAfterRefund',
        'parameters' => 
        array (
          'collected' => 
          array (
            'name' => 'collected',
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
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 17,
            'endLine' => 17,
            'startColumn' => 46,
            'endColumn' => 59,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'refundedTotal' => 
          array (
            'name' => 'refundedTotal',
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
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 17,
            'endLine' => 17,
            'startColumn' => 62,
            'endColumn' => 79,
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
            'name' => 'Modules\\Payment\\Domain\\PaymentStatus',
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
        'modifiers' => 17,
        'namespace' => 'Modules\\Payment\\Domain',
        'declaringClassName' => 'Modules\\Payment\\Domain\\RefundRules',
        'implementingClassName' => 'Modules\\Payment\\Domain\\RefundRules',
        'currentClassName' => 'Modules\\Payment\\Domain\\RefundRules',
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