<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Promotion/Contracts/PromotionUnavailable.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Promotion\Contracts\PromotionUnavailable
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-7efbf96a0646f72242228edb7c7ac37b955a5fe78518e75486e777004e6f82eb',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Promotion\\Contracts\\PromotionUnavailable',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Promotion/Contracts/PromotionUnavailable.php',
      ),
    ),
    'namespace' => 'Modules\\Promotion\\Contracts',
    'name' => 'Modules\\Promotion\\Contracts\\PromotionUnavailable',
    'shortName' => 'PromotionUnavailable',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Khuyến mãi/voucher hết lượt hoặc hết ngân sách ngay lúc ghi nhận (PlaceOrder) → đơn rollback.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 12,
    'endLine' => 38,
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
      'code_' => 
      array (
        'declaringClassName' => 'Modules\\Promotion\\Contracts\\PromotionUnavailable',
        'implementingClassName' => 'Modules\\Promotion\\Contracts\\PromotionUnavailable',
        'name' => 'code_',
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
        'startColumn' => 34,
        'endColumn' => 63,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'details_' => 
      array (
        'declaringClassName' => 'Modules\\Promotion\\Contracts\\PromotionUnavailable',
        'implementingClassName' => 'Modules\\Promotion\\Contracts\\PromotionUnavailable',
        'name' => 'details_',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
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
        'startColumn' => 83,
        'endColumn' => 114,
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
          'code_' => 
          array (
            'name' => 'code_',
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
            'startColumn' => 34,
            'endColumn' => 63,
            'parameterIndex' => 0,
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
            'startColumn' => 66,
            'endColumn' => 80,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'details_' => 
          array (
            'name' => 'details_',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
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
            'startColumn' => 83,
            'endColumn' => 114,
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
        'namespace' => 'Modules\\Promotion\\Contracts',
        'declaringClassName' => 'Modules\\Promotion\\Contracts\\PromotionUnavailable',
        'implementingClassName' => 'Modules\\Promotion\\Contracts\\PromotionUnavailable',
        'currentClassName' => 'Modules\\Promotion\\Contracts\\PromotionUnavailable',
        'aliasName' => NULL,
      ),
      'voucherExhausted' => 
      array (
        'name' => 'voucherExhausted',
        'parameters' => 
        array (
          'code' => 
          array (
            'name' => 'code',
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
            'startLine' => 19,
            'endLine' => 19,
            'startColumn' => 45,
            'endColumn' => 56,
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
        'startLine' => 19,
        'endLine' => 22,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Modules\\Promotion\\Contracts',
        'declaringClassName' => 'Modules\\Promotion\\Contracts\\PromotionUnavailable',
        'implementingClassName' => 'Modules\\Promotion\\Contracts\\PromotionUnavailable',
        'currentClassName' => 'Modules\\Promotion\\Contracts\\PromotionUnavailable',
        'aliasName' => NULL,
      ),
      'limitReached' => 
      array (
        'name' => 'limitReached',
        'parameters' => 
        array (
          'name' => 
          array (
            'name' => 'name',
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
            'startLine' => 24,
            'endLine' => 24,
            'startColumn' => 41,
            'endColumn' => 52,
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
        'startLine' => 24,
        'endLine' => 27,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Modules\\Promotion\\Contracts',
        'declaringClassName' => 'Modules\\Promotion\\Contracts\\PromotionUnavailable',
        'implementingClassName' => 'Modules\\Promotion\\Contracts\\PromotionUnavailable',
        'currentClassName' => 'Modules\\Promotion\\Contracts\\PromotionUnavailable',
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
        'startLine' => 29,
        'endLine' => 32,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Promotion\\Contracts',
        'declaringClassName' => 'Modules\\Promotion\\Contracts\\PromotionUnavailable',
        'implementingClassName' => 'Modules\\Promotion\\Contracts\\PromotionUnavailable',
        'currentClassName' => 'Modules\\Promotion\\Contracts\\PromotionUnavailable',
        'aliasName' => NULL,
      ),
      'details' => 
      array (
        'name' => 'details',
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
        'startLine' => 34,
        'endLine' => 37,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Promotion\\Contracts',
        'declaringClassName' => 'Modules\\Promotion\\Contracts\\PromotionUnavailable',
        'implementingClassName' => 'Modules\\Promotion\\Contracts\\PromotionUnavailable',
        'currentClassName' => 'Modules\\Promotion\\Contracts\\PromotionUnavailable',
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