<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Shared/Http/Middleware/ResolveStorefrontContext.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Shared\Http\Middleware\ResolveStorefrontContext
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-4505697c633505d82d2ef68dc0a7a82f8cdb8375794db7e431e43378d0ec8e6b',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Shared\\Http\\Middleware\\ResolveStorefrontContext',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Shared/Http/Middleware/ResolveStorefrontContext.php',
      ),
    ),
    'namespace' => 'Modules\\Shared\\Http\\Middleware',
    'name' => 'Modules\\Shared\\Http\\Middleware\\ResolveStorefrontContext',
    'shortName' => 'ResolveStorefrontContext',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Storefront API: khách vãng lai + ngôn ngữ (mặc định `vi`, đổi bằng header X-Vani-Locale — không theo
 * Accept-Language). Nguồn đơn (`web`/`app`/`zalo`) lấy từ header X-Vani-Source, mặc định `web`.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 19,
    'endLine' => 44,
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
      'LOCALE_HEADER' => 
      array (
        'declaringClassName' => 'Modules\\Shared\\Http\\Middleware\\ResolveStorefrontContext',
        'implementingClassName' => 'Modules\\Shared\\Http\\Middleware\\ResolveStorefrontContext',
        'name' => 'LOCALE_HEADER',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'X-Vani-Locale\'',
          'attributes' => 
          array (
            'startLine' => 21,
            'endLine' => 21,
            'startTokenPos' => 68,
            'startFilePos' => 632,
            'endTokenPos' => 68,
            'endFilePos' => 646,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 21,
        'endLine' => 21,
        'startColumn' => 5,
        'endColumn' => 49,
      ),
      'SOURCE_HEADER' => 
      array (
        'declaringClassName' => 'Modules\\Shared\\Http\\Middleware\\ResolveStorefrontContext',
        'implementingClassName' => 'Modules\\Shared\\Http\\Middleware\\ResolveStorefrontContext',
        'name' => 'SOURCE_HEADER',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'X-Vani-Source\'',
          'attributes' => 
          array (
            'startLine' => 23,
            'endLine' => 23,
            'startTokenPos' => 79,
            'startFilePos' => 683,
            'endTokenPos' => 79,
            'endFilePos' => 697,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 23,
        'endLine' => 23,
        'startColumn' => 5,
        'endColumn' => 49,
      ),
      'SOURCES' => 
      array (
        'declaringClassName' => 'Modules\\Shared\\Http\\Middleware\\ResolveStorefrontContext',
        'implementingClassName' => 'Modules\\Shared\\Http\\Middleware\\ResolveStorefrontContext',
        'name' => 'SOURCES',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'web\', \'app\', \'zalo\']',
          'attributes' => 
          array (
            'startLine' => 25,
            'endLine' => 25,
            'startTokenPos' => 90,
            'startFilePos' => 728,
            'endTokenPos' => 98,
            'endFilePos' => 749,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 25,
        'endLine' => 25,
        'startColumn' => 5,
        'endColumn' => 50,
      ),
    ),
    'immediateProperties' => 
    array (
      'context' => 
      array (
        'declaringClassName' => 'Modules\\Shared\\Http\\Middleware\\ResolveStorefrontContext',
        'implementingClassName' => 'Modules\\Shared\\Http\\Middleware\\ResolveStorefrontContext',
        'name' => 'context',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Modules\\Shared\\Context\\CurrentContext',
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
        'startColumn' => 33,
        'endColumn' => 72,
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 27,
            'endLine' => 27,
            'startColumn' => 33,
            'endColumn' => 72,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 27,
        'endLine' => 27,
        'startColumn' => 5,
        'endColumn' => 76,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Shared\\Http\\Middleware',
        'declaringClassName' => 'Modules\\Shared\\Http\\Middleware\\ResolveStorefrontContext',
        'implementingClassName' => 'Modules\\Shared\\Http\\Middleware\\ResolveStorefrontContext',
        'currentClassName' => 'Modules\\Shared\\Http\\Middleware\\ResolveStorefrontContext',
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
            'startLine' => 29,
            'endLine' => 29,
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
            'startLine' => 29,
            'endLine' => 29,
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
        'startLine' => 29,
        'endLine' => 43,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Shared\\Http\\Middleware',
        'declaringClassName' => 'Modules\\Shared\\Http\\Middleware\\ResolveStorefrontContext',
        'implementingClassName' => 'Modules\\Shared\\Http\\Middleware\\ResolveStorefrontContext',
        'currentClassName' => 'Modules\\Shared\\Http\\Middleware\\ResolveStorefrontContext',
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