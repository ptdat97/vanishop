<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Integration/IntegrationServiceProvider.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Integration\IntegrationServiceProvider
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-db262306b0812d1cad0da38d84b586b7ace8bfac8550af8aa088dac4cc89c8ea',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Integration\\IntegrationServiceProvider',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Integration/IntegrationServiceProvider.php',
      ),
    ),
    'namespace' => 'Modules\\Integration',
    'name' => 'Modules\\Integration\\IntegrationServiceProvider',
    'shortName' => 'IntegrationServiceProvider',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Integration platform: event feed, outbox/inbox, webhook, Integration API v1, replay (ADR-005, ADR-013).
 * Không chứa connector cụ thể — connector là plugin (`Connector::TAG`, `InboundHandler::TAG`).
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 48,
    'endLine' => 129,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Modules\\Shared\\Support\\ModuleServiceProvider',
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
      'moduleName' => 
      array (
        'name' => 'moduleName',
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
        'startLine' => 50,
        'endLine' => 53,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Modules\\Integration',
        'declaringClassName' => 'Modules\\Integration\\IntegrationServiceProvider',
        'implementingClassName' => 'Modules\\Integration\\IntegrationServiceProvider',
        'currentClassName' => 'Modules\\Integration\\IntegrationServiceProvider',
        'aliasName' => NULL,
      ),
      'register' => 
      array (
        'name' => 'register',
        'parameters' => 
        array (
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
        'startLine' => 55,
        'endLine' => 86,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Integration',
        'declaringClassName' => 'Modules\\Integration\\IntegrationServiceProvider',
        'implementingClassName' => 'Modules\\Integration\\IntegrationServiceProvider',
        'currentClassName' => 'Modules\\Integration\\IntegrationServiceProvider',
        'aliasName' => NULL,
      ),
      'boot' => 
      array (
        'name' => 'boot',
        'parameters' => 
        array (
          'permissions' => 
          array (
            'name' => 'permissions',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Identity\\Application\\PermissionRegistry',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 88,
            'endLine' => 88,
            'startColumn' => 26,
            'endColumn' => 56,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'navigation' => 
          array (
            'name' => 'navigation',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Extension\\Application\\Admin\\AdminNavigation',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 88,
            'endLine' => 88,
            'startColumn' => 59,
            'endColumn' => 85,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'router' => 
          array (
            'name' => 'router',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Routing\\Router',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 88,
            'endLine' => 88,
            'startColumn' => 88,
            'endColumn' => 101,
            'parameterIndex' => 2,
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
        'startLine' => 88,
        'endLine' => 128,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Integration',
        'declaringClassName' => 'Modules\\Integration\\IntegrationServiceProvider',
        'implementingClassName' => 'Modules\\Integration\\IntegrationServiceProvider',
        'currentClassName' => 'Modules\\Integration\\IntegrationServiceProvider',
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