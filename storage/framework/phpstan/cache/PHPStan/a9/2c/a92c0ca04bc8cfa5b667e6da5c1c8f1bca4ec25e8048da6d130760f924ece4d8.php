<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Customer/Contracts/AuthProvider.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Customer\Contracts\AuthProvider
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-35e290aee06bac8f74cf6af5a40ceec3d13c01ab1917e87072332f887fa16ccf',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Customer\\Contracts\\AuthProvider',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Customer/Contracts/AuthProvider.php',
      ),
    ),
    'namespace' => 'Modules\\Customer\\Contracts',
    'name' => 'Modules\\Customer\\Contracts\\AuthProvider',
    'shortName' => 'AuthProvider',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Extension point (tag `vani.customer.auth_providers`, ADR-030 §4.E): đăng nhập bằng tài khoản bên ngoài (Zalo,
 * Google, Facebook…). Plugin lo OAuth với nhà cung cấp; Core lo `state` một lần, danh sách `redirect_uri` cho phép,
 * ghép danh tính với khách, cấp token Bearer.
 *
 * Quy tắc ghép (Core): danh tính đã liên kết → đăng nhập; chưa liên kết → theo SĐT **đã xác minh** (tạo khách nếu
 * chưa có) hoặc email **đã xác minh** của khách đang có; không có cả hai → từ chối (khách xác minh SĐT bằng OTP).
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 17,
    'endLine' => 39,
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
        'declaringClassName' => 'Modules\\Customer\\Contracts\\AuthProvider',
        'implementingClassName' => 'Modules\\Customer\\Contracts\\AuthProvider',
        'name' => 'TAG',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'vani.customer.auth_providers\'',
          'attributes' => 
          array (
            'startLine' => 19,
            'endLine' => 19,
            'startTokenPos' => 36,
            'startFilePos' => 782,
            'endTokenPos' => 36,
            'endFilePos' => 811,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 19,
        'endLine' => 19,
        'startColumn' => 5,
        'endColumn' => 54,
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
        'docComment' => '/** Mã ổn định, dùng trong URL: `/auth/social/{code}/…` */',
        'startLine' => 22,
        'endLine' => 22,
        'startColumn' => 5,
        'endColumn' => 35,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Customer\\Contracts',
        'declaringClassName' => 'Modules\\Customer\\Contracts\\AuthProvider',
        'implementingClassName' => 'Modules\\Customer\\Contracts\\AuthProvider',
        'currentClassName' => 'Modules\\Customer\\Contracts\\AuthProvider',
        'aliasName' => NULL,
      ),
      'label' => 
      array (
        'name' => 'label',
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
        'startLine' => 24,
        'endLine' => 24,
        'startColumn' => 5,
        'endColumn' => 36,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Customer\\Contracts',
        'declaringClassName' => 'Modules\\Customer\\Contracts\\AuthProvider',
        'implementingClassName' => 'Modules\\Customer\\Contracts\\AuthProvider',
        'currentClassName' => 'Modules\\Customer\\Contracts\\AuthProvider',
        'aliasName' => NULL,
      ),
      'authorizationUrl' => 
      array (
        'name' => 'authorizationUrl',
        'parameters' => 
        array (
          'state' => 
          array (
            'name' => 'state',
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
            'startLine' => 29,
            'endLine' => 29,
            'startColumn' => 38,
            'endColumn' => 50,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'redirectUri' => 
          array (
            'name' => 'redirectUri',
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
            'startLine' => 29,
            'endLine' => 29,
            'startColumn' => 53,
            'endColumn' => 71,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * URL chuyển khách sang nhà cung cấp. Phải gửi kèm `$state` (Core kiểm tra khi quay về).
 */',
        'startLine' => 29,
        'endLine' => 29,
        'startColumn' => 5,
        'endColumn' => 81,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Customer\\Contracts',
        'declaringClassName' => 'Modules\\Customer\\Contracts\\AuthProvider',
        'implementingClassName' => 'Modules\\Customer\\Contracts\\AuthProvider',
        'currentClassName' => 'Modules\\Customer\\Contracts\\AuthProvider',
        'aliasName' => NULL,
      ),
      'resolve' => 
      array (
        'name' => 'resolve',
        'parameters' => 
        array (
          'params' => 
          array (
            'name' => 'params',
            'default' => NULL,
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
            'startLine' => 38,
            'endLine' => 38,
            'startColumn' => 29,
            'endColumn' => 41,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'redirectUri' => 
          array (
            'name' => 'redirectUri',
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
            'startLine' => 38,
            'endLine' => 38,
            'startColumn' => 44,
            'endColumn' => 62,
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
            'name' => 'Modules\\Customer\\Contracts\\Data\\ExternalIdentity',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Đổi tham số callback (vd. `code`) lấy danh tính. Gọi mạng được (có timeout); thất bại → AuthProviderFailed.
 *
 * @param  array<string, string>  $params
 *
 * @throws AuthProviderFailed
 */',
        'startLine' => 38,
        'endLine' => 38,
        'startColumn' => 5,
        'endColumn' => 82,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Customer\\Contracts',
        'declaringClassName' => 'Modules\\Customer\\Contracts\\AuthProvider',
        'implementingClassName' => 'Modules\\Customer\\Contracts\\AuthProvider',
        'currentClassName' => 'Modules\\Customer\\Contracts\\AuthProvider',
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