<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Storefront/Http/Controllers/Web/SeoController.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Storefront\Http\Controllers\Web\SeoController
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-11c96f395eacd96da5fc600d02ff818a4045f91995bb0fb424bf3b5ab66ddc3b',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Storefront\\Http\\Controllers\\Web\\SeoController',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Storefront/Http/Controllers/Web/SeoController.php',
      ),
    ),
    'namespace' => 'Modules\\Storefront\\Http\\Controllers\\Web',
    'name' => 'Modules\\Storefront\\Http\\Controllers\\Web\\SeoController',
    'shortName' => 'SeoController',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * robots.txt và sitemap.xml của native storefront (storefront §5): một sitemap gồm trang chủ, danh mục, thương hiệu,
 * sản phẩm đang hiển thị. Cache 1 giờ. Đường dẫn Admin không được nhắc tới (bí mật, ADR-020).
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 18,
    'endLine' => 70,
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
      'MAX_URLS' => 
      array (
        'declaringClassName' => 'Modules\\Storefront\\Http\\Controllers\\Web\\SeoController',
        'implementingClassName' => 'Modules\\Storefront\\Http\\Controllers\\Web\\SeoController',
        'name' => 'MAX_URLS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '45000',
          'attributes' => 
          array (
            'startLine' => 20,
            'endLine' => 20,
            'startTokenPos' => 63,
            'startFilePos' => 664,
            'endTokenPos' => 63,
            'endFilePos' => 669,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 20,
        'endLine' => 20,
        'startColumn' => 5,
        'endColumn' => 36,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'robots' => 
      array (
        'name' => 'robots',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Illuminate\\Http\\Response',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 22,
        'endLine' => 32,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Storefront\\Http\\Controllers\\Web',
        'declaringClassName' => 'Modules\\Storefront\\Http\\Controllers\\Web\\SeoController',
        'implementingClassName' => 'Modules\\Storefront\\Http\\Controllers\\Web\\SeoController',
        'currentClassName' => 'Modules\\Storefront\\Http\\Controllers\\Web\\SeoController',
        'aliasName' => NULL,
      ),
      'sitemap' => 
      array (
        'name' => 'sitemap',
        'parameters' => 
        array (
          'catalog' => 
          array (
            'name' => 'catalog',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Catalog\\Contracts\\CatalogReader',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 34,
            'endLine' => 34,
            'startColumn' => 29,
            'endColumn' => 50,
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
            'name' => 'Illuminate\\Http\\Response',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 34,
        'endLine' => 39,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Storefront\\Http\\Controllers\\Web',
        'declaringClassName' => 'Modules\\Storefront\\Http\\Controllers\\Web\\SeoController',
        'implementingClassName' => 'Modules\\Storefront\\Http\\Controllers\\Web\\SeoController',
        'currentClassName' => 'Modules\\Storefront\\Http\\Controllers\\Web\\SeoController',
        'aliasName' => NULL,
      ),
      'build' => 
      array (
        'name' => 'build',
        'parameters' => 
        array (
          'catalog' => 
          array (
            'name' => 'catalog',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Catalog\\Contracts\\CatalogReader',
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
            'startColumn' => 28,
            'endColumn' => 49,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 41,
        'endLine' => 69,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'Modules\\Storefront\\Http\\Controllers\\Web',
        'declaringClassName' => 'Modules\\Storefront\\Http\\Controllers\\Web\\SeoController',
        'implementingClassName' => 'Modules\\Storefront\\Http\\Controllers\\Web\\SeoController',
        'currentClassName' => 'Modules\\Storefront\\Http\\Controllers\\Web\\SeoController',
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