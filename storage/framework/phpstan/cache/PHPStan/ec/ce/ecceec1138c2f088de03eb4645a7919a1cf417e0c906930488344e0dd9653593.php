<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Catalog/Application/Search/SearchManager.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Catalog\Application\Search\SearchManager
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-a7edbc1fefe67bd6995aed96382813b5aefd0c5d3b18538573221f26df589a85',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Catalog\\Application\\Search\\SearchManager',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Catalog/Application/Search/SearchManager.php',
      ),
    ),
    'namespace' => 'Modules\\Catalog\\Application\\Search',
    'name' => 'Modules\\Catalog\\Application\\Search\\SearchManager',
    'shortName' => 'SearchManager',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Chọn SearchProvider theo cấu hình (VANI_SEARCH_PROVIDER, mặc định `database`; `meilisearch` cần plugin vani.search-meilisearch) trong các provider có hiệu lực trong phạm vi hiện tại
 * (SearchProvider do plugin cung cấp chỉ có mặt khi plugin được bật).
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 17,
    'endLine' => 52,
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
      'FALLBACK' => 
      array (
        'declaringClassName' => 'Modules\\Catalog\\Application\\Search\\SearchManager',
        'implementingClassName' => 'Modules\\Catalog\\Application\\Search\\SearchManager',
        'name' => 'FALLBACK',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'database\'',
          'attributes' => 
          array (
            'startLine' => 19,
            'endLine' => 19,
            'startTokenPos' => 58,
            'startFilePos' => 670,
            'endTokenPos' => 58,
            'endFilePos' => 679,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 19,
        'endLine' => 19,
        'startColumn' => 5,
        'endColumn' => 40,
      ),
    ),
    'immediateProperties' => 
    array (
      'extensions' => 
      array (
        'declaringClassName' => 'Modules\\Catalog\\Application\\Search\\SearchManager',
        'implementingClassName' => 'Modules\\Catalog\\Application\\Search\\SearchManager',
        'name' => 'extensions',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Modules\\Extension\\Contracts\\Extensions',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 22,
        'endLine' => 22,
        'startColumn' => 9,
        'endColumn' => 47,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'providerCode' => 
      array (
        'declaringClassName' => 'Modules\\Catalog\\Application\\Search\\SearchManager',
        'implementingClassName' => 'Modules\\Catalog\\Application\\Search\\SearchManager',
        'name' => 'providerCode',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 23,
        'endLine' => 23,
        'startColumn' => 9,
        'endColumn' => 45,
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
          'extensions' => 
          array (
            'name' => 'extensions',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Extension\\Contracts\\Extensions',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 22,
            'endLine' => 22,
            'startColumn' => 9,
            'endColumn' => 47,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'providerCode' => 
          array (
            'name' => 'providerCode',
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 23,
            'endLine' => 23,
            'startColumn' => 9,
            'endColumn' => 45,
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
        'startLine' => 21,
        'endLine' => 24,
        'startColumn' => 5,
        'endColumn' => 8,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Catalog\\Application\\Search',
        'declaringClassName' => 'Modules\\Catalog\\Application\\Search\\SearchManager',
        'implementingClassName' => 'Modules\\Catalog\\Application\\Search\\SearchManager',
        'currentClassName' => 'Modules\\Catalog\\Application\\Search\\SearchManager',
        'aliasName' => NULL,
      ),
      'search' => 
      array (
        'name' => 'search',
        'parameters' => 
        array (
          'query' => 
          array (
            'name' => 'query',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Catalog\\Contracts\\Data\\ProductSearchQuery',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 30,
            'endLine' => 30,
            'startColumn' => 28,
            'endColumn' => 52,
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
            'name' => 'Modules\\Catalog\\Contracts\\Data\\ProductSearchResult',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Tìm kiếm cho storefront. Provider cấu hình (vd. Meilisearch) lỗi → dùng provider `database` để trang danh
 * sách vẫn hoạt động.
 */',
        'startLine' => 30,
        'endLine' => 40,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Catalog\\Application\\Search',
        'declaringClassName' => 'Modules\\Catalog\\Application\\Search\\SearchManager',
        'implementingClassName' => 'Modules\\Catalog\\Application\\Search\\SearchManager',
        'currentClassName' => 'Modules\\Catalog\\Application\\Search\\SearchManager',
        'aliasName' => NULL,
      ),
      'provider' => 
      array (
        'name' => 'provider',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Modules\\Catalog\\Contracts\\SearchProvider',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Provider theo VANI_SEARCH_PROVIDER; provider đó không có hiệu lực (plugin chưa cài/bật) → `database` + cảnh báo,
 * để storefront và đồng bộ chỉ mục vẫn chạy.
 */',
        'startLine' => 46,
        'endLine' => 51,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Catalog\\Application\\Search',
        'declaringClassName' => 'Modules\\Catalog\\Application\\Search\\SearchManager',
        'implementingClassName' => 'Modules\\Catalog\\Application\\Search\\SearchManager',
        'currentClassName' => 'Modules\\Catalog\\Application\\Search\\SearchManager',
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