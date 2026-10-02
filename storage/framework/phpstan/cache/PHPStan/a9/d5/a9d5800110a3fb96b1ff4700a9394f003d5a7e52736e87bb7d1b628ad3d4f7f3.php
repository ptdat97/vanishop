<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Integration/Http/Controllers/Admin/HealthController.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Integration\Http\Controllers\Admin\HealthController
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-7466a8f7912809f0927a9a15920d916532d8c6ef278d50ca2a9d2d097e805e6f',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Integration\\Http\\Controllers\\Admin\\HealthController',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Integration/Http/Controllers/Admin/HealthController.php',
      ),
    ),
    'namespace' => 'Modules\\Integration\\Http\\Controllers\\Admin',
    'name' => 'Modules\\Integration\\Http\\Controllers\\Admin\\HealthController',
    'shortName' => 'HealthController',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Integration Health — cấp Owner: backlog, message lỗi (payload đã che PII), replay, client & webhook.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 23,
    'endLine' => 76,
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
    ),
    'immediateMethods' => 
    array (
      'index' => 
      array (
        'name' => 'index',
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
            'startLine' => 25,
            'endLine' => 25,
            'startColumn' => 27,
            'endColumn' => 42,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'queries' => 
          array (
            'name' => 'queries',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Integration\\Application\\HealthQueries',
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
            'startColumn' => 45,
            'endColumn' => 66,
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
            'name' => 'Inertia\\Response',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 25,
        'endLine' => 44,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Integration\\Http\\Controllers\\Admin',
        'declaringClassName' => 'Modules\\Integration\\Http\\Controllers\\Admin\\HealthController',
        'implementingClassName' => 'Modules\\Integration\\Http\\Controllers\\Admin\\HealthController',
        'currentClassName' => 'Modules\\Integration\\Http\\Controllers\\Admin\\HealthController',
        'aliasName' => NULL,
      ),
      'replay' => 
      array (
        'name' => 'replay',
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
            'startLine' => 46,
            'endLine' => 46,
            'startColumn' => 28,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'replay' => 
          array (
            'name' => 'replay',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Integration\\Application\\ReplayService',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 46,
            'endLine' => 46,
            'startColumn' => 46,
            'endColumn' => 66,
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
            'name' => 'Illuminate\\Http\\RedirectResponse',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 46,
        'endLine' => 66,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Integration\\Http\\Controllers\\Admin',
        'declaringClassName' => 'Modules\\Integration\\Http\\Controllers\\Admin\\HealthController',
        'implementingClassName' => 'Modules\\Integration\\Http\\Controllers\\Admin\\HealthController',
        'currentClassName' => 'Modules\\Integration\\Http\\Controllers\\Admin\\HealthController',
        'aliasName' => NULL,
      ),
      'resumeSubscription' => 
      array (
        'name' => 'resumeSubscription',
        'parameters' => 
        array (
          'subscription' => 
          array (
            'name' => 'subscription',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Integration\\Persistence\\Models\\WebhookSubscription',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 68,
            'endLine' => 68,
            'startColumn' => 40,
            'endColumn' => 72,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'audit' => 
          array (
            'name' => 'audit',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Identity\\Contracts\\AuditLogger',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 68,
            'endLine' => 68,
            'startColumn' => 75,
            'endColumn' => 92,
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
            'name' => 'Illuminate\\Http\\RedirectResponse',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 68,
        'endLine' => 75,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Integration\\Http\\Controllers\\Admin',
        'declaringClassName' => 'Modules\\Integration\\Http\\Controllers\\Admin\\HealthController',
        'implementingClassName' => 'Modules\\Integration\\Http\\Controllers\\Admin\\HealthController',
        'currentClassName' => 'Modules\\Integration\\Http\\Controllers\\Admin\\HealthController',
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