<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Extension/Application/Plugins/PluginStateCache.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Extension\Application\Plugins\PluginStateCache
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-f4a196117f20780178ebd76e5fb4f4d7d512c8f40871c85edd2121a4f7979b5e',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Extension\\Application\\Plugins\\PluginStateCache',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Extension/Application/Plugins/PluginStateCache.php',
      ),
    ),
    'namespace' => 'Modules\\Extension\\Application\\Plugins',
    'name' => 'Modules\\Extension\\Application\\Plugins\\PluginStateCache',
    'shortName' => 'PluginStateCache',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * File cache trạng thái plugin để lúc boot không cần truy vấn DB.
 * Được ghi lại mỗi khi install/enable/disable/uninstall.
 *
 * @phpstan-type CachedPlugin array{id: string, provider: string, path: string, status: string}
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 15,
    'endLine' => 49,
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
      'files' => 
      array (
        'declaringClassName' => 'Modules\\Extension\\Application\\Plugins\\PluginStateCache',
        'implementingClassName' => 'Modules\\Extension\\Application\\Plugins\\PluginStateCache',
        'name' => 'files',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Illuminate\\Filesystem\\Filesystem',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 18,
        'endLine' => 18,
        'startColumn' => 9,
        'endColumn' => 42,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'path' => 
      array (
        'declaringClassName' => 'Modules\\Extension\\Application\\Plugins\\PluginStateCache',
        'implementingClassName' => 'Modules\\Extension\\Application\\Plugins\\PluginStateCache',
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
        'startLine' => 19,
        'endLine' => 19,
        'startColumn' => 9,
        'endColumn' => 37,
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
          'files' => 
          array (
            'name' => 'files',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Filesystem\\Filesystem',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 18,
            'endLine' => 18,
            'startColumn' => 9,
            'endColumn' => 42,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startLine' => 19,
            'endLine' => 19,
            'startColumn' => 9,
            'endColumn' => 37,
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
        'startLine' => 17,
        'endLine' => 20,
        'startColumn' => 5,
        'endColumn' => 8,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Extension\\Application\\Plugins',
        'declaringClassName' => 'Modules\\Extension\\Application\\Plugins\\PluginStateCache',
        'implementingClassName' => 'Modules\\Extension\\Application\\Plugins\\PluginStateCache',
        'currentClassName' => 'Modules\\Extension\\Application\\Plugins\\PluginStateCache',
        'aliasName' => NULL,
      ),
      'read' => 
      array (
        'name' => 'read',
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
 * @return list<CachedPlugin> theo thứ tự nạp
 */',
        'startLine' => 25,
        'endLine' => 34,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Extension\\Application\\Plugins',
        'declaringClassName' => 'Modules\\Extension\\Application\\Plugins\\PluginStateCache',
        'implementingClassName' => 'Modules\\Extension\\Application\\Plugins\\PluginStateCache',
        'currentClassName' => 'Modules\\Extension\\Application\\Plugins\\PluginStateCache',
        'aliasName' => NULL,
      ),
      'write' => 
      array (
        'name' => 'write',
        'parameters' => 
        array (
          'plugins' => 
          array (
            'name' => 'plugins',
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
            'startLine' => 39,
            'endLine' => 39,
            'startColumn' => 27,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param  list<CachedPlugin>  $plugins
 */',
        'startLine' => 39,
        'endLine' => 43,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Extension\\Application\\Plugins',
        'declaringClassName' => 'Modules\\Extension\\Application\\Plugins\\PluginStateCache',
        'implementingClassName' => 'Modules\\Extension\\Application\\Plugins\\PluginStateCache',
        'currentClassName' => 'Modules\\Extension\\Application\\Plugins\\PluginStateCache',
        'aliasName' => NULL,
      ),
      'forget' => 
      array (
        'name' => 'forget',
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
        'startLine' => 45,
        'endLine' => 48,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Extension\\Application\\Plugins',
        'declaringClassName' => 'Modules\\Extension\\Application\\Plugins\\PluginStateCache',
        'implementingClassName' => 'Modules\\Extension\\Application\\Plugins\\PluginStateCache',
        'currentClassName' => 'Modules\\Extension\\Application\\Plugins\\PluginStateCache',
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