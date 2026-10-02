<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Storefront/Contracts/StorefrontBlock.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Storefront\Contracts\StorefrontBlock
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-8d6a4a5da9f1b49cc29a1b1ad485eed051ac64a43abcd4423b2ecd01981b4a3e',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Storefront\\Contracts\\StorefrontBlock',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Storefront/Contracts/StorefrontBlock.php',
      ),
    ),
    'namespace' => 'Modules\\Storefront\\Contracts',
    'name' => 'Modules\\Storefront\\Contracts\\StorefrontBlock',
    'shortName' => 'StorefrontBlock',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Extension point (tag `vani.storefront.blocks`, storefront §4, ADR-030 W6): khối của page builder (trang chủ).
 * Quản trị chọn khối + cấu hình theo `fields()` (Core validate); khi render Core gọi `resolve()` rồi render `view()`
 * với dữ liệu trả về. Lỗi ở `resolve()` hoặc lúc render → bỏ khối đó, trang vẫn hiển thị.
 * View: core `theme::blocks.<type>`; plugin dùng namespace view riêng (theme override được).
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 15,
    'endLine' => 38,
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
        'declaringClassName' => 'Modules\\Storefront\\Contracts\\StorefrontBlock',
        'implementingClassName' => 'Modules\\Storefront\\Contracts\\StorefrontBlock',
        'name' => 'TAG',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'vani.storefront.blocks\'',
          'attributes' => 
          array (
            'startLine' => 17,
            'endLine' => 17,
            'startTokenPos' => 36,
            'startFilePos' => 663,
            'endTokenPos' => 36,
            'endFilePos' => 686,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 17,
        'endLine' => 17,
        'startColumn' => 5,
        'endColumn' => 48,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'type' => 
      array (
        'name' => 'type',
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
        'docComment' => '/** Mã ổn định (snake_case), lưu trong cấu hình trang. */',
        'startLine' => 20,
        'endLine' => 20,
        'startColumn' => 5,
        'endColumn' => 35,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Storefront\\Contracts',
        'declaringClassName' => 'Modules\\Storefront\\Contracts\\StorefrontBlock',
        'implementingClassName' => 'Modules\\Storefront\\Contracts\\StorefrontBlock',
        'currentClassName' => 'Modules\\Storefront\\Contracts\\StorefrontBlock',
        'aliasName' => NULL,
      ),
      'label' => 
      array (
        'name' => 'label',
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
        'startLine' => 22,
        'endLine' => 22,
        'startColumn' => 5,
        'endColumn' => 36,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Storefront\\Contracts',
        'declaringClassName' => 'Modules\\Storefront\\Contracts\\StorefrontBlock',
        'implementingClassName' => 'Modules\\Storefront\\Contracts\\StorefrontBlock',
        'currentClassName' => 'Modules\\Storefront\\Contracts\\StorefrontBlock',
        'aliasName' => NULL,
      ),
      'fields' => 
      array (
        'name' => 'fields',
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
 * @return list<FieldDefinition>
 */',
        'startLine' => 27,
        'endLine' => 27,
        'startColumn' => 5,
        'endColumn' => 36,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Storefront\\Contracts',
        'declaringClassName' => 'Modules\\Storefront\\Contracts\\StorefrontBlock',
        'implementingClassName' => 'Modules\\Storefront\\Contracts\\StorefrontBlock',
        'currentClassName' => 'Modules\\Storefront\\Contracts\\StorefrontBlock',
        'aliasName' => NULL,
      ),
      'resolve' => 
      array (
        'name' => 'resolve',
        'parameters' => 
        array (
          'config' => 
          array (
            'name' => 'config',
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
            'startLine' => 35,
            'endLine' => 35,
            'startColumn' => 29,
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
            'startLine' => 35,
            'endLine' => 35,
            'startColumn' => 44,
            'endColumn' => 57,
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
 * Lấy dữ liệu hiển thị qua contract/Query (không I/O mạng đồng bộ).
 *
 * @param  array<string, mixed>  $config  giá trị đã validate theo fields()
 * @return array<string, mixed>
 */',
        'startLine' => 35,
        'endLine' => 35,
        'startColumn' => 5,
        'endColumn' => 66,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Storefront\\Contracts',
        'declaringClassName' => 'Modules\\Storefront\\Contracts\\StorefrontBlock',
        'implementingClassName' => 'Modules\\Storefront\\Contracts\\StorefrontBlock',
        'currentClassName' => 'Modules\\Storefront\\Contracts\\StorefrontBlock',
        'aliasName' => NULL,
      ),
      'view' => 
      array (
        'name' => 'view',
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
        'startLine' => 37,
        'endLine' => 37,
        'startColumn' => 5,
        'endColumn' => 35,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Storefront\\Contracts',
        'declaringClassName' => 'Modules\\Storefront\\Contracts\\StorefrontBlock',
        'implementingClassName' => 'Modules\\Storefront\\Contracts\\StorefrontBlock',
        'currentClassName' => 'Modules\\Storefront\\Contracts\\StorefrontBlock',
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