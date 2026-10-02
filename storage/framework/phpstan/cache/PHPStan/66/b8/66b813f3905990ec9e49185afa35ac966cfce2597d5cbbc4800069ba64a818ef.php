<?php declare(strict_types = 1);

// phpinternal-PHPStan\BetterReflection\Reflection\ReflectionFunction-realpath
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v3-6.73.0.5-dev-master@e4f5f6c-8.4.25-',
   'data' => 
  array (
    'name' => 'realpath',
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
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\FileReference',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'startLine' => 19,
        'endLine' => 20,
        'startColumn' => 9,
        'endColumn' => 20,
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
          0 => 
          array (
            'code' => '\\true',
            'attributes' => 
            array (
              'startLine' => 17,
              'endLine' => 17,
              'startTokenPos' => 11,
              'startFilePos' => 511,
              'endTokenPos' => 11,
              'endFilePos' => 514,
            ),
          ),
        ),
      ),
    ),
    'docComment' => '/**
 * Returns canonicalized absolute pathname
 * @link https://php.net/manual/en/function.realpath.php
 * @param string $path <p>
 * The path being checked.
 * </p>
 * @return string|false the canonicalized absolute pathname on success. The resulting path
 * will have no symbolic link, \'/./\' or \'/../\' components.
 * <p>
 * realpath returns false on failure, e.g. if
 * the file does not exist.
 * </p>
 */',
    'startLine' => 17,
    'endLine' => 23,
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
        'name' => 'realpath',
        'filename' => 'phpstorm-stubs:standard/standard_6.stub',
        'extensionName' => 'standard',
        'aliasName' => NULL,
      ),
    ),
  ),
));