<?php declare(strict_types = 1);

// phpinternal-PHPStan\BetterReflection\Reflection\ReflectionFunction-round
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v3-6.73.0.5-dev-master@e4f5f6c-8.4.25-',
   'data' => 
  array (
    'name' => 'round',
    'parameters' => 
    array (
      'num' => 
      array (
        'name' => 'num',
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
                  'name' => 'int',
                  'isIdentifier' => true,
                ),
              ),
              1 => 
              array (
                'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                'data' => 
                array (
                  'name' => 'float',
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
        'startLine' => 27,
        'endLine' => 27,
        'startColumn' => 9,
        'endColumn' => 22,
        'parameterIndex' => 0,
        'isOptional' => false,
      ),
      'precision' => 
      array (
        'name' => 'precision',
        'default' => 
        array (
          'code' => '0',
          'attributes' => 
          array (
            'startLine' => 28,
            'endLine' => 28,
            'startTokenPos' => 30,
            'startFilePos' => 1027,
            'endTokenPos' => 30,
            'endFilePos' => 1027,
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
        'startLine' => 28,
        'endLine' => 28,
        'startColumn' => 9,
        'endColumn' => 26,
        'parameterIndex' => 1,
        'isOptional' => true,
      ),
      'mode' => 
      array (
        'name' => 'mode',
        'default' => 
        array (
          'code' => '0',
          'attributes' => 
          array (
            'startLine' => 31,
            'endLine' => 31,
            'startTokenPos' => 76,
            'startFilePos' => 1267,
            'endTokenPos' => 76,
            'endFilePos' => 1267,
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
                  'name' => 'RoundingMode',
                  'isIdentifier' => false,
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
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\PhpStormStubsElementAvailable',
            'isRepeated' => false,
            'arguments' => 
            array (
              'from' => 
              array (
                'code' => '\'5.3\'',
                'attributes' => 
                array (
                  'startLine' => 29,
                  'endLine' => 29,
                  'startTokenPos' => 39,
                  'startFilePos' => 1105,
                  'endTokenPos' => 39,
                  'endFilePos' => 1109,
                ),
              ),
              'to' => 
              array (
                'code' => '\'8.4\'',
                'attributes' => 
                array (
                  'startLine' => 29,
                  'endLine' => 29,
                  'startTokenPos' => 45,
                  'startFilePos' => 1116,
                  'endTokenPos' => 45,
                  'endFilePos' => 1120,
                ),
              ),
            ),
          ),
          1 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Internal\\LanguageLevelTypeAware',
            'isRepeated' => false,
            'arguments' => 
            array (
              0 => 
              array (
                'code' => '[\'8.4\' => \'RoundingMode|int\']',
                'attributes' => 
                array (
                  'startLine' => 30,
                  'endLine' => 30,
                  'startTokenPos' => 52,
                  'startFilePos' => 1186,
                  'endTokenPos' => 58,
                  'endFilePos' => 1214,
                ),
              ),
              'default' => 
              array (
                'code' => '\'int\'',
                'attributes' => 
                array (
                  'startLine' => 30,
                  'endLine' => 30,
                  'startTokenPos' => 64,
                  'startFilePos' => 1226,
                  'endTokenPos' => 64,
                  'endFilePos' => 1230,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 29,
        'endLine' => 31,
        'startColumn' => 9,
        'endColumn' => 34,
        'parameterIndex' => 2,
        'isOptional' => true,
      ),
    ),
    'returnsReference' => false,
    'returnType' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
      'data' => 
      array (
        'name' => 'float',
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
        ),
      ),
    ),
    'docComment' => '/**
 * Returns the rounded value of val to specified precision (number of digits after the decimal point).
 * precision can also be negative or zero (default).
 * Note: PHP doesn\'t handle strings like "12,300.2" correctly by default. See converting from strings.
 * @link https://php.net/manual/en/function.round.php
 * @param int|float $num <p>
 * The value to round
 * </p>
 * @param int $precision [optional] <p>
 * The optional number of decimal digits to round to.
 * </p>
 * @param int $mode [optional] <p>
 * One of PHP_ROUND_HALF_UP,
 * PHP_ROUND_HALF_DOWN,
 * PHP_ROUND_HALF_EVEN, or
 * PHP_ROUND_HALF_ODD.
 * </p>
 * @return float The rounded value
 * @throws \\ValueError The function throws a ValueError if mode is invalid. Prior to PHP 8.4.0, an
 * invalid mode would silently default to PHP_ROUND_HALF_UP.
 */',
    'startLine' => 25,
    'endLine' => 34,
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
        'name' => 'round',
        'filename' => 'phpstorm-stubs:standard/standard_3.stub',
        'extensionName' => 'standard',
        'aliasName' => NULL,
      ),
    ),
  ),
));