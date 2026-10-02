<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Extension/PluginServiceProvider.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Extension\PluginServiceProvider
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-7b84eceb10fb0016cc174da05736296f1c006e54480c45a228ed2da65d4c5702',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Extension\\PluginServiceProvider',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Extension/PluginServiceProvider.php',
      ),
    ),
    'namespace' => 'Modules\\Extension',
    'name' => 'Modules\\Extension\\PluginServiceProvider',
    'shortName' => 'PluginServiceProvider',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 64,
    'docComment' => '/**
 * Lớp cơ sở (public API) cho ServiceProvider của plugin.
 *
 * Mọi listener/menu đăng ký qua các helper ở đây gắn với plugin id, nên chỉ có hiệu lực
 * khi plugin đang bật.
 *
 * @see docs/05-plugin/plugin-system.md
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 36,
    'endLine' => 349,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Illuminate\\Support\\ServiceProvider',
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
      'pluginId' => 
      array (
        'name' => 'pluginId',
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
        'docComment' => '/**
 * Id plugin, trùng với "id" trong vanishop.json.
 */',
        'startLine' => 41,
        'endLine' => 41,
        'startColumn' => 5,
        'endColumn' => 51,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 66,
        'namespace' => 'Modules\\Extension',
        'declaringClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'implementingClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'currentClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'aliasName' => NULL,
      ),
      'pluginPath' => 
      array (
        'name' => 'pluginPath',
        'parameters' => 
        array (
          'path' => 
          array (
            'name' => 'path',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 43,
                'endLine' => 43,
                'startTokenPos' => 157,
                'startFilePos' => 1496,
                'endTokenPos' => 157,
                'endFilePos' => 1497,
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
            'startLine' => 43,
            'endLine' => 43,
            'startColumn' => 35,
            'endColumn' => 51,
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
        'startLine' => 43,
        'endLine' => 48,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Modules\\Extension',
        'declaringClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'implementingClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'currentClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'aliasName' => NULL,
      ),
      'onFilter' => 
      array (
        'name' => 'onFilter',
        'parameters' => 
        array (
          'hook' => 
          array (
            'name' => 'hook',
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
            'startLine' => 50,
            'endLine' => 50,
            'startColumn' => 33,
            'endColumn' => 44,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'callback' => 
          array (
            'name' => 'callback',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'callable',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 50,
            'endLine' => 50,
            'startColumn' => 47,
            'endColumn' => 64,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'priority' => 
          array (
            'name' => 'priority',
            'default' => 
            array (
              'code' => '10',
              'attributes' => 
              array (
                'startLine' => 50,
                'endLine' => 50,
                'startTokenPos' => 237,
                'startFilePos' => 1755,
                'endTokenPos' => 237,
                'endFilePos' => 1756,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'int',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 50,
            'endLine' => 50,
            'startColumn' => 67,
            'endColumn' => 84,
            'parameterIndex' => 2,
            'isOptional' => true,
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
        'startLine' => 50,
        'endLine' => 53,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Modules\\Extension',
        'declaringClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'implementingClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'currentClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'aliasName' => NULL,
      ),
      'onAction' => 
      array (
        'name' => 'onAction',
        'parameters' => 
        array (
          'hook' => 
          array (
            'name' => 'hook',
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
            'startLine' => 55,
            'endLine' => 55,
            'startColumn' => 33,
            'endColumn' => 44,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'callback' => 
          array (
            'name' => 'callback',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'callable',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 55,
            'endLine' => 55,
            'startColumn' => 47,
            'endColumn' => 64,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'priority' => 
          array (
            'name' => 'priority',
            'default' => 
            array (
              'code' => '10',
              'attributes' => 
              array (
                'startLine' => 55,
                'endLine' => 55,
                'startTokenPos' => 299,
                'startFilePos' => 1964,
                'endTokenPos' => 299,
                'endFilePos' => 1965,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'int',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 55,
            'endLine' => 55,
            'startColumn' => 67,
            'endColumn' => 84,
            'parameterIndex' => 2,
            'isOptional' => true,
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
        'startLine' => 55,
        'endLine' => 58,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Modules\\Extension',
        'declaringClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'implementingClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'currentClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'aliasName' => NULL,
      ),
      'onValidate' => 
      array (
        'name' => 'onValidate',
        'parameters' => 
        array (
          'hook' => 
          array (
            'name' => 'hook',
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
            'startLine' => 60,
            'endLine' => 60,
            'startColumn' => 35,
            'endColumn' => 46,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'callback' => 
          array (
            'name' => 'callback',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'callable',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 60,
            'endLine' => 60,
            'startColumn' => 49,
            'endColumn' => 66,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'priority' => 
          array (
            'name' => 'priority',
            'default' => 
            array (
              'code' => '10',
              'attributes' => 
              array (
                'startLine' => 60,
                'endLine' => 60,
                'startTokenPos' => 361,
                'startFilePos' => 2175,
                'endTokenPos' => 361,
                'endFilePos' => 2176,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'int',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 60,
            'endLine' => 60,
            'startColumn' => 69,
            'endColumn' => 86,
            'parameterIndex' => 2,
            'isOptional' => true,
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
        'startLine' => 60,
        'endLine' => 63,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Modules\\Extension',
        'declaringClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'implementingClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'currentClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'aliasName' => NULL,
      ),
      'onSlot' => 
      array (
        'name' => 'onSlot',
        'parameters' => 
        array (
          'hook' => 
          array (
            'name' => 'hook',
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
            'startLine' => 65,
            'endLine' => 65,
            'startColumn' => 31,
            'endColumn' => 42,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'callback' => 
          array (
            'name' => 'callback',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'callable',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 65,
            'endLine' => 65,
            'startColumn' => 45,
            'endColumn' => 62,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'priority' => 
          array (
            'name' => 'priority',
            'default' => 
            array (
              'code' => '10',
              'attributes' => 
              array (
                'startLine' => 65,
                'endLine' => 65,
                'startTokenPos' => 423,
                'startFilePos' => 2384,
                'endTokenPos' => 423,
                'endFilePos' => 2385,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'int',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 65,
            'endLine' => 65,
            'startColumn' => 65,
            'endColumn' => 82,
            'parameterIndex' => 2,
            'isOptional' => true,
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
        'startLine' => 65,
        'endLine' => 68,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Modules\\Extension',
        'declaringClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'implementingClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'currentClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'aliasName' => NULL,
      ),
      'onEvent' => 
      array (
        'name' => 'onEvent',
        'parameters' => 
        array (
          'event' => 
          array (
            'name' => 'event',
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
            'startLine' => 77,
            'endLine' => 77,
            'startColumn' => 32,
            'endColumn' => 44,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'handler' => 
          array (
            'name' => 'handler',
            'default' => NULL,
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
                      'name' => 'callable',
                      'isIdentifier' => true,
                    ),
                  ),
                  1 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'string',
                      'isIdentifier' => true,
                    ),
                  ),
                  2 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'array',
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
            'startLine' => 77,
            'endLine' => 77,
            'startColumn' => 47,
            'endColumn' => 76,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Nghe domain event (`Modules\\<Ctx>\\Events\\*`) — chỉ chạy khi plugin đang bật; lỗi được ghi log, không làm
 * hỏng flow. Không dùng `Event::listen()` trực tiếp: listener khi đó chạy cả khi plugin đã tắt.
 *
 * @param  class-string  $event
 * @param  callable|class-string|array{0: class-string, 1: string}  $handler  class-string → `handle($event)`
 */',
        'startLine' => 77,
        'endLine' => 80,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Modules\\Extension',
        'declaringClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'implementingClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'currentClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'aliasName' => NULL,
      ),
      'schedule' => 
      array (
        'name' => 'schedule',
        'parameters' => 
        array (
          'define' => 
          array (
            'name' => 'define',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Closure',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 90,
            'endLine' => 90,
            'startColumn' => 33,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Tác vụ định kỳ của plugin: chỉ chạy khi plugin đang bật. Tác vụ chạy không có
 * CurrentContext — cần thì tự đặt (vd. `CurrentContext::runAs(ContextScope::system(\'…\'), …)`).
 *
 *   $this->schedule(fn (Schedule $schedule) => $schedule->command(\'vani:einvoice:sync\')->everyFiveMinutes());
 *
 * @param  Closure(Schedule): mixed  $define
 */',
        'startLine' => 90,
        'endLine' => 100,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Modules\\Extension',
        'declaringClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'implementingClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'currentClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'aliasName' => NULL,
      ),
      'settings' => 
      array (
        'name' => 'settings',
        'parameters' => 
        array (
          'definitions' => 
          array (
            'name' => 'definitions',
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
            'startLine' => 108,
            'endLine' => 108,
            'startColumn' => 33,
            'endColumn' => 50,
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
 * Khai báo cấu hình của plugin (namespace = plugin id) → Admin → Cấu hình sinh form.
 * Đọc lúc chạy qua contract `Modules\\Tenancy\\Contracts\\Settings::get($pluginId, $key, $default)`.
 *
 * @param  list<array{key: string, label: string, type?: string, default?: mixed, options?: array<string, string>, help?: string}>  $definitions
 */',
        'startLine' => 108,
        'endLine' => 117,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Modules\\Extension',
        'declaringClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'implementingClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'currentClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'aliasName' => NULL,
      ),
      'contribute' => 
      array (
        'name' => 'contribute',
        'parameters' => 
        array (
          'tag' => 
          array (
            'name' => 'tag',
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
            'startLine' => 123,
            'endLine' => 123,
            'startColumn' => 35,
            'endColumn' => 45,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'implementation' => 
          array (
            'name' => 'implementation',
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
            'startLine' => 123,
            'endLine' => 123,
            'startColumn' => 48,
            'endColumn' => 69,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Đóng góp implementation cho một extension point (vd. `vani.promotion.rules`).
 * Registry của Core chỉ thấy implementation này trong phạm vi plugin được bật.
 */',
        'startLine' => 123,
        'endLine' => 126,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Modules\\Extension',
        'declaringClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'implementingClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'currentClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'aliasName' => NULL,
      ),
      'adminFormSection' => 
      array (
        'name' => 'adminFormSection',
        'parameters' => 
        array (
          'resource' => 
          array (
            'name' => 'resource',
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
            'startLine' => 136,
            'endLine' => 136,
            'startColumn' => 41,
            'endColumn' => 56,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'key' => 
          array (
            'name' => 'key',
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
            'startLine' => 136,
            'endLine' => 136,
            'startColumn' => 59,
            'endColumn' => 69,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'label' => 
          array (
            'name' => 'label',
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
            'startLine' => 136,
            'endLine' => 136,
            'startColumn' => 72,
            'endColumn' => 84,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'fields' => 
          array (
            'name' => 'fields',
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
            'startLine' => 136,
            'endLine' => 136,
            'startColumn' => 87,
            'endColumn' => 99,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'load' => 
          array (
            'name' => 'load',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Closure',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 136,
            'endLine' => 136,
            'startColumn' => 102,
            'endColumn' => 114,
            'parameterIndex' => 4,
            'isOptional' => false,
          ),
          'save' => 
          array (
            'name' => 'save',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Closure',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 136,
            'endLine' => 136,
            'startColumn' => 117,
            'endColumn' => 129,
            'parameterIndex' => 5,
            'isOptional' => false,
          ),
          'order' => 
          array (
            'name' => 'order',
            'default' => 
            array (
              'code' => '500',
              'attributes' => 
              array (
                'startLine' => 136,
                'endLine' => 136,
                'startTokenPos' => 880,
                'startFilePos' => 5871,
                'endTokenPos' => 880,
                'endFilePos' => 5873,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'int',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 136,
            'endLine' => 136,
            'startColumn' => 132,
            'endColumn' => 147,
            'parameterIndex' => 6,
            'isOptional' => true,
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
 * Phần form của plugin trên màn hình tạo/sửa tài nguyên của Core (ADR-030 §4.A). `save` chạy trong transaction
 * lưu của Core, sau Core; lỗi → rollback cả form.
 *
 * @param  list<FieldDefinition>  $fields
 * @param  Closure(int): array<string, mixed>  $load  id → giá trị hiện tại
 * @param  Closure(int, array<string, mixed>): void  $save
 */',
        'startLine' => 136,
        'endLine' => 139,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Modules\\Extension',
        'declaringClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'implementingClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'currentClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'aliasName' => NULL,
      ),
      'adminColumn' => 
      array (
        'name' => 'adminColumn',
        'parameters' => 
        array (
          'resource' => 
          array (
            'name' => 'resource',
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
            'startLine' => 146,
            'endLine' => 146,
            'startColumn' => 36,
            'endColumn' => 51,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'key' => 
          array (
            'name' => 'key',
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
            'startLine' => 146,
            'endLine' => 146,
            'startColumn' => 54,
            'endColumn' => 64,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'label' => 
          array (
            'name' => 'label',
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
            'startLine' => 146,
            'endLine' => 146,
            'startColumn' => 67,
            'endColumn' => 79,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'resolve' => 
          array (
            'name' => 'resolve',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Closure',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 146,
            'endLine' => 146,
            'startColumn' => 82,
            'endColumn' => 97,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'order' => 
          array (
            'name' => 'order',
            'default' => 
            array (
              'code' => '500',
              'attributes' => 
              array (
                'startLine' => 146,
                'endLine' => 146,
                'startTokenPos' => 966,
                'startFilePos' => 6337,
                'endTokenPos' => 966,
                'endFilePos' => 6339,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'int',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 146,
            'endLine' => 146,
            'startColumn' => 100,
            'endColumn' => 115,
            'parameterIndex' => 4,
            'isOptional' => true,
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
 * Cột trên trang danh sách. `resolve` nhận cả trang id (batch) và trả id => giá trị.
 *
 * @param  Closure(list<int>): array<int, scalar|null>  $resolve
 */',
        'startLine' => 146,
        'endLine' => 149,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Modules\\Extension',
        'declaringClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'implementingClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'currentClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'aliasName' => NULL,
      ),
      'adminAction' => 
      array (
        'name' => 'adminAction',
        'parameters' => 
        array (
          'resource' => 
          array (
            'name' => 'resource',
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
            'startLine' => 157,
            'endLine' => 157,
            'startColumn' => 36,
            'endColumn' => 51,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'key' => 
          array (
            'name' => 'key',
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
            'startLine' => 157,
            'endLine' => 157,
            'startColumn' => 54,
            'endColumn' => 64,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'label' => 
          array (
            'name' => 'label',
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
            'startLine' => 157,
            'endLine' => 157,
            'startColumn' => 67,
            'endColumn' => 79,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'permission' => 
          array (
            'name' => 'permission',
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
            'startLine' => 157,
            'endLine' => 157,
            'startColumn' => 82,
            'endColumn' => 99,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'handle' => 
          array (
            'name' => 'handle',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Closure',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 157,
            'endLine' => 157,
            'startColumn' => 102,
            'endColumn' => 116,
            'parameterIndex' => 4,
            'isOptional' => false,
          ),
          'scope' => 
          array (
            'name' => 'scope',
            'default' => 
            array (
              'code' => '\'detail\'',
              'attributes' => 
              array (
                'startLine' => 157,
                'endLine' => 157,
                'startTokenPos' => 1051,
                'startFilePos' => 6872,
                'endTokenPos' => 1051,
                'endFilePos' => 6879,
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
            'startLine' => 157,
            'endLine' => 157,
            'startColumn' => 119,
            'endColumn' => 142,
            'parameterIndex' => 5,
            'isOptional' => true,
          ),
          'confirm' => 
          array (
            'name' => 'confirm',
            'default' => 
            array (
              'code' => 'false',
              'attributes' => 
              array (
                'startLine' => 157,
                'endLine' => 157,
                'startTokenPos' => 1060,
                'startFilePos' => 6898,
                'endTokenPos' => 1060,
                'endFilePos' => 6902,
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
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 157,
            'endLine' => 157,
            'startColumn' => 145,
            'endColumn' => 165,
            'parameterIndex' => 6,
            'isOptional' => true,
          ),
          'order' => 
          array (
            'name' => 'order',
            'default' => 
            array (
              'code' => '500',
              'attributes' => 
              array (
                'startLine' => 157,
                'endLine' => 157,
                'startTokenPos' => 1069,
                'startFilePos' => 6918,
                'endTokenPos' => 1069,
                'endFilePos' => 6920,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'int',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 157,
            'endLine' => 157,
            'startColumn' => 168,
            'endColumn' => 183,
            'parameterIndex' => 7,
            'isOptional' => true,
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
 * Nút thao tác trên trang chi tiết (`detail`) hoặc chọn nhiều ở danh sách (`bulk`). Core kiểm tra quyền + audit.
 *
 * @param  Closure(int): (string|null)  $handle
 * @param  \'detail\'|\'bulk\'|\'both\'  $scope
 */',
        'startLine' => 157,
        'endLine' => 160,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Modules\\Extension',
        'declaringClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'implementingClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'currentClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'aliasName' => NULL,
      ),
      'adminTab' => 
      array (
        'name' => 'adminTab',
        'parameters' => 
        array (
          'resource' => 
          array (
            'name' => 'resource',
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
            'startLine' => 167,
            'endLine' => 167,
            'startColumn' => 33,
            'endColumn' => 48,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'key' => 
          array (
            'name' => 'key',
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
            'startLine' => 167,
            'endLine' => 167,
            'startColumn' => 51,
            'endColumn' => 61,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'label' => 
          array (
            'name' => 'label',
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
            'startLine' => 167,
            'endLine' => 167,
            'startColumn' => 64,
            'endColumn' => 76,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'rows' => 
          array (
            'name' => 'rows',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Closure',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 167,
            'endLine' => 167,
            'startColumn' => 79,
            'endColumn' => 91,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'order' => 
          array (
            'name' => 'order',
            'default' => 
            array (
              'code' => '500',
              'attributes' => 
              array (
                'startLine' => 167,
                'endLine' => 167,
                'startTokenPos' => 1158,
                'startFilePos' => 7436,
                'endTokenPos' => 1158,
                'endFilePos' => 7438,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'int',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 167,
            'endLine' => 167,
            'startColumn' => 94,
            'endColumn' => 109,
            'parameterIndex' => 4,
            'isOptional' => true,
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
 * Tab trên trang chi tiết: danh sách dòng nhãn/giá trị. UI phức tạp hơn → trang riêng của plugin (`adminPages`).
 *
 * @param  Closure(int): list<array{label: string, value: string}>  $rows
 */',
        'startLine' => 167,
        'endLine' => 170,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Modules\\Extension',
        'declaringClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'implementingClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'currentClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'aliasName' => NULL,
      ),
      'adminFilter' => 
      array (
        'name' => 'adminFilter',
        'parameters' => 
        array (
          'resource' => 
          array (
            'name' => 'resource',
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
            'startLine' => 178,
            'endLine' => 178,
            'startColumn' => 36,
            'endColumn' => 51,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'key' => 
          array (
            'name' => 'key',
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
            'startLine' => 178,
            'endLine' => 178,
            'startColumn' => 54,
            'endColumn' => 64,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'label' => 
          array (
            'name' => 'label',
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
            'startLine' => 178,
            'endLine' => 178,
            'startColumn' => 67,
            'endColumn' => 79,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'options' => 
          array (
            'name' => 'options',
            'default' => NULL,
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
                      'name' => 'Closure',
                      'isIdentifier' => false,
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
            'startLine' => 178,
            'endLine' => 178,
            'startColumn' => 82,
            'endColumn' => 103,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'apply' => 
          array (
            'name' => 'apply',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Closure',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 178,
            'endLine' => 178,
            'startColumn' => 106,
            'endColumn' => 119,
            'parameterIndex' => 4,
            'isOptional' => false,
          ),
          'order' => 
          array (
            'name' => 'order',
            'default' => 
            array (
              'code' => '500',
              'attributes' => 
              array (
                'startLine' => 178,
                'endLine' => 178,
                'startTokenPos' => 1245,
                'startFilePos' => 8007,
                'endTokenPos' => 1245,
                'endFilePos' => 8009,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'int',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 178,
            'endLine' => 178,
            'startColumn' => 122,
            'endColumn' => 137,
            'parameterIndex' => 5,
            'isOptional' => true,
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
 * Bộ lọc trên trang danh sách: `apply` nhận giá trị đã chọn và trả id thoả (plugin không chạm truy vấn của Core).
 *
 * @param  array<string, string>|Closure(): array<string, string>  $options
 * @param  Closure(string): list<int>  $apply
 */',
        'startLine' => 178,
        'endLine' => 181,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Modules\\Extension',
        'declaringClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'implementingClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'currentClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'aliasName' => NULL,
      ),
      'publishHooks' => 
      array (
        'name' => 'publishHooks',
        'parameters' => 
        array (
          'file' => 
          array (
            'name' => 'file',
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
            'startLine' => 188,
            'endLine' => 188,
            'startColumn' => 37,
            'endColumn' => 48,
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
 * Plugin công bố hook cho plugin khác (ADR-030 §4.H): file trả mảng khai báo cùng định dạng hooks.php của module Core.
 * Tên hook phải bắt đầu bằng id plugin (`vani.loyalty.points.earned`). Plugin dùng hook này khai báo plugin công bố
 * trong `requires.plugins`.
 */',
        'startLine' => 188,
        'endLine' => 199,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Modules\\Extension',
        'declaringClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'implementingClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'currentClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'aliasName' => NULL,
      ),
      'adminMenu' => 
      array (
        'name' => 'adminMenu',
        'parameters' => 
        array (
          'key' => 
          array (
            'name' => 'key',
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
            'startLine' => 201,
            'endLine' => 201,
            'startColumn' => 34,
            'endColumn' => 44,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'label' => 
          array (
            'name' => 'label',
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
            'startLine' => 201,
            'endLine' => 201,
            'startColumn' => 47,
            'endColumn' => 59,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'route' => 
          array (
            'name' => 'route',
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
            'startLine' => 201,
            'endLine' => 201,
            'startColumn' => 62,
            'endColumn' => 74,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'permission' => 
          array (
            'name' => 'permission',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 201,
                'endLine' => 201,
                'startTokenPos' => 1445,
                'startFilePos' => 9149,
                'endTokenPos' => 1445,
                'endFilePos' => 9152,
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
                      'name' => 'string',
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
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 201,
            'endLine' => 201,
            'startColumn' => 77,
            'endColumn' => 102,
            'parameterIndex' => 3,
            'isOptional' => true,
          ),
          'order' => 
          array (
            'name' => 'order',
            'default' => 
            array (
              'code' => '500',
              'attributes' => 
              array (
                'startLine' => 201,
                'endLine' => 201,
                'startTokenPos' => 1454,
                'startFilePos' => 9168,
                'endTokenPos' => 1454,
                'endFilePos' => 9170,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'int',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 201,
            'endLine' => 201,
            'startColumn' => 105,
            'endColumn' => 120,
            'parameterIndex' => 4,
            'isOptional' => true,
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
        'startLine' => 201,
        'endLine' => 204,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Modules\\Extension',
        'declaringClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'implementingClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'currentClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'aliasName' => NULL,
      ),
      'permissions' => 
      array (
        'name' => 'permissions',
        'parameters' => 
        array (
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
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 209,
            'endLine' => 209,
            'startColumn' => 36,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param  array<string, string>  $permissions  code => mô tả
 */',
        'startLine' => 209,
        'endLine' => 216,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Modules\\Extension',
        'declaringClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'implementingClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'currentClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'aliasName' => NULL,
      ),
      'adminRoutes' => 
      array (
        'name' => 'adminRoutes',
        'parameters' => 
        array (
          'file' => 
          array (
            'name' => 'file',
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
            'startLine' => 221,
            'endLine' => 221,
            'startColumn' => 36,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Route Admin của plugin: /{VANI_ADMIN_PATH}/plugins/{slug}/…, tên route admin.plugins.{slug}.…
 */',
        'startLine' => 221,
        'endLine' => 235,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Modules\\Extension',
        'declaringClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'implementingClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'currentClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'aliasName' => NULL,
      ),
      'storefrontRoutes' => 
      array (
        'name' => 'storefrontRoutes',
        'parameters' => 
        array (
          'file' => 
          array (
            'name' => 'file',
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
            'startLine' => 242,
            'endLine' => 242,
            'startColumn' => 41,
            'endColumn' => 52,
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
 * Storefront API của plugin (ADR-030 §4.B): /api/storefront/v1/x/{slug}/…, tên route api.storefront.x.{slug}.…
 * Cùng middleware với Storefront API của Core (ngữ cảnh storefront, rate limit); plugin tắt → 404.
 * Cần khách đăng nhập: thêm middleware `vani.customer` (hoặc `vani.customer:optional`) trong file route.
 */',
        'startLine' => 242,
        'endLine' => 256,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Modules\\Extension',
        'declaringClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'implementingClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'currentClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'aliasName' => NULL,
      ),
      'storefrontPages' => 
      array (
        'name' => 'storefrontPages',
        'parameters' => 
        array (
          'file' => 
          array (
            'name' => 'file',
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
            'startLine' => 262,
            'endLine' => 262,
            'startColumn' => 40,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Trang native storefront của plugin: /p/{slug}/…, tên route storefront.p.{slug}.…; render trong layout theme
 * (`@extends(\'theme::layouts.app\')`). View đăng ký bằng storefrontViews().
 */',
        'startLine' => 262,
        'endLine' => 276,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Modules\\Extension',
        'declaringClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'implementingClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'currentClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'aliasName' => NULL,
      ),
      'storefrontViews' => 
      array (
        'name' => 'storefrontViews',
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
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 281,
            'endLine' => 281,
            'startColumn' => 40,
            'endColumn' => 51,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'namespace' => 
          array (
            'name' => 'namespace',
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
            'startLine' => 281,
            'endLine' => 281,
            'startColumn' => 54,
            'endColumn' => 70,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * View storefront của plugin (namespace riêng); theme override được tại custom/theme/<theme>/plugins/{slug}/.
 */',
        'startLine' => 281,
        'endLine' => 285,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Modules\\Extension',
        'declaringClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'implementingClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'currentClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'aliasName' => NULL,
      ),
      'accountPage' => 
      array (
        'name' => 'accountPage',
        'parameters' => 
        array (
          'key' => 
          array (
            'name' => 'key',
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
            'startLine' => 291,
            'endLine' => 291,
            'startColumn' => 36,
            'endColumn' => 46,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'label' => 
          array (
            'name' => 'label',
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
            'startLine' => 291,
            'endLine' => 291,
            'startColumn' => 49,
            'endColumn' => 61,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'route' => 
          array (
            'name' => 'route',
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
            'startLine' => 291,
            'endLine' => 291,
            'startColumn' => 64,
            'endColumn' => 76,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'order' => 
          array (
            'name' => 'order',
            'default' => 
            array (
              'code' => '500',
              'attributes' => 
              array (
                'startLine' => 291,
                'endLine' => 291,
                'startTokenPos' => 2014,
                'startFilePos' => 12603,
                'endTokenPos' => 2014,
                'endFilePos' => 12605,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'int',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 291,
            'endLine' => 291,
            'startColumn' => 79,
            'endColumn' => 94,
            'parameterIndex' => 3,
            'isOptional' => true,
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
 * Mục trong menu tài khoản khách native (`/tai-khoan`), trỏ tới route trang của plugin (storefrontPages()).
 * Trang cần đăng nhập: thêm middleware `vani.customer-session:required` trong file route.
 */',
        'startLine' => 291,
        'endLine' => 294,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Modules\\Extension',
        'declaringClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'implementingClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'currentClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'aliasName' => NULL,
      ),
      'pluginSlug' => 
      array (
        'name' => 'pluginSlug',
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
        'startLine' => 296,
        'endLine' => 299,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'Modules\\Extension',
        'declaringClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'implementingClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'currentClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'aliasName' => NULL,
      ),
      'webhookRoutes' => 
      array (
        'name' => 'webhookRoutes',
        'parameters' => 
        array (
          'file' => 
          array (
            'name' => 'file',
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
            'startLine' => 304,
            'endLine' => 304,
            'startColumn' => 38,
            'endColumn' => 49,
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
 * Webhook từ dịch vụ ngoài: /api/integrations/{slug}/…
 */',
        'startLine' => 304,
        'endLine' => 315,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Modules\\Extension',
        'declaringClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'implementingClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'currentClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'aliasName' => NULL,
      ),
      'refreshRouteLookups' => 
      array (
        'name' => 'refreshRouteLookups',
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
 * Route đăng ký sau khi app đã boot không nằm trong bảng tra cứu tên, khiến `Route::has()`/`route()`
 * không thấy (menu Admin biến mất). Nạp lại bảng tra cứu sau khi đăng ký route của plugin.
 */',
        'startLine' => 321,
        'endLine' => 328,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'Modules\\Extension',
        'declaringClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'implementingClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'currentClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'aliasName' => NULL,
      ),
      'migrations' => 
      array (
        'name' => 'migrations',
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
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 330,
            'endLine' => 330,
            'startColumn' => 35,
            'endColumn' => 46,
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
        'startLine' => 330,
        'endLine' => 333,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Modules\\Extension',
        'declaringClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'implementingClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'currentClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'aliasName' => NULL,
      ),
      'translations' => 
      array (
        'name' => 'translations',
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
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 335,
            'endLine' => 335,
            'startColumn' => 37,
            'endColumn' => 48,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'namespace' => 
          array (
            'name' => 'namespace',
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
            'startLine' => 335,
            'endLine' => 335,
            'startColumn' => 51,
            'endColumn' => 67,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 335,
        'endLine' => 338,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Modules\\Extension',
        'declaringClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'implementingClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'currentClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'aliasName' => NULL,
      ),
      'adminPages' => 
      array (
        'name' => 'adminPages',
        'parameters' => 
        array (
          'namespace' => 
          array (
            'name' => 'namespace',
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
            'startLine' => 343,
            'endLine' => 343,
            'startColumn' => 35,
            'endColumn' => 51,
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
            'startLine' => 343,
            'endLine' => 343,
            'startColumn' => 54,
            'endColumn' => 65,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Trang Inertia của plugin: Inertia::render(\'<Namespace>::<Trang>\').
 */',
        'startLine' => 343,
        'endLine' => 348,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Modules\\Extension',
        'declaringClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'implementingClassName' => 'Modules\\Extension\\PluginServiceProvider',
        'currentClassName' => 'Modules\\Extension\\PluginServiceProvider',
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