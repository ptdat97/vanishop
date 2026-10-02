<?php declare(strict_types = 1);

// phpinternal-PHPStan\BetterReflection\Reflection\ReflectionFunction-hash_file
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v3-6.73.0.5-dev-master@e4f5f6c-8.4.25-',
   'data' => 
  array (
    'name' => 'hash_file',
    'parameters' => 
    array (
      'algo' => 
      array (
        'name' => 'algo',
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
        'startLine' => 26,
        'endLine' => 26,
        'startColumn' => 9,
        'endColumn' => 20,
        'parameterIndex' => 0,
        'isOptional' => false,
      ),
      'filename' => 
      array (
        'name' => 'filename',
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
        'startLine' => 27,
        'endLine' => 27,
        'startColumn' => 9,
        'endColumn' => 24,
        'parameterIndex' => 1,
        'isOptional' => false,
      ),
      'binary' => 
      array (
        'name' => 'binary',
        'default' => 
        array (
          'code' => '\\false',
          'attributes' => 
          array (
            'startLine' => 28,
            'endLine' => 28,
            'startTokenPos' => 33,
            'startFilePos' => 1149,
            'endTokenPos' => 33,
            'endFilePos' => 1153,
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
        'startLine' => 28,
        'endLine' => 28,
        'startColumn' => 9,
        'endColumn' => 28,
        'parameterIndex' => 2,
        'isOptional' => true,
      ),
      'options' => 
      array (
        'name' => 'options',
        'default' => 
        array (
          'code' => '[]',
          'attributes' => 
          array (
            'startLine' => 30,
            'endLine' => 30,
            'startTokenPos' => 49,
            'startFilePos' => 1258,
            'endTokenPos' => 50,
            'endFilePos' => 1259,
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
        'isPromoted' => false,
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\PhpStormStubsElementAvailable',
            'isRepeated' => false,
            'arguments' => 
            array (
              0 => 
              array (
                'code' => '\'8.1\'',
                'attributes' => 
                array (
                  'startLine' => 29,
                  'endLine' => 29,
                  'startTokenPos' => 39,
                  'startFilePos' => 1225,
                  'endTokenPos' => 39,
                  'endFilePos' => 1229,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 29,
        'endLine' => 30,
        'startColumn' => 9,
        'endColumn' => 27,
        'parameterIndex' => 3,
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
      0 => 
      array (
        'name' => 'JetBrains\\PhpStorm\\Pure',
        'isRepeated' => false,
        'arguments' => 
        array (
        ),
      ),
    ),
    'docComment' => '/**
 * (PHP 5 &gt;= 5.1.2, PECL hash &gt;= 1.1)<br/>
 * Generate a hash value using the contents of a given file
 * @link https://php.net/manual/en/function.hash-file.php
 * @param string $algo <p>
 * Name of selected hashing algorithm (i.e. "md5", "sha256", "haval160,4", etc..)
 * </p>
 * @param string $filename <p>
 * URL describing location of file to be hashed; Supports fopen wrappers.
 * </p>
 * @param bool $binary [optional] <p>
 * When set to <b>TRUE</b>, outputs raw binary data.
 * <b>FALSE</b> outputs lowercase hexits.
 * </p>
 * @param array $options An array of options for the various hashing algorithms. Currently, only the
 * "seed" parameter is supported by the MurmurHash variants.
 * @return string|false a string containing the calculated message digest as lowercase hexits
 * unless <i>binary</i> is set to true in which case the raw
 * binary representation of the message digest is returned.
 */',
    'startLine' => 24,
    'endLine' => 33,
    'startColumn' => 5,
    'endColumn' => 5,
    'couldThrow' => false,
    'isClosure' => false,
    'isGenerator' => false,
    'isVariadic' => false,
    'isStatic' => false,
    'namespace' => NULL,
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\InternalLocatedSource',
      'data' => 
      array (
        'name' => 'hash_file',
        'filename' => 'phpstorm-stubs:hash/hash.stub',
        'extensionName' => 'hash',
        'aliasName' => NULL,
      ),
    ),
  ),
));