<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Storefront/Testing/StorefrontEnricherContract.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Storefront\Testing\StorefrontEnricherContract
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-43f2d3429f80e005aaf91b856a69007ad24f6792a9b74be75074b558c6eceacf',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Storefront\\Testing\\StorefrontEnricherContract',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Storefront/Testing/StorefrontEnricherContract.php',
      ),
    ),
    'namespace' => 'Modules\\Storefront\\Testing',
    'name' => 'Modules\\Storefront\\Testing\\StorefrontEnricherContract',
    'shortName' => 'StorefrontEnricherContract',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Contract test cho StorefrontEnricher: tài nguyên hợp lệ; chỉ trả dữ liệu cho phần tử được đưa vào; dữ liệu là
 * mảng giá trị JSON được; danh sách rỗng → rỗng; xác định.
 *
 *   StorefrontEnricherContract::define(\'vani.reviews\', fn () => app(RatingEnricher::class), items: fn () => [[\'id\' => 1, \'slug\' => \'a\']]);
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 16,
    'endLine' => 41,
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
            'startLine' => 22,
            'endLine' => 22,
            'startColumn' => 35,
            'endColumn' => 47,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'enricher' => 
          array (
            'name' => 'enricher',
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
            'startLine' => 22,
            'endLine' => 22,
            'startColumn' => 50,
            'endColumn' => 66,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'items' => 
          array (
            'name' => 'items',
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
            'startLine' => 22,
            'endLine' => 22,
            'startColumn' => 69,
            'endColumn' => 82,
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
        'docComment' => '/**
 * @param  Closure(): StorefrontEnricher  $enricher
 * @param  Closure(): list<array<string, mixed>>  $items  phần tử mẫu (có `id`) — có thể tạo dữ liệu trước khi trả
 */',
        'startLine' => 22,
        'endLine' => 40,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Modules\\Storefront\\Testing',
        'declaringClassName' => 'Modules\\Storefront\\Testing\\StorefrontEnricherContract',
        'implementingClassName' => 'Modules\\Storefront\\Testing\\StorefrontEnricherContract',
        'currentClassName' => 'Modules\\Storefront\\Testing\\StorefrontEnricherContract',
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