<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Checkout/Contracts/AddressDirectory.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Checkout\Contracts\AddressDirectory
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-980168df55983ce1d7447d73b7ca3ded182d931f02b94440542b6e6dadb4edda',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Checkout\\Contracts\\AddressDirectory',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Checkout/Contracts/AddressDirectory.php',
      ),
    ),
    'namespace' => 'Modules\\Checkout\\Contracts',
    'name' => 'Modules\\Checkout\\Contracts\\AddressDirectory',
    'shortName' => 'AddressDirectory',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Extension point (tag `vani.checkout.address_directories`): danh mục địa giới hành chính cho địa chỉ giao hàng.
 * Có implementation (vd. plugin hệ thống `vani.provinces-vn`) → Core kiểm tra mã tỉnh/phường khi đặt hàng, chụp tên
 * chuẩn vào đơn, Storefront API + checkout native cho chọn tỉnh/phường. Không có → địa chỉ nhập tự do.
 * Dùng implementation đầu tiên đang bật. Dữ liệu tĩnh/cache — không I/O mạng (chạy trong transaction đặt hàng).
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 13,
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
      'TAG' => 
      array (
        'declaringClassName' => 'Modules\\Checkout\\Contracts\\AddressDirectory',
        'implementingClassName' => 'Modules\\Checkout\\Contracts\\AddressDirectory',
        'name' => 'TAG',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'vani.checkout.address_directories\'',
          'attributes' => 
          array (
            'startLine' => 15,
            'endLine' => 15,
            'startTokenPos' => 31,
            'startFilePos' => 666,
            'endTokenPos' => 31,
            'endFilePos' => 700,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 15,
        'endLine' => 15,
        'startColumn' => 5,
        'endColumn' => 59,
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
        'startLine' => 17,
        'endLine' => 17,
        'startColumn' => 5,
        'endColumn' => 35,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Checkout\\Contracts',
        'declaringClassName' => 'Modules\\Checkout\\Contracts\\AddressDirectory',
        'implementingClassName' => 'Modules\\Checkout\\Contracts\\AddressDirectory',
        'currentClassName' => 'Modules\\Checkout\\Contracts\\AddressDirectory',
        'aliasName' => NULL,
      ),
      'provinces' => 
      array (
        'name' => 'provinces',
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
 * @return list<array{code: string, name: string}>
 */',
        'startLine' => 22,
        'endLine' => 22,
        'startColumn' => 5,
        'endColumn' => 39,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Checkout\\Contracts',
        'declaringClassName' => 'Modules\\Checkout\\Contracts\\AddressDirectory',
        'implementingClassName' => 'Modules\\Checkout\\Contracts\\AddressDirectory',
        'currentClassName' => 'Modules\\Checkout\\Contracts\\AddressDirectory',
        'aliasName' => NULL,
      ),
      'wards' => 
      array (
        'name' => 'wards',
        'parameters' => 
        array (
          'provinceCode' => 
          array (
            'name' => 'provinceCode',
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
            'startLine' => 27,
            'endLine' => 27,
            'startColumn' => 27,
            'endColumn' => 46,
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
 * @return list<array{code: string, name: string}> rỗng nếu không có tỉnh
 */',
        'startLine' => 27,
        'endLine' => 27,
        'startColumn' => 5,
        'endColumn' => 55,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Checkout\\Contracts',
        'declaringClassName' => 'Modules\\Checkout\\Contracts\\AddressDirectory',
        'implementingClassName' => 'Modules\\Checkout\\Contracts\\AddressDirectory',
        'currentClassName' => 'Modules\\Checkout\\Contracts\\AddressDirectory',
        'aliasName' => NULL,
      ),
      'province' => 
      array (
        'name' => 'province',
        'parameters' => 
        array (
          'provinceCode' => 
          array (
            'name' => 'provinceCode',
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
            'startLine' => 32,
            'endLine' => 32,
            'startColumn' => 30,
            'endColumn' => 49,
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
                  'name' => 'array',
                  'isIdentifier' => true,
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
        'docComment' => '/**
 * @return array{code: string, name: string}|null
 */',
        'startLine' => 32,
        'endLine' => 32,
        'startColumn' => 5,
        'endColumn' => 59,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Checkout\\Contracts',
        'declaringClassName' => 'Modules\\Checkout\\Contracts\\AddressDirectory',
        'implementingClassName' => 'Modules\\Checkout\\Contracts\\AddressDirectory',
        'currentClassName' => 'Modules\\Checkout\\Contracts\\AddressDirectory',
        'aliasName' => NULL,
      ),
      'ward' => 
      array (
        'name' => 'ward',
        'parameters' => 
        array (
          'provinceCode' => 
          array (
            'name' => 'provinceCode',
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
            'startLine' => 39,
            'endLine' => 39,
            'startColumn' => 26,
            'endColumn' => 45,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'wardCode' => 
          array (
            'name' => 'wardCode',
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
            'startLine' => 39,
            'endLine' => 39,
            'startColumn' => 48,
            'endColumn' => 63,
            'parameterIndex' => 1,
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
                  'name' => 'array',
                  'isIdentifier' => true,
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
        'docComment' => '/**
 * Phường/xã thuộc đúng tỉnh; sai tỉnh → null.
 *
 * @return array{code: string, name: string}|null
 */',
        'startLine' => 39,
        'endLine' => 39,
        'startColumn' => 5,
        'endColumn' => 73,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Checkout\\Contracts',
        'declaringClassName' => 'Modules\\Checkout\\Contracts\\AddressDirectory',
        'implementingClassName' => 'Modules\\Checkout\\Contracts\\AddressDirectory',
        'currentClassName' => 'Modules\\Checkout\\Contracts\\AddressDirectory',
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