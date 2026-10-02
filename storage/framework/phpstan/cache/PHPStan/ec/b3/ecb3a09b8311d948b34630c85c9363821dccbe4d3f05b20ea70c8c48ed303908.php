<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Integration/Domain/RetryPolicy.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Integration\Domain\RetryPolicy
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-fbafc55943023169ef17d778ed0f2ca3768146ffa94ea954cf092bf3075e11f0',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Integration\\Domain\\RetryPolicy',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Integration/Domain/RetryPolicy.php',
      ),
    ),
    'namespace' => 'Modules\\Integration\\Domain',
    'name' => 'Modules\\Integration\\Domain\\RetryPolicy',
    'shortName' => 'RetryPolicy',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 65568,
    'docComment' => '/**
 * Backoff 1m, 5m, 15m, 1h, 6h, 24h (± jitter). Hết lượt → dead.
 *
 * @see docs/11-integration/integration-platform.md §7
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 14,
    'endLine' => 55,
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
      'delays' => 
      array (
        'declaringClassName' => 'Modules\\Integration\\Domain\\RetryPolicy',
        'implementingClassName' => 'Modules\\Integration\\Domain\\RetryPolicy',
        'name' => 'delays',
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
        'default' => 
        array (
          'code' => '[60, 300, 900, 3600, 21600, 86400]',
          'attributes' => 
          array (
            'startLine' => 21,
            'endLine' => 21,
            'startTokenPos' => 49,
            'startFilePos' => 529,
            'endTokenPos' => 66,
            'endFilePos' => 562,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 21,
        'endLine' => 21,
        'startColumn' => 9,
        'endColumn' => 65,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'jitter' => 
      array (
        'declaringClassName' => 'Modules\\Integration\\Domain\\RetryPolicy',
        'implementingClassName' => 'Modules\\Integration\\Domain\\RetryPolicy',
        'name' => 'jitter',
        'modifiers' => 2049,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'float',
            'isIdentifier' => true,
          ),
        ),
        'default' => 
        array (
          'code' => '0.2',
          'attributes' => 
          array (
            'startLine' => 22,
            'endLine' => 22,
            'startTokenPos' => 77,
            'startFilePos' => 596,
            'endTokenPos' => 77,
            'endFilePos' => 598,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 22,
        'endLine' => 22,
        'startColumn' => 9,
        'endColumn' => 34,
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
          'delays' => 
          array (
            'name' => 'delays',
            'default' => 
            array (
              'code' => '[60, 300, 900, 3600, 21600, 86400]',
              'attributes' => 
              array (
                'startLine' => 21,
                'endLine' => 21,
                'startTokenPos' => 49,
                'startFilePos' => 529,
                'endTokenPos' => 66,
                'endFilePos' => 562,
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
            'startLine' => 21,
            'endLine' => 21,
            'startColumn' => 9,
            'endColumn' => 65,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
          'jitter' => 
          array (
            'name' => 'jitter',
            'default' => 
            array (
              'code' => '0.2',
              'attributes' => 
              array (
                'startLine' => 22,
                'endLine' => 22,
                'startTokenPos' => 77,
                'startFilePos' => 596,
                'endTokenPos' => 77,
                'endFilePos' => 598,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'float',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 22,
            'endLine' => 22,
            'startColumn' => 9,
            'endColumn' => 34,
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
 * @param  list<int>  $delays  giây chờ trước lần thử lại thứ 1, 2, …
 * @param  float  $jitter  tỉ lệ dao động ngẫu nhiên (0.2 = ±20%)
 */',
        'startLine' => 20,
        'endLine' => 27,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Integration\\Domain',
        'declaringClassName' => 'Modules\\Integration\\Domain\\RetryPolicy',
        'implementingClassName' => 'Modules\\Integration\\Domain\\RetryPolicy',
        'currentClassName' => 'Modules\\Integration\\Domain\\RetryPolicy',
        'aliasName' => NULL,
      ),
      'delayAfter' => 
      array (
        'name' => 'delayAfter',
        'parameters' => 
        array (
          'failedAttempts' => 
          array (
            'name' => 'failedAttempts',
            'default' => NULL,
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
            'startLine' => 34,
            'endLine' => 34,
            'startColumn' => 32,
            'endColumn' => 50,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'random' => 
          array (
            'name' => 'random',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 34,
                'endLine' => 34,
                'startTokenPos' => 147,
                'startFilePos' => 1099,
                'endTokenPos' => 147,
                'endFilePos' => 1102,
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
                      'name' => 'callable',
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
            'startLine' => 34,
            'endLine' => 34,
            'startColumn' => 53,
            'endColumn' => 76,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
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
                  'name' => 'int',
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
        'docComment' => '/**
 * Số giây chờ trước lần thử kế tiếp sau $failedAttempts lần thất bại; null = hết lượt (dead).
 *
 * @param  callable(int, int): int  $random  (min, max) → số ngẫu nhiên; mặc định random_int
 */',
        'startLine' => 34,
        'endLine' => 49,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Integration\\Domain',
        'declaringClassName' => 'Modules\\Integration\\Domain\\RetryPolicy',
        'implementingClassName' => 'Modules\\Integration\\Domain\\RetryPolicy',
        'currentClassName' => 'Modules\\Integration\\Domain\\RetryPolicy',
        'aliasName' => NULL,
      ),
      'maxRetries' => 
      array (
        'name' => 'maxRetries',
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
        'docComment' => NULL,
        'startLine' => 51,
        'endLine' => 54,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Integration\\Domain',
        'declaringClassName' => 'Modules\\Integration\\Domain\\RetryPolicy',
        'implementingClassName' => 'Modules\\Integration\\Domain\\RetryPolicy',
        'currentClassName' => 'Modules\\Integration\\Domain\\RetryPolicy',
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