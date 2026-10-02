<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Shared/Domain/Money/Currency.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Shared\Domain\Money\Currency
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-56868a04f3706fb3008b5455324c8d51874964ba40418d2617cf16a80b7a581e',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Shared\\Domain\\Money\\Currency',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Shared/Domain/Money/Currency.php',
      ),
    ),
    'namespace' => 'Modules\\Shared\\Domain\\Money',
    'name' => 'Modules\\Shared\\Domain\\Money\\Currency',
    'shortName' => 'Currency',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 65568,
    'docComment' => '/**
 * Tiền tệ ISO 4217 cùng số chữ số phần lẻ (minor unit exponent).
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 10,
    'endLine' => 43,
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
      'EXPONENTS' => 
      array (
        'declaringClassName' => 'Modules\\Shared\\Domain\\Money\\Currency',
        'implementingClassName' => 'Modules\\Shared\\Domain\\Money\\Currency',
        'name' => 'EXPONENTS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'VND\' => 0, \'USD\' => 2]',
          'attributes' => 
          array (
            'startLine' => 13,
            'endLine' => 16,
            'startTokenPos' => 37,
            'startFilePos' => 259,
            'endTokenPos' => 53,
            'endFilePos' => 305,
          ),
        ),
        'docComment' => '/** @var array<string, int> */',
        'attributes' => 
        array (
        ),
        'startLine' => 13,
        'endLine' => 16,
        'startColumn' => 5,
        'endColumn' => 6,
      ),
    ),
    'immediateProperties' => 
    array (
      'code' => 
      array (
        'declaringClassName' => 'Modules\\Shared\\Domain\\Money\\Currency',
        'implementingClassName' => 'Modules\\Shared\\Domain\\Money\\Currency',
        'name' => 'code',
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
        'startLine' => 19,
        'endLine' => 19,
        'startColumn' => 9,
        'endColumn' => 27,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'exponent' => 
      array (
        'declaringClassName' => 'Modules\\Shared\\Domain\\Money\\Currency',
        'implementingClassName' => 'Modules\\Shared\\Domain\\Money\\Currency',
        'name' => 'exponent',
        'modifiers' => 2049,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 20,
        'endLine' => 20,
        'startColumn' => 9,
        'endColumn' => 28,
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
          'code' => 
          array (
            'name' => 'code',
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
            'startLine' => 19,
            'endLine' => 19,
            'startColumn' => 9,
            'endColumn' => 27,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'exponent' => 
          array (
            'name' => 'exponent',
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 20,
            'endLine' => 20,
            'startColumn' => 9,
            'endColumn' => 28,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 18,
        'endLine' => 21,
        'startColumn' => 5,
        'endColumn' => 8,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'Modules\\Shared\\Domain\\Money',
        'declaringClassName' => 'Modules\\Shared\\Domain\\Money\\Currency',
        'implementingClassName' => 'Modules\\Shared\\Domain\\Money\\Currency',
        'currentClassName' => 'Modules\\Shared\\Domain\\Money\\Currency',
        'aliasName' => NULL,
      ),
      'of' => 
      array (
        'name' => 'of',
        'parameters' => 
        array (
          'code' => 
          array (
            'name' => 'code',
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
            'startLine' => 23,
            'endLine' => 23,
            'startColumn' => 31,
            'endColumn' => 42,
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
        'startLine' => 23,
        'endLine' => 32,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Modules\\Shared\\Domain\\Money',
        'declaringClassName' => 'Modules\\Shared\\Domain\\Money\\Currency',
        'implementingClassName' => 'Modules\\Shared\\Domain\\Money\\Currency',
        'currentClassName' => 'Modules\\Shared\\Domain\\Money\\Currency',
        'aliasName' => NULL,
      ),
      'vnd' => 
      array (
        'name' => 'vnd',
        'parameters' => 
        array (
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
        'startLine' => 34,
        'endLine' => 37,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Modules\\Shared\\Domain\\Money',
        'declaringClassName' => 'Modules\\Shared\\Domain\\Money\\Currency',
        'implementingClassName' => 'Modules\\Shared\\Domain\\Money\\Currency',
        'currentClassName' => 'Modules\\Shared\\Domain\\Money\\Currency',
        'aliasName' => NULL,
      ),
      'equals' => 
      array (
        'name' => 'equals',
        'parameters' => 
        array (
          'other' => 
          array (
            'name' => 'other',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'self',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 39,
            'endLine' => 39,
            'startColumn' => 28,
            'endColumn' => 38,
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
        'docComment' => NULL,
        'startLine' => 39,
        'endLine' => 42,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Shared\\Domain\\Money',
        'declaringClassName' => 'Modules\\Shared\\Domain\\Money\\Currency',
        'implementingClassName' => 'Modules\\Shared\\Domain\\Money\\Currency',
        'currentClassName' => 'Modules\\Shared\\Domain\\Money\\Currency',
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