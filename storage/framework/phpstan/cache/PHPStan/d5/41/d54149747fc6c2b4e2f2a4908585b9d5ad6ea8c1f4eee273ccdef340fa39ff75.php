<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Catalog/Contracts/CatalogReader.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Catalog\Contracts\CatalogReader
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-a66874becada5122169bcb1f45b3a7cf8e763c0240389a594f5a3cb3f4802a0b',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Catalog\\Contracts\\CatalogReader',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Catalog/Contracts/CatalogReader.php',
      ),
    ),
    'namespace' => 'Modules\\Catalog\\Contracts',
    'name' => 'Modules\\Catalog\\Contracts\\CatalogReader',
    'shortName' => 'CatalogReader',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Service contract: đọc catalog đang hiển thị cho storefront.
 * Dữ liệu trả về là mảng thuần (DTO dạng mảng), không có model.
 *
 * @phpstan-type VariantView array{id: int, sku: string, color_code: string, size_code: string, size_system: string}
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 16,
    'endLine' => 64,
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
      'categoryTree' => 
      array (
        'name' => 'categoryTree',
        'parameters' => 
        array (
          'locale' => 
          array (
            'name' => 'locale',
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
            'startLine' => 23,
            'endLine' => 23,
            'startColumn' => 34,
            'endColumn' => 47,
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
 * Cây danh mục đang hiển thị.
 *
 * @return list<array<string, mixed>>
 */',
        'startLine' => 23,
        'endLine' => 23,
        'startColumn' => 5,
        'endColumn' => 56,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Catalog\\Contracts',
        'declaringClassName' => 'Modules\\Catalog\\Contracts\\CatalogReader',
        'implementingClassName' => 'Modules\\Catalog\\Contracts\\CatalogReader',
        'currentClassName' => 'Modules\\Catalog\\Contracts\\CatalogReader',
        'aliasName' => NULL,
      ),
      'category' => 
      array (
        'name' => 'category',
        'parameters' => 
        array (
          'slug' => 
          array (
            'name' => 'slug',
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
            'startLine' => 28,
            'endLine' => 28,
            'startColumn' => 30,
            'endColumn' => 41,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'locale' => 
          array (
            'name' => 'locale',
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
            'startLine' => 28,
            'endLine' => 28,
            'startColumn' => 44,
            'endColumn' => 57,
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
 * @return array<string, mixed>|null
 */',
        'startLine' => 28,
        'endLine' => 28,
        'startColumn' => 5,
        'endColumn' => 67,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Catalog\\Contracts',
        'declaringClassName' => 'Modules\\Catalog\\Contracts\\CatalogReader',
        'implementingClassName' => 'Modules\\Catalog\\Contracts\\CatalogReader',
        'currentClassName' => 'Modules\\Catalog\\Contracts\\CatalogReader',
        'aliasName' => NULL,
      ),
      'brands' => 
      array (
        'name' => 'brands',
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
 * Thương hiệu đang hiện (status = active), theo thứ tự hiển thị.
 *
 * @return list<array{id: int, code: string, slug: string, name: string, description: ?string, logo_url: ?string}>
 */',
        'startLine' => 35,
        'endLine' => 35,
        'startColumn' => 5,
        'endColumn' => 36,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Catalog\\Contracts',
        'declaringClassName' => 'Modules\\Catalog\\Contracts\\CatalogReader',
        'implementingClassName' => 'Modules\\Catalog\\Contracts\\CatalogReader',
        'currentClassName' => 'Modules\\Catalog\\Contracts\\CatalogReader',
        'aliasName' => NULL,
      ),
      'brand' => 
      array (
        'name' => 'brand',
        'parameters' => 
        array (
          'slug' => 
          array (
            'name' => 'slug',
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
            'startLine' => 40,
            'endLine' => 40,
            'startColumn' => 27,
            'endColumn' => 38,
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
 * @return array{id: int, code: string, slug: string, name: string, description: ?string, logo_url: ?string, meta_title: ?string, meta_description: ?string}|null
 */',
        'startLine' => 40,
        'endLine' => 40,
        'startColumn' => 5,
        'endColumn' => 48,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Catalog\\Contracts',
        'declaringClassName' => 'Modules\\Catalog\\Contracts\\CatalogReader',
        'implementingClassName' => 'Modules\\Catalog\\Contracts\\CatalogReader',
        'currentClassName' => 'Modules\\Catalog\\Contracts\\CatalogReader',
        'aliasName' => NULL,
      ),
      'searchProducts' => 
      array (
        'name' => 'searchProducts',
        'parameters' => 
        array (
          'filters' => 
          array (
            'name' => 'filters',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Catalog\\Contracts\\Data\\ProductFilters',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 47,
            'endLine' => 47,
            'startColumn' => 36,
            'endColumn' => 58,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'locale' => 
          array (
            'name' => 'locale',
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
            'startLine' => 47,
            'endLine' => 47,
            'startColumn' => 61,
            'endColumn' => 74,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Danh sách sản phẩm đang hiển thị; mỗi item có "variant_ids" (các variant đang bán).
 *
 * @return array{items: list<array<string, mixed>>, total: int, page: int, per_page: int, facets: array{color_families: array<string, int>, attributes: list<array<string, mixed>>, brands: list<array{slug: string, name: string, count: int}>}}
 */',
        'startLine' => 47,
        'endLine' => 47,
        'startColumn' => 5,
        'endColumn' => 83,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Catalog\\Contracts',
        'declaringClassName' => 'Modules\\Catalog\\Contracts\\CatalogReader',
        'implementingClassName' => 'Modules\\Catalog\\Contracts\\CatalogReader',
        'currentClassName' => 'Modules\\Catalog\\Contracts\\CatalogReader',
        'aliasName' => NULL,
      ),
      'productDetail' => 
      array (
        'name' => 'productDetail',
        'parameters' => 
        array (
          'slug' => 
          array (
            'name' => 'slug',
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
            'startLine' => 54,
            'endLine' => 54,
            'startColumn' => 35,
            'endColumn' => 46,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'locale' => 
          array (
            'name' => 'locale',
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
            'startLine' => 54,
            'endLine' => 54,
            'startColumn' => 49,
            'endColumn' => 62,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'now' => 
          array (
            'name' => 'now',
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
            'startLine' => 54,
            'endLine' => 54,
            'startColumn' => 65,
            'endColumn' => 72,
            'parameterIndex' => 2,
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
 * Chi tiết sản phẩm đang hiển thị; có "variants" (list<VariantView>) các variant đang bán.
 *
 * @return array<string, mixed>|null
 */',
        'startLine' => 54,
        'endLine' => 54,
        'startColumn' => 5,
        'endColumn' => 82,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Catalog\\Contracts',
        'declaringClassName' => 'Modules\\Catalog\\Contracts\\CatalogReader',
        'implementingClassName' => 'Modules\\Catalog\\Contracts\\CatalogReader',
        'currentClassName' => 'Modules\\Catalog\\Contracts\\CatalogReader',
        'aliasName' => NULL,
      ),
      'sellableVariants' => 
      array (
        'name' => 'sellableVariants',
        'parameters' => 
        array (
          'variantIds' => 
          array (
            'name' => 'variantIds',
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
            'startLine' => 63,
            'endLine' => 63,
            'startColumn' => 38,
            'endColumn' => 54,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'locale' => 
          array (
            'name' => 'locale',
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
            'startLine' => 63,
            'endLine' => 63,
            'startColumn' => 57,
            'endColumn' => 70,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'now' => 
          array (
            'name' => 'now',
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
            'startLine' => 63,
            'endLine' => 63,
            'startColumn' => 73,
            'endColumn' => 80,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Variant đang bán được (variant active + sản phẩm đang hiển thị) — cho giỏ hàng, checkout.
 * Variant không bán được thì không có mặt.
 *
 * @param  list<int>  $variantIds
 * @return array<int, SellableVariant>
 */',
        'startLine' => 63,
        'endLine' => 63,
        'startColumn' => 5,
        'endColumn' => 89,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Catalog\\Contracts',
        'declaringClassName' => 'Modules\\Catalog\\Contracts\\CatalogReader',
        'implementingClassName' => 'Modules\\Catalog\\Contracts\\CatalogReader',
        'currentClassName' => 'Modules\\Catalog\\Contracts\\CatalogReader',
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