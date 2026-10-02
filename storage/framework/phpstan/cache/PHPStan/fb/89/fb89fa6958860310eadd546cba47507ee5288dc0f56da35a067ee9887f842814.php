<?php declare(strict_types = 1);

// osfsl-/Users/dat/Ecommerce/vanishop/vendor/composer/../tormjens/eventy/src/Events.php-PHPStan\BetterReflection\Reflection\ReflectionClass-TorMorten\Eventy\Events
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-f0e5d22add8d677c3567bc7e7d5ede61a3aef649cf296146555127cf88e1f1ee-8.4.25-6.73.0.5',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'TorMorten\\Eventy\\Events',
        'filename' => '/Users/dat/Ecommerce/vanishop/vendor/composer/../tormjens/eventy/src/Events.php',
      ),
    ),
    'namespace' => 'TorMorten\\Eventy',
    'name' => 'TorMorten\\Eventy\\Events',
    'shortName' => 'Events',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 5,
    'endLine' => 154,
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
    ),
    'immediateProperties' => 
    array (
      'action' => 
      array (
        'declaringClassName' => 'TorMorten\\Eventy\\Events',
        'implementingClassName' => 'TorMorten\\Eventy\\Events',
        'name' => 'action',
        'modifiers' => 2,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * Holds all registered actions.
 *
 * @var TorMorten\\Events\\Action
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 12,
        'endLine' => 12,
        'startColumn' => 5,
        'endColumn' => 22,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'filter' => 
      array (
        'declaringClassName' => 'TorMorten\\Eventy\\Events',
        'implementingClassName' => 'TorMorten\\Eventy\\Events',
        'name' => 'filter',
        'modifiers' => 2,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * Holds all registered filters.
 *
 * @var TorMorten\\Events\\Filter
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 19,
        'endLine' => 19,
        'startColumn' => 5,
        'endColumn' => 22,
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
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Construct the class.
 */',
        'startLine' => 24,
        'endLine' => 28,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'TorMorten\\Eventy',
        'declaringClassName' => 'TorMorten\\Eventy\\Events',
        'implementingClassName' => 'TorMorten\\Eventy\\Events',
        'currentClassName' => 'TorMorten\\Eventy\\Events',
        'aliasName' => NULL,
      ),
      'getAction' => 
      array (
        'name' => 'getAction',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the action instance.
 *
 * @return TorMorten\\Events\\Action
 */',
        'startLine' => 35,
        'endLine' => 38,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'TorMorten\\Eventy',
        'declaringClassName' => 'TorMorten\\Eventy\\Events',
        'implementingClassName' => 'TorMorten\\Eventy\\Events',
        'currentClassName' => 'TorMorten\\Eventy\\Events',
        'aliasName' => NULL,
      ),
      'getFilter' => 
      array (
        'name' => 'getFilter',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the action instance.
 *
 * @return TorMorten\\Events\\Filter
 */',
        'startLine' => 45,
        'endLine' => 48,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'TorMorten\\Eventy',
        'declaringClassName' => 'TorMorten\\Eventy\\Events',
        'implementingClassName' => 'TorMorten\\Eventy\\Events',
        'currentClassName' => 'TorMorten\\Eventy\\Events',
        'aliasName' => NULL,
      ),
      'addAction' => 
      array (
        'name' => 'addAction',
        'parameters' => 
        array (
          'hook' => 
          array (
            'name' => 'hook',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 58,
            'endLine' => 58,
            'startColumn' => 31,
            'endColumn' => 35,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'callback' => 
          array (
            'name' => 'callback',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 58,
            'endLine' => 58,
            'startColumn' => 38,
            'endColumn' => 46,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'priority' => 
          array (
            'name' => 'priority',
            'default' => 
            array (
              'code' => '20',
              'attributes' => 
              array (
                'startLine' => 58,
                'endLine' => 58,
                'startTokenPos' => 127,
                'startFilePos' => 1112,
                'endTokenPos' => 127,
                'endFilePos' => 1113,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 58,
            'endLine' => 58,
            'startColumn' => 49,
            'endColumn' => 62,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
          'arguments' => 
          array (
            'name' => 'arguments',
            'default' => 
            array (
              'code' => '1',
              'attributes' => 
              array (
                'startLine' => 58,
                'endLine' => 58,
                'startTokenPos' => 134,
                'startFilePos' => 1129,
                'endTokenPos' => 134,
                'endFilePos' => 1129,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 58,
            'endLine' => 58,
            'startColumn' => 65,
            'endColumn' => 78,
            'parameterIndex' => 3,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Add an action.
 *
 * @param string $hook      Hook name
 * @param mixed  $callback  Function to execute
 * @param int    $priority  Priority of the action
 * @param int    $arguments Number of arguments to accept
 */',
        'startLine' => 58,
        'endLine' => 61,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'TorMorten\\Eventy',
        'declaringClassName' => 'TorMorten\\Eventy\\Events',
        'implementingClassName' => 'TorMorten\\Eventy\\Events',
        'currentClassName' => 'TorMorten\\Eventy\\Events',
        'aliasName' => NULL,
      ),
      'removeAction' => 
      array (
        'name' => 'removeAction',
        'parameters' => 
        array (
          'hook' => 
          array (
            'name' => 'hook',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 70,
            'endLine' => 70,
            'startColumn' => 34,
            'endColumn' => 38,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'callback' => 
          array (
            'name' => 'callback',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 70,
            'endLine' => 70,
            'startColumn' => 41,
            'endColumn' => 49,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'priority' => 
          array (
            'name' => 'priority',
            'default' => 
            array (
              'code' => '20',
              'attributes' => 
              array (
                'startLine' => 70,
                'endLine' => 70,
                'startTokenPos' => 178,
                'startFilePos' => 1474,
                'endTokenPos' => 178,
                'endFilePos' => 1475,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 70,
            'endLine' => 70,
            'startColumn' => 52,
            'endColumn' => 65,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Remove an action.
 *
 * @param string $hook     Hook name
 * @param mixed  $callback Function to execute
 * @param int    $priority Priority of the action
 */',
        'startLine' => 70,
        'endLine' => 73,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'TorMorten\\Eventy',
        'declaringClassName' => 'TorMorten\\Eventy\\Events',
        'implementingClassName' => 'TorMorten\\Eventy\\Events',
        'currentClassName' => 'TorMorten\\Eventy\\Events',
        'aliasName' => NULL,
      ),
      'removeAllActions' => 
      array (
        'name' => 'removeAllActions',
        'parameters' => 
        array (
          'hook' => 
          array (
            'name' => 'hook',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 80,
                'endLine' => 80,
                'startTokenPos' => 213,
                'startFilePos' => 1683,
                'endTokenPos' => 213,
                'endFilePos' => 1686,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 80,
            'endLine' => 80,
            'startColumn' => 38,
            'endColumn' => 49,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Remove all actions.
 *
 * @param string $hook Hook name
 */',
        'startLine' => 80,
        'endLine' => 83,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'TorMorten\\Eventy',
        'declaringClassName' => 'TorMorten\\Eventy\\Events',
        'implementingClassName' => 'TorMorten\\Eventy\\Events',
        'currentClassName' => 'TorMorten\\Eventy\\Events',
        'aliasName' => NULL,
      ),
      'addFilter' => 
      array (
        'name' => 'addFilter',
        'parameters' => 
        array (
          'hook' => 
          array (
            'name' => 'hook',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 93,
            'endLine' => 93,
            'startColumn' => 31,
            'endColumn' => 35,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'callback' => 
          array (
            'name' => 'callback',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 93,
            'endLine' => 93,
            'startColumn' => 38,
            'endColumn' => 46,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'priority' => 
          array (
            'name' => 'priority',
            'default' => 
            array (
              'code' => '20',
              'attributes' => 
              array (
                'startLine' => 93,
                'endLine' => 93,
                'startTokenPos' => 248,
                'startFilePos' => 2059,
                'endTokenPos' => 248,
                'endFilePos' => 2060,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 93,
            'endLine' => 93,
            'startColumn' => 49,
            'endColumn' => 62,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
          'arguments' => 
          array (
            'name' => 'arguments',
            'default' => 
            array (
              'code' => '1',
              'attributes' => 
              array (
                'startLine' => 93,
                'endLine' => 93,
                'startTokenPos' => 255,
                'startFilePos' => 2076,
                'endTokenPos' => 255,
                'endFilePos' => 2076,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 93,
            'endLine' => 93,
            'startColumn' => 65,
            'endColumn' => 78,
            'parameterIndex' => 3,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Adds a filter.
 *
 * @param string $hook      Hook name
 * @param mixed  $callback  Function to execute
 * @param int    $priority  Priority of the action
 * @param int    $arguments Number of arguments to accept
 */',
        'startLine' => 93,
        'endLine' => 96,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'TorMorten\\Eventy',
        'declaringClassName' => 'TorMorten\\Eventy\\Events',
        'implementingClassName' => 'TorMorten\\Eventy\\Events',
        'currentClassName' => 'TorMorten\\Eventy\\Events',
        'aliasName' => NULL,
      ),
      'removeFilter' => 
      array (
        'name' => 'removeFilter',
        'parameters' => 
        array (
          'hook' => 
          array (
            'name' => 'hook',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 105,
            'endLine' => 105,
            'startColumn' => 34,
            'endColumn' => 38,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'callback' => 
          array (
            'name' => 'callback',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 105,
            'endLine' => 105,
            'startColumn' => 41,
            'endColumn' => 49,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'priority' => 
          array (
            'name' => 'priority',
            'default' => 
            array (
              'code' => '20',
              'attributes' => 
              array (
                'startLine' => 105,
                'endLine' => 105,
                'startTokenPos' => 299,
                'startFilePos' => 2420,
                'endTokenPos' => 299,
                'endFilePos' => 2421,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 105,
            'endLine' => 105,
            'startColumn' => 52,
            'endColumn' => 65,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Remove a filter.
 *
 * @param string $hook     Hook name
 * @param mixed  $callback Function to execute
 * @param int    $priority Priority of the action
 */',
        'startLine' => 105,
        'endLine' => 108,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'TorMorten\\Eventy',
        'declaringClassName' => 'TorMorten\\Eventy\\Events',
        'implementingClassName' => 'TorMorten\\Eventy\\Events',
        'currentClassName' => 'TorMorten\\Eventy\\Events',
        'aliasName' => NULL,
      ),
      'removeAllFilters' => 
      array (
        'name' => 'removeAllFilters',
        'parameters' => 
        array (
          'hook' => 
          array (
            'name' => 'hook',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 115,
                'endLine' => 115,
                'startTokenPos' => 334,
                'startFilePos' => 2629,
                'endTokenPos' => 334,
                'endFilePos' => 2632,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 115,
            'endLine' => 115,
            'startColumn' => 38,
            'endColumn' => 49,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Remove all filters.
 *
 * @param string $hook Hook name
 */',
        'startLine' => 115,
        'endLine' => 118,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'TorMorten\\Eventy',
        'declaringClassName' => 'TorMorten\\Eventy\\Events',
        'implementingClassName' => 'TorMorten\\Eventy\\Events',
        'currentClassName' => 'TorMorten\\Eventy\\Events',
        'aliasName' => NULL,
      ),
      'action' => 
      array (
        'name' => 'action',
        'parameters' => 
        array (
          'action' => 
          array (
            'name' => 'action',
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
            'startLine' => 132,
            'endLine' => 132,
            'startColumn' => 28,
            'endColumn' => 41,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'parameters' => 
          array (
            'name' => 'parameters',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => true,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 132,
            'endLine' => 132,
            'startColumn' => 44,
            'endColumn' => 57,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Set a new action.
 *
 * Actions never return anything. It is merely a way of executing code at a specific time in your code.
 *
 * You can add as many parameters as you\'d like.
 *
 * @param string $action        Name of hook
 * @param mixed  ...$parameters
 *
 * @return void
 */',
        'startLine' => 132,
        'endLine' => 135,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => true,
        'modifiers' => 1,
        'namespace' => 'TorMorten\\Eventy',
        'declaringClassName' => 'TorMorten\\Eventy\\Events',
        'implementingClassName' => 'TorMorten\\Eventy\\Events',
        'currentClassName' => 'TorMorten\\Eventy\\Events',
        'aliasName' => NULL,
      ),
      'filter' => 
      array (
        'name' => 'filter',
        'parameters' => 
        array (
          'action' => 
          array (
            'name' => 'action',
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
            'startLine' => 150,
            'endLine' => 150,
            'startColumn' => 28,
            'endColumn' => 41,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'value' => 
          array (
            'name' => 'value',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 150,
            'endLine' => 150,
            'startColumn' => 44,
            'endColumn' => 49,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'parameters' => 
          array (
            'name' => 'parameters',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => true,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 150,
            'endLine' => 150,
            'startColumn' => 52,
            'endColumn' => 65,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Set a new filter.
 *
 * Filters should always return something. The first parameter will always be the default value.
 *
 * You can add as many parameters as you\'d like.
 *
 * @param string $action        Name of hook
 * @param mixed  $value         The original filter value
 * @param mixed  ...$parameters
 *
 * @return mixed
 */',
        'startLine' => 150,
        'endLine' => 153,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => true,
        'modifiers' => 1,
        'namespace' => 'TorMorten\\Eventy',
        'declaringClassName' => 'TorMorten\\Eventy\\Events',
        'implementingClassName' => 'TorMorten\\Eventy\\Events',
        'currentClassName' => 'TorMorten\\Eventy\\Events',
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