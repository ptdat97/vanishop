<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Catalog/Testing/SearchProviderContract.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Catalog\Testing\SearchProviderContract
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-640b78066dcad006fa424232ad95f8a7b9ec83cae42f729cac079b2cd2d4bb9e',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Catalog\\Testing\\SearchProviderContract',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Catalog/Testing/SearchProviderContract.php',
      ),
    ),
    'namespace' => 'Modules\\Catalog\\Testing',
    'name' => 'Modules\\Catalog\\Testing\\SearchProviderContract',
    'shortName' => 'SearchProviderContract',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Contract test cho SearchProvider. Plugin cung cấp `scenario`: tạo dữ liệu (nếu cần) và trả
 * [ProductDocument, ProductSearchQuery tìm thấy tài liệu đó]. Kiểm tra: mã ổn định; index idempotent (lặp lại không
 * nhân bản kết quả); tìm thấy sau khi index; remove idempotent và không còn trong kết quả; total ≥ số id trả về.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 20,
    'endLine' => 53,
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
      'define' => 
      array (
        'name' => 'define',
        'parameters' => 
        array (
          'label' => 
          array (
            'name' => 'label',
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
            'startColumn' => 35,
            'endColumn' => 47,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'provider' => 
          array (
            'name' => 'provider',
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
            'startLine' => 27,
            'endLine' => 27,
            'startColumn' => 50,
            'endColumn' => 66,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'scenario' => 
          array (
            'name' => 'scenario',
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
            'startLine' => 27,
            'endLine' => 27,
            'startColumn' => 69,
            'endColumn' => 85,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'removeHidesFromSearch' => 
          array (
            'name' => 'removeHidesFromSearch',
            'default' => 
            array (
              'code' => 'true',
              'attributes' => 
              array (
                'startLine' => 27,
                'endLine' => 27,
                'startTokenPos' => 91,
                'startFilePos' => 1199,
                'endTokenPos' => 91,
                'endFilePos' => 1202,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'bool',
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
            'startColumn' => 88,
            'endColumn' => 121,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param  Closure(): SearchProvider  $provider
 * @param  Closure(): array{0: ProductDocument, 1: ProductSearchQuery}  $scenario
 * @param  bool  $removeHidesFromSearch  false với provider đọc thẳng DB (remove là no-op theo thiết kế)
 */',
        'startLine' => 27,
        'endLine' => 52,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Modules\\Catalog\\Testing',
        'declaringClassName' => 'Modules\\Catalog\\Testing\\SearchProviderContract',
        'implementingClassName' => 'Modules\\Catalog\\Testing\\SearchProviderContract',
        'currentClassName' => 'Modules\\Catalog\\Testing\\SearchProviderContract',
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