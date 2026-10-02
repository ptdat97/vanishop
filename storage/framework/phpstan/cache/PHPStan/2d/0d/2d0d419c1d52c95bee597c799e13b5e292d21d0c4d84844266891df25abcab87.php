<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Extension/Application/Plugins/PluginActivation.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Extension\Application\Plugins\PluginActivation
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-b05dea177d006aca88598dcffb8a8bfbb823c24b6495b10cd5d8b45c2fcd9a27',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Extension\\Application\\Plugins\\PluginActivation',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Extension/Application/Plugins/PluginActivation.php',
      ),
    ),
    'namespace' => 'Modules\\Extension\\Application\\Plugins',
    'name' => 'Modules\\Extension\\Application\\Plugins\\PluginActivation',
    'shortName' => 'PluginActivation',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Plugin có đang bật không. Plugin bật/tắt cho cả cửa hàng (ADR-028) — không có phạm vi brand/kênh.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 15,
    'endLine' => 72,
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
      'CACHE_KEY' => 
      array (
        'declaringClassName' => 'Modules\\Extension\\Application\\Plugins\\PluginActivation',
        'implementingClassName' => 'Modules\\Extension\\Application\\Plugins\\PluginActivation',
        'name' => 'CACHE_KEY',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'vani:plugins:enabled\'',
          'attributes' => 
          array (
            'startLine' => 17,
            'endLine' => 17,
            'startTokenPos' => 53,
            'startFilePos' => 458,
            'endTokenPos' => 53,
            'endFilePos' => 479,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 17,
        'endLine' => 17,
        'startColumn' => 5,
        'endColumn' => 53,
      ),
    ),
    'immediateProperties' => 
    array (
      'enabled' => 
      array (
        'declaringClassName' => 'Modules\\Extension\\Application\\Plugins\\PluginActivation',
        'implementingClassName' => 'Modules\\Extension\\Application\\Plugins\\PluginActivation',
        'name' => 'enabled',
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
            'startLine' => 20,
            'endLine' => 20,
            'startTokenPos' => 67,
            'startFilePos' => 554,
            'endTokenPos' => 67,
            'endFilePos' => 557,
          ),
        ),
        'docComment' => '/** @var array<string, true>|null */',
        'attributes' => 
        array (
        ),
        'startLine' => 20,
        'endLine' => 20,
        'startColumn' => 5,
        'endColumn' => 35,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'version' => 
      array (
        'declaringClassName' => 'Modules\\Extension\\Application\\Plugins\\PluginActivation',
        'implementingClassName' => 'Modules\\Extension\\Application\\Plugins\\PluginActivation',
        'name' => 'version',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'default' => 
        array (
          'code' => '0',
          'attributes' => 
          array (
            'startLine' => 22,
            'endLine' => 22,
            'startTokenPos' => 78,
            'startFilePos' => 588,
            'endTokenPos' => 78,
            'endFilePos' => 588,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 22,
        'endLine' => 22,
        'startColumn' => 5,
        'endColumn' => 29,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
    ),
    'immediateMethods' => 
    array (
      'isActive' => 
      array (
        'name' => 'isActive',
        'parameters' => 
        array (
          'pluginId' => 
          array (
            'name' => 'pluginId',
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
            'startLine' => 24,
            'endLine' => 24,
            'startColumn' => 30,
            'endColumn' => 45,
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
        'docComment' => NULL,
        'startLine' => 24,
        'endLine' => 27,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Extension\\Application\\Plugins',
        'declaringClassName' => 'Modules\\Extension\\Application\\Plugins\\PluginActivation',
        'implementingClassName' => 'Modules\\Extension\\Application\\Plugins\\PluginActivation',
        'currentClassName' => 'Modules\\Extension\\Application\\Plugins\\PluginActivation',
        'aliasName' => NULL,
      ),
      'flush' => 
      array (
        'name' => 'flush',
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
        'startLine' => 29,
        'endLine' => 33,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Extension\\Application\\Plugins',
        'declaringClassName' => 'Modules\\Extension\\Application\\Plugins\\PluginActivation',
        'implementingClassName' => 'Modules\\Extension\\Application\\Plugins\\PluginActivation',
        'currentClassName' => 'Modules\\Extension\\Application\\Plugins\\PluginActivation',
        'aliasName' => NULL,
      ),
      'forgetCache' => 
      array (
        'name' => 'forgetCache',
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
        'docComment' => '/**
 * Gọi khi trạng thái plugin đổi (PluginManager): xoá cache dùng chung.
 */',
        'startLine' => 38,
        'endLine' => 41,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Modules\\Extension\\Application\\Plugins',
        'declaringClassName' => 'Modules\\Extension\\Application\\Plugins\\PluginActivation',
        'implementingClassName' => 'Modules\\Extension\\Application\\Plugins\\PluginActivation',
        'currentClassName' => 'Modules\\Extension\\Application\\Plugins\\PluginActivation',
        'aliasName' => NULL,
      ),
      'version' => 
      array (
        'name' => 'version',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** Tăng mỗi lần flush — ScopedExtensions dùng để biết kết quả `tagged()` đã ghi nhớ có còn đúng. */',
        'startLine' => 44,
        'endLine' => 47,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Extension\\Application\\Plugins',
        'declaringClassName' => 'Modules\\Extension\\Application\\Plugins\\PluginActivation',
        'implementingClassName' => 'Modules\\Extension\\Application\\Plugins\\PluginActivation',
        'currentClassName' => 'Modules\\Extension\\Application\\Plugins\\PluginActivation',
        'aliasName' => NULL,
      ),
      'enabledIds' => 
      array (
        'name' => 'enabledIds',
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
 * Plugin đang bật — lưu trong cache dùng chung (redis/database ở production) để request/job không truy vấn DB.
 *
 * @return array<string, true>
 */',
        'startLine' => 54,
        'endLine' => 71,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'Modules\\Extension\\Application\\Plugins',
        'declaringClassName' => 'Modules\\Extension\\Application\\Plugins\\PluginActivation',
        'implementingClassName' => 'Modules\\Extension\\Application\\Plugins\\PluginActivation',
        'currentClassName' => 'Modules\\Extension\\Application\\Plugins\\PluginActivation',
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