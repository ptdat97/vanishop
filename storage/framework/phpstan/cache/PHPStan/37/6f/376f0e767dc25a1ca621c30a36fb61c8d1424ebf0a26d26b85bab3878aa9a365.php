<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Notification/Persistence/Models/NotificationLog.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Notification\Persistence\Models\NotificationLog
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-455d4f909b33e3e8472d901981adf88a10479c3f3ab8b14eced67f56571f81bd',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Notification\\Persistence\\Models\\NotificationLog',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Notification/Persistence/Models/NotificationLog.php',
      ),
    ),
    'namespace' => 'Modules\\Notification\\Persistence\\Models',
    'name' => 'Modules\\Notification\\Persistence\\Models\\NotificationLog',
    'shortName' => 'NotificationLog',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * @property int $id
 * @property string $idempotency_key
 * @property string $type
 * @property string $category
 * @property string $channel
 * @property int|null $template_id
 * @property int|null $customer_id
 * @property string $recipient
 * @property string|null $subject
 * @property string|null $body
 * @property array<string, mixed>|null $meta
 * @property string $status
 * @property int $attempts
 * @property string|null $provider_message_id
 * @property string|null $error
 * @property string|null $correlation_id
 * @property Carbon $created_at
 * @property Carbon|null $sent_at
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 30,
    'endLine' => 48,
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
      'UPDATED_AT' => 
      array (
        'declaringClassName' => 'Modules\\Notification\\Persistence\\Models\\NotificationLog',
        'implementingClassName' => 'Modules\\Notification\\Persistence\\Models\\NotificationLog',
        'name' => 'UPDATED_AT',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => 'null',
          'attributes' => 
          array (
            'startLine' => 32,
            'endLine' => 32,
            'startTokenPos' => 47,
            'startFilePos' => 833,
            'endTokenPos' => 47,
            'endFilePos' => 836,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 32,
        'endLine' => 32,
        'startColumn' => 5,
        'endColumn' => 35,
      ),
      'QUEUED' => 
      array (
        'declaringClassName' => 'Modules\\Notification\\Persistence\\Models\\NotificationLog',
        'implementingClassName' => 'Modules\\Notification\\Persistence\\Models\\NotificationLog',
        'name' => 'QUEUED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'queued\'',
          'attributes' => 
          array (
            'startLine' => 34,
            'endLine' => 34,
            'startTokenPos' => 58,
            'startFilePos' => 866,
            'endTokenPos' => 58,
            'endFilePos' => 873,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 34,
        'endLine' => 34,
        'startColumn' => 5,
        'endColumn' => 35,
      ),
      'SENT' => 
      array (
        'declaringClassName' => 'Modules\\Notification\\Persistence\\Models\\NotificationLog',
        'implementingClassName' => 'Modules\\Notification\\Persistence\\Models\\NotificationLog',
        'name' => 'SENT',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'sent\'',
          'attributes' => 
          array (
            'startLine' => 36,
            'endLine' => 36,
            'startTokenPos' => 69,
            'startFilePos' => 901,
            'endTokenPos' => 69,
            'endFilePos' => 906,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 36,
        'endLine' => 36,
        'startColumn' => 5,
        'endColumn' => 31,
      ),
      'FAILED' => 
      array (
        'declaringClassName' => 'Modules\\Notification\\Persistence\\Models\\NotificationLog',
        'implementingClassName' => 'Modules\\Notification\\Persistence\\Models\\NotificationLog',
        'name' => 'FAILED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'failed\'',
          'attributes' => 
          array (
            'startLine' => 38,
            'endLine' => 38,
            'startTokenPos' => 80,
            'startFilePos' => 936,
            'endTokenPos' => 80,
            'endFilePos' => 943,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 38,
        'endLine' => 38,
        'startColumn' => 5,
        'endColumn' => 35,
      ),
      'SKIPPED' => 
      array (
        'declaringClassName' => 'Modules\\Notification\\Persistence\\Models\\NotificationLog',
        'implementingClassName' => 'Modules\\Notification\\Persistence\\Models\\NotificationLog',
        'name' => 'SKIPPED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'skipped\'',
          'attributes' => 
          array (
            'startLine' => 40,
            'endLine' => 40,
            'startTokenPos' => 91,
            'startFilePos' => 974,
            'endTokenPos' => 91,
            'endFilePos' => 982,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 40,
        'endLine' => 40,
        'startColumn' => 5,
        'endColumn' => 37,
      ),
    ),
    'immediateProperties' => 
    array (
      'guarded' => 
      array (
        'declaringClassName' => 'Modules\\Notification\\Persistence\\Models\\NotificationLog',
        'implementingClassName' => 'Modules\\Notification\\Persistence\\Models\\NotificationLog',
        'name' => 'guarded',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[]',
          'attributes' => 
          array (
            'startLine' => 42,
            'endLine' => 42,
            'startTokenPos' => 100,
            'startFilePos' => 1011,
            'endTokenPos' => 101,
            'endFilePos' => 1012,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 42,
        'endLine' => 42,
        'startColumn' => 5,
        'endColumn' => 28,
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
        'startLine' => 44,
        'endLine' => 47,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Modules\\Notification\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Notification\\Persistence\\Models\\NotificationLog',
        'implementingClassName' => 'Modules\\Notification\\Persistence\\Models\\NotificationLog',
        'currentClassName' => 'Modules\\Notification\\Persistence\\Models\\NotificationLog',
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