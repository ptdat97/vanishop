<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Integration/Application/CanonicalPayloads.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Integration\Application\CanonicalPayloads
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-a1580672c51160923a326c1f7f0daaa92d231b3be56efdc709d9c9a19faacc8a',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Integration\\Application\\CanonicalPayloads',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Integration/Application/CanonicalPayloads.php',
      ),
    ),
    'namespace' => 'Modules\\Integration\\Application',
    'name' => 'Modules\\Integration\\Application\\CanonicalPayloads',
    'shortName' => 'CanonicalPayloads',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Payload canonical có version cho đối tác. Chỉ THÊM field trong cùng version (R20).
 *
 * @see docs/11-integration/integration-platform.md §4
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 17,
    'endLine' => 70,
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
      'ORDER_SCHEMA' => 
      array (
        'declaringClassName' => 'Modules\\Integration\\Application\\CanonicalPayloads',
        'implementingClassName' => 'Modules\\Integration\\Application\\CanonicalPayloads',
        'name' => 'ORDER_SCHEMA',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'vanishop.order.v1\'',
          'attributes' => 
          array (
            'startLine' => 19,
            'endLine' => 19,
            'startTokenPos' => 53,
            'startFilePos' => 482,
            'endTokenPos' => 53,
            'endFilePos' => 500,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 19,
        'endLine' => 19,
        'startColumn' => 5,
        'endColumn' => 52,
      ),
    ),
    'immediateProperties' => 
    array (
      'orders' => 
      array (
        'declaringClassName' => 'Modules\\Integration\\Application\\CanonicalPayloads',
        'implementingClassName' => 'Modules\\Integration\\Application\\CanonicalPayloads',
        'name' => 'orders',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Modules\\Ordering\\Contracts\\OrderReader',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 21,
        'endLine' => 21,
        'startColumn' => 33,
        'endColumn' => 68,
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
          'orders' => 
          array (
            'name' => 'orders',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Ordering\\Contracts\\OrderReader',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 21,
            'endLine' => 21,
            'startColumn' => 33,
            'endColumn' => 68,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 21,
        'endLine' => 21,
        'startColumn' => 5,
        'endColumn' => 72,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Integration\\Application',
        'declaringClassName' => 'Modules\\Integration\\Application\\CanonicalPayloads',
        'implementingClassName' => 'Modules\\Integration\\Application\\CanonicalPayloads',
        'currentClassName' => 'Modules\\Integration\\Application\\CanonicalPayloads',
        'aliasName' => NULL,
      ),
      'order' => 
      array (
        'name' => 'order',
        'parameters' => 
        array (
          'order' => 
          array (
            'name' => 'order',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Ordering\\Contracts\\Data\\OrderData',
                'isIdentifier' => false,
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
            'startColumn' => 27,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return array<string, mixed>
 */',
        'startLine' => 26,
        'endLine' => 33,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Integration\\Application',
        'declaringClassName' => 'Modules\\Integration\\Application\\CanonicalPayloads',
        'implementingClassName' => 'Modules\\Integration\\Application\\CanonicalPayloads',
        'currentClassName' => 'Modules\\Integration\\Application\\CanonicalPayloads',
        'aliasName' => NULL,
      ),
      'canonical' => 
      array (
        'name' => 'canonical',
        'parameters' => 
        array (
          'order' => 
          array (
            'name' => 'order',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Ordering\\Contracts\\Data\\OrderData',
                'isIdentifier' => false,
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
            'startColumn' => 32,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return array<string, mixed>
 */',
        'startLine' => 38,
        'endLine' => 69,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'Modules\\Integration\\Application',
        'declaringClassName' => 'Modules\\Integration\\Application\\CanonicalPayloads',
        'implementingClassName' => 'Modules\\Integration\\Application\\CanonicalPayloads',
        'currentClassName' => 'Modules\\Integration\\Application\\CanonicalPayloads',
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