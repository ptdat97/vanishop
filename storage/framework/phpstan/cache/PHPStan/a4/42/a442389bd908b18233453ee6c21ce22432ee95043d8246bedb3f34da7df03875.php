<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Ordering/Contracts/OrderWriter.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Ordering\Contracts\OrderWriter
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-05bc40d6fcbc0097bc5939ff2165d3aedb76990a5a5b33ec50997b50b1cba16b',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Ordering\\Contracts\\OrderWriter',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Ordering/Contracts/OrderWriter.php',
      ),
    ),
    'namespace' => 'Modules\\Ordering\\Contracts',
    'name' => 'Modules\\Ordering\\Contracts\\OrderWriter',
    'shortName' => 'OrderWriter',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Service contract: tạo đơn từ bản nháp đã tính xong. Chạy TRONG transaction PlaceOrder.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 13,
    'endLine' => 24,
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
      'create' => 
      array (
        'name' => 'create',
        'parameters' => 
        array (
          'draft' => 
          array (
            'name' => 'draft',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Ordering\\Contracts\\Data\\OrderDraft',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 15,
            'endLine' => 15,
            'startColumn' => 28,
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
            'name' => 'Modules\\Ordering\\Contracts\\Data\\PlacedOrder',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 15,
        'endLine' => 15,
        'startColumn' => 5,
        'endColumn' => 59,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Ordering\\Contracts',
        'declaringClassName' => 'Modules\\Ordering\\Contracts\\OrderWriter',
        'implementingClassName' => 'Modules\\Ordering\\Contracts\\OrderWriter',
        'currentClassName' => 'Modules\\Ordering\\Contracts\\OrderWriter',
        'aliasName' => NULL,
      ),
      'reassignCustomer' => 
      array (
        'name' => 'reassignCustomer',
        'parameters' => 
        array (
          'fromCustomerId' => 
          array (
            'name' => 'fromCustomerId',
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
            'startLine' => 23,
            'endLine' => 23,
            'startColumn' => 38,
            'endColumn' => 56,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'toCustomerId' => 
          array (
            'name' => 'toCustomerId',
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
            'startLine' => 23,
            'endLine' => 23,
            'startColumn' => 59,
            'endColumn' => 75,
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
        'docComment' => '/**
 * Chuyển mọi đơn của khách nguồn sang khách đích (hợp nhất khách hàng). Snapshot trên đơn giữ nguyên.
 * Mỗi đơn có một dòng order_events.
 *
 * @return int số đơn đã chuyển
 */',
        'startLine' => 23,
        'endLine' => 23,
        'startColumn' => 5,
        'endColumn' => 82,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Ordering\\Contracts',
        'declaringClassName' => 'Modules\\Ordering\\Contracts\\OrderWriter',
        'implementingClassName' => 'Modules\\Ordering\\Contracts\\OrderWriter',
        'currentClassName' => 'Modules\\Ordering\\Contracts\\OrderWriter',
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