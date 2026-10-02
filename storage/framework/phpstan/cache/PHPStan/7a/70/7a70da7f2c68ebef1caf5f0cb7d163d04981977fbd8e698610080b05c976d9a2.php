<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/app/Logging/ContextProcessor.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Logging\ContextProcessor
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-b56ea74772224afa359c63b35c2d91cf33596c1ae7d46f608e8d72a90c8705e3',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Logging\\ContextProcessor',
        'filename' => '/Users/dat/Ecommerce/vanishop/app/Logging/ContextProcessor.php',
      ),
    ),
    'namespace' => 'App\\Logging',
    'name' => 'App\\Logging\\ContextProcessor',
    'shortName' => 'ContextProcessor',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Đưa Laravel Context (correlation_id) vào mọi dòng log.
 *
 * `Context::add(\'correlation_id\', …)` trong AssignCorrelationId chỉ giữ trong bộ nhớ
 * của request/job; muốn truy vết được theo id thì id phải xuất hiện trong log.
 * Processor gắn vào channel qua `tap()`/`processors` trong config/logging.php.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 18,
    'endLine' => 38,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'Monolog\\Processor\\ProcessorInterface',
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
      'keys' => 
      array (
        'declaringClassName' => 'App\\Logging\\ContextProcessor',
        'implementingClassName' => 'App\\Logging\\ContextProcessor',
        'name' => 'keys',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'default' => 
        array (
          'code' => '[\'correlation_id\']',
          'attributes' => 
          array (
            'startLine' => 23,
            'endLine' => 23,
            'startTokenPos' => 62,
            'startFilePos' => 731,
            'endTokenPos' => 64,
            'endFilePos' => 748,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 23,
        'endLine' => 23,
        'startColumn' => 33,
        'endColumn' => 81,
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
          'keys' => 
          array (
            'name' => 'keys',
            'default' => 
            array (
              'code' => '[\'correlation_id\']',
              'attributes' => 
              array (
                'startLine' => 23,
                'endLine' => 23,
                'startTokenPos' => 62,
                'startFilePos' => 731,
                'endTokenPos' => 64,
                'endFilePos' => 748,
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 23,
            'endLine' => 23,
            'startColumn' => 33,
            'endColumn' => 81,
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
 * @param  list<string>  $keys  khoá trong Context cần đưa vào log
 */',
        'startLine' => 23,
        'endLine' => 23,
        'startColumn' => 5,
        'endColumn' => 85,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Logging',
        'declaringClassName' => 'App\\Logging\\ContextProcessor',
        'implementingClassName' => 'App\\Logging\\ContextProcessor',
        'currentClassName' => 'App\\Logging\\ContextProcessor',
        'aliasName' => NULL,
      ),
      '__invoke' => 
      array (
        'name' => '__invoke',
        'parameters' => 
        array (
          'record' => 
          array (
            'name' => 'record',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Monolog\\LogRecord',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 25,
            'endLine' => 25,
            'startColumn' => 30,
            'endColumn' => 46,
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
            'name' => 'Monolog\\LogRecord',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 25,
        'endLine' => 37,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Logging',
        'declaringClassName' => 'App\\Logging\\ContextProcessor',
        'implementingClassName' => 'App\\Logging\\ContextProcessor',
        'currentClassName' => 'App\\Logging\\ContextProcessor',
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