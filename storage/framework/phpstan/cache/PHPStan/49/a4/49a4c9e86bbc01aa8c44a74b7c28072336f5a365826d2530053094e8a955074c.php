<?php declare(strict_types = 1);

// osfsl-/Users/dat/Ecommerce/vanishop/vendor/composer/../symfony/uid/Ulid.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Symfony\Component\Uid\Ulid
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-e8726c7e7933bceec356630c61e217809eca22bff363cf09e861b933a58503bb-8.4.25-6.73.0.5',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Symfony\\Component\\Uid\\Ulid',
        'filename' => '/Users/dat/Ecommerce/vanishop/vendor/composer/../symfony/uid/Ulid.php',
      ),
    ),
    'namespace' => 'Symfony\\Component\\Uid',
    'name' => 'Symfony\\Component\\Uid\\Ulid',
    'shortName' => 'Ulid',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * A ULID is lexicographically sortable and contains a 48-bit timestamp and 80-bit of crypto-random entropy.
 *
 * @see https://github.com/ulid/spec
 *
 * @author Nicolas Grekas <p@tchwork.com>
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 23,
    'endLine' => 288,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Symfony\\Component\\Uid\\AbstractUid',
    'implementsClassNames' => 
    array (
      0 => 'Symfony\\Component\\Uid\\TimeBasedUidInterface',
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
      'FORMAT_BINARY' => 
      array (
        'declaringClassName' => 'Symfony\\Component\\Uid\\Ulid',
        'implementingClassName' => 'Symfony\\Component\\Uid\\Ulid',
        'name' => 'FORMAT_BINARY',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '1',
          'attributes' => 
          array (
            'startLine' => 25,
            'endLine' => 25,
            'startTokenPos' => 38,
            'startFilePos' => 636,
            'endTokenPos' => 38,
            'endFilePos' => 636,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 25,
        'endLine' => 25,
        'startColumn' => 5,
        'endColumn' => 35,
      ),
      'FORMAT_BASE_32' => 
      array (
        'declaringClassName' => 'Symfony\\Component\\Uid\\Ulid',
        'implementingClassName' => 'Symfony\\Component\\Uid\\Ulid',
        'name' => 'FORMAT_BASE_32',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '1 << 1',
          'attributes' => 
          array (
            'startLine' => 26,
            'endLine' => 26,
            'startTokenPos' => 49,
            'startFilePos' => 673,
            'endTokenPos' => 53,
            'endFilePos' => 678,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 26,
        'endLine' => 26,
        'startColumn' => 5,
        'endColumn' => 41,
      ),
      'FORMAT_BASE_58' => 
      array (
        'declaringClassName' => 'Symfony\\Component\\Uid\\Ulid',
        'implementingClassName' => 'Symfony\\Component\\Uid\\Ulid',
        'name' => 'FORMAT_BASE_58',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '1 << 2',
          'attributes' => 
          array (
            'startLine' => 27,
            'endLine' => 27,
            'startTokenPos' => 64,
            'startFilePos' => 715,
            'endTokenPos' => 68,
            'endFilePos' => 720,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 27,
        'endLine' => 27,
        'startColumn' => 5,
        'endColumn' => 41,
      ),
      'FORMAT_RFC_4122' => 
      array (
        'declaringClassName' => 'Symfony\\Component\\Uid\\Ulid',
        'implementingClassName' => 'Symfony\\Component\\Uid\\Ulid',
        'name' => 'FORMAT_RFC_4122',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '1 << 3',
          'attributes' => 
          array (
            'startLine' => 28,
            'endLine' => 28,
            'startTokenPos' => 79,
            'startFilePos' => 758,
            'endTokenPos' => 83,
            'endFilePos' => 763,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 28,
        'endLine' => 28,
        'startColumn' => 5,
        'endColumn' => 42,
      ),
      'FORMAT_RFC_9562' => 
      array (
        'declaringClassName' => 'Symfony\\Component\\Uid\\Ulid',
        'implementingClassName' => 'Symfony\\Component\\Uid\\Ulid',
        'name' => 'FORMAT_RFC_9562',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => 'self::FORMAT_RFC_4122',
          'attributes' => 
          array (
            'startLine' => 29,
            'endLine' => 29,
            'startTokenPos' => 94,
            'startFilePos' => 801,
            'endTokenPos' => 96,
            'endFilePos' => 821,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 29,
        'endLine' => 29,
        'startColumn' => 5,
        'endColumn' => 57,
      ),
      'FORMAT_ALL' => 
      array (
        'declaringClassName' => 'Symfony\\Component\\Uid\\Ulid',
        'implementingClassName' => 'Symfony\\Component\\Uid\\Ulid',
        'name' => 'FORMAT_ALL',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '-1',
          'attributes' => 
          array (
            'startLine' => 30,
            'endLine' => 30,
            'startTokenPos' => 107,
            'startFilePos' => 854,
            'endTokenPos' => 108,
            'endFilePos' => 855,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 30,
        'endLine' => 30,
        'startColumn' => 5,
        'endColumn' => 33,
      ),
      'NIL' => 
      array (
        'declaringClassName' => 'Symfony\\Component\\Uid\\Ulid',
        'implementingClassName' => 'Symfony\\Component\\Uid\\Ulid',
        'name' => 'NIL',
        'modifiers' => 2,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'00000000000000000000000000\'',
          'attributes' => 
          array (
            'startLine' => 32,
            'endLine' => 32,
            'startTokenPos' => 119,
            'startFilePos' => 885,
            'endTokenPos' => 119,
            'endFilePos' => 912,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 32,
        'endLine' => 32,
        'startColumn' => 5,
        'endColumn' => 55,
      ),
      'MAX' => 
      array (
        'declaringClassName' => 'Symfony\\Component\\Uid\\Ulid',
        'implementingClassName' => 'Symfony\\Component\\Uid\\Ulid',
        'name' => 'MAX',
        'modifiers' => 2,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'7ZZZZZZZZZZZZZZZZZZZZZZZZZ\'',
          'attributes' => 
          array (
            'startLine' => 33,
            'endLine' => 33,
            'startTokenPos' => 130,
            'startFilePos' => 941,
            'endTokenPos' => 130,
            'endFilePos' => 968,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 33,
        'endLine' => 33,
        'startColumn' => 5,
        'endColumn' => 55,
      ),
    ),
    'immediateProperties' => 
    array (
      'time' => 
      array (
        'declaringClassName' => 'Symfony\\Component\\Uid\\Ulid',
        'implementingClassName' => 'Symfony\\Component\\Uid\\Ulid',
        'name' => 'time',
        'modifiers' => 20,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'default' => 
        array (
          'code' => '\'\'',
          'attributes' => 
          array (
            'startLine' => 35,
            'endLine' => 35,
            'startTokenPos' => 143,
            'startFilePos' => 1006,
            'endTokenPos' => 143,
            'endFilePos' => 1007,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 35,
        'endLine' => 35,
        'startColumn' => 5,
        'endColumn' => 37,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'rand' => 
      array (
        'declaringClassName' => 'Symfony\\Component\\Uid\\Ulid',
        'implementingClassName' => 'Symfony\\Component\\Uid\\Ulid',
        'name' => 'rand',
        'modifiers' => 20,
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
            'startLine' => 36,
            'endLine' => 36,
            'startTokenPos' => 156,
            'startFilePos' => 1043,
            'endTokenPos' => 157,
            'endFilePos' => 1044,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 36,
        'endLine' => 36,
        'startColumn' => 5,
        'endColumn' => 36,
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
      '__construct' => 
      array (
        'name' => '__construct',
        'parameters' => 
        array (
          'ulid' => 
          array (
            'name' => 'ulid',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 38,
                'endLine' => 38,
                'startTokenPos' => 173,
                'startFilePos' => 1096,
                'endTokenPos' => 173,
                'endFilePos' => 1099,
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
            'startLine' => 38,
            'endLine' => 38,
            'startColumn' => 33,
            'endColumn' => 52,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 38,
        'endLine' => 53,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Symfony\\Component\\Uid',
        'declaringClassName' => 'Symfony\\Component\\Uid\\Ulid',
        'implementingClassName' => 'Symfony\\Component\\Uid\\Ulid',
        'currentClassName' => 'Symfony\\Component\\Uid\\Ulid',
        'aliasName' => NULL,
      ),
      'isValid' => 
      array (
        'name' => 'isValid',
        'parameters' => 
        array (
          'ulid' => 
          array (
            'name' => 'ulid',
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
            'startLine' => 58,
            'endLine' => 58,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param int-mask-of<Ulid::FORMAT_*> $format
 */',
        'startLine' => 58,
        'endLine' => 93,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => true,
        'modifiers' => 17,
        'namespace' => 'Symfony\\Component\\Uid',
        'declaringClassName' => 'Symfony\\Component\\Uid\\Ulid',
        'implementingClassName' => 'Symfony\\Component\\Uid\\Ulid',
        'currentClassName' => 'Symfony\\Component\\Uid\\Ulid',
        'aliasName' => NULL,
      ),
      'fromString' => 
      array (
        'name' => 'fromString',
        'parameters' => 
        array (
          'ulid' => 
          array (
            'name' => 'ulid',
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
            'startLine' => 95,
            'endLine' => 95,
            'startColumn' => 39,
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
            'name' => 'static',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 95,
        'endLine' => 134,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Symfony\\Component\\Uid',
        'declaringClassName' => 'Symfony\\Component\\Uid\\Ulid',
        'implementingClassName' => 'Symfony\\Component\\Uid\\Ulid',
        'currentClassName' => 'Symfony\\Component\\Uid\\Ulid',
        'aliasName' => NULL,
      ),
      'toBinary' => 
      array (
        'name' => 'toBinary',
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
        'startLine' => 136,
        'endLine' => 151,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Symfony\\Component\\Uid',
        'declaringClassName' => 'Symfony\\Component\\Uid\\Ulid',
        'implementingClassName' => 'Symfony\\Component\\Uid\\Ulid',
        'currentClassName' => 'Symfony\\Component\\Uid\\Ulid',
        'aliasName' => NULL,
      ),
      'toBase32' => 
      array (
        'name' => 'toBase32',
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
 * Returns the identifier as a base32 case insensitive string.
 *
 * @see https://tools.ietf.org/html/rfc4648#section-6
 *
 * @example 09EJ0S614A9FXVG9C5537Q9ZE1 (len=26)
 */',
        'startLine' => 160,
        'endLine' => 163,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Symfony\\Component\\Uid',
        'declaringClassName' => 'Symfony\\Component\\Uid\\Ulid',
        'implementingClassName' => 'Symfony\\Component\\Uid\\Ulid',
        'currentClassName' => 'Symfony\\Component\\Uid\\Ulid',
        'aliasName' => NULL,
      ),
      'getDateTime' => 
      array (
        'name' => 'getDateTime',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'DateTimeImmutable',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 165,
        'endLine' => 185,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Symfony\\Component\\Uid',
        'declaringClassName' => 'Symfony\\Component\\Uid\\Ulid',
        'implementingClassName' => 'Symfony\\Component\\Uid\\Ulid',
        'currentClassName' => 'Symfony\\Component\\Uid\\Ulid',
        'aliasName' => NULL,
      ),
      'generate' => 
      array (
        'name' => 'generate',
        'parameters' => 
        array (
          'time' => 
          array (
            'name' => 'time',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 187,
                'endLine' => 187,
                'startTokenPos' => 1551,
                'startFilePos' => 5977,
                'endTokenPos' => 1551,
                'endFilePos' => 5980,
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
                      'name' => 'DateTimeInterface',
                      'isIdentifier' => false,
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
            'startLine' => 187,
            'endLine' => 187,
            'startColumn' => 37,
            'endColumn' => 68,
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
        'startLine' => 187,
        'endLine' => 243,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Symfony\\Component\\Uid',
        'declaringClassName' => 'Symfony\\Component\\Uid\\Ulid',
        'implementingClassName' => 'Symfony\\Component\\Uid\\Ulid',
        'currentClassName' => 'Symfony\\Component\\Uid\\Ulid',
        'aliasName' => NULL,
      ),
      'transformToBase32' => 
      array (
        'name' => 'transformToBase32',
        'parameters' => 
        array (
          'ulid' => 
          array (
            'name' => 'ulid',
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
            'startLine' => 250,
            'endLine' => 250,
            'startColumn' => 47,
            'endColumn' => 58,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'format' => 
          array (
            'name' => 'format',
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
            'startLine' => 250,
            'endLine' => 250,
            'startColumn' => 61,
            'endColumn' => 71,
            'parameterIndex' => 1,
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
                  'name' => 'string',
                  'isIdentifier' => true,
                ),
              ),
              1 => 
              array (
                'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                'data' => 
                array (
                  'name' => 'false',
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
 * @param int-mask-of<Ulid::FORMAT_*> $format
 *
 * @return string|false The base32 string or false if the format doesn\'t match the input
 */',
        'startLine' => 250,
        'endLine' => 271,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 20,
        'namespace' => 'Symfony\\Component\\Uid',
        'declaringClassName' => 'Symfony\\Component\\Uid\\Ulid',
        'implementingClassName' => 'Symfony\\Component\\Uid\\Ulid',
        'currentClassName' => 'Symfony\\Component\\Uid\\Ulid',
        'aliasName' => NULL,
      ),
      'binaryToBase32' => 
      array (
        'name' => 'binaryToBase32',
        'parameters' => 
        array (
          'ulid' => 
          array (
            'name' => 'ulid',
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
            'startLine' => 273,
            'endLine' => 273,
            'startColumn' => 44,
            'endColumn' => 55,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 273,
        'endLine' => 287,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 20,
        'namespace' => 'Symfony\\Component\\Uid',
        'declaringClassName' => 'Symfony\\Component\\Uid\\Ulid',
        'implementingClassName' => 'Symfony\\Component\\Uid\\Ulid',
        'currentClassName' => 'Symfony\\Component\\Uid\\Ulid',
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