<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Cart/Contracts/Carts.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Cart\Contracts\Carts
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-6e50368b8364adfc6e333c8c99d3483b42d52809f3b90754e724eaef611b718a',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Cart\\Contracts\\Carts',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Cart/Contracts/Carts.php',
      ),
    ),
    'namespace' => 'Modules\\Cart\\Contracts',
    'name' => 'Modules\\Cart\\Contracts\\Carts',
    'shortName' => 'Carts',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Service contract giỏ hàng. Kênh lấy từ CurrentContext; giỏ của kênh khác coi như không tồn tại.
 * Giỏ KHÔNG giữ hàng — chỉ kiểm tra số có thể bán; giữ hàng xảy ra trong PlaceOrder.
 *
 * Mọi phương thức ném CartRejected cho lỗi nghiệp vụ.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 17,
    'endLine' => 65,
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
          'currencyCode' => 
          array (
            'name' => 'currencyCode',
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
            'startLine' => 19,
            'endLine' => 19,
            'startColumn' => 28,
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
            'name' => 'Modules\\Cart\\Contracts\\Data\\NewCart',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 19,
        'endLine' => 19,
        'startColumn' => 5,
        'endColumn' => 58,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Cart\\Contracts',
        'declaringClassName' => 'Modules\\Cart\\Contracts\\Carts',
        'implementingClassName' => 'Modules\\Cart\\Contracts\\Carts',
        'currentClassName' => 'Modules\\Cart\\Contracts\\Carts',
        'aliasName' => NULL,
      ),
      'view' => 
      array (
        'name' => 'view',
        'parameters' => 
        array (
          'key' => 
          array (
            'name' => 'key',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Cart\\Contracts\\Data\\CartKey',
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
            'startColumn' => 26,
            'endColumn' => 37,
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
            'name' => 'Modules\\Cart\\Contracts\\Data\\CartView',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 21,
        'endLine' => 21,
        'startColumn' => 5,
        'endColumn' => 49,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Cart\\Contracts',
        'declaringClassName' => 'Modules\\Cart\\Contracts\\Carts',
        'implementingClassName' => 'Modules\\Cart\\Contracts\\Carts',
        'currentClassName' => 'Modules\\Cart\\Contracts\\Carts',
        'aliasName' => NULL,
      ),
      'addLine' => 
      array (
        'name' => 'addLine',
        'parameters' => 
        array (
          'key' => 
          array (
            'name' => 'key',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Cart\\Contracts\\Data\\CartKey',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 28,
            'endLine' => 28,
            'startColumn' => 29,
            'endColumn' => 40,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'variantId' => 
          array (
            'name' => 'variantId',
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
            'startLine' => 28,
            'endLine' => 28,
            'startColumn' => 43,
            'endColumn' => 56,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'quantity' => 
          array (
            'name' => 'quantity',
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
            'startLine' => 28,
            'endLine' => 28,
            'startColumn' => 59,
            'endColumn' => 71,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'options' => 
          array (
            'name' => 'options',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 28,
                'endLine' => 28,
                'startTokenPos' => 97,
                'startFilePos' => 954,
                'endTokenPos' => 98,
                'endFilePos' => 955,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 28,
            'endLine' => 28,
            'startColumn' => 74,
            'endColumn' => 92,
            'parameterIndex' => 3,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Modules\\Cart\\Contracts\\Data\\CartView',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Thêm variant; đã có dòng cùng variant + cùng tuỳ chọn thì cộng dồn số lượng.
 *
 * @param  array<string, array<string, mixed>>  $options  tuỳ chọn theo plugin id (CartLineOption)
 */',
        'startLine' => 28,
        'endLine' => 28,
        'startColumn' => 5,
        'endColumn' => 104,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Cart\\Contracts',
        'declaringClassName' => 'Modules\\Cart\\Contracts\\Carts',
        'implementingClassName' => 'Modules\\Cart\\Contracts\\Carts',
        'currentClassName' => 'Modules\\Cart\\Contracts\\Carts',
        'aliasName' => NULL,
      ),
      'updateLine' => 
      array (
        'name' => 'updateLine',
        'parameters' => 
        array (
          'key' => 
          array (
            'name' => 'key',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Cart\\Contracts\\Data\\CartKey',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 33,
            'endLine' => 33,
            'startColumn' => 32,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'lineId' => 
          array (
            'name' => 'lineId',
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
            'startLine' => 33,
            'endLine' => 33,
            'startColumn' => 46,
            'endColumn' => 56,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'quantity' => 
          array (
            'name' => 'quantity',
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
            'startLine' => 33,
            'endLine' => 33,
            'startColumn' => 59,
            'endColumn' => 71,
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
            'name' => 'Modules\\Cart\\Contracts\\Data\\CartView',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Đặt số lượng tuyệt đối; 0 = xoá dòng.
 */',
        'startLine' => 33,
        'endLine' => 33,
        'startColumn' => 5,
        'endColumn' => 83,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Cart\\Contracts',
        'declaringClassName' => 'Modules\\Cart\\Contracts\\Carts',
        'implementingClassName' => 'Modules\\Cart\\Contracts\\Carts',
        'currentClassName' => 'Modules\\Cart\\Contracts\\Carts',
        'aliasName' => NULL,
      ),
      'removeLine' => 
      array (
        'name' => 'removeLine',
        'parameters' => 
        array (
          'key' => 
          array (
            'name' => 'key',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Cart\\Contracts\\Data\\CartKey',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 35,
            'endLine' => 35,
            'startColumn' => 32,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'lineId' => 
          array (
            'name' => 'lineId',
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
            'startLine' => 35,
            'endLine' => 35,
            'startColumn' => 46,
            'endColumn' => 56,
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
            'name' => 'Modules\\Cart\\Contracts\\Data\\CartView',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 35,
        'endLine' => 35,
        'startColumn' => 5,
        'endColumn' => 68,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Cart\\Contracts',
        'declaringClassName' => 'Modules\\Cart\\Contracts\\Carts',
        'implementingClassName' => 'Modules\\Cart\\Contracts\\Carts',
        'currentClassName' => 'Modules\\Cart\\Contracts\\Carts',
        'aliasName' => NULL,
      ),
      'merge' => 
      array (
        'name' => 'merge',
        'parameters' => 
        array (
          'source' => 
          array (
            'name' => 'source',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Cart\\Contracts\\Data\\CartKey',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 41,
            'endLine' => 41,
            'startColumn' => 27,
            'endColumn' => 41,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'target' => 
          array (
            'name' => 'target',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Cart\\Contracts\\Data\\CartKey',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 41,
            'endLine' => 41,
            'startColumn' => 44,
            'endColumn' => 58,
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
            'name' => 'Modules\\Cart\\Contracts\\Data\\CartView',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Gộp giỏ nguồn vào giỏ đích (khi khách đăng nhập): cộng dồn, kẹp theo giới hạn và số có thể bán,
 * bỏ dòng không còn bán; giỏ nguồn chuyển sang "merged".
 */',
        'startLine' => 41,
        'endLine' => 41,
        'startColumn' => 5,
        'endColumn' => 70,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Cart\\Contracts',
        'declaringClassName' => 'Modules\\Cart\\Contracts\\Carts',
        'implementingClassName' => 'Modules\\Cart\\Contracts\\Carts',
        'currentClassName' => 'Modules\\Cart\\Contracts\\Carts',
        'aliasName' => NULL,
      ),
      'forCustomer' => 
      array (
        'name' => 'forCustomer',
        'parameters' => 
        array (
          'customerId' => 
          array (
            'name' => 'customerId',
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
            'startLine' => 47,
            'endLine' => 47,
            'startColumn' => 33,
            'endColumn' => 47,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'currencyCode' => 
          array (
            'name' => 'currencyCode',
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
            'startLine' => 47,
            'endLine' => 47,
            'startColumn' => 50,
            'endColumn' => 69,
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
            'name' => 'Modules\\Cart\\Contracts\\Data\\CartView',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Giỏ đang mở của khách hàng trên kênh hiện tại (tạo mới nếu chưa có). Giỏ của khách truy cập bằng
 * phiên đăng nhập (CurrentContext là actor customer), không cần token giỏ.
 */',
        'startLine' => 47,
        'endLine' => 47,
        'startColumn' => 5,
        'endColumn' => 81,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Cart\\Contracts',
        'declaringClassName' => 'Modules\\Cart\\Contracts\\Carts',
        'implementingClassName' => 'Modules\\Cart\\Contracts\\Carts',
        'currentClassName' => 'Modules\\Cart\\Contracts\\Carts',
        'aliasName' => NULL,
      ),
      'attachToCustomer' => 
      array (
        'name' => 'attachToCustomer',
        'parameters' => 
        array (
          'guestCart' => 
          array (
            'name' => 'guestCart',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Cart\\Contracts\\Data\\CartKey',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 53,
            'endLine' => 53,
            'startColumn' => 38,
            'endColumn' => 55,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'customerId' => 
          array (
            'name' => 'customerId',
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
            'startLine' => 53,
            'endLine' => 53,
            'startColumn' => 58,
            'endColumn' => 72,
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
            'name' => 'Modules\\Cart\\Contracts\\Data\\CartView',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Khi khách đăng nhập: gắn giỏ vãng lai (xác thực bằng token) vào khách. Khách đã có giỏ mở trên kênh →
 * gộp vào giỏ đó; chưa có → giỏ vãng lai thành giỏ của khách và token cũ bị vô hiệu.
 */',
        'startLine' => 53,
        'endLine' => 53,
        'startColumn' => 5,
        'endColumn' => 84,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Cart\\Contracts',
        'declaringClassName' => 'Modules\\Cart\\Contracts\\Carts',
        'implementingClassName' => 'Modules\\Cart\\Contracts\\Carts',
        'currentClassName' => 'Modules\\Cart\\Contracts\\Carts',
        'aliasName' => NULL,
      ),
      'lockForCheckout' => 
      array (
        'name' => 'lockForCheckout',
        'parameters' => 
        array (
          'key' => 
          array (
            'name' => 'key',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Cart\\Contracts\\Data\\CartKey',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 59,
            'endLine' => 59,
            'startColumn' => 37,
            'endColumn' => 48,
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
            'name' => 'Modules\\Cart\\Contracts\\Data\\CartView',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Khoá giỏ (FOR UPDATE) cho PlaceOrder và trả trạng thái hiện tại. Giỏ đã đóng → CartRejected::closed.
 * Phải gọi TRONG transaction đặt hàng: hai request đặt cùng một giỏ được xếp hàng, request sau thấy giỏ đã đóng.
 */',
        'startLine' => 59,
        'endLine' => 59,
        'startColumn' => 5,
        'endColumn' => 60,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Cart\\Contracts',
        'declaringClassName' => 'Modules\\Cart\\Contracts\\Carts',
        'implementingClassName' => 'Modules\\Cart\\Contracts\\Carts',
        'currentClassName' => 'Modules\\Cart\\Contracts\\Carts',
        'aliasName' => NULL,
      ),
      'markConverted' => 
      array (
        'name' => 'markConverted',
        'parameters' => 
        array (
          'key' => 
          array (
            'name' => 'key',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Cart\\Contracts\\Data\\CartKey',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 64,
            'endLine' => 64,
            'startColumn' => 35,
            'endColumn' => 46,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'orderPublicId' => 
          array (
            'name' => 'orderPublicId',
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
            'startLine' => 64,
            'endLine' => 64,
            'startColumn' => 49,
            'endColumn' => 69,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Đánh dấu giỏ đã đặt hàng (trong cùng transaction với lockForCheckout).
 */',
        'startLine' => 64,
        'endLine' => 64,
        'startColumn' => 5,
        'endColumn' => 77,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Cart\\Contracts',
        'declaringClassName' => 'Modules\\Cart\\Contracts\\Carts',
        'implementingClassName' => 'Modules\\Cart\\Contracts\\Carts',
        'currentClassName' => 'Modules\\Cart\\Contracts\\Carts',
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