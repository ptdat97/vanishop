<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Extension/Application/Hooks/Points/HookedInertiaFactory.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Extension\Application\Hooks\Points\HookedInertiaFactory
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-23c89904e9d29182bc5362b64e56a38c707ffc75a4b57f545358356f3e4e00ec',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Extension\\Application\\Hooks\\Points\\HookedInertiaFactory',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Extension/Application/Hooks/Points/HookedInertiaFactory.php',
      ),
    ),
    'namespace' => 'Modules\\Extension\\Application\\Hooks\\Points',
    'name' => 'Modules\\Extension\\Application\\Hooks\\Points\\HookedInertiaFactory',
    'shortName' => 'HookedInertiaFactory',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Điểm mở rộng tự động cho MỌI trang Admin (Inertia): filter `vani.admin.page.<component>` trên props trước khi render.
 * Tên: component viết thường, `::` và `/` thành `.` — `Ordering::Orders/Show` → `vani.admin.page.ordering.orders.show`.
 * Chỉ chạy khi có listener; lỗi listener bị bỏ qua (khai báo `on_error: skip`).
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 16,
    'endLine' => 35,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Inertia\\ResponseFactory',
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
      'render' => 
      array (
        'name' => 'render',
        'parameters' => 
        array (
          'component' => 
          array (
            'name' => 'component',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 18,
            'endLine' => 18,
            'startColumn' => 28,
            'endColumn' => 37,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'props' => 
          array (
            'name' => 'props',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 18,
                'endLine' => 18,
                'startTokenPos' => 57,
                'startFilePos' => 673,
                'endTokenPos' => 58,
                'endFilePos' => 674,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 18,
            'endLine' => 18,
            'startColumn' => 40,
            'endColumn' => 50,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Inertia\\Response',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 18,
        'endLine' => 29,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Extension\\Application\\Hooks\\Points',
        'declaringClassName' => 'Modules\\Extension\\Application\\Hooks\\Points\\HookedInertiaFactory',
        'implementingClassName' => 'Modules\\Extension\\Application\\Hooks\\Points\\HookedInertiaFactory',
        'currentClassName' => 'Modules\\Extension\\Application\\Hooks\\Points\\HookedInertiaFactory',
        'aliasName' => NULL,
      ),
      'hookName' => 
      array (
        'name' => 'hookName',
        'parameters' => 
        array (
          'component' => 
          array (
            'name' => 'component',
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
            'startLine' => 31,
            'endLine' => 31,
            'startColumn' => 37,
            'endColumn' => 53,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 31,
        'endLine' => 34,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Modules\\Extension\\Application\\Hooks\\Points',
        'declaringClassName' => 'Modules\\Extension\\Application\\Hooks\\Points\\HookedInertiaFactory',
        'implementingClassName' => 'Modules\\Extension\\Application\\Hooks\\Points\\HookedInertiaFactory',
        'currentClassName' => 'Modules\\Extension\\Application\\Hooks\\Points\\HookedInertiaFactory',
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