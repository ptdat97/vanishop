<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/app/Http/Middleware/HandleInertiaRequests.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Http\Middleware\HandleInertiaRequests
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-ccd44078cb155a4271924926418f5762b164cc822823de04b649f8ae58e2dea0',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Http\\Middleware\\HandleInertiaRequests',
        'filename' => '/Users/dat/Ecommerce/vanishop/app/Http/Middleware/HandleInertiaRequests.php',
      ),
    ),
    'namespace' => 'App\\Http\\Middleware',
    'name' => 'App\\Http\\Middleware\\HandleInertiaRequests',
    'shortName' => 'HandleInertiaRequests',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Inertia cho Admin (chỉ gắn vào route Admin; storefront dùng Blade).
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 15,
    'endLine' => 58,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Inertia\\Middleware',
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
      'rootView' => 
      array (
        'declaringClassName' => 'App\\Http\\Middleware\\HandleInertiaRequests',
        'implementingClassName' => 'App\\Http\\Middleware\\HandleInertiaRequests',
        'name' => 'rootView',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'admin\'',
          'attributes' => 
          array (
            'startLine' => 24,
            'endLine' => 24,
            'startTokenPos' => 57,
            'startFilePos' => 572,
            'endTokenPos' => 57,
            'endFilePos' => 578,
          ),
        ),
        'docComment' => '/**
 * The root template that\'s loaded on the first page visit.
 *
 * @see https://inertiajs.com/server-side-setup#root-template
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 24,
        'endLine' => 24,
        'startColumn' => 5,
        'endColumn' => 34,
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
      'version' => 
      array (
        'name' => 'version',
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
            'startColumn' => 29,
            'endColumn' => 44,
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
                  'name' => 'string',
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
 * Determines the current asset version.
 *
 * @see https://inertiajs.com/asset-versioning
 */',
        'startLine' => 31,
        'endLine' => 34,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Http\\Middleware',
        'declaringClassName' => 'App\\Http\\Middleware\\HandleInertiaRequests',
        'implementingClassName' => 'App\\Http\\Middleware\\HandleInertiaRequests',
        'currentClassName' => 'App\\Http\\Middleware\\HandleInertiaRequests',
        'aliasName' => NULL,
      ),
      'share' => 
      array (
        'name' => 'share',
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
            'startLine' => 43,
            'endLine' => 43,
            'startColumn' => 27,
            'endColumn' => 42,
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
 * Define the props that are shared by default.
 *
 * @see https://inertiajs.com/shared-data
 *
 * @return array<string, mixed>
 */',
        'startLine' => 43,
        'endLine' => 57,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Http\\Middleware',
        'declaringClassName' => 'App\\Http\\Middleware\\HandleInertiaRequests',
        'implementingClassName' => 'App\\Http\\Middleware\\HandleInertiaRequests',
        'currentClassName' => 'App\\Http\\Middleware\\HandleInertiaRequests',
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