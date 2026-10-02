<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Integration/Persistence/Models/WebhookSubscription.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Integration\Persistence\Models\WebhookSubscription
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-a125485d95bb6a708cab9ca85e5d52b0e483365b61e2a86e714e361aab878c31',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Integration\\Persistence\\Models\\WebhookSubscription',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Integration/Persistence/Models/WebhookSubscription.php',
      ),
    ),
    'namespace' => 'Modules\\Integration\\Persistence\\Models',
    'name' => 'Modules\\Integration\\Persistence\\Models\\WebhookSubscription',
    'shortName' => 'WebhookSubscription',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * @property int $id
 * @property int $client_id
 * @property string $url
 * @property list<string> $event_types
 * @property string $secret
 * @property string $status
 * @property Carbon|null $failing_since
 * @property-read IntegrationClient $client
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 21,
    'endLine' => 62,
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
      'TARGET_PREFIX' => 
      array (
        'declaringClassName' => 'Modules\\Integration\\Persistence\\Models\\WebhookSubscription',
        'implementingClassName' => 'Modules\\Integration\\Persistence\\Models\\WebhookSubscription',
        'name' => 'TARGET_PREFIX',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'webhook:\'',
          'attributes' => 
          array (
            'startLine' => 23,
            'endLine' => 23,
            'startTokenPos' => 52,
            'startFilePos' => 552,
            'endTokenPos' => 52,
            'endFilePos' => 561,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 23,
        'endLine' => 23,
        'startColumn' => 5,
        'endColumn' => 44,
      ),
    ),
    'immediateProperties' => 
    array (
      'table' => 
      array (
        'declaringClassName' => 'Modules\\Integration\\Persistence\\Models\\WebhookSubscription',
        'implementingClassName' => 'Modules\\Integration\\Persistence\\Models\\WebhookSubscription',
        'name' => 'table',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'integration_webhook_subscriptions\'',
          'attributes' => 
          array (
            'startLine' => 25,
            'endLine' => 25,
            'startTokenPos' => 61,
            'startFilePos' => 588,
            'endTokenPos' => 61,
            'endFilePos' => 622,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 25,
        'endLine' => 25,
        'startColumn' => 5,
        'endColumn' => 59,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'fillable' => 
      array (
        'declaringClassName' => 'Modules\\Integration\\Persistence\\Models\\WebhookSubscription',
        'implementingClassName' => 'Modules\\Integration\\Persistence\\Models\\WebhookSubscription',
        'name' => 'fillable',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'client_id\', \'url\', \'event_types\', \'secret\', \'status\', \'failing_since\']',
          'attributes' => 
          array (
            'startLine' => 27,
            'endLine' => 27,
            'startTokenPos' => 70,
            'startFilePos' => 652,
            'endTokenPos' => 87,
            'endFilePos' => 723,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 27,
        'endLine' => 27,
        'startColumn' => 5,
        'endColumn' => 99,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'hidden' => 
      array (
        'declaringClassName' => 'Modules\\Integration\\Persistence\\Models\\WebhookSubscription',
        'implementingClassName' => 'Modules\\Integration\\Persistence\\Models\\WebhookSubscription',
        'name' => 'hidden',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'secret\']',
          'attributes' => 
          array (
            'startLine' => 29,
            'endLine' => 29,
            'startTokenPos' => 96,
            'startFilePos' => 751,
            'endTokenPos' => 98,
            'endFilePos' => 760,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 29,
        'endLine' => 29,
        'startColumn' => 5,
        'endColumn' => 35,
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
        'startLine' => 31,
        'endLine' => 34,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Modules\\Integration\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Integration\\Persistence\\Models\\WebhookSubscription',
        'implementingClassName' => 'Modules\\Integration\\Persistence\\Models\\WebhookSubscription',
        'currentClassName' => 'Modules\\Integration\\Persistence\\Models\\WebhookSubscription',
        'aliasName' => NULL,
      ),
      'client' => 
      array (
        'name' => 'client',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Illuminate\\Database\\Eloquent\\Relations\\BelongsTo',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return BelongsTo<IntegrationClient, $this>
 */',
        'startLine' => 39,
        'endLine' => 42,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Integration\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Integration\\Persistence\\Models\\WebhookSubscription',
        'implementingClassName' => 'Modules\\Integration\\Persistence\\Models\\WebhookSubscription',
        'currentClassName' => 'Modules\\Integration\\Persistence\\Models\\WebhookSubscription',
        'aliasName' => NULL,
      ),
      'target' => 
      array (
        'name' => 'target',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 44,
        'endLine' => 47,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Integration\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Integration\\Persistence\\Models\\WebhookSubscription',
        'implementingClassName' => 'Modules\\Integration\\Persistence\\Models\\WebhookSubscription',
        'currentClassName' => 'Modules\\Integration\\Persistence\\Models\\WebhookSubscription',
        'aliasName' => NULL,
      ),
      'wants' => 
      array (
        'name' => 'wants',
        'parameters' => 
        array (
          'eventType' => 
          array (
            'name' => 'eventType',
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
            'startLine' => 52,
            'endLine' => 52,
            'startColumn' => 27,
            'endColumn' => 43,
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
        'docComment' => '/**
 * `*` = mọi event; `order.*` = mọi event của aggregate order; còn lại so khớp chính xác.
 */',
        'startLine' => 52,
        'endLine' => 61,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Integration\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Integration\\Persistence\\Models\\WebhookSubscription',
        'implementingClassName' => 'Modules\\Integration\\Persistence\\Models\\WebhookSubscription',
        'currentClassName' => 'Modules\\Integration\\Persistence\\Models\\WebhookSubscription',
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