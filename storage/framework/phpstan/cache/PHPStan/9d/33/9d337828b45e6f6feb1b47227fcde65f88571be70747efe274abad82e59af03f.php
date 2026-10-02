<?php declare(strict_types = 1);

// phpinternal-PHPStan\BetterReflection\Reflection\ReflectionFunction-spl_object_id
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v3-6.73.0.5-dev-master@e4f5f6c-8.4.25-',
   'data' => 
  array (
    'name' => 'spl_object_id',
    'parameters' => 
    array (
      'object' => 
      array (
        'name' => 'object',
        'default' => NULL,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'object',
            'isIdentifier' => true,
          ),
        ),
        'isVariadic' => false,
        'byRef' => false,
        'isPromoted' => false,
        'attributes' => 
        array (
        ),
        'startLine' => 12,
        'endLine' => 12,
        'startColumn' => 28,
        'endColumn' => 41,
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
    ),
    'docComment' => '/**
 * return the integer object handle for given object
 * @link https://php.net/manual/en/function.spl-object-id.php
 * @param object $object Any object.
 * @return int An integer identifier that is unique for each currently existing object and is always
 * the same for each object.
 * @since 7.2
 */',
    'startLine' => 12,
    'endLine' => 14,
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
        'name' => 'spl_object_id',
        'filename' => 'phpstorm-stubs:SPL/SPL_f.stub',
        'extensionName' => 'SPL',
        'aliasName' => NULL,
      ),
    ),
  ),
));