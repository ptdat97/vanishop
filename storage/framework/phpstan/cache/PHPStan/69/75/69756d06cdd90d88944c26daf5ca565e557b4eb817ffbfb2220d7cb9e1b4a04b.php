<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Integration/Contracts/Data/DeliveryResult.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Integration\Contracts\Data\DeliveryResult
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-b7b84f9d7ca0ed271c5d0b14a45a39565b25a8087ef9c393af79341c898cba75',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Integration\\Contracts\\Data\\DeliveryResult',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Integration/Contracts/Data/DeliveryResult.php',
      ),
    ),
    'namespace' => 'Modules\\Integration\\Contracts\\Data',
    'name' => 'Modules\\Integration\\Contracts\\Data\\DeliveryResult',
    'shortName' => 'DeliveryResult',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 65568,
    'docComment' => '/**
 * Kết quả gửi/xử lý một message: ok | retryable (thử lại theo backoff) | permanent (không thử lại)
 * | stale (inbox: bản cũ hơn dữ liệu hiện có, bỏ qua).
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 11,
    'endLine' => 67,
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
      'OK' => 
      array (
        'declaringClassName' => 'Modules\\Integration\\Contracts\\Data\\DeliveryResult',
        'implementingClassName' => 'Modules\\Integration\\Contracts\\Data\\DeliveryResult',
        'name' => 'OK',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'ok\'',
          'attributes' => 
          array (
            'startLine' => 13,
            'endLine' => 13,
            'startTokenPos' => 35,
            'startFilePos' => 338,
            'endTokenPos' => 35,
            'endFilePos' => 341,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 13,
        'endLine' => 13,
        'startColumn' => 5,
        'endColumn' => 28,
      ),
      'RETRYABLE' => 
      array (
        'declaringClassName' => 'Modules\\Integration\\Contracts\\Data\\DeliveryResult',
        'implementingClassName' => 'Modules\\Integration\\Contracts\\Data\\DeliveryResult',
        'name' => 'RETRYABLE',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'retryable\'',
          'attributes' => 
          array (
            'startLine' => 15,
            'endLine' => 15,
            'startTokenPos' => 46,
            'startFilePos' => 375,
            'endTokenPos' => 46,
            'endFilePos' => 385,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 15,
        'endLine' => 15,
        'startColumn' => 5,
        'endColumn' => 42,
      ),
      'PERMANENT' => 
      array (
        'declaringClassName' => 'Modules\\Integration\\Contracts\\Data\\DeliveryResult',
        'implementingClassName' => 'Modules\\Integration\\Contracts\\Data\\DeliveryResult',
        'name' => 'PERMANENT',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'permanent\'',
          'attributes' => 
          array (
            'startLine' => 17,
            'endLine' => 17,
            'startTokenPos' => 57,
            'startFilePos' => 419,
            'endTokenPos' => 57,
            'endFilePos' => 429,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 17,
        'endLine' => 17,
        'startColumn' => 5,
        'endColumn' => 42,
      ),
      'STALE' => 
      array (
        'declaringClassName' => 'Modules\\Integration\\Contracts\\Data\\DeliveryResult',
        'implementingClassName' => 'Modules\\Integration\\Contracts\\Data\\DeliveryResult',
        'name' => 'STALE',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'stale\'',
          'attributes' => 
          array (
            'startLine' => 19,
            'endLine' => 19,
            'startTokenPos' => 68,
            'startFilePos' => 459,
            'endTokenPos' => 68,
            'endFilePos' => 465,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 19,
        'endLine' => 19,
        'startColumn' => 5,
        'endColumn' => 34,
      ),
    ),
    'immediateProperties' => 
    array (
      'kind' => 
      array (
        'declaringClassName' => 'Modules\\Integration\\Contracts\\Data\\DeliveryResult',
        'implementingClassName' => 'Modules\\Integration\\Contracts\\Data\\DeliveryResult',
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
        'startLine' => 22,
        'endLine' => 22,
        'startColumn' => 9,
        'endColumn' => 27,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'error' => 
      array (
        'declaringClassName' => 'Modules\\Integration\\Contracts\\Data\\DeliveryResult',
        'implementingClassName' => 'Modules\\Integration\\Contracts\\Data\\DeliveryResult',
        'name' => 'error',
        'modifiers' => 2049,
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
        'default' => 
        array (
          'code' => 'null',
          'attributes' => 
          array (
            'startLine' => 23,
            'endLine' => 23,
            'startTokenPos' => 94,
            'startFilePos' => 564,
            'endTokenPos' => 94,
            'endFilePos' => 567,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 23,
        'endLine' => 23,
        'startColumn' => 9,
        'endColumn' => 36,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'externalId' => 
      array (
        'declaringClassName' => 'Modules\\Integration\\Contracts\\Data\\DeliveryResult',
        'implementingClassName' => 'Modules\\Integration\\Contracts\\Data\\DeliveryResult',
        'name' => 'externalId',
        'modifiers' => 2049,
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
        'default' => 
        array (
          'code' => 'null',
          'attributes' => 
          array (
            'startLine' => 25,
            'endLine' => 25,
            'startTokenPos' => 108,
            'startFilePos' => 720,
            'endTokenPos' => 108,
            'endFilePos' => 723,
          ),
        ),
        'docComment' => '/** Định danh phía nhận (số chứng từ ERP…) — lưu vào external_references nếu có. */',
        'attributes' => 
        array (
        ),
        'startLine' => 25,
        'endLine' => 25,
        'startColumn' => 9,
        'endColumn' => 41,
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
            'startLine' => 22,
            'endLine' => 22,
            'startColumn' => 9,
            'endColumn' => 27,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'error' => 
          array (
            'name' => 'error',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 23,
                'endLine' => 23,
                'startTokenPos' => 94,
                'startFilePos' => 564,
                'endTokenPos' => 94,
                'endFilePos' => 567,
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 23,
            'endLine' => 23,
            'startColumn' => 9,
            'endColumn' => 36,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'externalId' => 
          array (
            'name' => 'externalId',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 25,
                'endLine' => 25,
                'startTokenPos' => 108,
                'startFilePos' => 720,
                'endTokenPos' => 108,
                'endFilePos' => 723,
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 25,
            'endLine' => 25,
            'startColumn' => 9,
            'endColumn' => 41,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 21,
        'endLine' => 26,
        'startColumn' => 5,
        'endColumn' => 8,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'Modules\\Integration\\Contracts\\Data',
        'declaringClassName' => 'Modules\\Integration\\Contracts\\Data\\DeliveryResult',
        'implementingClassName' => 'Modules\\Integration\\Contracts\\Data\\DeliveryResult',
        'currentClassName' => 'Modules\\Integration\\Contracts\\Data\\DeliveryResult',
        'aliasName' => NULL,
      ),
      'ok' => 
      array (
        'name' => 'ok',
        'parameters' => 
        array (
          'externalId' => 
          array (
            'name' => 'externalId',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 28,
                'endLine' => 28,
                'startTokenPos' => 131,
                'startFilePos' => 788,
                'endTokenPos' => 131,
                'endFilePos' => 791,
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
            'startLine' => 28,
            'endLine' => 28,
            'startColumn' => 31,
            'endColumn' => 56,
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
            'name' => 'self',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 28,
        'endLine' => 31,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Modules\\Integration\\Contracts\\Data',
        'declaringClassName' => 'Modules\\Integration\\Contracts\\Data\\DeliveryResult',
        'implementingClassName' => 'Modules\\Integration\\Contracts\\Data\\DeliveryResult',
        'currentClassName' => 'Modules\\Integration\\Contracts\\Data\\DeliveryResult',
        'aliasName' => NULL,
      ),
      'retryable' => 
      array (
        'name' => 'retryable',
        'parameters' => 
        array (
          'error' => 
          array (
            'name' => 'error',
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
            'startLine' => 33,
            'endLine' => 33,
            'startColumn' => 38,
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
            'name' => 'self',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 33,
        'endLine' => 36,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Modules\\Integration\\Contracts\\Data',
        'declaringClassName' => 'Modules\\Integration\\Contracts\\Data\\DeliveryResult',
        'implementingClassName' => 'Modules\\Integration\\Contracts\\Data\\DeliveryResult',
        'currentClassName' => 'Modules\\Integration\\Contracts\\Data\\DeliveryResult',
        'aliasName' => NULL,
      ),
      'permanent' => 
      array (
        'name' => 'permanent',
        'parameters' => 
        array (
          'error' => 
          array (
            'name' => 'error',
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
            'startLine' => 38,
            'endLine' => 38,
            'startColumn' => 38,
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
            'name' => 'self',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 38,
        'endLine' => 41,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Modules\\Integration\\Contracts\\Data',
        'declaringClassName' => 'Modules\\Integration\\Contracts\\Data\\DeliveryResult',
        'implementingClassName' => 'Modules\\Integration\\Contracts\\Data\\DeliveryResult',
        'currentClassName' => 'Modules\\Integration\\Contracts\\Data\\DeliveryResult',
        'aliasName' => NULL,
      ),
      'stale' => 
      array (
        'name' => 'stale',
        'parameters' => 
        array (
          'reason' => 
          array (
            'name' => 'reason',
            'default' => 
            array (
              'code' => '\'stale\'',
              'attributes' => 
              array (
                'startLine' => 43,
                'endLine' => 43,
                'startTokenPos' => 243,
                'startFilePos' => 1165,
                'endTokenPos' => 243,
                'endFilePos' => 1171,
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
            'startColumn' => 34,
            'endColumn' => 57,
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
            'name' => 'self',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 43,
        'endLine' => 46,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Modules\\Integration\\Contracts\\Data',
        'declaringClassName' => 'Modules\\Integration\\Contracts\\Data\\DeliveryResult',
        'implementingClassName' => 'Modules\\Integration\\Contracts\\Data\\DeliveryResult',
        'currentClassName' => 'Modules\\Integration\\Contracts\\Data\\DeliveryResult',
        'aliasName' => NULL,
      ),
      'isOk' => 
      array (
        'name' => 'isOk',
        'parameters' => 
        array (
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
        'startLine' => 48,
        'endLine' => 51,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Integration\\Contracts\\Data',
        'declaringClassName' => 'Modules\\Integration\\Contracts\\Data\\DeliveryResult',
        'implementingClassName' => 'Modules\\Integration\\Contracts\\Data\\DeliveryResult',
        'currentClassName' => 'Modules\\Integration\\Contracts\\Data\\DeliveryResult',
        'aliasName' => NULL,
      ),
      'isRetryable' => 
      array (
        'name' => 'isRetryable',
        'parameters' => 
        array (
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
        'startLine' => 53,
        'endLine' => 56,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Integration\\Contracts\\Data',
        'declaringClassName' => 'Modules\\Integration\\Contracts\\Data\\DeliveryResult',
        'implementingClassName' => 'Modules\\Integration\\Contracts\\Data\\DeliveryResult',
        'currentClassName' => 'Modules\\Integration\\Contracts\\Data\\DeliveryResult',
        'aliasName' => NULL,
      ),
      'isPermanent' => 
      array (
        'name' => 'isPermanent',
        'parameters' => 
        array (
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
        'startLine' => 58,
        'endLine' => 61,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Integration\\Contracts\\Data',
        'declaringClassName' => 'Modules\\Integration\\Contracts\\Data\\DeliveryResult',
        'implementingClassName' => 'Modules\\Integration\\Contracts\\Data\\DeliveryResult',
        'currentClassName' => 'Modules\\Integration\\Contracts\\Data\\DeliveryResult',
        'aliasName' => NULL,
      ),
      'isStale' => 
      array (
        'name' => 'isStale',
        'parameters' => 
        array (
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
        'startLine' => 63,
        'endLine' => 66,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Integration\\Contracts\\Data',
        'declaringClassName' => 'Modules\\Integration\\Contracts\\Data\\DeliveryResult',
        'implementingClassName' => 'Modules\\Integration\\Contracts\\Data\\DeliveryResult',
        'currentClassName' => 'Modules\\Integration\\Contracts\\Data\\DeliveryResult',
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