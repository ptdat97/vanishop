<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Notification/Contracts/NotificationChannel.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Notification\Contracts\NotificationChannel
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-e3df75c79b89a5b5b42b5db9188d1b990e63141adc7bbf72b96f397d1b1c7d2b',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Notification\\Contracts\\NotificationChannel',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Notification/Contracts/NotificationChannel.php',
      ),
    ),
    'namespace' => 'Modules\\Notification\\Contracts',
    'name' => 'Modules\\Notification\\Contracts\\NotificationChannel',
    'shortName' => 'NotificationChannel',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Extension point: kênh gửi tin. Core có `mail`; SMS brandname, Zalo ZNS, web push là plugin
 * (`contribute(NotificationChannel::TAG, …)`, có hiệu lực khi plugin bật).
 *
 * Tin được gửi bất đồng bộ, có retry: `send()` không ném exception cho lỗi dự kiến mà trả
 * `SendResult::retryable()` (timeout, 5xx, hết quota tạm thời) hoặc `permanent()` (số không hợp lệ, template bị từ chối).
 * `$message->idempotencyKey` ổn định giữa các lần thử — gửi kèm cho nhà cung cấp nếu họ hỗ trợ.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 19,
    'endLine' => 29,
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
      'TAG' => 
      array (
        'declaringClassName' => 'Modules\\Notification\\Contracts\\NotificationChannel',
        'implementingClassName' => 'Modules\\Notification\\Contracts\\NotificationChannel',
        'name' => 'TAG',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'vani.notification.channels\'',
          'attributes' => 
          array (
            'startLine' => 21,
            'endLine' => 21,
            'startTokenPos' => 46,
            'startFilePos' => 872,
            'endTokenPos' => 46,
            'endFilePos' => 899,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 21,
        'endLine' => 21,
        'startColumn' => 5,
        'endColumn' => 52,
      ),
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
        'startLine' => 23,
        'endLine' => 23,
        'startColumn' => 5,
        'endColumn' => 35,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Notification\\Contracts',
        'declaringClassName' => 'Modules\\Notification\\Contracts\\NotificationChannel',
        'implementingClassName' => 'Modules\\Notification\\Contracts\\NotificationChannel',
        'currentClassName' => 'Modules\\Notification\\Contracts\\NotificationChannel',
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
            'startLine' => 26,
            'endLine' => 26,
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
        'docComment' => '/** Người nhận có địa chỉ phù hợp với kênh (email cho mail, SĐT cho sms/zns). */',
        'startLine' => 26,
        'endLine' => 26,
        'startColumn' => 5,
        'endColumn' => 57,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Notification\\Contracts',
        'declaringClassName' => 'Modules\\Notification\\Contracts\\NotificationChannel',
        'implementingClassName' => 'Modules\\Notification\\Contracts\\NotificationChannel',
        'currentClassName' => 'Modules\\Notification\\Contracts\\NotificationChannel',
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
            'startLine' => 28,
            'endLine' => 28,
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
        'startLine' => 28,
        'endLine' => 28,
        'startColumn' => 5,
        'endColumn' => 63,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Notification\\Contracts',
        'declaringClassName' => 'Modules\\Notification\\Contracts\\NotificationChannel',
        'implementingClassName' => 'Modules\\Notification\\Contracts\\NotificationChannel',
        'currentClassName' => 'Modules\\Notification\\Contracts\\NotificationChannel',
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