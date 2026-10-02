<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Integration/Http/Middleware/AuthenticateIntegrationClient.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Integration\Http\Middleware\AuthenticateIntegrationClient
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-e6a8f36d2b1aaa07200fc6e6a4581de41f0456fb4987243284bc67f91a1f5f2c',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Integration\\Http\\Middleware\\AuthenticateIntegrationClient',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Integration/Http/Middleware/AuthenticateIntegrationClient.php',
      ),
    ),
    'namespace' => 'Modules\\Integration\\Http\\Middleware',
    'name' => 'Modules\\Integration\\Http\\Middleware\\AuthenticateIntegrationClient',
    'shortName' => 'AuthenticateIntegrationClient',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Integration API: `X-Vani-Key-Id` + `X-Vani-Signature: t=<unix>,v1=<hmac>` ký trên METHOD.path?query.body.
 * Thành công → CurrentContext là actor `integration`.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 24,
    'endLine' => 63,
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
      'ATTRIBUTE' => 
      array (
        'declaringClassName' => 'Modules\\Integration\\Http\\Middleware\\AuthenticateIntegrationClient',
        'implementingClassName' => 'Modules\\Integration\\Http\\Middleware\\AuthenticateIntegrationClient',
        'name' => 'ATTRIBUTE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'integration_client\'',
          'attributes' => 
          array (
            'startLine' => 26,
            'endLine' => 26,
            'startTokenPos' => 93,
            'startFilePos' => 824,
            'endTokenPos' => 93,
            'endFilePos' => 843,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 26,
        'endLine' => 26,
        'startColumn' => 5,
        'endColumn' => 50,
      ),
    ),
    'immediateProperties' => 
    array (
      'context' => 
      array (
        'declaringClassName' => 'Modules\\Integration\\Http\\Middleware\\AuthenticateIntegrationClient',
        'implementingClassName' => 'Modules\\Integration\\Http\\Middleware\\AuthenticateIntegrationClient',
        'name' => 'context',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Modules\\Shared\\Context\\CurrentContext',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 28,
        'endLine' => 28,
        'startColumn' => 33,
        'endColumn' => 72,
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
          'context' => 
          array (
            'name' => 'context',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Shared\\Context\\CurrentContext',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 28,
            'endLine' => 28,
            'startColumn' => 33,
            'endColumn' => 72,
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
        'startLine' => 28,
        'endLine' => 28,
        'startColumn' => 5,
        'endColumn' => 76,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Integration\\Http\\Middleware',
        'declaringClassName' => 'Modules\\Integration\\Http\\Middleware\\AuthenticateIntegrationClient',
        'implementingClassName' => 'Modules\\Integration\\Http\\Middleware\\AuthenticateIntegrationClient',
        'currentClassName' => 'Modules\\Integration\\Http\\Middleware\\AuthenticateIntegrationClient',
        'aliasName' => NULL,
      ),
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
            'startLine' => 30,
            'endLine' => 30,
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
            'startLine' => 30,
            'endLine' => 30,
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
        'startLine' => 30,
        'endLine' => 62,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Integration\\Http\\Middleware',
        'declaringClassName' => 'Modules\\Integration\\Http\\Middleware\\AuthenticateIntegrationClient',
        'implementingClassName' => 'Modules\\Integration\\Http\\Middleware\\AuthenticateIntegrationClient',
        'currentClassName' => 'Modules\\Integration\\Http\\Middleware\\AuthenticateIntegrationClient',
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