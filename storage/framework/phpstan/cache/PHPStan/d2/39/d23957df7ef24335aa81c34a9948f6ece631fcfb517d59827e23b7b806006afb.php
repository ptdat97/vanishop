<?php declare(strict_types = 1);

// phpinternal-PHPStan\BetterReflection\Reflection\ReflectionFunction-debug_backtrace
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v3-6.73.0.5-dev-master@e4f5f6c-8.4.25-',
   'data' => 
  array (
    'name' => 'debug_backtrace',
    'parameters' => 
    array (
      'options' => 
      array (
        'name' => 'options',
        'default' => 
        array (
          'code' => '\\DEBUG_BACKTRACE_PROVIDE_OBJECT',
          'attributes' => 
          array (
            'startLine' => 103,
            'endLine' => 103,
            'startTokenPos' => 25,
            'startFilePos' => 2863,
            'endTokenPos' => 25,
            'endFilePos' => 2892,
          ),
        ),
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
        'startLine' => 103,
        'endLine' => 103,
        'startColumn' => 30,
        'endColumn' => 74,
        'parameterIndex' => 0,
        'isOptional' => true,
      ),
      'limit' => 
      array (
        'name' => 'limit',
        'default' => 
        array (
          'code' => '0',
          'attributes' => 
          array (
            'startLine' => 103,
            'endLine' => 103,
            'startTokenPos' => 34,
            'startFilePos' => 2908,
            'endTokenPos' => 34,
            'endFilePos' => 2908,
          ),
        ),
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
        'startLine' => 103,
        'endLine' => 103,
        'startColumn' => 77,
        'endColumn' => 90,
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
        'name' => 'array',
        'isIdentifier' => true,
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
              'startLine' => 102,
              'endLine' => 102,
              'startTokenPos' => 11,
              'startFilePos' => 2812,
              'endTokenPos' => 11,
              'endFilePos' => 2815,
            ),
          ),
        ),
      ),
    ),
    'docComment' => '/**
 * Generates a backtrace
 * @link https://php.net/manual/en/function.debug-backtrace.php
 * @param int $options [optional] <p>
 * As of 5.3.6, this parameter is a bitmask for the following options:</p>
 * <b>debug_backtrace</b> options
 * <table>
 * <tr valign="top">
 * <td>DEBUG_BACKTRACE_PROVIDE_OBJECT</td>
 * <td>
 * Whether or not to populate the "object" index.
 * </td>
 * </tr>
 * <tr valign="top">
 * <td>DEBUG_BACKTRACE_IGNORE_ARGS</td>
 * <td>
 * Whether or not to omit the "args" index, and thus all the function/method arguments,
 * to save memory.
 * </td>
 * </tr>
 * </table>
 * <p>
 * Before 5.3.6, the only values recognized are true or false, which are the same as
 * setting or not setting the <b>DEBUG_BACKTRACE_PROVIDE_OBJECT</b> option respectively.
 * </p>
 * @param int $limit [optional] <p>
 * As of 5.4.0, this parameter can be used to limit the number of stack frames returned.
 * By default (<i>limit</i>=0) it returns all stack frames.
 * </p>
 * @return array <p>an array of associative arrays. The possible returned elements
 * are as follows:
 * </p>
 * <p>
 * Possible returned elements from <b>debug_backtrace</b>
 * </p>
 * <table>
 * <tr valign="top">
 * <td>Name</td>
 * <td>Type</td>
 * <td>Description</td>
 * </tr>
 * <tr valign="top">
 * <td>function</td>
 * <td>string</td>
 * <td>
 * The current function name. See also
 * __FUNCTION__.
 * </td>
 * </tr>
 * <tr valign="top">
 * <td>line</td>
 * <td>integer</td>
 * <td>
 * The current line number. See also
 * __LINE__.
 * </td>
 * </tr>
 * <tr valign="top">
 * <td>file</td>
 * <td>string</td>
 * <td>
 * The current file name. See also
 * __FILE__.
 * </td>
 * </tr>
 * <tr valign="top">
 * <td>class</td>
 * <td>string</td>
 * <td>
 * The current class name. See also
 * __CLASS__
 * </td>
 * </tr>
 * <tr valign="top">
 * <td>object</td>
 * <td>object</td>
 * <td>
 * The current object.
 * </td>
 * </tr>
 * <tr valign="top">
 * <td>type</td>
 * <td>string</td>
 * <td>
 * The current call type. If a method call, "->" is returned. If a static
 * method call, "::" is returned. If a function call, nothing is returned.
 * </td>
 * </tr>
 * <tr valign="top">
 * <td>args</td>
 * <td>array</td>
 * <td>
 * If inside a function, this lists the functions arguments. If
 * inside an included file, this lists the included file name(s).
 * </td>
 * </tr>
 * </table>
 */',
    'startLine' => 102,
    'endLine' => 105,
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
        'name' => 'debug_backtrace',
        'filename' => 'phpstorm-stubs:Core/Core.stub',
        'extensionName' => 'Core',
        'aliasName' => NULL,
      ),
    ),
  ),
));