<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Catalog/Contracts/SearchProvider.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Catalog\Contracts\SearchProvider
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-447c5966507c054442ccbcc168362272b2ca8a6535de9a219d19e8c66fc449d2',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Catalog\\Contracts\\SearchProvider',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Catalog/Contracts/SearchProvider.php',
      ),
    ),
    'namespace' => 'Modules\\Catalog\\Contracts',
    'name' => 'Modules\\Catalog\\Contracts\\SearchProvider',
    'shortName' => 'SearchProvider',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Extension point: nhà cung cấp tìm kiếm sản phẩm (tag vani.search.providers).
 * Core có "database"; chỉ mục ngoài là plugin: vani.search-meilisearch (Algolia, Elasticsearch… tương tự). Provider có chỉ mục
 * cần cấu hình implement thêm ConfigurableSearchIndex.
 *
 * @see docs/04-extension/extension-point-catalog.md
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 18,
    'endLine' => 36,
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
        'declaringClassName' => 'Modules\\Catalog\\Contracts\\SearchProvider',
        'implementingClassName' => 'Modules\\Catalog\\Contracts\\SearchProvider',
        'name' => 'TAG',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'vani.search.providers\'',
          'attributes' => 
          array (
            'startLine' => 21,
            'endLine' => 21,
            'startTokenPos' => 48,
            'startFilePos' => 742,
            'endTokenPos' => 48,
            'endFilePos' => 764,
          ),
        ),
        'docComment' => '/** Tag extension point: plugin đóng góp qua `contribute(SearchProvider::TAG, …)`. */',
        'attributes' => 
        array (
        ),
        'startLine' => 21,
        'endLine' => 21,
        'startColumn' => 5,
        'endColumn' => 47,
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
        'startLine' => 23,
        'endLine' => 23,
        'startColumn' => 5,
        'endColumn' => 35,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Catalog\\Contracts',
        'declaringClassName' => 'Modules\\Catalog\\Contracts\\SearchProvider',
        'implementingClassName' => 'Modules\\Catalog\\Contracts\\SearchProvider',
        'currentClassName' => 'Modules\\Catalog\\Contracts\\SearchProvider',
        'aliasName' => NULL,
      ),
      'index' => 
      array (
        'name' => 'index',
        'parameters' => 
        array (
          'document' => 
          array (
            'name' => 'document',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Catalog\\Contracts\\Data\\ProductDocument',
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
            'startColumn' => 27,
            'endColumn' => 51,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Thêm/cập nhật tài liệu. Idempotent.
 */',
        'startLine' => 28,
        'endLine' => 28,
        'startColumn' => 5,
        'endColumn' => 59,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Catalog\\Contracts',
        'declaringClassName' => 'Modules\\Catalog\\Contracts\\SearchProvider',
        'implementingClassName' => 'Modules\\Catalog\\Contracts\\SearchProvider',
        'currentClassName' => 'Modules\\Catalog\\Contracts\\SearchProvider',
        'aliasName' => NULL,
      ),
      'remove' => 
      array (
        'name' => 'remove',
        'parameters' => 
        array (
          'styleId' => 
          array (
            'name' => 'styleId',
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
            'startColumn' => 28,
            'endColumn' => 39,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Gỡ style khỏi chỉ mục. Idempotent.
 */',
        'startLine' => 33,
        'endLine' => 33,
        'startColumn' => 5,
        'endColumn' => 47,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Catalog\\Contracts',
        'declaringClassName' => 'Modules\\Catalog\\Contracts\\SearchProvider',
        'implementingClassName' => 'Modules\\Catalog\\Contracts\\SearchProvider',
        'currentClassName' => 'Modules\\Catalog\\Contracts\\SearchProvider',
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
            'startLine' => 35,
            'endLine' => 35,
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
        'docComment' => NULL,
        'startLine' => 35,
        'endLine' => 35,
        'startColumn' => 5,
        'endColumn' => 75,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Catalog\\Contracts',
        'declaringClassName' => 'Modules\\Catalog\\Contracts\\SearchProvider',
        'implementingClassName' => 'Modules\\Catalog\\Contracts\\SearchProvider',
        'currentClassName' => 'Modules\\Catalog\\Contracts\\SearchProvider',
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