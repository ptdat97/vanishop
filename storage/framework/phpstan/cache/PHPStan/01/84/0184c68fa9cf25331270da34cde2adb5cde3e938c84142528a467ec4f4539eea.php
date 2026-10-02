<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Storefront/Application/PageBlocks.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Storefront\Application\PageBlocks
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-2010adc91c001486f1db816e89988034a5d144221fad6872aefcbb84255bf727',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Storefront\\Application\\PageBlocks',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Storefront/Application/PageBlocks.php',
      ),
    ),
    'namespace' => 'Modules\\Storefront\\Application',
    'name' => 'Modules\\Storefront\\Application\\PageBlocks',
    'shortName' => 'PageBlocks',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Page builder trang chủ: danh sách khối (loại + cấu hình) lưu ở cấu hình cửa hàng `core.storefront.home_blocks`.
 * Chưa cấu hình → trang chủ mặc định của theme.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 22,
    'endLine' => 114,
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
      'MAX_BLOCKS' => 
      array (
        'declaringClassName' => 'Modules\\Storefront\\Application\\PageBlocks',
        'implementingClassName' => 'Modules\\Storefront\\Application\\PageBlocks',
        'name' => 'MAX_BLOCKS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '30',
          'attributes' => 
          array (
            'startLine' => 24,
            'endLine' => 24,
            'startTokenPos' => 83,
            'startFilePos' => 737,
            'endTokenPos' => 83,
            'endFilePos' => 738,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 24,
        'endLine' => 24,
        'startColumn' => 5,
        'endColumn' => 33,
      ),
      'SETTING' => 
      array (
        'declaringClassName' => 'Modules\\Storefront\\Application\\PageBlocks',
        'implementingClassName' => 'Modules\\Storefront\\Application\\PageBlocks',
        'name' => 'SETTING',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'storefront.home_blocks\'',
          'attributes' => 
          array (
            'startLine' => 26,
            'endLine' => 26,
            'startTokenPos' => 94,
            'startFilePos' => 770,
            'endTokenPos' => 94,
            'endFilePos' => 793,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 26,
        'endLine' => 26,
        'startColumn' => 5,
        'endColumn' => 53,
      ),
    ),
    'immediateProperties' => 
    array (
      'extensions' => 
      array (
        'declaringClassName' => 'Modules\\Storefront\\Application\\PageBlocks',
        'implementingClassName' => 'Modules\\Storefront\\Application\\PageBlocks',
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
        'startLine' => 29,
        'endLine' => 29,
        'startColumn' => 9,
        'endColumn' => 47,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'settings' => 
      array (
        'declaringClassName' => 'Modules\\Storefront\\Application\\PageBlocks',
        'implementingClassName' => 'Modules\\Storefront\\Application\\PageBlocks',
        'name' => 'settings',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Modules\\Tenancy\\Contracts\\Settings',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 30,
        'endLine' => 30,
        'startColumn' => 9,
        'endColumn' => 43,
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
            'startLine' => 29,
            'endLine' => 29,
            'startColumn' => 9,
            'endColumn' => 47,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'settings' => 
          array (
            'name' => 'settings',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Tenancy\\Contracts\\Settings',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 30,
            'endLine' => 30,
            'startColumn' => 9,
            'endColumn' => 43,
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
        'startLine' => 28,
        'endLine' => 31,
        'startColumn' => 5,
        'endColumn' => 8,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Storefront\\Application',
        'declaringClassName' => 'Modules\\Storefront\\Application\\PageBlocks',
        'implementingClassName' => 'Modules\\Storefront\\Application\\PageBlocks',
        'currentClassName' => 'Modules\\Storefront\\Application\\PageBlocks',
        'aliasName' => NULL,
      ),
      'types' => 
      array (
        'name' => 'types',
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
 * @return array<string, StorefrontBlock>
 */',
        'startLine' => 36,
        'endLine' => 39,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Storefront\\Application',
        'declaringClassName' => 'Modules\\Storefront\\Application\\PageBlocks',
        'implementingClassName' => 'Modules\\Storefront\\Application\\PageBlocks',
        'currentClassName' => 'Modules\\Storefront\\Application\\PageBlocks',
        'aliasName' => NULL,
      ),
      'home' => 
      array (
        'name' => 'home',
        'parameters' => 
        array (
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
 * @return list<array{type: string, config: array<string, mixed>}>|null null = chưa cấu hình
 */',
        'startLine' => 44,
        'endLine' => 49,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Storefront\\Application',
        'declaringClassName' => 'Modules\\Storefront\\Application\\PageBlocks',
        'implementingClassName' => 'Modules\\Storefront\\Application\\PageBlocks',
        'currentClassName' => 'Modules\\Storefront\\Application\\PageBlocks',
        'aliasName' => NULL,
      ),
      'saveHome' => 
      array (
        'name' => 'saveHome',
        'parameters' => 
        array (
          'blocks' => 
          array (
            'name' => 'blocks',
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
            'startLine' => 54,
            'endLine' => 54,
            'startColumn' => 30,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param  list<array{type?: mixed, config?: mixed}>  $blocks
 */',
        'startLine' => 54,
        'endLine' => 79,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Storefront\\Application',
        'declaringClassName' => 'Modules\\Storefront\\Application\\PageBlocks',
        'implementingClassName' => 'Modules\\Storefront\\Application\\PageBlocks',
        'currentClassName' => 'Modules\\Storefront\\Application\\PageBlocks',
        'aliasName' => NULL,
      ),
      'resetHome' => 
      array (
        'name' => 'resetHome',
        'parameters' => 
        array (
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
        'docComment' => NULL,
        'startLine' => 81,
        'endLine' => 84,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Storefront\\Application',
        'declaringClassName' => 'Modules\\Storefront\\Application\\PageBlocks',
        'implementingClassName' => 'Modules\\Storefront\\Application\\PageBlocks',
        'currentClassName' => 'Modules\\Storefront\\Application\\PageBlocks',
        'aliasName' => NULL,
      ),
      'render' => 
      array (
        'name' => 'render',
        'parameters' => 
        array (
          'blocks' => 
          array (
            'name' => 'blocks',
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
            'startLine' => 92,
            'endLine' => 92,
            'startColumn' => 28,
            'endColumn' => 40,
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
 * Render các khối đã cấu hình: khối lỗi (loại không còn, resolve/render lỗi) bị bỏ, ghi log.
 *
 * @param  list<array{type: string, config: array<string, mixed>}>  $blocks
 * @return list<array{type: string, html: HtmlString}>
 */',
        'startLine' => 92,
        'endLine' => 113,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Storefront\\Application',
        'declaringClassName' => 'Modules\\Storefront\\Application\\PageBlocks',
        'implementingClassName' => 'Modules\\Storefront\\Application\\PageBlocks',
        'currentClassName' => 'Modules\\Storefront\\Application\\PageBlocks',
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