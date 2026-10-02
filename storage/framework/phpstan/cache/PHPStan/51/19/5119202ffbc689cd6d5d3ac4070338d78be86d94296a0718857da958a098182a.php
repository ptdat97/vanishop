<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Identity/Http/Middleware/ConfigureAdminSession.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Identity\Http\Middleware\ConfigureAdminSession
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-b69c2f086e5712ff66f9e83db0374752bc9934c2182e54e8b73bbb64370a0bc3',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Identity\\Http\\Middleware\\ConfigureAdminSession',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Identity/Http/Middleware/ConfigureAdminSession.php',
      ),
    ),
    'namespace' => 'Modules\\Identity\\Http\\Middleware',
    'name' => 'Modules\\Identity\\Http\\Middleware\\ConfigureAdminSession',
    'shortName' => 'ConfigureAdminSession',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Chạy TRƯỚC StartSession (đầu nhóm "web"). Request vào Admin dùng cookie phiên riêng, giới hạn path
 * theo đường dẫn Admin, hết hạn khi không hoạt động (ADR-020). Cookie XSRF-TOKEN của Admin cũng theo
 * path này nên không đè token của storefront.
 *
 * Mọi request web đều tự đặt cấu hình phiên của mình (Admin hoặc mặc định), nên tiến trình sống lâu
 * (queue worker, Octane, test) không mang cấu hình Admin sang request storefront.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 22,
    'endLine' => 73,
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
      'DEFAULTS_BINDING' => 
      array (
        'declaringClassName' => 'Modules\\Identity\\Http\\Middleware\\ConfigureAdminSession',
        'implementingClassName' => 'Modules\\Identity\\Http\\Middleware\\ConfigureAdminSession',
        'name' => 'DEFAULTS_BINDING',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'vanishop.session.defaults\'',
          'attributes' => 
          array (
            'startLine' => 24,
            'endLine' => 24,
            'startTokenPos' => 63,
            'startFilePos' => 890,
            'endTokenPos' => 63,
            'endFilePos' => 916,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 24,
        'endLine' => 24,
        'startColumn' => 5,
        'endColumn' => 64,
      ),
    ),
    'immediateProperties' => 
    array (
      'sessions' => 
      array (
        'declaringClassName' => 'Modules\\Identity\\Http\\Middleware\\ConfigureAdminSession',
        'implementingClassName' => 'Modules\\Identity\\Http\\Middleware\\ConfigureAdminSession',
        'name' => 'sessions',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Illuminate\\Session\\SessionManager',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 27,
        'endLine' => 27,
        'startColumn' => 9,
        'endColumn' => 49,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'app' => 
      array (
        'declaringClassName' => 'Modules\\Identity\\Http\\Middleware\\ConfigureAdminSession',
        'implementingClassName' => 'Modules\\Identity\\Http\\Middleware\\ConfigureAdminSession',
        'name' => 'app',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Illuminate\\Contracts\\Foundation\\Application',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 28,
        'endLine' => 28,
        'startColumn' => 9,
        'endColumn' => 41,
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
          'sessions' => 
          array (
            'name' => 'sessions',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Session\\SessionManager',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 27,
            'endLine' => 27,
            'startColumn' => 9,
            'endColumn' => 49,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'app' => 
          array (
            'name' => 'app',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Contracts\\Foundation\\Application',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 28,
            'endLine' => 28,
            'startColumn' => 9,
            'endColumn' => 41,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 26,
        'endLine' => 29,
        'startColumn' => 5,
        'endColumn' => 8,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Identity\\Http\\Middleware',
        'declaringClassName' => 'Modules\\Identity\\Http\\Middleware\\ConfigureAdminSession',
        'implementingClassName' => 'Modules\\Identity\\Http\\Middleware\\ConfigureAdminSession',
        'currentClassName' => 'Modules\\Identity\\Http\\Middleware\\ConfigureAdminSession',
        'aliasName' => NULL,
      ),
      'handle' => 
      array (
        'name' => 'handle',
        'parameters' => 
        array (
          'request' => 
          array (
            'name' => 'request',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Http\\Request',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 31,
            'endLine' => 31,
            'startColumn' => 28,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'next' => 
          array (
            'name' => 'next',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Closure',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 31,
            'endLine' => 31,
            'startColumn' => 46,
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
            'name' => 'Symfony\\Component\\HttpFoundation\\Response',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 31,
        'endLine' => 57,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Identity\\Http\\Middleware',
        'declaringClassName' => 'Modules\\Identity\\Http\\Middleware\\ConfigureAdminSession',
        'implementingClassName' => 'Modules\\Identity\\Http\\Middleware\\ConfigureAdminSession',
        'currentClassName' => 'Modules\\Identity\\Http\\Middleware\\ConfigureAdminSession',
        'aliasName' => NULL,
      ),
      'snapshotDefaults' => 
      array (
        'name' => 'snapshotDefaults',
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
 * Chụp cấu hình phiên mặc định (storefront) lúc boot.
 *
 * @return array<string, mixed>
 */',
        'startLine' => 64,
        'endLine' => 72,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Modules\\Identity\\Http\\Middleware',
        'declaringClassName' => 'Modules\\Identity\\Http\\Middleware\\ConfigureAdminSession',
        'implementingClassName' => 'Modules\\Identity\\Http\\Middleware\\ConfigureAdminSession',
        'currentClassName' => 'Modules\\Identity\\Http\\Middleware\\ConfigureAdminSession',
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