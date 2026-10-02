<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Extension/Application/Hooks/CallerPlugin.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Extension\Application\Hooks\CallerPlugin
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-570233deb433c8c31ef9648e3cfabf26f90b78c83cedca98ed7f71a8f67d5417',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Extension\\Application\\Hooks\\CallerPlugin',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Extension/Application/Hooks/CallerPlugin.php',
      ),
    ),
    'namespace' => 'Modules\\Extension\\Application\\Hooks',
    'name' => 'Modules\\Extension\\Application\\Hooks\\CallerPlugin',
    'shortName' => 'CallerPlugin',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Xác định plugin sở hữu listener từ vị trí gọi (file nằm trong thư mục plugin nào) — để helper ngắn
 * `vani_add_filter()` vẫn gắn listener với plugin (chỉ chạy khi plugin bật) mà không cần truyền plugin id.
 * Gọi từ modules/ (Core) → null.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 14,
    'endLine' => 53,
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
      'paths' => 
      array (
        'declaringClassName' => 'Modules\\Extension\\Application\\Hooks\\CallerPlugin',
        'implementingClassName' => 'Modules\\Extension\\Application\\Hooks\\CallerPlugin',
        'name' => 'paths',
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
            'startLine' => 17,
            'endLine' => 17,
            'startTokenPos' => 41,
            'startFilePos' => 588,
            'endTokenPos' => 41,
            'endFilePos' => 591,
          ),
        ),
        'docComment' => '/** @var array<string, string>|null thư mục plugin (realpath) => plugin id */',
        'attributes' => 
        array (
        ),
        'startLine' => 17,
        'endLine' => 17,
        'startColumn' => 5,
        'endColumn' => 33,
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
      'resolve' => 
      array (
        'name' => 'resolve',
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
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 19,
        'endLine' => 37,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Extension\\Application\\Hooks',
        'declaringClassName' => 'Modules\\Extension\\Application\\Hooks\\CallerPlugin',
        'implementingClassName' => 'Modules\\Extension\\Application\\Hooks\\CallerPlugin',
        'currentClassName' => 'Modules\\Extension\\Application\\Hooks\\CallerPlugin',
        'aliasName' => NULL,
      ),
      'paths' => 
      array (
        'name' => 'paths',
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
 * @return array<string, string>
 */',
        'startLine' => 42,
        'endLine' => 52,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'Modules\\Extension\\Application\\Hooks',
        'declaringClassName' => 'Modules\\Extension\\Application\\Hooks\\CallerPlugin',
        'implementingClassName' => 'Modules\\Extension\\Application\\Hooks\\CallerPlugin',
        'currentClassName' => 'Modules\\Extension\\Application\\Hooks\\CallerPlugin',
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