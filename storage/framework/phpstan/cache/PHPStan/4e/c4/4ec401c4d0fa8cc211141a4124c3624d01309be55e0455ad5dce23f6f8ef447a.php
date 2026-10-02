<?php declare(strict_types = 1);

// phpinternal-PHPStan\BetterReflection\Reflection\ReflectionFunction-array_push
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v3-6.73.0.5-dev-master@e4f5f6c-8.4.25-',
   'data' => 
  array (
    'name' => 'array_push',
    'parameters' => 
    array (
      'array' => 
      array (
        'name' => 'array',
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
        'byRef' => true,
        'isPromoted' => false,
        'attributes' => 
        array (
        ),
        'startLine' => 17,
        'endLine' => 17,
        'startColumn' => 25,
        'endColumn' => 37,
        'parameterIndex' => 0,
        'isOptional' => false,
      ),
      'values' => 
      array (
        'name' => 'values',
        'default' => NULL,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'mixed',
            'isIdentifier' => true,
          ),
        ),
        'isVariadic' => true,
        'byRef' => false,
        'isPromoted' => false,
        'attributes' => 
        array (
        ),
        'startLine' => 17,
        'endLine' => 17,
        'startColumn' => 40,
        'endColumn' => 55,
        'parameterIndex' => 1,
        'isOptional' => true,
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
    ),
    'docComment' => '/**
 * Push elements onto the end of array
 * Since 7.3.0 this function can be called with only one parameter.
 * For earlier versions at least two parameters are required.
 * @link https://php.net/manual/en/function.array-push.php
 * @param array &$array <p>
 * The input array.
 * </p>
 * @param mixed ...$values <p>
 * The pushed variables.
 * </p>
 * @return int the number of elements in the array.
 */',
    'startLine' => 17,
    'endLine' => 19,
    'startColumn' => 5,
    'endColumn' => 5,
    'couldThrow' => false,
    'isClosure' => false,
    'isGenerator' => false,
    'isVariadic' => true,
    'isStatic' => false,
    'namespace' => NULL,
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\InternalLocatedSource',
      'data' => 
      array (
        'name' => 'array_push',
        'filename' => 'phpstorm-stubs:standard/standard_8.stub',
        'extensionName' => 'standard',
        'aliasName' => NULL,
      ),
    ),
  ),
));