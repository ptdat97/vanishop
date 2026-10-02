<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Integration/Contracts/IntegrationEvents.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Integration\Contracts\IntegrationEvents
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-770e27352fe626659e9f700e25e525cbf507d7653c938e0b26fb8924445159fd',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Integration\\Contracts\\IntegrationEvents',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Integration/Contracts/IntegrationEvents.php',
      ),
    ),
    'namespace' => 'Modules\\Integration\\Contracts',
    'name' => 'Modules\\Integration\\Contracts\\IntegrationEvents',
    'shortName' => 'IntegrationEvents',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Service contract: phát một event tích hợp — ghi vào event feed (`GET /events`) và fan-out thành message
 * outbox cho webhook subscription + connector đăng ký loại event đó, trong cùng một transaction.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 13,
    'endLine' => 19,
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
      'publish' => 
      array (
        'name' => 'publish',
        'parameters' => 
        array (
          'event' => 
          array (
            'name' => 'event',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Integration\\Contracts\\Data\\IntegrationEvent',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 18,
            'endLine' => 18,
            'startColumn' => 29,
            'endColumn' => 51,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return string event_id (uuid)
 */',
        'startLine' => 18,
        'endLine' => 18,
        'startColumn' => 5,
        'endColumn' => 61,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Integration\\Contracts',
        'declaringClassName' => 'Modules\\Integration\\Contracts\\IntegrationEvents',
        'implementingClassName' => 'Modules\\Integration\\Contracts\\IntegrationEvents',
        'currentClassName' => 'Modules\\Integration\\Contracts\\IntegrationEvents',
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