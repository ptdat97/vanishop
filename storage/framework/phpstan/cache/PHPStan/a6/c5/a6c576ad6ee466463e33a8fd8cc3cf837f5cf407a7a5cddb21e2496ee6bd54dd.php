<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Integration/Application/Delivery/WebhookSender.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Integration\Application\Delivery\WebhookSender
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-3c33a294cc4cb073aee3779c9b73664602668d0fad5ef8dd2c1fa02e720f1184',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Integration\\Application\\Delivery\\WebhookSender',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Integration/Application/Delivery/WebhookSender.php',
      ),
    ),
    'namespace' => 'Modules\\Integration\\Application\\Delivery',
    'name' => 'Modules\\Integration\\Application\\Delivery\\WebhookSender',
    'shortName' => 'WebhookSender',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Gửi webhook cho đối tác: envelope JSON, ký HMAC, `Idempotency-Key = message_id`. Đối tác phải trả 2xx trong 10s.
 * Lỗi liên tục quá `pause_after_hours` → subscription `paused`.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 19,
    'endLine' => 80,
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
      'pauseAfterHours' => 
      array (
        'declaringClassName' => 'Modules\\Integration\\Application\\Delivery\\WebhookSender',
        'implementingClassName' => 'Modules\\Integration\\Application\\Delivery\\WebhookSender',
        'name' => 'pauseAfterHours',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'default' => 
        array (
          'code' => '24',
          'attributes' => 
          array (
            'startLine' => 21,
            'endLine' => 21,
            'startTokenPos' => 76,
            'startFilePos' => 742,
            'endTokenPos' => 76,
            'endFilePos' => 743,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 21,
        'endLine' => 21,
        'startColumn' => 33,
        'endColumn' => 74,
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
          'pauseAfterHours' => 
          array (
            'name' => 'pauseAfterHours',
            'default' => 
            array (
              'code' => '24',
              'attributes' => 
              array (
                'startLine' => 21,
                'endLine' => 21,
                'startTokenPos' => 76,
                'startFilePos' => 742,
                'endTokenPos' => 76,
                'endFilePos' => 743,
              ),
            ),
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
            'startLine' => 21,
            'endLine' => 21,
            'startColumn' => 33,
            'endColumn' => 74,
            'parameterIndex' => 0,
            'isOptional' => true,
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
        'endColumn' => 78,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Integration\\Application\\Delivery',
        'declaringClassName' => 'Modules\\Integration\\Application\\Delivery\\WebhookSender',
        'implementingClassName' => 'Modules\\Integration\\Application\\Delivery\\WebhookSender',
        'currentClassName' => 'Modules\\Integration\\Application\\Delivery\\WebhookSender',
        'aliasName' => NULL,
      ),
      'send' => 
      array (
        'name' => 'send',
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
            'startLine' => 23,
            'endLine' => 23,
            'startColumn' => 26,
            'endColumn' => 58,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'record' => 
          array (
            'name' => 'record',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Integration\\Persistence\\Models\\OutboxRecord',
                'isIdentifier' => false,
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
            'startColumn' => 61,
            'endColumn' => 80,
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
            'name' => 'Modules\\Integration\\Contracts\\Data\\DeliveryResult',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 23,
        'endLine' => 63,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Integration\\Application\\Delivery',
        'declaringClassName' => 'Modules\\Integration\\Application\\Delivery\\WebhookSender',
        'implementingClassName' => 'Modules\\Integration\\Application\\Delivery\\WebhookSender',
        'currentClassName' => 'Modules\\Integration\\Application\\Delivery\\WebhookSender',
        'aliasName' => NULL,
      ),
      'recordFailure' => 
      array (
        'name' => 'recordFailure',
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
            'startLine' => 65,
            'endLine' => 65,
            'startColumn' => 36,
            'endColumn' => 68,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 65,
        'endLine' => 79,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'Modules\\Integration\\Application\\Delivery',
        'declaringClassName' => 'Modules\\Integration\\Application\\Delivery\\WebhookSender',
        'implementingClassName' => 'Modules\\Integration\\Application\\Delivery\\WebhookSender',
        'currentClassName' => 'Modules\\Integration\\Application\\Delivery\\WebhookSender',
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