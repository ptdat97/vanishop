<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Storefront/Contracts/StorefrontEnricher.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Storefront\Contracts\StorefrontEnricher
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-0deafb7d4bda20f14777c24f4701d09930da3ff61a9a5d67b18b21531744cd7d',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Storefront\\Contracts\\StorefrontEnricher',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Storefront/Contracts/StorefrontEnricher.php',
      ),
    ),
    'namespace' => 'Modules\\Storefront\\Contracts',
    'name' => 'Modules\\Storefront\\Contracts\\StorefrontEnricher',
    'shortName' => 'StorefrontEnricher',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Extension point (tag `vani.storefront.enrichers`, ADR-030): plugin bổ sung dữ liệu cho tài nguyên storefront.
 * Chạy ở Presenter nên native storefront và Storefront API nhận cùng dữ liệu. Kết quả chỉ được gắn dưới
 * `extensions.<plugin-id>` của từng phần tử; plugin không sửa dữ liệu của Core hay plugin khác.
 *
 * Nhận cả danh sách một lần (batch, tránh N+1). Lỗi → Core bỏ dữ liệu của plugin đó, trang vẫn chạy.
 * Không gọi mạng đồng bộ; đọc dữ liệu riêng của plugin (bảng plg_*, cache).
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 15,
    'endLine' => 31,
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
        'declaringClassName' => 'Modules\\Storefront\\Contracts\\StorefrontEnricher',
        'implementingClassName' => 'Modules\\Storefront\\Contracts\\StorefrontEnricher',
        'name' => 'TAG',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'vani.storefront.enrichers\'',
          'attributes' => 
          array (
            'startLine' => 17,
            'endLine' => 17,
            'startTokenPos' => 31,
            'startFilePos' => 737,
            'endTokenPos' => 31,
            'endFilePos' => 763,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 17,
        'endLine' => 17,
        'startColumn' => 5,
        'endColumn' => 51,
      ),
      'RESOURCES' => 
      array (
        'declaringClassName' => 'Modules\\Storefront\\Contracts\\StorefrontEnricher',
        'implementingClassName' => 'Modules\\Storefront\\Contracts\\StorefrontEnricher',
        'name' => 'RESOURCES',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'product_card\', \'product\', \'cart\', \'order\']',
          'attributes' => 
          array (
            'startLine' => 19,
            'endLine' => 19,
            'startTokenPos' => 42,
            'startFilePos' => 796,
            'endTokenPos' => 53,
            'endFilePos' => 839,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 19,
        'endLine' => 19,
        'startColumn' => 5,
        'endColumn' => 74,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'resource' => 
      array (
        'name' => 'resource',
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
        'docComment' => '/**
 * Tài nguyên áp dụng: product_card (phần tử danh sách) | product (PDP) | cart | order.
 */',
        'startLine' => 24,
        'endLine' => 24,
        'startColumn' => 5,
        'endColumn' => 39,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Storefront\\Contracts',
        'declaringClassName' => 'Modules\\Storefront\\Contracts\\StorefrontEnricher',
        'implementingClassName' => 'Modules\\Storefront\\Contracts\\StorefrontEnricher',
        'currentClassName' => 'Modules\\Storefront\\Contracts\\StorefrontEnricher',
        'aliasName' => NULL,
      ),
      'enrich' => 
      array (
        'name' => 'enrich',
        'parameters' => 
        array (
          'items' => 
          array (
            'name' => 'items',
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
            'startLine' => 30,
            'endLine' => 30,
            'startColumn' => 28,
            'endColumn' => 39,
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
            'startLine' => 30,
            'endLine' => 30,
            'startColumn' => 42,
            'endColumn' => 55,
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
 * @param  list<array<string, mixed>>  $items  dữ liệu đã trình bày của Core (chỉ đọc), mỗi phần tử có `id`
 * @return array<int|string, array<string, mixed>> id → dữ liệu của plugin (bỏ qua phần tử không có gì để thêm)
 */',
        'startLine' => 30,
        'endLine' => 30,
        'startColumn' => 5,
        'endColumn' => 64,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Storefront\\Contracts',
        'declaringClassName' => 'Modules\\Storefront\\Contracts\\StorefrontEnricher',
        'implementingClassName' => 'Modules\\Storefront\\Contracts\\StorefrontEnricher',
        'currentClassName' => 'Modules\\Storefront\\Contracts\\StorefrontEnricher',
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