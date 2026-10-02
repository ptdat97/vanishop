<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Notification/Application/Channels/MailChannel.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Notification\Application\Channels\MailChannel
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-0cd8041b42a8e34957e64f924561427dc08bf315027acd0257ab0a64813b9fac',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Notification\\Application\\Channels\\MailChannel',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Notification/Application/Channels/MailChannel.php',
      ),
    ),
    'namespace' => 'Modules\\Notification\\Application\\Channels',
    'name' => 'Modules\\Notification\\Application\\Channels\\MailChannel',
    'shortName' => 'MailChannel',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Kênh email của Core (văn bản thuần; gửi qua mailer mặc định của Laravel).
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 18,
    'endLine' => 47,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'Modules\\Notification\\Contracts\\NotificationChannel',
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
      'code' => 
      array (
        'name' => 'code',
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
        'startLine' => 20,
        'endLine' => 23,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Notification\\Application\\Channels',
        'declaringClassName' => 'Modules\\Notification\\Application\\Channels\\MailChannel',
        'implementingClassName' => 'Modules\\Notification\\Application\\Channels\\MailChannel',
        'currentClassName' => 'Modules\\Notification\\Application\\Channels\\MailChannel',
        'aliasName' => NULL,
      ),
      'canReach' => 
      array (
        'name' => 'canReach',
        'parameters' => 
        array (
          'recipient' => 
          array (
            'name' => 'recipient',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Notification\\Contracts\\Data\\Recipient',
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
            'endColumn' => 49,
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
        'startLine' => 25,
        'endLine' => 28,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Notification\\Application\\Channels',
        'declaringClassName' => 'Modules\\Notification\\Application\\Channels\\MailChannel',
        'implementingClassName' => 'Modules\\Notification\\Application\\Channels\\MailChannel',
        'currentClassName' => 'Modules\\Notification\\Application\\Channels\\MailChannel',
        'aliasName' => NULL,
      ),
      'send' => 
      array (
        'name' => 'send',
        'parameters' => 
        array (
          'message' => 
          array (
            'name' => 'message',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Notification\\Contracts\\Data\\OutgoingMessage',
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
            'startColumn' => 26,
            'endColumn' => 49,
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
            'name' => 'Modules\\Notification\\Contracts\\Data\\SendResult',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 30,
        'endLine' => 46,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Notification\\Application\\Channels',
        'declaringClassName' => 'Modules\\Notification\\Application\\Channels\\MailChannel',
        'implementingClassName' => 'Modules\\Notification\\Application\\Channels\\MailChannel',
        'currentClassName' => 'Modules\\Notification\\Application\\Channels\\MailChannel',
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