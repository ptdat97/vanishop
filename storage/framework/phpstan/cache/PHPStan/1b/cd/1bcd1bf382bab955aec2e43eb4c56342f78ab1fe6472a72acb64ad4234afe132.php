<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Notification/Contracts/Notifier.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Notification\Contracts\Notifier
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-0504575dee607967344ab8f683b39926b9a984119376fe6ae5d2ea971a5c6bb7',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Notification\\Contracts\\Notifier',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Notification/Contracts/Notifier.php',
      ),
    ),
    'namespace' => 'Modules\\Notification\\Contracts',
    'name' => 'Modules\\Notification\\Contracts\\Notifier',
    'shortName' => 'Notifier',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Service contract: gửi một tin theo loại (`order_placed`…) tới người nhận, trên mọi kênh có template đang bật
 * và kênh đó liên lạc được với người nhận. Tin marketing chỉ gửi khi khách có consent theo kênh.
 * Idempotent theo `NotificationRequest::$key` + kênh.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 14,
    'endLine' => 20,
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
      'notify' => 
      array (
        'name' => 'notify',
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
                'name' => 'Modules\\Notification\\Contracts\\Data\\NotificationRequest',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 19,
            'endLine' => 19,
            'startColumn' => 28,
            'endColumn' => 55,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return list<string> kênh đã xếp hàng gửi
 */',
        'startLine' => 19,
        'endLine' => 19,
        'startColumn' => 5,
        'endColumn' => 64,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Notification\\Contracts',
        'declaringClassName' => 'Modules\\Notification\\Contracts\\Notifier',
        'implementingClassName' => 'Modules\\Notification\\Contracts\\Notifier',
        'currentClassName' => 'Modules\\Notification\\Contracts\\Notifier',
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