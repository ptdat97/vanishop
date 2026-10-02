<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Integration/Domain/MessageStatus.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Integration\Domain\MessageStatus
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-8e5fdfffabe416ce11cba5954fd991e8ba198a9a0a75a3e94a404232c82bd3dd',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Integration\\Domain\\MessageStatus',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Integration/Domain/MessageStatus.php',
      ),
    ),
    'namespace' => 'Modules\\Integration\\Domain',
    'name' => 'Modules\\Integration\\Domain\\MessageStatus',
    'shortName' => 'MessageStatus',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => true,
    'isBackedEnum' => true,
    'modifiers' => 0,
    'docComment' => '/**
 * Trạng thái message outbox/inbox.
 *
 * - pending: chờ gửi (lần đầu hoặc đã hẹn retry theo next_attempt_at)
 * - processing: một worker đang giữ
 * - sent/processed: xong
 * - failed: lỗi vĩnh viễn (dữ liệu sai, mapping thiếu…) — không tự retry, chờ sửa rồi replay
 * - dead: hết lượt retry — chờ replay
 * - ignored_stale: (inbox) bản cũ hơn dữ liệu hiện có, bỏ qua
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 17,
    'endLine' => 32,
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
      'name' => 
      array (
        'declaringClassName' => 'Modules\\Integration\\Domain\\MessageStatus',
        'implementingClassName' => 'Modules\\Integration\\Domain\\MessageStatus',
        'name' => 'name',
        'modifiers' => 2177,
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
        'startLine' => NULL,
        'endLine' => NULL,
        'startColumn' => -1,
        'endColumn' => -1,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'value' => 
      array (
        'declaringClassName' => 'Modules\\Integration\\Domain\\MessageStatus',
        'implementingClassName' => 'Modules\\Integration\\Domain\\MessageStatus',
        'name' => 'value',
        'modifiers' => 2177,
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
        'startLine' => NULL,
        'endLine' => NULL,
        'startColumn' => -1,
        'endColumn' => -1,
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
      'isReplayable' => 
      array (
        'name' => 'isReplayable',
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
        'startLine' => 28,
        'endLine' => 31,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Integration\\Domain',
        'declaringClassName' => 'Modules\\Integration\\Domain\\MessageStatus',
        'implementingClassName' => 'Modules\\Integration\\Domain\\MessageStatus',
        'currentClassName' => 'Modules\\Integration\\Domain\\MessageStatus',
        'aliasName' => NULL,
      ),
      'cases' => 
      array (
        'name' => 'cases',
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
        'docComment' => NULL,
        'startLine' => NULL,
        'endLine' => NULL,
        'startColumn' => -1,
        'endColumn' => -1,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Modules\\Integration\\Domain',
        'declaringClassName' => 'Modules\\Integration\\Domain\\MessageStatus',
        'implementingClassName' => 'Modules\\Integration\\Domain\\MessageStatus',
        'currentClassName' => 'Modules\\Integration\\Domain\\MessageStatus',
        'aliasName' => NULL,
      ),
      'from' => 
      array (
        'name' => 'from',
        'parameters' => 
        array (
          'value' => 
          array (
            'name' => 'value',
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
                      'name' => 'string',
                      'isIdentifier' => true,
                    ),
                  ),
                  1 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'int',
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
            'startLine' => NULL,
            'endLine' => NULL,
            'startColumn' => -1,
            'endColumn' => -1,
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
            'name' => 'static',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => NULL,
        'endLine' => NULL,
        'startColumn' => -1,
        'endColumn' => -1,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Modules\\Integration\\Domain',
        'declaringClassName' => 'Modules\\Integration\\Domain\\MessageStatus',
        'implementingClassName' => 'Modules\\Integration\\Domain\\MessageStatus',
        'currentClassName' => 'Modules\\Integration\\Domain\\MessageStatus',
        'aliasName' => NULL,
      ),
      'tryFrom' => 
      array (
        'name' => 'tryFrom',
        'parameters' => 
        array (
          'value' => 
          array (
            'name' => 'value',
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
                      'name' => 'string',
                      'isIdentifier' => true,
                    ),
                  ),
                  1 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'int',
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
            'startLine' => NULL,
            'endLine' => NULL,
            'startColumn' => -1,
            'endColumn' => -1,
            'parameterIndex' => 0,
            'isOptional' => false,
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
                  'name' => 'static',
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
        'startLine' => NULL,
        'endLine' => NULL,
        'startColumn' => -1,
        'endColumn' => -1,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Modules\\Integration\\Domain',
        'declaringClassName' => 'Modules\\Integration\\Domain\\MessageStatus',
        'implementingClassName' => 'Modules\\Integration\\Domain\\MessageStatus',
        'currentClassName' => 'Modules\\Integration\\Domain\\MessageStatus',
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
    'backingType' => 
    array (
      'name' => 'string',
      'isIdentifier' => true,
    ),
    'cases' => 
    array (
      'Pending' => 
      array (
        'name' => 'Pending',
        'value' => 
        array (
          'code' => '\'pending\'',
          'attributes' => 
          array (
            'startLine' => 19,
            'endLine' => 19,
            'startTokenPos' => 32,
            'startFilePos' => 566,
            'endTokenPos' => 32,
            'endFilePos' => 574,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 19,
        'endLine' => 19,
        'startColumn' => 5,
        'endColumn' => 29,
      ),
      'Received' => 
      array (
        'name' => 'Received',
        'value' => 
        array (
          'code' => '\'received\'',
          'attributes' => 
          array (
            'startLine' => 20,
            'endLine' => 20,
            'startTokenPos' => 41,
            'startFilePos' => 597,
            'endTokenPos' => 41,
            'endFilePos' => 606,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 20,
        'endLine' => 20,
        'startColumn' => 5,
        'endColumn' => 31,
      ),
      'Processing' => 
      array (
        'name' => 'Processing',
        'value' => 
        array (
          'code' => '\'processing\'',
          'attributes' => 
          array (
            'startLine' => 21,
            'endLine' => 21,
            'startTokenPos' => 50,
            'startFilePos' => 631,
            'endTokenPos' => 50,
            'endFilePos' => 642,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 21,
        'endLine' => 21,
        'startColumn' => 5,
        'endColumn' => 35,
      ),
      'Sent' => 
      array (
        'name' => 'Sent',
        'value' => 
        array (
          'code' => '\'sent\'',
          'attributes' => 
          array (
            'startLine' => 22,
            'endLine' => 22,
            'startTokenPos' => 59,
            'startFilePos' => 661,
            'endTokenPos' => 59,
            'endFilePos' => 666,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 22,
        'endLine' => 22,
        'startColumn' => 5,
        'endColumn' => 23,
      ),
      'Processed' => 
      array (
        'name' => 'Processed',
        'value' => 
        array (
          'code' => '\'processed\'',
          'attributes' => 
          array (
            'startLine' => 23,
            'endLine' => 23,
            'startTokenPos' => 68,
            'startFilePos' => 690,
            'endTokenPos' => 68,
            'endFilePos' => 700,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 23,
        'endLine' => 23,
        'startColumn' => 5,
        'endColumn' => 33,
      ),
      'Failed' => 
      array (
        'name' => 'Failed',
        'value' => 
        array (
          'code' => '\'failed\'',
          'attributes' => 
          array (
            'startLine' => 24,
            'endLine' => 24,
            'startTokenPos' => 77,
            'startFilePos' => 721,
            'endTokenPos' => 77,
            'endFilePos' => 728,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 24,
        'endLine' => 24,
        'startColumn' => 5,
        'endColumn' => 27,
      ),
      'Dead' => 
      array (
        'name' => 'Dead',
        'value' => 
        array (
          'code' => '\'dead\'',
          'attributes' => 
          array (
            'startLine' => 25,
            'endLine' => 25,
            'startTokenPos' => 86,
            'startFilePos' => 747,
            'endTokenPos' => 86,
            'endFilePos' => 752,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 25,
        'endLine' => 25,
        'startColumn' => 5,
        'endColumn' => 23,
      ),
      'IgnoredStale' => 
      array (
        'name' => 'IgnoredStale',
        'value' => 
        array (
          'code' => '\'ignored_stale\'',
          'attributes' => 
          array (
            'startLine' => 26,
            'endLine' => 26,
            'startTokenPos' => 95,
            'startFilePos' => 779,
            'endTokenPos' => 95,
            'endFilePos' => 793,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 26,
        'endLine' => 26,
        'startColumn' => 5,
        'endColumn' => 40,
      ),
    ),
  ),
));