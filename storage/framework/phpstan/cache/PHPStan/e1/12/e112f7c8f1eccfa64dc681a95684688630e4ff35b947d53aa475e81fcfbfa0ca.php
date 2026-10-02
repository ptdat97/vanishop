<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Extension/Domain/Plugin/PluginManifest.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Extension\Domain\Plugin\PluginManifest
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-02f07d1067a881c5e08c659b0f1a5e60e98fcbf25c1b5ee9c355c9e0bfcc4964',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Extension\\Domain\\Plugin\\PluginManifest',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Extension/Domain/Plugin/PluginManifest.php',
      ),
    ),
    'namespace' => 'Modules\\Extension\\Domain\\Plugin',
    'name' => 'Modules\\Extension\\Domain\\Plugin\\PluginManifest',
    'shortName' => 'PluginManifest',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 65568,
    'docComment' => '/**
 * Nội dung vanishop.json của một plugin.
 *
 * @see docs/05-plugin/plugin-system.md §3
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 12,
    'endLine' => 100,
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
      'ID_PATTERN' => 
      array (
        'declaringClassName' => 'Modules\\Extension\\Domain\\Plugin\\PluginManifest',
        'implementingClassName' => 'Modules\\Extension\\Domain\\Plugin\\PluginManifest',
        'name' => 'ID_PATTERN',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'/^[a-z0-9]+(?:-[a-z0-9]+)*\\.[a-z0-9]+(?:-[a-z0-9]+)*$/\'',
          'attributes' => 
          array (
            'startLine' => 14,
            'endLine' => 14,
            'startTokenPos' => 35,
            'startFilePos' => 249,
            'endTokenPos' => 35,
            'endFilePos' => 304,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 14,
        'endLine' => 14,
        'startColumn' => 5,
        'endColumn' => 88,
      ),
      'VERSION_PATTERN' => 
      array (
        'declaringClassName' => 'Modules\\Extension\\Domain\\Plugin\\PluginManifest',
        'implementingClassName' => 'Modules\\Extension\\Domain\\Plugin\\PluginManifest',
        'name' => 'VERSION_PATTERN',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'/^\\d+\\.\\d+\\.\\d+(?:-[0-9A-Za-z.-]+)?$/\'',
          'attributes' => 
          array (
            'startLine' => 16,
            'endLine' => 16,
            'startTokenPos' => 46,
            'startFilePos' => 344,
            'endTokenPos' => 46,
            'endFilePos' => 382,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 16,
        'endLine' => 16,
        'startColumn' => 5,
        'endColumn' => 76,
      ),
      'KINDS' => 
      array (
        'declaringClassName' => 'Modules\\Extension\\Domain\\Plugin\\PluginManifest',
        'implementingClassName' => 'Modules\\Extension\\Domain\\Plugin\\PluginManifest',
        'name' => 'KINDS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'payment_gateway\', \'shipping_carrier\', \'shipping_rate\', \'tax\', \'promotion\', \'notification_channel\', \'search\', \'integration\', \'marketing\', \'analytics\', \'customer_service\', \'content\', \'theme_extension\', \'language\', \'feature\']',
          'attributes' => 
          array (
            'startLine' => 22,
            'endLine' => 25,
            'startTokenPos' => 59,
            'startFilePos' => 681,
            'endTokenPos' => 106,
            'endFilePos' => 927,
          ),
        ),
        'docComment' => '/**
 * Loại plugin (ADR-030 §4.H). Loại gắn với một extension point (vd. `payment_gateway`) được module sở hữu contract
 * khai báo qua `Extensions::kindContract()`; doctor cảnh báo khi plugin không đóng góp contract đó.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 22,
        'endLine' => 25,
        'startColumn' => 5,
        'endColumn' => 6,
      ),
    ),
    'immediateProperties' => 
    array (
      'id' => 
      array (
        'declaringClassName' => 'Modules\\Extension\\Domain\\Plugin\\PluginManifest',
        'implementingClassName' => 'Modules\\Extension\\Domain\\Plugin\\PluginManifest',
        'name' => 'id',
        'modifiers' => 2049,
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
        'startLine' => 34,
        'endLine' => 34,
        'startColumn' => 9,
        'endColumn' => 25,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'name' => 
      array (
        'declaringClassName' => 'Modules\\Extension\\Domain\\Plugin\\PluginManifest',
        'implementingClassName' => 'Modules\\Extension\\Domain\\Plugin\\PluginManifest',
        'name' => 'name',
        'modifiers' => 2049,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 35,
        'endLine' => 35,
        'startColumn' => 9,
        'endColumn' => 26,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'version' => 
      array (
        'declaringClassName' => 'Modules\\Extension\\Domain\\Plugin\\PluginManifest',
        'implementingClassName' => 'Modules\\Extension\\Domain\\Plugin\\PluginManifest',
        'name' => 'version',
        'modifiers' => 2049,
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
        'startLine' => 36,
        'endLine' => 36,
        'startColumn' => 9,
        'endColumn' => 30,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'kind' => 
      array (
        'declaringClassName' => 'Modules\\Extension\\Domain\\Plugin\\PluginManifest',
        'implementingClassName' => 'Modules\\Extension\\Domain\\Plugin\\PluginManifest',
        'name' => 'kind',
        'modifiers' => 2049,
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
        'startLine' => 37,
        'endLine' => 37,
        'startColumn' => 9,
        'endColumn' => 27,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'provider' => 
      array (
        'declaringClassName' => 'Modules\\Extension\\Domain\\Plugin\\PluginManifest',
        'implementingClassName' => 'Modules\\Extension\\Domain\\Plugin\\PluginManifest',
        'name' => 'provider',
        'modifiers' => 2049,
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
        'startLine' => 38,
        'endLine' => 38,
        'startColumn' => 9,
        'endColumn' => 31,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'requiresCore' => 
      array (
        'declaringClassName' => 'Modules\\Extension\\Domain\\Plugin\\PluginManifest',
        'implementingClassName' => 'Modules\\Extension\\Domain\\Plugin\\PluginManifest',
        'name' => 'requiresCore',
        'modifiers' => 2049,
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
        'startLine' => 39,
        'endLine' => 39,
        'startColumn' => 9,
        'endColumn' => 35,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'requiresPlugins' => 
      array (
        'declaringClassName' => 'Modules\\Extension\\Domain\\Plugin\\PluginManifest',
        'implementingClassName' => 'Modules\\Extension\\Domain\\Plugin\\PluginManifest',
        'name' => 'requiresPlugins',
        'modifiers' => 2049,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 40,
        'endLine' => 40,
        'startColumn' => 9,
        'endColumn' => 37,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'conflicts' => 
      array (
        'declaringClassName' => 'Modules\\Extension\\Domain\\Plugin\\PluginManifest',
        'implementingClassName' => 'Modules\\Extension\\Domain\\Plugin\\PluginManifest',
        'name' => 'conflicts',
        'modifiers' => 2049,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 41,
        'endLine' => 41,
        'startColumn' => 9,
        'endColumn' => 31,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'permissions' => 
      array (
        'declaringClassName' => 'Modules\\Extension\\Domain\\Plugin\\PluginManifest',
        'implementingClassName' => 'Modules\\Extension\\Domain\\Plugin\\PluginManifest',
        'name' => 'permissions',
        'modifiers' => 2049,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 42,
        'endLine' => 42,
        'startColumn' => 9,
        'endColumn' => 33,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'path' => 
      array (
        'declaringClassName' => 'Modules\\Extension\\Domain\\Plugin\\PluginManifest',
        'implementingClassName' => 'Modules\\Extension\\Domain\\Plugin\\PluginManifest',
        'name' => 'path',
        'modifiers' => 2049,
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
        'startLine' => 43,
        'endLine' => 43,
        'startColumn' => 9,
        'endColumn' => 27,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'bundled' => 
      array (
        'declaringClassName' => 'Modules\\Extension\\Domain\\Plugin\\PluginManifest',
        'implementingClassName' => 'Modules\\Extension\\Domain\\Plugin\\PluginManifest',
        'name' => 'bundled',
        'modifiers' => 2049,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'default' => 
        array (
          'code' => 'false',
          'attributes' => 
          array (
            'startLine' => 45,
            'endLine' => 45,
            'startTokenPos' => 198,
            'startFilePos' => 1663,
            'endTokenPos' => 198,
            'endFilePos' => 1667,
          ),
        ),
        'docComment' => '/** Plugin hệ thống (ADR-029): `vani:install` tự cài + bật. */',
        'attributes' => 
        array (
        ),
        'startLine' => 45,
        'endLine' => 45,
        'startColumn' => 9,
        'endColumn' => 36,
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
          'id' => 
          array (
            'name' => 'id',
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
            'startLine' => 34,
            'endLine' => 34,
            'startColumn' => 9,
            'endColumn' => 25,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'name' => 
          array (
            'name' => 'name',
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 35,
            'endLine' => 35,
            'startColumn' => 9,
            'endColumn' => 26,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'version' => 
          array (
            'name' => 'version',
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
            'startLine' => 36,
            'endLine' => 36,
            'startColumn' => 9,
            'endColumn' => 30,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'kind' => 
          array (
            'name' => 'kind',
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
            'startLine' => 37,
            'endLine' => 37,
            'startColumn' => 9,
            'endColumn' => 27,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'provider' => 
          array (
            'name' => 'provider',
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
            'startLine' => 38,
            'endLine' => 38,
            'startColumn' => 9,
            'endColumn' => 31,
            'parameterIndex' => 4,
            'isOptional' => false,
          ),
          'requiresCore' => 
          array (
            'name' => 'requiresCore',
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
            'startLine' => 39,
            'endLine' => 39,
            'startColumn' => 9,
            'endColumn' => 35,
            'parameterIndex' => 5,
            'isOptional' => false,
          ),
          'requiresPlugins' => 
          array (
            'name' => 'requiresPlugins',
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 40,
            'endLine' => 40,
            'startColumn' => 9,
            'endColumn' => 37,
            'parameterIndex' => 6,
            'isOptional' => false,
          ),
          'conflicts' => 
          array (
            'name' => 'conflicts',
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 41,
            'endLine' => 41,
            'startColumn' => 9,
            'endColumn' => 31,
            'parameterIndex' => 7,
            'isOptional' => false,
          ),
          'permissions' => 
          array (
            'name' => 'permissions',
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 42,
            'endLine' => 42,
            'startColumn' => 9,
            'endColumn' => 33,
            'parameterIndex' => 8,
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
            'startLine' => 43,
            'endLine' => 43,
            'startColumn' => 9,
            'endColumn' => 27,
            'parameterIndex' => 9,
            'isOptional' => false,
          ),
          'bundled' => 
          array (
            'name' => 'bundled',
            'default' => 
            array (
              'code' => 'false',
              'attributes' => 
              array (
                'startLine' => 45,
                'endLine' => 45,
                'startTokenPos' => 198,
                'startFilePos' => 1663,
                'endTokenPos' => 198,
                'endFilePos' => 1667,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'bool',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 45,
            'endLine' => 45,
            'startColumn' => 9,
            'endColumn' => 36,
            'parameterIndex' => 10,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param  array<string, string>  $name  locale => tên hiển thị
 * @param  array<string, string>  $requiresPlugins  plugin id => ràng buộc phiên bản
 * @param  list<string>  $conflicts
 * @param  list<string>  $permissions
 */',
        'startLine' => 33,
        'endLine' => 46,
        'startColumn' => 5,
        'endColumn' => 8,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Extension\\Domain\\Plugin',
        'declaringClassName' => 'Modules\\Extension\\Domain\\Plugin\\PluginManifest',
        'implementingClassName' => 'Modules\\Extension\\Domain\\Plugin\\PluginManifest',
        'currentClassName' => 'Modules\\Extension\\Domain\\Plugin\\PluginManifest',
        'aliasName' => NULL,
      ),
      'fromArray' => 
      array (
        'name' => 'fromArray',
        'parameters' => 
        array (
          'data' => 
          array (
            'name' => 'data',
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
            'startLine' => 51,
            'endLine' => 51,
            'startColumn' => 38,
            'endColumn' => 48,
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
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 51,
            'endLine' => 51,
            'startColumn' => 51,
            'endColumn' => 62,
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
            'name' => 'self',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param  array<string, mixed>  $data
 */',
        'startLine' => 51,
        'endLine' => 94,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Modules\\Extension\\Domain\\Plugin',
        'declaringClassName' => 'Modules\\Extension\\Domain\\Plugin\\PluginManifest',
        'implementingClassName' => 'Modules\\Extension\\Domain\\Plugin\\PluginManifest',
        'currentClassName' => 'Modules\\Extension\\Domain\\Plugin\\PluginManifest',
        'aliasName' => NULL,
      ),
      'displayName' => 
      array (
        'name' => 'displayName',
        'parameters' => 
        array (
          'locale' => 
          array (
            'name' => 'locale',
            'default' => 
            array (
              'code' => '\'vi\'',
              'attributes' => 
              array (
                'startLine' => 96,
                'endLine' => 96,
                'startTokenPos' => 749,
                'startFilePos' => 3762,
                'endTokenPos' => 749,
                'endFilePos' => 3765,
              ),
            ),
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
            'startLine' => 96,
            'endLine' => 96,
            'startColumn' => 33,
            'endColumn' => 53,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 96,
        'endLine' => 99,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Extension\\Domain\\Plugin',
        'declaringClassName' => 'Modules\\Extension\\Domain\\Plugin\\PluginManifest',
        'implementingClassName' => 'Modules\\Extension\\Domain\\Plugin\\PluginManifest',
        'currentClassName' => 'Modules\\Extension\\Domain\\Plugin\\PluginManifest',
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