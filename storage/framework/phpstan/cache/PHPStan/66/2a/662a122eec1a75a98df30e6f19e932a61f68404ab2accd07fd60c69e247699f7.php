<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Extension/Application/Hooks/Points/ViewHooks.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Extension\Application\Hooks\Points\ViewHooks
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-dbb0d32b50b25272c83c17b24c59e9985842a6f3c451ea1bf90f83f16c6c286c',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Extension\\Application\\Hooks\\Points\\ViewHooks',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Extension/Application/Hooks/Points/ViewHooks.php',
      ),
    ),
    'namespace' => 'Modules\\Extension\\Application\\Hooks\\Points',
    'name' => 'Modules\\Extension\\Application\\Hooks\\Points\\ViewHooks',
    'shortName' => 'ViewHooks',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * View composer: điểm mở rộng tự động cho MỌI view của theme storefront — filter `vani.storefront.view.<view>` trên
 * dữ liệu view (`theme::pages.product` → `vani.storefront.view.pages.product`). Chỉ khi có listener; chỉ dữ liệu
 * hiển thị — giá/tồn/khuyến mãi khi đặt hàng vẫn tính lại ở Core.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 15,
    'endLine' => 34,
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
      'hooks' => 
      array (
        'declaringClassName' => 'Modules\\Extension\\Application\\Hooks\\Points\\ViewHooks',
        'implementingClassName' => 'Modules\\Extension\\Application\\Hooks\\Points\\ViewHooks',
        'name' => 'hooks',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Modules\\Extension\\Application\\Hooks\\HookManager',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 17,
        'endLine' => 17,
        'startColumn' => 33,
        'endColumn' => 67,
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
          'hooks' => 
          array (
            'name' => 'hooks',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Extension\\Application\\Hooks\\HookManager',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 17,
            'endLine' => 17,
            'startColumn' => 33,
            'endColumn' => 67,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 17,
        'endLine' => 17,
        'startColumn' => 5,
        'endColumn' => 71,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Extension\\Application\\Hooks\\Points',
        'declaringClassName' => 'Modules\\Extension\\Application\\Hooks\\Points\\ViewHooks',
        'implementingClassName' => 'Modules\\Extension\\Application\\Hooks\\Points\\ViewHooks',
        'currentClassName' => 'Modules\\Extension\\Application\\Hooks\\Points\\ViewHooks',
        'aliasName' => NULL,
      ),
      'compose' => 
      array (
        'name' => 'compose',
        'parameters' => 
        array (
          'view' => 
          array (
            'name' => 'view',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\View\\View',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 19,
            'endLine' => 19,
            'startColumn' => 29,
            'endColumn' => 38,
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
        'docComment' => NULL,
        'startLine' => 19,
        'endLine' => 33,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Extension\\Application\\Hooks\\Points',
        'declaringClassName' => 'Modules\\Extension\\Application\\Hooks\\Points\\ViewHooks',
        'implementingClassName' => 'Modules\\Extension\\Application\\Hooks\\Points\\ViewHooks',
        'currentClassName' => 'Modules\\Extension\\Application\\Hooks\\Points\\ViewHooks',
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