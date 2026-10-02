<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Notification/Testing/NotificationChannelContract.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Notification\Testing\NotificationChannelContract
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-3a669db03ee7fb3c7e8ac51658e334167061eb459efe3409bd7011ab582862a7',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Notification\\Testing\\NotificationChannelContract',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Notification/Testing/NotificationChannelContract.php',
      ),
    ),
    'namespace' => 'Modules\\Notification\\Testing',
    'name' => 'Modules\\Notification\\Testing\\NotificationChannelContract',
    'shortName' => 'NotificationChannelContract',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Contract test cho NotificationChannel. Plugin cung cấp tin mẫu hợp lệ cho kênh, người nhận không liên lạc được,
 * và hai `prepare` giả lập nhà cung cấp (thành công / lỗi tạm thời, vd. Http::fake 200 / 503).
 * Kiểm tra: mã ổn định; canReach đúng; gửi thành công → sent; lỗi tạm thời → retryable (không ném exception);
 * tin thiếu nội dung/tham số → permanent (không ném exception).
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 19,
    'endLine' => 55,
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
      'define' => 
      array (
        'name' => 'define',
        'parameters' => 
        array (
          'label' => 
          array (
            'name' => 'label',
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
            'startLine' => 27,
            'endLine' => 27,
            'startColumn' => 35,
            'endColumn' => 47,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'channel' => 
          array (
            'name' => 'channel',
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
            'startLine' => 27,
            'endLine' => 27,
            'startColumn' => 50,
            'endColumn' => 65,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
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
            'startLine' => 27,
            'endLine' => 27,
            'startColumn' => 68,
            'endColumn' => 83,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'unreachable' => 
          array (
            'name' => 'unreachable',
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
            'startLine' => 27,
            'endLine' => 27,
            'startColumn' => 86,
            'endColumn' => 107,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'succeed' => 
          array (
            'name' => 'succeed',
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
            'startLine' => 27,
            'endLine' => 27,
            'startColumn' => 110,
            'endColumn' => 125,
            'parameterIndex' => 4,
            'isOptional' => false,
          ),
          'failTemporarily' => 
          array (
            'name' => 'failTemporarily',
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
            'startLine' => 27,
            'endLine' => 27,
            'startColumn' => 128,
            'endColumn' => 151,
            'parameterIndex' => 5,
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
        'docComment' => '/**
 * @param  Closure(): NotificationChannel  $channel
 * @param  Closure(): OutgoingMessage  $message
 * @param  Closure(): void  $succeed
 * @param  Closure(): void  $failTemporarily
 */',
        'startLine' => 27,
        'endLine' => 54,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Modules\\Notification\\Testing',
        'declaringClassName' => 'Modules\\Notification\\Testing\\NotificationChannelContract',
        'implementingClassName' => 'Modules\\Notification\\Testing\\NotificationChannelContract',
        'currentClassName' => 'Modules\\Notification\\Testing\\NotificationChannelContract',
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