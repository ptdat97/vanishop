<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Payment/Console/ReconcilePaymentsCommand.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Payment\Console\ReconcilePaymentsCommand
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-b13fca4f37064ecfec093cb926e7858106d0ebd787dce850d68c8d2eaaaa38b3',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Payment\\Console\\ReconcilePaymentsCommand',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Payment/Console/ReconcilePaymentsCommand.php',
      ),
    ),
    'namespace' => 'Modules\\Payment\\Console',
    'name' => 'Modules\\Payment\\Console\\ReconcilePaymentsCommand',
    'shortName' => 'ReconcilePaymentsCommand',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Không nhận được IPN: hỏi cổng trạng thái các payment treo > 5 phút (cổng hỗ trợ query).
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 21,
    'endLine' => 53,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Illuminate\\Console\\Command',
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
      'signature' => 
      array (
        'declaringClassName' => 'Modules\\Payment\\Console\\ReconcilePaymentsCommand',
        'implementingClassName' => 'Modules\\Payment\\Console\\ReconcilePaymentsCommand',
        'name' => 'signature',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'vani:payment:reconcile\'',
          'attributes' => 
          array (
            'startLine' => 23,
            'endLine' => 23,
            'startTokenPos' => 85,
            'startFilePos' => 679,
            'endTokenPos' => 85,
            'endFilePos' => 702,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 23,
        'endLine' => 23,
        'startColumn' => 5,
        'endColumn' => 52,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'description' => 
      array (
        'declaringClassName' => 'Modules\\Payment\\Console\\ReconcilePaymentsCommand',
        'implementingClassName' => 'Modules\\Payment\\Console\\ReconcilePaymentsCommand',
        'name' => 'description',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'Truy vấn cổng cho các giao dịch treo\'',
          'attributes' => 
          array (
            'startLine' => 25,
            'endLine' => 25,
            'startTokenPos' => 94,
            'startFilePos' => 735,
            'endTokenPos' => 94,
            'endFilePos' => 779,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 25,
        'endLine' => 25,
        'startColumn' => 5,
        'endColumn' => 75,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
    ),
    'immediateMethods' => 
    array (
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
            'startLine' => 27,
            'endLine' => 27,
            'startColumn' => 28,
            'endColumn' => 50,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 27,
            'endLine' => 27,
            'startColumn' => 53,
            'endColumn' => 77,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'payments' => 
          array (
            'name' => 'payments',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Payment\\Application\\PaymentService',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 27,
            'endLine' => 27,
            'startColumn' => 80,
            'endColumn' => 103,
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
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 27,
        'endLine' => 52,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Payment\\Console',
        'declaringClassName' => 'Modules\\Payment\\Console\\ReconcilePaymentsCommand',
        'implementingClassName' => 'Modules\\Payment\\Console\\ReconcilePaymentsCommand',
        'currentClassName' => 'Modules\\Payment\\Console\\ReconcilePaymentsCommand',
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