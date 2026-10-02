<?php declare(strict_types = 1);

// phpinternal-PHPStan\BetterReflection\Reflection\ReflectionFunction-sleep
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v3-6.73.0.5-dev-master@e4f5f6c-8.4.25-',
   'data' => 
  array (
    'name' => 'sleep',
    'parameters' => 
    array (
      'seconds' => 
      array (
        'name' => 'seconds',
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
        'startLine' => 27,
        'endLine' => 27,
        'startColumn' => 20,
        'endColumn' => 31,
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
      0 => 
      array (
        'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
        'isRepeated' => false,
        'arguments' => 
        array (
          0 => 
          array (
            'code' => '["8.0" => "int"]',
            'attributes' => 
            array (
              'startLine' => 26,
              'endLine' => 26,
              'startTokenPos' => 11,
              'startFilePos' => 1056,
              'endTokenPos' => 17,
              'endFilePos' => 1071,
            ),
          ),
          'default' => 
          array (
            'code' => '"int|false"',
            'attributes' => 
            array (
              'startLine' => 26,
              'endLine' => 26,
              'startTokenPos' => 23,
              'startFilePos' => 1083,
              'endTokenPos' => 23,
              'endFilePos' => 1093,
            ),
          ),
        ),
      ),
    ),
    'docComment' => '/**
 * Delays the program execution for the given number of seconds
 * @link https://php.net/manual/en/function.sleep.php
 * @param int<0,max> $seconds <p>
 * Halt time in seconds (must be greater than or equal to 0).
 * </p>
 * @return int Returns zero on success.
 * <p>
 * If the call was interrupted by a signal, sleep() returns a
 * non-zero value. On Windows, this value will always be 192
 * (the value of the WAIT_IO_COMPLETION constant within the Windows API).
 * On other platforms, the return value will be the
 * number of seconds left to sleep.
 * </p>
 * <p>
 * As of PHP 8.0, if the specified number of seconds is negative,
 * this function will throw a ValueError.
 * Before PHP 8.0, an E_WARNING was raised instead, and the function returned false.
 * </p>
 * @throws \\ValueError If the specified number of seconds is negative, this function will throw a
 * ValueError.
 */',
    'startLine' => 26,
    'endLine' => 29,
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
        'name' => 'sleep',
        'filename' => 'phpstorm-stubs:standard/standard_0.stub',
        'extensionName' => 'standard',
        'aliasName' => NULL,
      ),
    ),
  ),
));