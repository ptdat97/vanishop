<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Integration/Persistence/Models/IntegrationClient.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Integration\Persistence\Models\IntegrationClient
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-1733e68944a1f67a1959542187435f32046ebc9df84edf11fd75f3cb2af4e996',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Integration\\Persistence\\Models\\IntegrationClient',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Integration/Persistence/Models/IntegrationClient.php',
      ),
    ),
    'namespace' => 'Modules\\Integration\\Persistence\\Models',
    'name' => 'Modules\\Integration\\Persistence\\Models\\IntegrationClient',
    'shortName' => 'IntegrationClient',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Danh tính một hệ thống ngoài gọi Integration API / nhận webhook (ERP, ODO, POS).
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string $status
 * @property list<string> $scopes
 * @property list<string>|null $ip_allowlist
 * @property int $rate_limit
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 21,
    'endLine' => 55,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Illuminate\\Database\\Eloquent\\Model',
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
      'fillable' => 
      array (
        'declaringClassName' => 'Modules\\Integration\\Persistence\\Models\\IntegrationClient',
        'implementingClassName' => 'Modules\\Integration\\Persistence\\Models\\IntegrationClient',
        'name' => 'fillable',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'code\', \'name\', \'status\', \'scopes\', \'ip_allowlist\', \'rate_limit\']',
          'attributes' => 
          array (
            'startLine' => 23,
            'endLine' => 23,
            'startTokenPos' => 45,
            'startFilePos' => 565,
            'endTokenPos' => 62,
            'endFilePos' => 630,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 23,
        'endLine' => 23,
        'startColumn' => 5,
        'endColumn' => 93,
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
      'casts' => 
      array (
        'name' => 'casts',
        'parameters' => 
        array (
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
        'docComment' => NULL,
        'startLine' => 25,
        'endLine' => 28,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Modules\\Integration\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Integration\\Persistence\\Models\\IntegrationClient',
        'implementingClassName' => 'Modules\\Integration\\Persistence\\Models\\IntegrationClient',
        'currentClassName' => 'Modules\\Integration\\Persistence\\Models\\IntegrationClient',
        'aliasName' => NULL,
      ),
      'keys' => 
      array (
        'name' => 'keys',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Illuminate\\Database\\Eloquent\\Relations\\HasMany',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return HasMany<ClientKey, $this>
 */',
        'startLine' => 33,
        'endLine' => 36,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Integration\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Integration\\Persistence\\Models\\IntegrationClient',
        'implementingClassName' => 'Modules\\Integration\\Persistence\\Models\\IntegrationClient',
        'currentClassName' => 'Modules\\Integration\\Persistence\\Models\\IntegrationClient',
        'aliasName' => NULL,
      ),
      'subscriptions' => 
      array (
        'name' => 'subscriptions',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Illuminate\\Database\\Eloquent\\Relations\\HasMany',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return HasMany<WebhookSubscription, $this>
 */',
        'startLine' => 41,
        'endLine' => 44,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Integration\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Integration\\Persistence\\Models\\IntegrationClient',
        'implementingClassName' => 'Modules\\Integration\\Persistence\\Models\\IntegrationClient',
        'currentClassName' => 'Modules\\Integration\\Persistence\\Models\\IntegrationClient',
        'aliasName' => NULL,
      ),
      'isActive' => 
      array (
        'name' => 'isActive',
        'parameters' => 
        array (
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
        'startLine' => 46,
        'endLine' => 49,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Integration\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Integration\\Persistence\\Models\\IntegrationClient',
        'implementingClassName' => 'Modules\\Integration\\Persistence\\Models\\IntegrationClient',
        'currentClassName' => 'Modules\\Integration\\Persistence\\Models\\IntegrationClient',
        'aliasName' => NULL,
      ),
      'hasScope' => 
      array (
        'name' => 'hasScope',
        'parameters' => 
        array (
          'scope' => 
          array (
            'name' => 'scope',
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
            'startLine' => 51,
            'endLine' => 51,
            'startColumn' => 30,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 51,
        'endLine' => 54,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Integration\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Integration\\Persistence\\Models\\IntegrationClient',
        'implementingClassName' => 'Modules\\Integration\\Persistence\\Models\\IntegrationClient',
        'currentClassName' => 'Modules\\Integration\\Persistence\\Models\\IntegrationClient',
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