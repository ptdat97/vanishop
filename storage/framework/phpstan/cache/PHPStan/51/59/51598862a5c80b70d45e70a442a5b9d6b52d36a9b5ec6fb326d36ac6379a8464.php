<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Integration/Contracts/IntegrationAccessDenied.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Integration\Contracts\IntegrationAccessDenied
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-e902322d4f90eca2501635115f6c83e7e71d43add40aa5a2594b1fb802984427',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Integration\\Contracts\\IntegrationAccessDenied',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Integration/Contracts/IntegrationAccessDenied.php',
      ),
    ),
    'namespace' => 'Modules\\Integration\\Contracts',
    'name' => 'Modules\\Integration\\Contracts\\IntegrationAccessDenied',
    'shortName' => 'IntegrationAccessDenied',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Lỗi xác thực/phân quyền của Integration API: {"error": {"code": "integration.*"}}.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 12,
    'endLine' => 43,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Modules\\Shared\\Domain\\BusinessRuleViolation',
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
      'errorCode' => 
      array (
        'declaringClassName' => 'Modules\\Integration\\Contracts\\IntegrationAccessDenied',
        'implementingClassName' => 'Modules\\Integration\\Contracts\\IntegrationAccessDenied',
        'name' => 'errorCode',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 14,
        'endLine' => 14,
        'startColumn' => 51,
        'endColumn' => 84,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'httpStatus' => 
      array (
        'declaringClassName' => 'Modules\\Integration\\Contracts\\IntegrationAccessDenied',
        'implementingClassName' => 'Modules\\Integration\\Contracts\\IntegrationAccessDenied',
        'name' => 'httpStatus',
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
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 14,
        'endLine' => 14,
        'startColumn' => 87,
        'endColumn' => 118,
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
          'message' => 
          array (
            'name' => 'message',
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
            'startLine' => 14,
            'endLine' => 14,
            'startColumn' => 34,
            'endColumn' => 48,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'errorCode' => 
          array (
            'name' => 'errorCode',
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 14,
            'endLine' => 14,
            'startColumn' => 51,
            'endColumn' => 84,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'httpStatus' => 
          array (
            'name' => 'httpStatus',
            'default' => NULL,
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
            'startLine' => 14,
            'endLine' => 14,
            'startColumn' => 87,
            'endColumn' => 118,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 14,
        'endLine' => 17,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'Modules\\Integration\\Contracts',
        'declaringClassName' => 'Modules\\Integration\\Contracts\\IntegrationAccessDenied',
        'implementingClassName' => 'Modules\\Integration\\Contracts\\IntegrationAccessDenied',
        'currentClassName' => 'Modules\\Integration\\Contracts\\IntegrationAccessDenied',
        'aliasName' => NULL,
      ),
      'unauthenticated' => 
      array (
        'name' => 'unauthenticated',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'self',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 19,
        'endLine' => 22,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Modules\\Integration\\Contracts',
        'declaringClassName' => 'Modules\\Integration\\Contracts\\IntegrationAccessDenied',
        'implementingClassName' => 'Modules\\Integration\\Contracts\\IntegrationAccessDenied',
        'currentClassName' => 'Modules\\Integration\\Contracts\\IntegrationAccessDenied',
        'aliasName' => NULL,
      ),
      'ipNotAllowed' => 
      array (
        'name' => 'ipNotAllowed',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'self',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 24,
        'endLine' => 27,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Modules\\Integration\\Contracts',
        'declaringClassName' => 'Modules\\Integration\\Contracts\\IntegrationAccessDenied',
        'implementingClassName' => 'Modules\\Integration\\Contracts\\IntegrationAccessDenied',
        'currentClassName' => 'Modules\\Integration\\Contracts\\IntegrationAccessDenied',
        'aliasName' => NULL,
      ),
      'insufficientScope' => 
      array (
        'name' => 'insufficientScope',
        'parameters' => 
        array (
          'scope' => 
          array (
            'name' => 'scope',
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
            'startLine' => 29,
            'endLine' => 29,
            'startColumn' => 46,
            'endColumn' => 58,
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
            'name' => 'self',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 29,
        'endLine' => 32,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Modules\\Integration\\Contracts',
        'declaringClassName' => 'Modules\\Integration\\Contracts\\IntegrationAccessDenied',
        'implementingClassName' => 'Modules\\Integration\\Contracts\\IntegrationAccessDenied',
        'currentClassName' => 'Modules\\Integration\\Contracts\\IntegrationAccessDenied',
        'aliasName' => NULL,
      ),
      'errorCode' => 
      array (
        'name' => 'errorCode',
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
        'startLine' => 34,
        'endLine' => 37,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Integration\\Contracts',
        'declaringClassName' => 'Modules\\Integration\\Contracts\\IntegrationAccessDenied',
        'implementingClassName' => 'Modules\\Integration\\Contracts\\IntegrationAccessDenied',
        'currentClassName' => 'Modules\\Integration\\Contracts\\IntegrationAccessDenied',
        'aliasName' => NULL,
      ),
      'status' => 
      array (
        'name' => 'status',
        'parameters' => 
        array (
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
        'startLine' => 39,
        'endLine' => 42,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Integration\\Contracts',
        'declaringClassName' => 'Modules\\Integration\\Contracts\\IntegrationAccessDenied',
        'implementingClassName' => 'Modules\\Integration\\Contracts\\IntegrationAccessDenied',
        'currentClassName' => 'Modules\\Integration\\Contracts\\IntegrationAccessDenied',
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