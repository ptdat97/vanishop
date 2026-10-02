<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Storefront/Application/Theme/Themes.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Storefront\Application\Theme\Themes
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-092c41ba4d396f6f9b2492757e049467bc077683316241fd3e1b3dc6e681a114',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Storefront\\Application\\Theme\\Themes',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Storefront/Application/Theme/Themes.php',
      ),
    ),
    'namespace' => 'Modules\\Storefront\\Application\\Theme',
    'name' => 'Modules\\Storefront\\Application\\Theme\\Themes',
    'shortName' => 'Themes',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Theme của native storefront (storefront §2, ADR-025): một theme đang hoạt động cho cả cửa hàng (cấu hình
 * `core.theme`), view tìm theo chuỗi theme đang hoạt động → theme cha → … → `vani-base`.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 15,
    'endLine' => 136,
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
      'BASE' => 
      array (
        'declaringClassName' => 'Modules\\Storefront\\Application\\Theme\\Themes',
        'implementingClassName' => 'Modules\\Storefront\\Application\\Theme\\Themes',
        'name' => 'BASE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'vani-base\'',
          'attributes' => 
          array (
            'startLine' => 17,
            'endLine' => 17,
            'startTokenPos' => 48,
            'startFilePos' => 465,
            'endTokenPos' => 48,
            'endFilePos' => 475,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 17,
        'endLine' => 17,
        'startColumn' => 5,
        'endColumn' => 36,
      ),
      'MAX_DEPTH' => 
      array (
        'declaringClassName' => 'Modules\\Storefront\\Application\\Theme\\Themes',
        'implementingClassName' => 'Modules\\Storefront\\Application\\Theme\\Themes',
        'name' => 'MAX_DEPTH',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '5',
          'attributes' => 
          array (
            'startLine' => 20,
            'endLine' => 20,
            'startTokenPos' => 61,
            'startFilePos' => 602,
            'endTokenPos' => 61,
            'endFilePos' => 602,
          ),
        ),
        'docComment' => '/** Giới hạn độ sâu chuỗi theme cha (chống vòng lặp do khai báo sai). */',
        'attributes' => 
        array (
        ),
        'startLine' => 20,
        'endLine' => 20,
        'startColumn' => 5,
        'endColumn' => 32,
      ),
    ),
    'immediateProperties' => 
    array (
      'themes' => 
      array (
        'declaringClassName' => 'Modules\\Storefront\\Application\\Theme\\Themes',
        'implementingClassName' => 'Modules\\Storefront\\Application\\Theme\\Themes',
        'name' => 'themes',
        'modifiers' => 4,
        'type' => 
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
        'default' => 
        array (
          'code' => 'null',
          'attributes' => 
          array (
            'startLine' => 23,
            'endLine' => 23,
            'startTokenPos' => 75,
            'startFilePos' => 677,
            'endTokenPos' => 75,
            'endFilePos' => 680,
          ),
        ),
        'docComment' => '/** @var array<string, Theme>|null */',
        'attributes' => 
        array (
        ),
        'startLine' => 23,
        'endLine' => 23,
        'startColumn' => 5,
        'endColumn' => 34,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'path' => 
      array (
        'declaringClassName' => 'Modules\\Storefront\\Application\\Theme\\Themes',
        'implementingClassName' => 'Modules\\Storefront\\Application\\Theme\\Themes',
        'name' => 'path',
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
        'startLine' => 26,
        'endLine' => 26,
        'startColumn' => 9,
        'endColumn' => 37,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'settings' => 
      array (
        'declaringClassName' => 'Modules\\Storefront\\Application\\Theme\\Themes',
        'implementingClassName' => 'Modules\\Storefront\\Application\\Theme\\Themes',
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
        'startLine' => 27,
        'endLine' => 27,
        'startColumn' => 9,
        'endColumn' => 43,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'default' => 
      array (
        'declaringClassName' => 'Modules\\Storefront\\Application\\Theme\\Themes',
        'implementingClassName' => 'Modules\\Storefront\\Application\\Theme\\Themes',
        'name' => 'default',
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
        'startLine' => 28,
        'endLine' => 28,
        'startColumn' => 9,
        'endColumn' => 40,
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
          'path' => 
          array (
            'name' => 'path',
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
            'startLine' => 26,
            'endLine' => 26,
            'startColumn' => 9,
            'endColumn' => 37,
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
            'startLine' => 27,
            'endLine' => 27,
            'startColumn' => 9,
            'endColumn' => 43,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'default' => 
          array (
            'name' => 'default',
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
            'startLine' => 28,
            'endLine' => 28,
            'startColumn' => 9,
            'endColumn' => 40,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 25,
        'endLine' => 29,
        'startColumn' => 5,
        'endColumn' => 8,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Storefront\\Application\\Theme',
        'declaringClassName' => 'Modules\\Storefront\\Application\\Theme\\Themes',
        'implementingClassName' => 'Modules\\Storefront\\Application\\Theme\\Themes',
        'currentClassName' => 'Modules\\Storefront\\Application\\Theme\\Themes',
        'aliasName' => NULL,
      ),
      'all' => 
      array (
        'name' => 'all',
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
 * @return array<string, Theme>
 */',
        'startLine' => 34,
        'endLine' => 62,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Storefront\\Application\\Theme',
        'declaringClassName' => 'Modules\\Storefront\\Application\\Theme\\Themes',
        'implementingClassName' => 'Modules\\Storefront\\Application\\Theme\\Themes',
        'currentClassName' => 'Modules\\Storefront\\Application\\Theme\\Themes',
        'aliasName' => NULL,
      ),
      'active' => 
      array (
        'name' => 'active',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Modules\\Storefront\\Application\\Theme\\Theme',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Theme đang hoạt động; cấu hình trỏ tới theme không có → mặc định → `vani-base`.
 */',
        'startLine' => 67,
        'endLine' => 74,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Storefront\\Application\\Theme',
        'declaringClassName' => 'Modules\\Storefront\\Application\\Theme\\Themes',
        'implementingClassName' => 'Modules\\Storefront\\Application\\Theme\\Themes',
        'currentClassName' => 'Modules\\Storefront\\Application\\Theme\\Themes',
        'aliasName' => NULL,
      ),
      'chain' => 
      array (
        'name' => 'chain',
        'parameters' => 
        array (
          'theme' => 
          array (
            'name' => 'theme',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 81,
                'endLine' => 81,
                'startTokenPos' => 529,
                'startFilePos' => 2680,
                'endTokenPos' => 529,
                'endFilePos' => 2683,
              ),
            ),
            'type' => 
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
                      'name' => 'Modules\\Storefront\\Application\\Theme\\Theme',
                      'isIdentifier' => false,
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
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 81,
            'endLine' => 81,
            'startColumn' => 27,
            'endColumn' => 46,
            'parameterIndex' => 0,
            'isOptional' => true,
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
 * Chuỗi theme để tìm view: theme đang hoạt động → cha → … (luôn kết thúc ở `vani-base`).
 *
 * @return list<Theme>
 */',
        'startLine' => 81,
        'endLine' => 97,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Storefront\\Application\\Theme',
        'declaringClassName' => 'Modules\\Storefront\\Application\\Theme\\Themes',
        'implementingClassName' => 'Modules\\Storefront\\Application\\Theme\\Themes',
        'currentClassName' => 'Modules\\Storefront\\Application\\Theme\\Themes',
        'aliasName' => NULL,
      ),
      'tokens' => 
      array (
        'name' => 'tokens',
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
 * Token giao diện: mặc định của chuỗi theme (cha trước, con ghi đè) rồi cấu hình `theme.tokens` của cửa hàng.
 *
 * @return array<string, string>
 */',
        'startLine' => 104,
        'endLine' => 127,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Storefront\\Application\\Theme',
        'declaringClassName' => 'Modules\\Storefront\\Application\\Theme\\Themes',
        'implementingClassName' => 'Modules\\Storefront\\Application\\Theme\\Themes',
        'currentClassName' => 'Modules\\Storefront\\Application\\Theme\\Themes',
        'aliasName' => NULL,
      ),
      'safeValue' => 
      array (
        'name' => 'safeValue',
        'parameters' => 
        array (
          'value' => 
          array (
            'name' => 'value',
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
            'startLine' => 132,
            'endLine' => 132,
            'startColumn' => 39,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Giá trị token đi vào `<style>`: chỉ cho ký tự của màu/độ dài/font, chặn `;{}<>` để không thoát khỏi khai báo CSS.
 */',
        'startLine' => 132,
        'endLine' => 135,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 20,
        'namespace' => 'Modules\\Storefront\\Application\\Theme',
        'declaringClassName' => 'Modules\\Storefront\\Application\\Theme\\Themes',
        'implementingClassName' => 'Modules\\Storefront\\Application\\Theme\\Themes',
        'currentClassName' => 'Modules\\Storefront\\Application\\Theme\\Themes',
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