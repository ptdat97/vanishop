<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Shared/Http/Middleware/AssignCorrelationId.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Shared\Http\Middleware\AssignCorrelationId
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-da2315a7368e94f50caf1704dddf1ddf67fcea0f772390495b72fea7c6090393',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Shared\\Http\\Middleware\\AssignCorrelationId',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Shared/Http/Middleware/AssignCorrelationId.php',
      ),
    ),
    'namespace' => 'Modules\\Shared\\Http\\Middleware',
    'name' => 'Modules\\Shared\\Http\\Middleware\\AssignCorrelationId',
    'shortName' => 'AssignCorrelationId',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Gắn correlation id cho mọi request; id đi vào log context và được truyền sang queued job.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 16,
    'endLine' => 34,
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
      'HEADER' => 
      array (
        'declaringClassName' => 'Modules\\Shared\\Http\\Middleware\\AssignCorrelationId',
        'implementingClassName' => 'Modules\\Shared\\Http\\Middleware\\AssignCorrelationId',
        'name' => 'HEADER',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'X-Correlation-Id\'',
          'attributes' => 
          array (
            'startLine' => 18,
            'endLine' => 18,
            'startTokenPos' => 58,
            'startFilePos' => 408,
            'endTokenPos' => 58,
            'endFilePos' => 425,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 18,
        'endLine' => 18,
        'startColumn' => 5,
        'endColumn' => 45,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'handle' => 
      array (
        'name' => 'handle',
        'parameters' => 
        array (
          'request' => 
          array (
            'name' => 'request',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Http\\Request',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 20,
            'endLine' => 20,
            'startColumn' => 28,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'next' => 
          array (
            'name' => 'next',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Closure',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 20,
            'endLine' => 20,
            'startColumn' => 46,
            'endColumn' => 58,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Symfony\\Component\\HttpFoundation\\Response',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 20,
        'endLine' => 33,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Shared\\Http\\Middleware',
        'declaringClassName' => 'Modules\\Shared\\Http\\Middleware\\AssignCorrelationId',
        'implementingClassName' => 'Modules\\Shared\\Http\\Middleware\\AssignCorrelationId',
        'currentClassName' => 'Modules\\Shared\\Http\\Middleware\\AssignCorrelationId',
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