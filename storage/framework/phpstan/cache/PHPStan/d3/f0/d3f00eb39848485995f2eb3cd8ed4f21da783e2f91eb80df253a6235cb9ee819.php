<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Storefront/View/Components/HookSlot.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Storefront\View\Components\HookSlot
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-8bff951aca4ee9887603fe9c368282a32c33b20508b76c3cad998681ed26f4dc',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Storefront\\View\\Components\\HookSlot',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Storefront/View/Components/HookSlot.php',
      ),
    ),
    'namespace' => 'Modules\\Storefront\\View\\Components',
    'name' => 'Modules\\Storefront\\View\\Components\\HookSlot',
    'shortName' => 'HookSlot',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * `<x-vani::hook-slot name="vani.storefront.pdp.after_price" :args="[$product]" />` — render các phần tử plugin
 * trả cho slot theo priority (ADR-025). Chỉ nối thêm; phần tử lỗi (listener hoặc lúc render) bị bỏ, ghi log.
 * Phần tử hợp lệ: SlotView, Htmlable (vd. view()), hoặc null (không có gì để hiện). Chuỗi thô bị bỏ để không chèn HTML chưa escape.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 20,
    'endLine' => 48,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Illuminate\\View\\Component',
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
      'name' => 
      array (
        'declaringClassName' => 'Modules\\Storefront\\View\\Components\\HookSlot',
        'implementingClassName' => 'Modules\\Storefront\\View\\Components\\HookSlot',
        'name' => 'name',
        'modifiers' => 1,
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
        'endColumn' => 27,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'args' => 
      array (
        'declaringClassName' => 'Modules\\Storefront\\View\\Components\\HookSlot',
        'implementingClassName' => 'Modules\\Storefront\\View\\Components\\HookSlot',
        'name' => 'args',
        'modifiers' => 1,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'default' => 
        array (
          'code' => '[]',
          'attributes' => 
          array (
            'startLine' => 27,
            'endLine' => 27,
            'startTokenPos' => 88,
            'startFilePos' => 929,
            'endTokenPos' => 89,
            'endFilePos' => 930,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 27,
        'endLine' => 27,
        'startColumn' => 9,
        'endColumn' => 31,
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
          'name' => 
          array (
            'name' => 'name',
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
            'endColumn' => 27,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'args' => 
          array (
            'name' => 'args',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 27,
                'endLine' => 27,
                'startTokenPos' => 88,
                'startFilePos' => 929,
                'endTokenPos' => 89,
                'endFilePos' => 930,
              ),
            ),
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 27,
            'endLine' => 27,
            'startColumn' => 9,
            'endColumn' => 31,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param  list<mixed>  $args
 */',
        'startLine' => 25,
        'endLine' => 28,
        'startColumn' => 5,
        'endColumn' => 8,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Storefront\\View\\Components',
        'declaringClassName' => 'Modules\\Storefront\\View\\Components\\HookSlot',
        'implementingClassName' => 'Modules\\Storefront\\View\\Components\\HookSlot',
        'currentClassName' => 'Modules\\Storefront\\View\\Components\\HookSlot',
        'aliasName' => NULL,
      ),
      'render' => 
      array (
        'name' => 'render',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Illuminate\\Contracts\\Support\\Htmlable',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 30,
        'endLine' => 47,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Storefront\\View\\Components',
        'declaringClassName' => 'Modules\\Storefront\\View\\Components\\HookSlot',
        'implementingClassName' => 'Modules\\Storefront\\View\\Components\\HookSlot',
        'currentClassName' => 'Modules\\Storefront\\View\\Components\\HookSlot',
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