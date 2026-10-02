<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Storefront/StorefrontServiceProvider.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Storefront\StorefrontServiceProvider
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-641e7b9c794072bb65463f9b1255e4432f2a17798763d406e210fcfbd8b6b2b4',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Storefront\\StorefrontServiceProvider',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Storefront/StorefrontServiceProvider.php',
      ),
    ),
    'namespace' => 'Modules\\Storefront',
    'name' => 'Modules\\Storefront\\StorefrontServiceProvider',
    'shortName' => 'StorefrontServiceProvider',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Tầng ghép cho storefront (Storefront API và native storefront): không có bảng riêng,
 * chỉ gọi contract của các module Core. Xem docs/14-storefront/storefront.md.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 32,
    'endLine' => 81,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Modules\\Shared\\Support\\ModuleServiceProvider',
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
      'moduleName' => 
      array (
        'name' => 'moduleName',
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
        'startLine' => 34,
        'endLine' => 37,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Modules\\Storefront',
        'declaringClassName' => 'Modules\\Storefront\\StorefrontServiceProvider',
        'implementingClassName' => 'Modules\\Storefront\\StorefrontServiceProvider',
        'currentClassName' => 'Modules\\Storefront\\StorefrontServiceProvider',
        'aliasName' => NULL,
      ),
      'register' => 
      array (
        'name' => 'register',
        'parameters' => 
        array (
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
        'docComment' => NULL,
        'startLine' => 39,
        'endLine' => 46,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Storefront',
        'declaringClassName' => 'Modules\\Storefront\\StorefrontServiceProvider',
        'implementingClassName' => 'Modules\\Storefront\\StorefrontServiceProvider',
        'currentClassName' => 'Modules\\Storefront\\StorefrontServiceProvider',
        'aliasName' => NULL,
      ),
      'boot' => 
      array (
        'name' => 'boot',
        'parameters' => 
        array (
          'router' => 
          array (
            'name' => 'router',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Routing\\Router',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 48,
            'endLine' => 48,
            'startColumn' => 26,
            'endColumn' => 39,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'permissions' => 
          array (
            'name' => 'permissions',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Identity\\Application\\PermissionRegistry',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 48,
            'endLine' => 48,
            'startColumn' => 42,
            'endColumn' => 72,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'navigation' => 
          array (
            'name' => 'navigation',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Extension\\Application\\Admin\\AdminNavigation',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 48,
            'endLine' => 48,
            'startColumn' => 75,
            'endColumn' => 101,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 48,
        'endLine' => 80,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Storefront',
        'declaringClassName' => 'Modules\\Storefront\\StorefrontServiceProvider',
        'implementingClassName' => 'Modules\\Storefront\\StorefrontServiceProvider',
        'currentClassName' => 'Modules\\Storefront\\StorefrontServiceProvider',
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