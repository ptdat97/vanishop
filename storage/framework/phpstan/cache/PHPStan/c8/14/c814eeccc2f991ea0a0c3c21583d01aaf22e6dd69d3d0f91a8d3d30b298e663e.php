<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Ordering/Domain/CustomerStatus.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Ordering\Domain\CustomerStatus
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-95f9386e23fa606d132416eaa0e32e8566b42e2d763170258df82c2b344645ad',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Ordering\\Domain\\CustomerStatus',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Ordering/Domain/CustomerStatus.php',
      ),
    ),
    'namespace' => 'Modules\\Ordering\\Domain',
    'name' => 'Modules\\Ordering\\Domain\\CustomerStatus',
    'shortName' => 'CustomerStatus',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Nhãn trạng thái cho khách, TÍNH từ 4 chiều (order/payment/fulfillment/return) — docs/09-order/order.md §3.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 10,
    'endLine' => 45,
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
      'LABELS' => 
      array (
        'declaringClassName' => 'Modules\\Ordering\\Domain\\CustomerStatus',
        'implementingClassName' => 'Modules\\Ordering\\Domain\\CustomerStatus',
        'name' => 'LABELS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'cancelled\' => \'Đã huỷ\', \'returning\' => \'Đang đổi/trả\', \'returned\' => \'Đã trả hàng\', \'completed\' => \'Hoàn tất\', \'delivered\' => \'Đã giao\', \'shipping\' => \'Đang giao\', \'delivery_failed\' => \'Giao không thành công\', \'awaiting_payment\' => \'Chờ thanh toán\', \'awaiting_confirmation\' => \'Chờ xác nhận\', \'preparing\' => \'Đang chuẩn bị hàng\']',
          'attributes' => 
          array (
            'startLine' => 33,
            'endLine' => 44,
            'startTokenPos' => 267,
            'startFilePos' => 1411,
            'endTokenPos' => 339,
            'endFilePos' => 1866,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 33,
        'endLine' => 44,
        'startColumn' => 5,
        'endColumn' => 6,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'of' => 
      array (
        'name' => 'of',
        'parameters' => 
        array (
          'orderStatus' => 
          array (
            'name' => 'orderStatus',
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
            'startLine' => 15,
            'endLine' => 15,
            'startColumn' => 31,
            'endColumn' => 49,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'paymentStatus' => 
          array (
            'name' => 'paymentStatus',
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
            'startLine' => 15,
            'endLine' => 15,
            'startColumn' => 52,
            'endColumn' => 72,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'fulfillmentStatus' => 
          array (
            'name' => 'fulfillmentStatus',
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
            'startLine' => 15,
            'endLine' => 15,
            'startColumn' => 75,
            'endColumn' => 99,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'returnStatus' => 
          array (
            'name' => 'returnStatus',
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
            'startLine' => 15,
            'endLine' => 15,
            'startColumn' => 102,
            'endColumn' => 121,
            'parameterIndex' => 3,
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
 * @return array{code: string, label: string}
 */',
        'startLine' => 15,
        'endLine' => 31,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Modules\\Ordering\\Domain',
        'declaringClassName' => 'Modules\\Ordering\\Domain\\CustomerStatus',
        'implementingClassName' => 'Modules\\Ordering\\Domain\\CustomerStatus',
        'currentClassName' => 'Modules\\Ordering\\Domain\\CustomerStatus',
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