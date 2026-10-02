<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Extension/Application/Plugins/PluginHealth.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Extension\Application\Plugins\PluginHealth
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-09a8c2d1a719a64893a5b5a37fe1ec82cc497c04f0e48ea47398e40548d487df',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Extension\\Application\\Plugins\\PluginHealth',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Extension/Application/Plugins/PluginHealth.php',
      ),
    ),
    'namespace' => 'Modules\\Extension\\Application\\Plugins',
    'name' => 'Modules\\Extension\\Application\\Plugins\\PluginHealth',
    'shortName' => 'PluginHealth',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Chạy PluginHealthCheck của các plugin đang bật; lưu kết quả gần nhất vào cache dùng chung cho Admin.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 16,
    'endLine' => 65,
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
        'declaringClassName' => 'Modules\\Extension\\Application\\Plugins\\PluginHealth',
        'implementingClassName' => 'Modules\\Extension\\Application\\Plugins\\PluginHealth',
        'name' => 'CACHE_KEY',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'vani:plugins:health\'',
          'attributes' => 
          array (
            'startLine' => 18,
            'endLine' => 18,
            'startTokenPos' => 58,
            'startFilePos' => 471,
            'endTokenPos' => 58,
            'endFilePos' => 491,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 18,
        'endLine' => 18,
        'startColumn' => 5,
        'endColumn' => 52,
      ),
    ),
    'immediateProperties' => 
    array (
      'extensions' => 
      array (
        'declaringClassName' => 'Modules\\Extension\\Application\\Plugins\\PluginHealth',
        'implementingClassName' => 'Modules\\Extension\\Application\\Plugins\\PluginHealth',
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
        'startLine' => 20,
        'endLine' => 20,
        'startColumn' => 33,
        'endColumn' => 71,
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
            'startLine' => 20,
            'endLine' => 20,
            'startColumn' => 33,
            'endColumn' => 71,
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
        'startLine' => 20,
        'endLine' => 20,
        'startColumn' => 5,
        'endColumn' => 75,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Extension\\Application\\Plugins',
        'declaringClassName' => 'Modules\\Extension\\Application\\Plugins\\PluginHealth',
        'implementingClassName' => 'Modules\\Extension\\Application\\Plugins\\PluginHealth',
        'currentClassName' => 'Modules\\Extension\\Application\\Plugins\\PluginHealth',
        'aliasName' => NULL,
      ),
      'run' => 
      array (
        'name' => 'run',
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
 * @return array<string, array{status: string, message: string, checked_at: string}> plugin id => kết quả
 */',
        'startLine' => 25,
        'endLine' => 51,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Extension\\Application\\Plugins',
        'declaringClassName' => 'Modules\\Extension\\Application\\Plugins\\PluginHealth',
        'implementingClassName' => 'Modules\\Extension\\Application\\Plugins\\PluginHealth',
        'currentClassName' => 'Modules\\Extension\\Application\\Plugins\\PluginHealth',
        'aliasName' => NULL,
      ),
      'last' => 
      array (
        'name' => 'last',
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
 * @return array<string, array{status: string, message: string, checked_at: string}>
 */',
        'startLine' => 56,
        'endLine' => 59,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Extension\\Application\\Plugins',
        'declaringClassName' => 'Modules\\Extension\\Application\\Plugins\\PluginHealth',
        'implementingClassName' => 'Modules\\Extension\\Application\\Plugins\\PluginHealth',
        'currentClassName' => 'Modules\\Extension\\Application\\Plugins\\PluginHealth',
        'aliasName' => NULL,
      ),
      'rank' => 
      array (
        'name' => 'rank',
        'parameters' => 
        array (
          'status' => 
          array (
            'name' => 'status',
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
            'startLine' => 61,
            'endLine' => 61,
            'startColumn' => 34,
            'endColumn' => 47,
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
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 61,
        'endLine' => 64,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 20,
        'namespace' => 'Modules\\Extension\\Application\\Plugins',
        'declaringClassName' => 'Modules\\Extension\\Application\\Plugins\\PluginHealth',
        'implementingClassName' => 'Modules\\Extension\\Application\\Plugins\\PluginHealth',
        'currentClassName' => 'Modules\\Extension\\Application\\Plugins\\PluginHealth',
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