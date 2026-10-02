<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Integration/Console/WebhookCommand.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Integration\Console\WebhookCommand
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-9c257ea6ea25a79028a6e867808d2187d7d939502e01f07aac3a21ebeed4a25d',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Integration\\Console\\WebhookCommand',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Integration/Console/WebhookCommand.php',
      ),
    ),
    'namespace' => 'Modules\\Integration\\Console',
    'name' => 'Modules\\Integration\\Console\\WebhookCommand',
    'shortName' => 'WebhookCommand',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 12,
    'endLine' => 33,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Illuminate\\Console\\Command',
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
      'signature' => 
      array (
        'declaringClassName' => 'Modules\\Integration\\Console\\WebhookCommand',
        'implementingClassName' => 'Modules\\Integration\\Console\\WebhookCommand',
        'name' => 'signature',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'vani:integration:webhook
        {client : Mã Integration Client}
        {url : URL HTTPS nhận webhook}
        {--event=* : Loại event (order.created, payment.captured…; * = tất cả)}\'',
          'attributes' => 
          array (
            'startLine' => 14,
            'endLine' => 17,
            'startTokenPos' => 53,
            'startFilePos' => 318,
            'endTokenPos' => 53,
            'endFilePos' => 514,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 14,
        'endLine' => 17,
        'startColumn' => 5,
        'endColumn' => 89,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'description' => 
      array (
        'declaringClassName' => 'Modules\\Integration\\Console\\WebhookCommand',
        'implementingClassName' => 'Modules\\Integration\\Console\\WebhookCommand',
        'name' => 'description',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'Đăng ký webhook subscription cho một Integration Client (secret ký hiện một lần).\'',
          'attributes' => 
          array (
            'startLine' => 19,
            'endLine' => 19,
            'startTokenPos' => 62,
            'startFilePos' => 547,
            'endTokenPos' => 62,
            'endFilePos' => 641,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 19,
        'endLine' => 19,
        'startColumn' => 5,
        'endColumn' => 125,
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
      'handle' => 
      array (
        'name' => 'handle',
        'parameters' => 
        array (
          'provisioning' => 
          array (
            'name' => 'provisioning',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Integration\\Application\\ClientProvisioning',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 21,
            'endLine' => 21,
            'startColumn' => 28,
            'endColumn' => 59,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 21,
            'endLine' => 21,
            'startColumn' => 62,
            'endColumn' => 84,
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
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 21,
        'endLine' => 32,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Integration\\Console',
        'declaringClassName' => 'Modules\\Integration\\Console\\WebhookCommand',
        'implementingClassName' => 'Modules\\Integration\\Console\\WebhookCommand',
        'currentClassName' => 'Modules\\Integration\\Console\\WebhookCommand',
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